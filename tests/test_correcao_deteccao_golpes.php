<?php
/**
 * SeniorShield - Suíte de Testes da Correção de Detecção de Golpes
 *
 * Valida os 10 Casos de Testes Manuais Reais reportados:
 * 1. Falsa entrega com taxa, ameaça e urgência
 * 2. Prêmio falso de R$ 5.000 com taxa
 * 3. Falso bloqueio bancário pedindo dados
 * 4. Entrega legítima sem pagamento adicional (falso positivo corrigido)
 * 5. Mensagem cotidiana legítima
 * 6. Falsa central pedindo código e ameaçando perda de acesso
 * 7. Mensalidade legítima com consulta ao site oficial (falso positivo corrigido)
 * 8. Prêmio escolar sem cobrança
 * 9. Falso suporte pedindo código SMS
 * 10. Falsa taxa de entrega solicitando Pix
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
echo " SeniorShield - Validação Oficial dos 10 Casos de Testes Manuais\n";
echo "=================================================================\n\n";

// ---------------------------------------------------------------------
// TESTE 1: Falsa entrega com taxa, ameaça de devolução e urgência
// ---------------------------------------------------------------------
echo "--- TESTE 1: Falsa entrega com taxa, ameaça e urgência ---\n";
$msg1 = "Olá! Seu pedido está retido na alfândega. Para liberar sua entrega, pague a taxa de R$ 8,90 ainda hoje pelo link abaixo. Caso contrário, seu pacote será devolvido. Regularize agora: entrega-rastreio-pendente.com";
$r1 = $service->analisarMensagem($msg1);

assertCondition($r1['sucesso'] === true, "Teste 1: Processado com sucesso");
assertCondition($r1['pontuacao_isd'] >= 75, "Teste 1: Pontuação reflete alto risco/crítico (obtido: {$r1['pontuacao_isd']})");
assertCondition(hasIndicatorType($r1, 'entrega', $service), "Teste 1: Detectou Falsa entrega ('retido na alfândega', devolução de pacote)");
assertCondition(hasIndicatorType($r1, 'cobranca', $service), "Teste 1: Detectou Cobrança ativa ('pague a taxa')");
assertCondition(hasIndicatorType($r1, 'urgencia', $service), "Teste 1: Detectou Urgência extrema ('ainda hoje', 'regularize agora')");
assertCondition(hasIndicatorType($r1, 'link', $service), "Teste 1: Detectou Link suspeito");
assertCondition(!hasIndicatorType($r1, 'ameaca', $service), "Teste 1: Ameaça de devolução de pacote NÃO confunde com bloqueio de conta (ID 6)");

// ---------------------------------------------------------------------
// TESTE 2: Prêmio falso de R$ 5.000 com taxa de liberação via Pix
// ---------------------------------------------------------------------
echo "\n--- TESTE 2: Prêmio falso de R$ 5.000 com taxa via Pix ---\n";
$msg2 = "PARABÉNS! Você foi sorteado e ganhou R$ 5.000,00. Para receber seu prêmio, pague uma taxa de liberação de R$ 29,90 via Pix nos próximos 10 minutos. Não perca essa oportunidade! Clique no link para confirmar.";
$r2 = $service->analisarMensagem($msg2);

assertCondition($r2['sucesso'] === true, "Teste 2: Processado com sucesso");
assertCondition($r2['pontuacao_isd'] >= 75, "Teste 2: Pontuação reflete alto risco/crítico (obtido: {$r2['pontuacao_isd']})");
assertCondition(hasIndicatorType($r2, 'falso_premio', $service), "Teste 2: Detectou Falso prêmio ('você foi sorteado e ganhou', 'receber seu prêmio')");
assertCondition(hasIndicatorType($r2, 'cobranca', $service), "Teste 2: Detectou Cobrança ativa ('taxa de liberação', 'via pix')");
assertCondition(hasIndicatorType($r2, 'urgencia', $service), "Teste 2: Detectou Urgência ('próximos 10 minutos', 'não perca essa oportunidade')");
assertCondition(hasIndicatorType($r2, 'link', $service), "Teste 2: Detectou Link suspeito");

// ---------------------------------------------------------------------
// TESTE 3: Falso bloqueio bancário pedindo dados bancários e senha
// ---------------------------------------------------------------------
echo "\n--- TESTE 3: Falso bloqueio bancário pedindo dados bancários e senha ---\n";
$msg3 = "ALERTA DE SEGURANÇA: sua conta será bloqueada permanentemente em 30 minutos devido a uma atividade suspeita. Para evitar o bloqueio, confirme imediatamente seus dados bancários e sua senha no link de regularização enviado nesta mensagem.";
$r3 = $service->analisarMensagem($msg3);

assertCondition($r3['sucesso'] === true, "Teste 3: Processado com sucesso");
assertCondition($r3['pontuacao_isd'] >= 70, "Teste 3: Pontuação reflete alto risco/crítico (obtido: {$r3['pontuacao_isd']})");
assertCondition(hasIndicatorType($r3, 'ameaca', $service), "Teste 3: Detectou Ameaça de bloqueio de conta ('conta será bloqueada permanentemente')");
assertCondition(hasIndicatorType($r3, 'credenciais', $service), "Teste 3: Detectou Pedido de senha ('sua senha')");
assertCondition(hasIndicatorType($r3, 'urgencia', $service), "Teste 3: Detectou Urgência extrema ('imediatamente')");
assertCondition(hasIndicatorType($r3, 'link', $service), "Teste 3: Detectou Link suspeito");
// Verifica que 'dados bancários' não foi atribuído como senha
$termosCred = [];
foreach ($r3['indicadores_detectados'] as $ind) {
    if ($service->obterTipoIndicador($ind) === 'credenciais') {
        $termosCred = $ind['termos_detectados'];
    }
}
$temDadosBancariosEmSenha = false;
foreach ($termosCred as $t) {
    if (str_contains(mb_strtolower($t), 'dados')) {
        $temDadosBancariosEmSenha = true;
    }
}
assertCondition(!$temDadosBancariosEmSenha, "Teste 3: Termo 'dados bancários' NÃO foi classificado como senha ou código");

// ---------------------------------------------------------------------
// TESTE 4: Entrega legítima sem pagamento adicional
// ---------------------------------------------------------------------
echo "\n--- TESTE 4: Entrega legítima sem pagamento adicional ---\n";
$msg4 = "Olá! Seu pedido saiu para entrega e deverá chegar hoje até as 18h. Você pode acompanhar o andamento pelo aplicativo oficial da loja. Não é necessário realizar nenhum pagamento adicional.";
$r4 = $service->analisarMensagem($msg4);

assertCondition($r4['sucesso'] === true, "Teste 4: Processado com sucesso");
assertCondition($r4['pontuacao_isd'] === 0, "Teste 4: ISD deve ser 0 (obtido: {$r4['pontuacao_isd']})");
assertCondition($r4['total_indicadores'] === 0, "Teste 4: Nenhum indicador deve ser detectado (obtido: {$r4['total_indicadores']})");
assertCondition(!hasIndicatorType($r4, 'cobranca', $service), "Teste 4: Palavra 'pagamento' NÃO ativou cobrança indevidamente");
assertCondition($r4['classificacao'] === 'Risco muito baixo', "Teste 4: Classificado como Risco muito baixo");

// ---------------------------------------------------------------------
// TESTE 5: Mensagem cotidiana de reunião
// ---------------------------------------------------------------------
echo "\n--- TESTE 5: Mensagem cotidiana legítima ---\n";
$msg5 = "Bom dia! Passando para lembrar que nossa reunião será amanhã às 9h, na sala de informática. Por favor, confirme sua presença respondendo a esta mensagem. Obrigado!";
$r5 = $service->analisarMensagem($msg5);

assertCondition($r5['sucesso'] === true, "Teste 5: Processado com sucesso");
assertCondition($r5['pontuacao_isd'] === 0, "Teste 5: ISD deve ser 0 (obtido: {$r5['pontuacao_isd']})");
assertCondition($r5['total_indicadores'] === 0, "Teste 5: 0 indicadores detectados");
assertCondition($r5['classificacao'] === 'Risco muito baixo', "Teste 5: Classificado como Risco muito baixo");

// ---------------------------------------------------------------------
// TESTE 6: Falsa central pedindo código e ameaçando perda de acesso
// ---------------------------------------------------------------------
echo "\n--- TESTE 6: Falsa central pedindo código e ameaçando perda de acesso ---\n";
$msg6 = "Somos da equipe de suporte. Detectamos um acesso não autorizado à sua conta. Para cancelar a operação, envie o código de verificação que acabou de receber por SMS. Se não confirmar imediatamente, perderá o acesso à sua conta.";
$r6 = $service->analisarMensagem($msg6);

assertCondition($r6['sucesso'] === true, "Teste 6: Processado com sucesso");
assertCondition($r6['pontuacao_isd'] >= 75, "Teste 6: Pontuação reflete alto risco/crítico (obtido: {$r6['pontuacao_isd']})");
assertCondition(hasIndicatorType($r6, 'falso_suporte', $service), "Teste 6: Detectou Falso suporte ('somos da equipe de suporte')");
assertCondition(hasIndicatorType($r6, 'credenciais', $service), "Teste 6: Detectou Pedido de código SMS / verificação");
assertCondition(hasIndicatorType($r6, 'ameaca', $service), "Teste 6: Detectou Ameaça de perda de acesso ('perderá o acesso à sua conta')");
assertCondition(hasIndicatorType($r6, 'urgencia', $service), "Teste 6: Detectou Urgência ('imediatamente')");

// ---------------------------------------------------------------------
// TESTE 7: Mensalidade legítima com consulta ao site oficial
// ---------------------------------------------------------------------
echo "\n--- TESTE 7: Mensalidade legítima com consulta ao site oficial ---\n";
$msg7 = "Olá! Sua mensalidade vence no dia 25. Consulte o boleto e as opções de pagamento diretamente na área do cliente do site oficial da empresa.";
$r7 = $service->analisarMensagem($msg7);

assertCondition($r7['sucesso'] === true, "Teste 7: Processado com sucesso");
assertCondition($r7['pontuacao_isd'] === 0, "Teste 7: ISD deve ser 0 (obtido: {$r7['pontuacao_isd']})");
assertCondition(!hasIndicatorType($r7, 'cobranca', $service), "Teste 7: Palavra 'pagamento' em aviso informativo legítimo NÃO ativou cobrança");
assertCondition($r7['classificacao'] === 'Risco muito baixo', "Teste 7: Classificado como Risco muito baixo");

// ---------------------------------------------------------------------
// TESTE 8: Prêmio escolar sem cobrança
// ---------------------------------------------------------------------
echo "\n--- TESTE 8: Prêmio escolar sem cobrança ---\n";
$msg8 = "Parabéns! Você foi sorteado no concurso cultural da nossa escola. O resultado está disponível no mural oficial, e a entrega do certificado será realizada presencialmente, sem cobrança de taxa.";
$r8 = $service->analisarMensagem($msg8);

assertCondition($r8['sucesso'] === true, "Teste 8: Processado com sucesso");
assertCondition($r8['pontuacao_isd'] === 0, "Teste 8: ISD deve ser 0 em premiação legítima sem taxa (obtido: {$r8['pontuacao_isd']})");
assertCondition(!hasIndicatorType($r8, 'falso_premio', $service), "Teste 8: Premiação legítima sem cobrança isenta de falso prêmio");
assertCondition($r8['classificacao'] === 'Risco muito baixo', "Teste 8: Classificado como Risco muito baixo");

// ---------------------------------------------------------------------
// TESTE 9: Falso suporte pedindo código SMS
// ---------------------------------------------------------------------
echo "\n--- TESTE 9: Falso suporte pedindo código SMS ---\n";
$msg9 = "Olá, sou do suporte técnico. Para concluir a verificação da sua conta, envie o código que recebeu por SMS. Nossa equipe precisa desse código para confirmar sua identidade.";
$r9 = $service->analisarMensagem($msg9);

assertCondition($r9['sucesso'] === true, "Teste 9: Processado com sucesso");
assertCondition($r9['pontuacao_isd'] >= 45, "Teste 9: Pedido de código SMS por falso suporte atinge Suspeito/Alto risco (obtido: {$r9['pontuacao_isd']})");
assertCondition(hasIndicatorType($r9, 'falso_suporte', $service), "Teste 9: Detectou Falso suporte ('sou do suporte técnico')");
assertCondition(hasIndicatorType($r9, 'credenciais', $service), "Teste 9: Detectou Pedido de código SMS ('código que recebeu por sms')");

// ---------------------------------------------------------------------
// TESTE 10: Falsa taxa de entrega solicitando Pix
// ---------------------------------------------------------------------
echo "\n--- TESTE 10: Falsa taxa de entrega solicitando Pix ---\n";
$msg10 = "Informamos que sua encomenda está aguardando liberação. Para concluir a entrega, faça um Pix de R$ 12,50 para a transportadora. Após a confirmação, seu pedido seguirá para o endereço cadastrado.";
$r10 = $service->analisarMensagem($msg10);

assertCondition($r10['sucesso'] === true, "Teste 10: Processado com sucesso");
assertCondition($r10['pontuacao_isd'] >= 45, "Teste 10: Falsa taxa de entrega com Pix atinge Suspeito/Alto risco (obtido: {$r10['pontuacao_isd']})");
assertCondition(hasIndicatorType($r10, 'entrega', $service), "Teste 10: Detectou Falsa entrega ('aguardando liberação', 'concluir a entrega')");
assertCondition(hasIndicatorType($r10, 'cobranca', $service), "Teste 10: Detectou Cobrança ativa de Pix ('faça um pix')");

echo "\n=================================================================\n";
echo " Resultado Final dos 10 Casos: {$passedTests} de {$totalTests} aprovados! (Falhas: {$failedTests})\n";
echo "=================================================================\n";

if ($failedTests > 0) {
    exit(1);
}
