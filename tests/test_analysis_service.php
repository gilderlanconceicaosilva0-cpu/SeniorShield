<?php
/**
 * SeniorShield - Testes Automatizados da Fase 3 (Motor do ISD e Histórico)
 */


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Services/AnalysisService.php';
require_once __DIR__ . '/../src/Services/AuthService.php';

$pdo = getDbConnection();
$service = new AnalysisService($pdo);
$authService = new AuthService($pdo);

// Cria um usuário temporário para os testes.
$emailTeste = 'teste_analysis_' . uniqid() . '@seniorshield.local';
$senhaTeste = 'SenhaForte@2026';

    $resCadastro = $authService->cadastrar(
    'Usuario Teste',
    $emailTeste,
    $senhaTeste,
    $senhaTeste
);

$idUsuarioTeste = (int) ($resCadastro['id_usuario'] ?? 0);

if (empty($resCadastro['sucesso']) || $idUsuarioTeste <= 0) {
    exit("ERRO: Não foi possível criar o usuário temporário.\n");
}

$totalTests = 0;
$passedTests = 0;

function assertTest(bool $condition, string $description, ?string $extra = null): void {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] {$description}\n";
    } else {
        echo "  [FAIL] {$description}\n";
        if ($extra) {
            echo "         Detalhe: {$extra}\n";
        }
    }
}

echo "=================================================================\n";
echo " Executando Testes Unitários e Funcionais do AnalysisService (Fase 3)\n";
echo "=================================================================\n\n";

// 1. Validações de Entrada
echo "1. Validações de entrada de dados (RF08, RNF04):\n";
$resVazio = $service->analisarMensagem('');
assertTest($resVazio['sucesso'] === false, 'Mensagem vazia deve ser rejeitada');

$resCurto = $service->analisarMensagem('oi');
assertTest($resCurto['sucesso'] === false, 'Mensagem com menos de 3 caracteres deve ser rejeitada');

$resNormal = $service->analisarMensagem('Olá bom dia tudo bem com você?');
assertTest($resNormal['sucesso'] === true, 'Mensagem válida deve ser processada');
assertTest($resNormal['pontuacao_isd'] === 0, 'Mensagem sem termos suspeitos deve ter pontuação 0');
assertTest($resNormal['classificacao'] === 'Risco muito baixo', 'Pontuação 0 deve ser classificada como "Risco muito baixo"');

// 2. Teste do Exemplo Oficial da Especificação (spec.md Seção 4)
// Urgência (15) + Pedido de dinheiro (25) + Link suspeito (20) + Pedido de senha (25) = 85 (Risco crítico)
echo "\n2. Exemplo oficial da especificação (spec.md Linhas 129-139):\n";
$msgExemploSpec = 'URGENTE: Você precisa pagar o pix agora ou sua conta terá bloqueio hoje. Clique em bit.ly/seguro e informe sua senha para liberar.';
$resExemplo = $service->analisarMensagem($msgExemploSpec);
assertTest($resExemplo['sucesso'] === true, 'Processamento da mensagem do exemplo');
assertTest($resExemplo['pontuacao_isd'] >= 85, 'Pontuação deve somar os pesos dos 4 indicadores (>= 85)', "Obtido: {$resExemplo['pontuacao_isd']}");
assertTest($resExemplo['classificacao'] === 'Risco crítico', 'Classificação deve ser "Risco crítico"');

// 3. Teste de Acentuação e Case Insensitivity
echo "\n3. Teste de normalização e acentuação:\n";
$msgAcentos = 'POR FAVOR FAÇA UMA TRANSFERENCIA URGENTE PELO PIX';
$resAcentos = $service->analisarMensagem($msgAcentos);
assertTest($resAcentos['pontuacao_isd'] >= 40, 'Deve identificar "urgente" e "transferencia" sem acento e em maiúsculas', "Obtido: {$resAcentos['pontuacao_isd']}");

// 4. Teste de fronteira de palavras (evitar falso positivo com "pixel")
echo "\n4. Teste de isolamento de palavras:\n";
$msgFalsoPositivo = 'Comprei uma câmera com alta resolução de pixel e monitor moderno.';
$resFalsoPositivo = $service->analisarMensagem($msgFalsoPositivo);
assertTest($resFalsoPositivo['pontuacao_isd'] === 0, 'A palavra "pixel" NÃO deve acionar o indicador de "pix"', "Obtido: {$resFalsoPositivo['pontuacao_isd']}");

