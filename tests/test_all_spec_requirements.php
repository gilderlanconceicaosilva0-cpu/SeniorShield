<?php
/**
 * SeniorShield - Bateria Completa de Testes Automatizados (Spec.md)
 *
 * Valida todos os requisitos funcionais (RF01 a RF45) e critérios de aceite (CA01 a CA16).
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helpers/utils.php';
require_once __DIR__ . '/../src/Helpers/auth_guard.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Services/UserService.php';
require_once __DIR__ . '/../src/Services/AnalysisService.php';
require_once __DIR__ . '/../src/Services/IndicatorService.php';
require_once __DIR__ . '/../src/Services/EducationService.php';

$pdo = getDbConnection();
$authService = new AuthService($pdo);
$userService = new UserService($pdo);
$analysisService = new AnalysisService($pdo);
$indicatorService = new IndicatorService($pdo);
$educationService = new EducationService($pdo);

$total = 0;
$passed = 0;

function it(string $requisito, string $descricao, bool $condicao, ?string $detalhe = null): void {
    global $total, $passed;
    $total++;
    if ($condicao) {
        $passed++;
        echo "  [PASS] {$requisito}: {$descricao}\n";
    } else {
        echo "  [FAIL] {$requisito}: {$descricao}\n";
        if ($detalhe) {
            echo "         Motivo: {$detalhe}\n";
        }
    }
}

echo "=================================================================\n";
echo " SeniorShield - Validação Oficial de Requisitos (spec.md)\n";
echo "=================================================================\n\n";

// ---------------------------------------------------------------------
// BLOCO 1: Autenticação, Usuários e Segurança (RF01-RF07, CA01-CA03, CA16)
// ---------------------------------------------------------------------
echo "--- BLOCO 1: Autenticação e Segurança (RF01-RF07, CA01-CA03, CA16) ---\n";

$emailTeste = 'teste_suite_' . time() . '@seniorshield.local';
$senhaTeste = 'SenhaForte@2026';

// RF01, RF02, CA01: Cadastro de usuário
$resCad = $authService->cadastrar('Maria Teste Silva', $emailTeste, $senhaTeste, $senhaTeste);
it('RF01/RF02/CA01', 'Cadastro de novo usuário com dados válidos', $resCad['sucesso'] === true);
$idUsuarioTeste = (int)($resCad['id_usuario'] ?? 0);

// RF03: Rejeição de e-mail duplicado
$resCadDup = $authService->cadastrar('Maria Duplicada', $emailTeste, $senhaTeste, $senhaTeste);
it('RF03', 'Impedir cadastro de dois usuários com mesmo e-mail', $resCadDup['sucesso'] === false);

// RF04, RNF01, RNF02, CA16: Hash seguro de senha
$stmtHash = $pdo->prepare('SELECT senha_hash FROM usuarios WHERE id_usuario = ?');
$stmtHash->execute([$idUsuarioTeste]);
$hash = $stmtHash->fetchColumn();
it('RF04/CA16', 'Senha armazenada com hash seguro e não em texto puro', !empty($hash) && $hash !== $senhaTeste && password_verify($senhaTeste, $hash));

// RF05, CA02: Login bem-sucedido
$resLogin = $authService->autenticar($emailTeste, $senhaTeste);
it('RF05/CA02', 'Login com credenciais corretas', $resLogin['sucesso'] === true);

// CA03: Login inválido
$resLoginInvalido = $authService->autenticar($emailTeste, 'SenhaErrada123');
it('CA03', 'Impedir acesso com credenciais incorretas', $resLoginInvalido['sucesso'] === false);

// RF06: Identificação de tipo de usuário
it('RF06', 'Identificar tipo de usuário (comum por padrão)', ($resLogin['usuario']['tipo'] ?? '') === 'comum');

// RF07, CA13: Controle de acesso administrativo
initSecureSession();
$_SESSION['usuario_id'] = $idUsuarioTeste;
$_SESSION['tipo_usuario'] = 'comum';
it('RF07/CA13', 'Usuário comum não possui permissão de administrador', is_admin() === false);

// ---------------------------------------------------------------------
// BLOCO 2: Análise de Mensagens e Motor do ISD (RF08-RF16, CA04-CA06)
// ---------------------------------------------------------------------
echo "\n--- BLOCO 2: Análise de Mensagens e ISD (RF08-RF16, CA04-CA06) ---\n";

// RF08: Informar mensagem para análise
$msgTeste = 'URGENTE: Sua conta teve tentativa de acesso. Acesse bit.ly/banco-seguro e envie o código sms e senha agora para evitar bloqueio.';
$resAnalise = $analysisService->analisarMensagem($msgTeste, $idUsuarioTeste);
it('RF08', 'Receber mensagem textual para análise', $resAnalise['sucesso'] === true);

// RF09, RF10: Identificar indicadores de risco
it('RF09/RF10', 'Identificar indicadores de risco cadastrados na mensagem', !empty($resAnalise['indicadores_detectados']));

// RF11: Calcular ISD a partir dos pesos
it('RF11', 'Utilizar os pesos dos indicadores para calcular o ISD', $resAnalise['pontuacao_isd'] > 0);

// RF12, CA05: Apresentar ISD na escala de 0 a 100
it('RF12/CA05', 'ISD apresentado na escala de 0 a 100', $resAnalise['pontuacao_isd'] >= 0 && $resAnalise['pontuacao_isd'] <= 100);

// RF13: Classificar resultado de acordo com as faixas
it('RF13', 'Classificar resultado oficial de risco', in_array($resAnalise['classificacao'], [
    ISD_FAIXA_RISCO_MUITO_BAIXO, ISD_FAIXA_ATENCAO, ISD_FAIXA_SUSPEITO, ISD_FAIXA_ALTO_RISCO, ISD_FAIXA_RISCO_CRITICO
], true));

// RF14, CA06: Apresentar indicadores que contribuíram para o resultado
$temIndicadoresDetalhados = true;
foreach ($resAnalise['indicadores_detectados'] as $ind) {
    if (empty($ind['nome']) || empty($ind['peso'])) {
        $temIndicadoresDetalhados = false;
    }
}
it('RF14/CA06', 'Apresentar indicadores e pesos que contribuíram para o resultado', $temIndicadoresDetalhados);

// RF15: Disclaimer de avaliação de risco
it('RF15', 'Informar que o resultado representa avaliação indicativa de risco', !empty($resAnalise['disclaimer_avaliacao']));

// RF16: Aviso de privacidade (não inserir senhas/dados pessoais)
it('RF16', 'Orientar o usuário a não inserir dados sensíveis ou senhas', !empty($resAnalise['aviso_privacidade']));

// ---------------------------------------------------------------------
// BLOCO 3: Histórico de Análises (RF17-RF23, CA07, CA08)
// ---------------------------------------------------------------------
echo "\n--- BLOCO 3: Histórico de Análises (RF17-RF23, CA07, CA08) ---\n";

$idAnaliseGravada = (int)$resAnalise['id_analise'];
it('RF17/CA07', 'Armazenar análise no banco associada ao usuário', $resAnalise['salvo_no_historico'] === true && $idAnaliseGravada > 0);

// RF18-RF21: Dados gravados na tabela ANALISES
$stmtAnaliseBd = $pdo->prepare('SELECT mensagem, pontuacao_isd, classificacao, data_analise FROM ANALISES WHERE id_analise = ?');
$stmtAnaliseBd->execute([$idAnaliseGravada]);
$dadosAnaliseBd = $stmtAnaliseBd->fetch(PDO::FETCH_ASSOC);

it('RF18', 'Registrar texto da mensagem analisada', !empty($dadosAnaliseBd['mensagem']));
it('RF19', 'Registrar pontuação do ISD', isset($dadosAnaliseBd['pontuacao_isd']));
it('RF20', 'Registrar classificação da análise', !empty($dadosAnaliseBd['classificacao']));
it('RF21', 'Registrar data e hora da análise', !empty($dadosAnaliseBd['data_analise']));

// RF22: Tabela ANALISE_INDICADORES
$stmtAi = $pdo->prepare('SELECT COUNT(*) FROM ANALISE_INDICADORES WHERE id_analise = ?');
$stmtAi->execute([$idAnaliseGravada]);
it('RF22', 'Registrar quais indicadores foram identificados com peso aplicado', (int)$stmtAi->fetchColumn() > 0);

// RF23, CA08: Consulta ao histórico
$historicoUsuario = $analysisService->obterHistoricoUsuario($idUsuarioTeste);
it('RF23/CA08', 'Permitir que o usuário consulte seu histórico de análises', !empty($historicoUsuario));

// ---------------------------------------------------------------------
// BLOCO 4: Conteúdos Educativos (RF24-RF27, CA09, CA15)
// ---------------------------------------------------------------------
echo "\n--- BLOCO 4: Conteúdos Educativos (RF24-RF27, CA09, CA15) ---\n";

// RF24, CA09: Exibição de conteúdos educativos ativos
$conteudosAtivos = $educationService->listarConteudos(true);
it('RF24/CA09', 'Exibir conteúdos educativos ativos para o usuário', !empty($conteudosAtivos));

// RF25, CA15: Cadastrar conteúdo educativo
$novoArtigo = $educationService->salvarConteudo([
    'titulo' => 'Como identificar boletos falsos',
    'texto'  => 'Sempre confira os três primeiros dígitos do código de barras para checar o banco emissor.',
    'tipo'   => 'dica',
    'ativo'  => 1
]);
it('RF25/CA15', 'Administrador cadastra conteúdo educativo', $novoArtigo['sucesso'] === true);
$idArtigoTeste = (int)$novoArtigo['id'];

// RF26: Atualizar conteúdo educativo
$updateArtigo = $educationService->salvarConteudo([
    'titulo' => 'Como identificar boletos falsos (Atualizado)',
    'texto'  => 'Sempre confira os três primeiros dígitos e o nome do beneficiário no comprovante.',
    'tipo'   => 'dica',
    'ativo'  => 1
], $idArtigoTeste);
it('RF26', 'Administrador atualiza conteúdo educativo', $updateArtigo['sucesso'] === true);

// RF27: Desativar conteúdo educativo
$educationService->alternarStatusConteudo($idArtigoTeste);
$artigoAposToggle = $educationService->obterConteudoPorId($idArtigoTeste);
it('RF27', 'Administrador desativa conteúdo educativo', $artigoAposToggle['ativo'] === 0);

// Limpeza
$educationService->excluirConteudo($idArtigoTeste);

// ---------------------------------------------------------------------
// BLOCO 5: Modo Aprendizado (RF28-RF35, CA10-CA12)
// ---------------------------------------------------------------------
echo "\n--- BLOCO 5: Modo Aprendizado (RF28-RF35, CA10-CA12) ---\n";

// RF28, RF29, CA10: Disponibilizar situações simuladas com mensagem
$questoesAprendizado = $educationService->listarQuestoes(true);
it('RF28/RF29/CA10', 'Apresentar situações simuladas ativas para o usuário', !empty($questoesAprendizado));
$primeiraQuestao = $questoesAprendizado[0];

// RF30, RF31, RF32, CA11: Resposta, confirmação e explicação didática
$resSimulado = $educationService->processarResposta(
    (int)$primeiraQuestao['id_questao'],
    $primeiraQuestao['resposta_correta'],
    $idUsuarioTeste
);
it('RF30', 'Permitir que o usuário informe sua resposta', $resSimulado['sucesso'] === true);
it('RF31/CA11', 'Informar se a resposta estava correta', $resSimulado['acertou'] === true);
it('RF32', 'Apresentar explicação didática dos sinais existentes', !empty($resSimulado['explicacao']));

// RF33, RF34, CA12: Registro de tentativa e resultado no banco
it('RF33/RF34/CA12', 'Registrar tentativa e resultado no banco de dados', !empty($resSimulado['id_tentativa']));

// RF35: Consulta de desempenho no Modo Aprendizado
$desempenhoUsuario = $educationService->obterDesempenhoUsuario($idUsuarioTeste);
it('RF35', 'Permitir a consulta do desempenho do usuário no Modo Aprendizado', $desempenhoUsuario['total_tentativas'] > 0 && isset($desempenhoUsuario['taxa_acerto']));

// ---------------------------------------------------------------------
// BLOCO 6: Gestão Administrativa (RF36-RF45, CA14)
// ---------------------------------------------------------------------
echo "\n--- BLOCO 6: Área Administrativa (RF36-RF45, CA14) ---\n";

// RF36, RF38, CA14: Cadastrar indicador e definir peso
$novoIndicadorAdmin = $indicatorService->salvarIndicador([
    'nome'           => 'Falsa Intimação Policial',
    'descricao'      => 'Ameaça de mandato de prisão imediata',
    'palavras_chave' => 'mandato de prisao, intimacao judicial, policia federal',
    'peso'           => 25,
    'ativo'          => 1
]);
it('RF36/RF38/CA14', 'Cadastrar indicador e definir peso no ISD', $novoIndicadorAdmin['sucesso'] === true);
$idIndAdmin = (int)$novoIndicadorAdmin['id'];

// RF37, RF38: Atualizar indicador e alterar peso
$updateIndAdmin = $indicatorService->salvarIndicador([
    'nome'           => 'Falsa Intimação Policial Urgente',
    'descricao'      => 'Ameaça grave de prisão ou protesto',
    'palavras_chave' => 'mandato de prisao, intimacao judicial, policia federal, cartorio',
    'peso'           => 30,
    'ativo'          => 1
], $idIndAdmin);
it('RF37/RF38', 'Atualizar indicador e alterar peso', $updateIndAdmin['sucesso'] === true);

// RF39: Ativar/desativar indicador
$indicatorService->alternarStatusIndicador($idIndAdmin);
$indAposToggle = $indicatorService->obterIndicadorPorId($idIndAdmin);
it('RF39', 'Ativar ou desativar indicadores', $indAposToggle['ativo'] === 0);

// Limpeza
$indicatorService->excluirIndicador($idIndAdmin);

// RF40: Cadastrar tipo de golpe
$novoTipo = $indicatorService->salvarTipoGolpe([
    'nome'      => 'Golpe da Falsa Herança',
    'descricao' => 'Promessa de fortuna de parente distante no exterior',
    'ativo'     => 1
]);
it('RF40', 'Cadastrar tipo de golpe', $novoTipo['sucesso'] === true);
$idTipoTeste = (int)$novoTipo['id'];

// RF41: Atualizar tipo de golpe
$updateTipo = $indicatorService->salvarTipoGolpe([
    'nome'      => 'Golpe da Falsa Herança Internacional',
    'descricao' => 'Promessa de herança milionária exigindo taxa de transferência',
    'ativo'     => 1
], $idTipoTeste);
it('RF41', 'Atualizar tipo de golpe', $updateTipo['sucesso'] === true);

// RF42: Ativar ou desativar tipos de golpes
$indicatorService->alternarStatusTipoGolpe($idTipoTeste);
$tipoAposToggle = $indicatorService->obterTipoGolpePorId($idTipoTeste);
it('RF42', 'Ativar ou desativar tipos de golpes', $tipoAposToggle['ativo'] === 0);

// RF43: Cadastrar questão do Modo Aprendizado
$novaQuestaoAdmin = $educationService->salvarQuestao([
    'mensagem'         => 'Recebemos uma notificação de herança em seu nome no valor de US$ 500.000. Pague a taxa de R$ 350 via Pix para liberação.',
    'resposta_correta' => 'golpe',
    'explicacao'       => 'Heranças inesperadas com cobrança de taxa prévia de liberação são golpes clássicos.',
    'id_tipo_golpe'    => $idTipoTeste,
    'ativo'            => 1
]);
it('RF43', 'Cadastrar questão do Modo Aprendizado', $novaQuestaoAdmin['sucesso'] === true);
$idQuestaoAdmin = (int)$novaQuestaoAdmin['id'];

// RF44: Atualizar questão do Modo Aprendizado
$updateQuestaoAdmin = $educationService->salvarQuestao([
    'mensagem'         => 'Recebemos notificação de herança em seu nome no valor de US$ 1.000.000. Pague taxa para liberar.',
    'resposta_correta' => 'golpe',
    'explicacao'       => 'Heranças milionárias pedindo taxas antecipadas são fraudes conhecidas internacionalmente.',
    'id_tipo_golpe'    => $idTipoTeste,
    'ativo'            => 1
], $idQuestaoAdmin);
it('RF44', 'Atualizar questão do Modo Aprendizado', $updateQuestaoAdmin['sucesso'] === true);

// RF45: Ativar ou desativar questão do Modo Aprendizado
$educationService->alternarStatusQuestao($idQuestaoAdmin);
$questaoAposToggle = $educationService->obterQuestaoPorId($idQuestaoAdmin);
it('RF45', 'Ativar ou desativar questões do Modo Aprendizado', $questaoAposToggle['ativo'] === 0);

// Limpeza das entidades de teste criadas neste bloco
$educationService->excluirQuestao($idQuestaoAdmin);
$pdo->prepare('DELETE FROM TIPOS_GOLPE WHERE id_tipo_golpe = ?')->execute([$idTipoTeste]);

// Limpeza do usuário de teste e dados gerados
$pdo->prepare('DELETE FROM usuarios WHERE id_usuario = ?')->execute([$idUsuarioTeste]);

echo "\n=================================================================\n";
echo " RESULTADO FINAL DA ESPECIFICAÇÃO: {$passed} de {$total} requisitos aprovados!\n";
echo "=================================================================\n";
