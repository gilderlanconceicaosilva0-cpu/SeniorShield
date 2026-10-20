<?php
/**
 * SeniorShield - Suíte de Testes da Correção de Detecção de Golpes
 *
 * Valida a precisão da detecção para os 4 cenários reais reportados
 * e cenários legítimos (incluindo tratamento contextual de "link abaixo").
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Services/AnalysisService.php';

$pdo = getDbConnection();
$service = new AnalysisService($pdo);

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function assertCondition(bool $cond, string $description, ?string $detail = null): void {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    if ($cond) {
        $passedTests++;
        echo "  [PASS] {$description}\n";
    } else {
        $failedTests++;
        echo "  [FAIL] {$description}\n";
        if ($detail) {
            echo "         Detalhes: {$detail}\n";
        }
    }
}

function hasIndicatorType(array $res, string $tipoEsperado, AnalysisService $svc): bool {
    foreach ($res['indicadores_detectados'] as $ind) {
        if ($svc->obterTipoIndicador($ind) === $tipoEsperado) {
            return true;
        }
    }
    return false;
}

echo "=================================================================\n";
echo " SeniorShield - Validação das Correções do Detector de Golpes\n";
echo "=================================================================\n\n";

// ---------------------------------------------------------------------
// TESTE 1: Falsa mensagem de encomenda retida (Alfândega, taxa R$ 9,87, urgência e devolução)
// ---------------------------------------------------------------------
echo "--- TESTE 1: Falsa encomenda retida com taxa, urgência e ameaça de devolução ---\n";
$msg1 = "ATENÇÃO: Sua encomenda está retida devido a uma pendência na alfândega. Para liberar a entrega ainda hoje, pague a taxa de R$ 9,87 pelo link abaixo. Caso não regularize, seu pedido será devolvido ao remetente.\n\nhttps://regularizar-encomenda.example/pagamento";
$r1 = $service->analisarMensagem($msg1);

assertCondition($r1['sucesso'] === true, "Teste 1: Processado com sucesso");
assertCondition($r1['pontuacao_isd'] >= 75, "Teste 1: Pontuação reflete alto risco/crítico (esperado >= 75, obtido: {$r1['pontuacao_isd']})");
assertCondition(in_array($r1['classificacao'], ['Alto risco', 'Risco crítico'], true), "Teste 1: Classificação deve ser 'Alto risco' ou 'Risco crítico' (obtido: {$r1['classificacao']})");
assertCondition(hasIndicatorType($r1, 'cobranca', $service), "Teste 1: Detectou indicador de Cobrança / Pedido de dinheiro ('pague a taxa')");
assertCondition(hasIndicatorType($r1, 'link', $service), "Teste 1: Detectou indicador de Link suspeito (link em contexto malicioso)");
assertCondition(hasIndicatorType($r1, 'urgencia', $service), "Teste 1: Detectou indicador de Urgência extrema ('ainda hoje')");
assertCondition(hasIndicatorType($r1, 'entrega', $service), "Teste 1: Detectou indicador de Falsa entrega ('encomenda retida', 'pendência na alfândega')");
assertCondition(hasIndicatorType($r1, 'ameaca', $service), "Teste 1: Detectou indicador de Ameaça ('devolvido ao remetente')");

// ---------------------------------------------------------------------
// TESTE 2: Mensagem legítima de encomenda em entrega
// ---------------------------------------------------------------------
echo "\n--- TESTE 2: Mensagem legítima de entrega de encomenda ---\n";
$msg2 = "Olá! Sua encomenda saiu para entrega e deve chegar hoje ao endereço informado. Caso não esteja em casa, você poderá consultar a previsão de entrega no aplicativo oficial da transportadora. Obrigado pela preferência!";
$r2 = $service->analisarMensagem($msg2);

assertCondition($r2['sucesso'] === true, "Teste 2: Processado com sucesso");
assertCondition($r2['pontuacao_isd'] === 0, "Teste 2: Pontuação deve permanecer exatamente 0 (obtido: {$r2['pontuacao_isd']})");
assertCondition($r2['total_indicadores'] === 0, "Teste 2: Nenhum indicador deve ser detectado (obtido: {$r2['total_indicadores']})");
assertCondition($r2['classificacao'] === 'Risco muito baixo', "Teste 2: Classificação 'Risco muito baixo'");

// ---------------------------------------------------------------------
// TESTE 3: Falso prêmio de R$ 5.000 com taxa de liberação e prazo terminando hoje
// ---------------------------------------------------------------------
echo "\n--- TESTE 3: Falso prêmio de R$ 5.000 com taxa e prazo terminando hoje ---\n";
$msg3 = "PARABÉNS! Você foi selecionado para receber um prêmio de R$ 5.000. Para confirmar o recebimento, precisamos que você faça o cadastro imediatamente e pague uma pequena taxa de liberação. Não perca essa oportunidade, pois o prazo termina hoje!";
$r3 = $service->analisarMensagem($msg3);

assertCondition($r3['sucesso'] === true, "Teste 3: Processado com sucesso");
assertCondition($r3['pontuacao_isd'] >= 60, "Teste 3: Pontuação deve refletir risco alto/crítico (esperado >= 60, obtido: {$r3['pontuacao_isd']})");
assertCondition(hasIndicatorType($r3, 'falso_premio', $service), "Teste 3: Detectou Falso prêmio ('você foi selecionado para receber um prêmio')");
assertCondition(hasIndicatorType($r3, 'cobranca', $service), "Teste 3: Detectou Cobrança ('taxa de liberação', 'pague uma pequena taxa')");
assertCondition(hasIndicatorType($r3, 'urgencia', $service), "Teste 3: Detectou Urgência ('imediatamente', 'prazo termina hoje', 'não perca essa oportunidade')");

// ---------------------------------------------------------------------
// TESTE 4: Falso prêmio de R$ 2.000 com cobrança de taxa de R$ 100 ainda hoje
// ---------------------------------------------------------------------
echo "\n--- TESTE 4: Falso prêmio de R$ 2.000 com cobrança de taxa de R$ 100 ainda hoje ---\n";
$msg4 = "Você ganhou um prêmio de R$ 2.000! Para receber o dinheiro, pague uma taxa de R$ 100 ainda hoje. Não perca essa oportunidade!";
$r4 = $service->analisarMensagem($msg4);

assertCondition($r4['sucesso'] === true, "Teste 4: Processado com sucesso");
assertCondition($r4['pontuacao_isd'] >= 60, "Teste 4: Pontuação deve refletir risco alto/crítico (esperado >= 60, obtido: {$r4['pontuacao_isd']})");
assertCondition(hasIndicatorType($r4, 'falso_premio', $service), "Teste 4: Detectou Falso prêmio ('você ganhou um prêmio')");
assertCondition(hasIndicatorType($r4, 'cobranca', $service), "Teste 4: Detectou Cobrança ('pague uma taxa', 'dinheiro')");
assertCondition(hasIndicatorType($r4, 'urgencia', $service), "Teste 4: Detectou Urgência ('ainda hoje', 'não perca essa oportunidade')");

// ---------------------------------------------------------------------
// TESTE LEGÍTIMO 1: Mensagem de entrega com 'link abaixo' sem contexto de golpe
// ---------------------------------------------------------------------
echo "\n--- TESTE LEGÍTIMO 1: Entrega com expressão 'link abaixo' sem contexto de golpe ---\n";
$msgLegitima1 = "Prezado cliente, seu pedido #9482 foi despachado e a entrega é totalmente gratuita. Você pode acompanhar o status da remessa pelo link abaixo no site oficial: https://rastreamento.correios.com.br. Obrigado pela preferência!";
$rLeg1 = $service->analisarMensagem($msgLegitima1);

assertCondition($rLeg1['sucesso'] === true, "Legítimo 1: Processado com sucesso");
assertCondition($rLeg1['pontuacao_isd'] === 0, "Legítimo 1: Expressão 'link abaixo' NÃO deve pontuar como golpe (obtido: {$rLeg1['pontuacao_isd']})");
assertCondition(!hasIndicatorType($rLeg1, 'link', $service), "Legítimo 1: Indicador de link suspeito NÃO acionado");
assertCondition($rLeg1['classificacao'] === 'Risco muito baixo', "Legítimo 1: Classificação 'Risco muito baixo'");

// ---------------------------------------------------------------------
// TESTE LEGÍTIMO 2: Aviso preventivo de segurança bancária
// ---------------------------------------------------------------------
echo "\n--- TESTE LEGÍTIMO 2: Aviso preventivo de segurança bancária ---\n";
$msgLegitima2 = "Aviso de Segurança: O banco nunca solicita sua senha ou código de confirmação por mensagem. Em caso de dúvida, utilize o aplicativo oficial.";
$rLeg2 = $service->analisarMensagem($msgLegitima2);

assertCondition($rLeg2['sucesso'] === true, "Legítimo 2: Processado com sucesso");
assertCondition($rLeg2['pontuacao_isd'] === 0, "Legítimo 2: Aviso preventivo deve ter pontuação 0 (obtido: {$rLeg2['pontuacao_isd']})");
assertCondition(!hasIndicatorType($rLeg2, 'credenciais', $service), "Legítimo 2: Indicador de credenciais desativado pelo contexto preventivo");
assertCondition($rLeg2['classificacao'] === 'Risco muito baixo', "Legítimo 2: Classificação 'Risco muito baixo'");

// ---------------------------------------------------------------------
// TESTE LEGÍTIMO 3: Cortesia / cupom de loja totalmente gratuito
// ---------------------------------------------------------------------
echo "\n--- TESTE LEGÍTIMO 3: Cortesia/cupom sem cobrança ---\n";
$msgLegitima3 = "Parabéns pelo seu aniversário! Como cortesia, você ganhou um cupom de 10% de desconto para compras na loja física esta semana. A entrega é gratuita e não há qualquer taxa.";
$rLeg3 = $service->analisarMensagem($msgLegitima3);

assertCondition($rLeg3['sucesso'] === true, "Legítimo 3: Processado com sucesso");
assertCondition($rLeg3['pontuacao_isd'] <= 20, "Legítimo 3: Cortesia gratuita sem cobrança permanece em risco muito baixo (obtido: {$rLeg3['pontuacao_isd']})");
assertCondition($rLeg3['classificacao'] === 'Risco muito baixo', "Legítimo 3: Classificação 'Risco muito baixo'");

echo "\n=================================================================\n";
echo " Resultado dos Testes de Detecção: {$passedTests} de {$totalTests} aprovados! (Falhas: {$failedTests})\n";
echo "=================================================================\n";

if ($failedTests > 0) {
    exit(1);
}