// 5. Teste de Limitação do ISD em 100 pontos (RF12)
echo "\n5. Limite máximo do ISD (RF12 - Max 100 pontos):\n";
// Mensagem contendo todos os 6 indicadores ativos
$msgSobrecarga = 'URGENTE imediatamente bloqueio hoje. Envie o pix dinheiro transferência. Acesse bit.ly clique aqui. Informe sua senha código sms. Parabéns você ganhou prêmio liberado. Sua conta foi bloqueada cpf bloqueado.';
$resSobrecarga = $service->analisarMensagem($msgSobrecarga);
assertTest($resSobrecarga['pontuacao_isd'] === 100, 'Pontuação não pode ultrapassar 100 pontos', "Obtido: {$resSobrecarga['pontuacao_isd']}");
assertTest($resSobrecarga['classificacao'] === 'Risco crítico', '100 pontos deve ser classificado como "Risco crítico"');

// 6. Teste de Faixas de Risco (RF13)
echo "\n6. Classificações de faixas de risco (RF13):\n";
assertTest(getIsdClassification(0) === 'Risco muito baixo', '0 pontos -> Risco muito baixo');
assertTest(getIsdClassification(20) === 'Risco muito baixo', '20 pontos -> Risco muito baixo');
assertTest(getIsdClassification(21) === 'Atenção', '21 pontos -> Atenção');
assertTest(getIsdClassification(40) === 'Atenção', '40 pontos -> Atenção');
assertTest(getIsdClassification(41) === 'Suspeito', '41 pontos -> Suspeito');
assertTest(getIsdClassification(60) === 'Suspeito', '60 pontos -> Suspeito');
assertTest(getIsdClassification(61) === 'Alto risco', '61 pontos -> Alto risco');
assertTest(getIsdClassification(80) === 'Alto risco', '80 pontos -> Alto risco');
assertTest(getIsdClassification(81) === 'Risco crítico', '81 pontos -> Risco crítico');
assertTest(getIsdClassification(100) === 'Risco crítico', '100 pontos -> Risco crítico');

// 7. Teste de Persistência Relacional com Usuário (RF17-RF22, CA07)
echo "\n7. Persistência no Banco de Dados (RF17-RF22, CA07):\n";
// Usamos o usuário ID 1 que já existe no banco
$msgPersistir = 'URGENTE: Notamos uma transação suspeita na sua conta. Clique em bit.ly/seguro e informe seu código sms para evitar bloqueio.';
$resPersistir = $service->analisarMensagem($msgPersistir, $idUsuarioTeste);

assertTest($resPersistir['salvo_no_historico'] === true, 'Análise com ID de usuário deve ser gravada no histórico');
assertTest(!empty($resPersistir['id_analise']), 'ID da análise gerada deve ser retornado');

$idCriado = (int)$resPersistir['id_analise'];

// Verifica se os indicadores foram gravados na tabela ANALISE_INDICADORES (RF22)
$stmtCheckAi = $pdo->prepare('SELECT COUNT(*) FROM ANALISE_INDICADORES WHERE id_analise = ?');
$stmtCheckAi->execute([$idCriado]);
$countAi = (int)$stmtCheckAi->fetchColumn();
assertTest($countAi > 0, 'Indicadores detectados devem estar vinculados na tabela ANALISE_INDICADORES', "Vinculados: {$countAi}");

// 8. Teste de Consulta ao Histórico do Usuário (RF23, CA08)
echo "\n8. Consulta de histórico e detalhes (RF23, CA08):\n";
$historico = $service->obterHistoricoUsuario($idUsuarioTeste);
assertTest(!empty($historico), 'Histórico do usuário deve listar as análises gravadas');
assertTest($historico[0]['id_analise'] === $idCriado, 'A análise mais recente deve aparecer no topo do histórico');

$analiseDetalhada = $service->obterAnalisePorId($idCriado, $idUsuarioTeste);
assertTest($analiseDetalhada !== null, 'obterAnalisePorId deve retornar os dados completos');
assertTest(!empty($analiseDetalhada['indicadores']), 'Detalhes da análise devem conter os indicadores associados');

// Estatísticas do usuário
$stats = $service->obterEstatisticasUsuario($idUsuarioTeste);
assertTest($stats['total'] >= 1, 'Estatísticas do usuário devem contabilizar a análise', "Total: {$stats['total']}");

// Limpeza da análise de teste para não poluir o banco do usuário
$service->excluirAnalise($idCriado, $idUsuarioTeste);
$analiseAposExclusao = $service->obterAnalisePorId($idCriado, $idUsuarioTeste);
assertTest($analiseAposExclusao === null, 'Exclusão da análise de teste deve funcionar');

$pdo->prepare('DELETE FROM usuarios WHERE id_usuario = ?')
    ->execute([$idUsuarioTeste]);

echo "\n=================================================================\n";
echo " Resultado dos Testes: {$passedTests} de {$totalTests} aprovados!\n";
echo "=================================================================\n";
