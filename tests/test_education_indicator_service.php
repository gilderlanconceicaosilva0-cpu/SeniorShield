<?php
/**
 * SeniorShield - Testes Automatizados da Fase 4 e Fase 5 (Indicadores, Conteúdos e Aprendizado)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Services/IndicatorService.php';
require_once __DIR__ . '/../src/Services/EducationService.php';
require_once __DIR__ . '/../src/Services/AuthService.php';

$pdo = getDbConnection();
$indService = new IndicatorService($pdo);
$eduService = new EducationService($pdo);
$authService = new AuthService($pdo);

// Cria um usuário temporário para os testes.
$emailTeste = 'teste_education_' . uniqid() . '@seniorshield.local';
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
echo " Testes Automatizados - IndicatorService & EducationService\n";
echo "=================================================================\n\n";

// 1. Gestão de Indicadores (RF36-RF39, CA14)
echo "1. Indicadores e Pesos (RF36-RF39, CA14):\n";
$indicadores = $indService->listarIndicadores();
assertTest(!empty($indicadores), 'Listar indicadores ativos do seed', 'Total: ' . count($indicadores));

// Salvar novo indicador de teste
$novoInd = $indService->salvarIndicador([
    'nome'           => 'Indicador Teste Automação',
    'descricao'      => 'Descrição do indicador para testes',
    'palavras_chave' => 'palavra_teste_1, termo_teste_2',
    'peso'           => 18,
    'ativo'          => 1
]);
assertTest($novoInd['sucesso'] === true, 'Cadastrar novo indicador (RF36, CA14)');
$idIndTeste = (int)$novoInd['id'];

// Atualizar peso (RF38)
$updateInd = $indService->salvarIndicador([
    'nome'           => 'Indicador Teste Alterado',
    'descricao'      => 'Descrição alterada',
    'palavras_chave' => 'palavra_teste_1, termo_teste_2, novo_termo',
    'peso'           => 30,
    'ativo'          => 1
], $idIndTeste);
assertTest($updateInd['sucesso'] === true, 'Atualizar indicador e peso (RF37, RF38)');

// Alternar status (RF39)
$indService->alternarStatusIndicador($idIndTeste);
$indStatus = $indService->obterIndicadorPorId($idIndTeste);
assertTest($indStatus['ativo'] === 0, 'Desativar indicador com sucesso (RF39)');

// Excluir indicador teste
$resultadoExclusao = $indService->excluirIndicador($idIndTeste);

assertTest(
    $resultadoExclusao['sucesso'] === true,
    'Excluir indicador teste sem histórico'
);

$indExcluido = $indService->obterIndicadorPorId($idIndTeste);

    assertTest(
    $indExcluido === null,
    'Confirmar que o indicador foi removido do banco'
);

// 2. Tipos de Golpe (RF40-RF42)
echo "\n2. Tipos de Golpe (RF40-RF42):\n";
$tipos = $indService->listarTiposGolpe();
assertTest(!empty($tipos), 'Listar tipos de golpe do seed', 'Total: ' . count($tipos));

// 3. Conteúdos Educativos (RF24-RF27, CA09, CA15)
echo "\n3. Conteúdos Educativos (RF24-RF27, CA09, CA15):\n";
$conteudos = $eduService->listarConteudos(true);
assertTest(!empty($conteudos), 'Listar conteúdos educativos ativos (RF24, CA09)', 'Total: ' . count($conteudos));

$novoConteudo = $eduService->salvarConteudo([
    'titulo' => 'Dica de Teste Unitário',
    'texto'  => 'Texto explicativo sobre como se proteger de mensagens suspeitas.',
    'tipo'   => 'dica',
    'ativo'  => 1
]);
assertTest($novoConteudo['sucesso'] === true, 'Cadastrar conteúdo educativo (RF25, CA15)');
$idContTeste = (int)$novoConteudo['id'];

// Alternar status (RF27)
$eduService->alternarStatusConteudo($idContTeste);
$contStatus = $eduService->obterConteudoPorId($idContTeste);
assertTest($contStatus['ativo'] === 0, 'Desativar conteúdo educativo (RF27)');

// Excluir conteúdo teste
$eduService->excluirConteudo($idContTeste);
$contExcluido = $eduService->obterConteudoPorId($idContTeste);
assertTest($contExcluido === null, 'Excluir conteúdo teste');

// 4. Modo Aprendizado e Tentativas (RF28-RF35, RF43-RF45, CA10-CA12)
echo "\n4. Modo Aprendizado & Tentativas (RF28-RF35, RF43-RF45, CA10-CA12):\n";
$questoes = $eduService->listarQuestoes(true);
assertTest(!empty($questoes), 'Listar questões simuladas ativas (RF28, RF29, CA10)', 'Total: ' . count($questoes));

// Testar resposta correta na questão 1 (que é golpe)
$resTentativaAcerto = $eduService->processarResposta(1, 'golpe', $idUsuarioTeste);
assertTest($resTentativaAcerto['sucesso'] === true, 'Processar resposta da questão simulada (RF30)');
assertTest($resTentativaAcerto['acertou'] === true, 'Informar que resposta estava correta (RF31, CA11)');
assertTest(!empty($resTentativaAcerto['explicacao']), 'Apresentar explicação didática dos sinais (RF32, CA11)');
assertTest(!empty($resTentativaAcerto['id_tentativa']), 'Registrar tentativa no banco de dados (RF33, RF34, CA12)');

// Testar resposta errada na questão 1
$resTentativaErro = $eduService->processarResposta(1, 'seguro', $idUsuarioTeste);
assertTest($resTentativaErro['acertou'] === false, 'Informar resposta incorreta quando aplicável (RF31, CA11)');

// Desempenho do Usuário (RF35)
$desempenho = $eduService->obterDesempenhoUsuario($idUsuarioTeste);
assertTest($desempenho['total_tentativas'] >= 2, 'Consultar total de tentativas realizadas (RF35)', 'Total: ' . $desempenho['total_tentativas']);
assertTest($desempenho['total_acertos'] >= 1, 'Consultar total de acertos (RF35)', 'Acertos: ' . $desempenho['total_acertos']);
assertTest(isset($desempenho['taxa_acerto']), 'Calcular taxa percentual de acerto (RF35)', 'Taxa: ' . $desempenho['taxa_acerto'] . '%');

// Limpeza das tentativas de teste criadas para o usuário 1
if (!empty($resTentativaAcerto['id_tentativa'])) {
    $pdo->prepare('DELETE FROM TENTATIVAS_APRENDIZADO WHERE id_tentativa = ?')->execute([(int)$resTentativaAcerto['id_tentativa']]);
}
if (!empty($resTentativaErro['id_tentativa'])) {
    $pdo->prepare('DELETE FROM TENTATIVAS_APRENDIZADO WHERE id_tentativa = ?')->execute([(int)$resTentativaErro['id_tentativa']]);
}

echo "\n=================================================================\n";
echo " Resultado dos Testes: {$passedTests} de {$totalTests} aprovados!\n";
echo "=================================================================\n";

// Remove o usuário temporário criado para os testes.
$pdo->prepare('DELETE FROM usuarios WHERE id_usuario = ?')
    ->execute([$idUsuarioTeste]);