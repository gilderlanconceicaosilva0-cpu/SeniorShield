<?php
/**
 * SeniorShield - Suíte de Testes Automatizados de Regressão e Validação Contextual
 *
 * Cobre:
 * 1. Todos os 8 Casos de Referência da Seção 5 (especificação da auditoria).
 * 2. Pelo menos 24 cenários adicionais representativos cobrindo as 20 categorias obrigatórias.
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

function hasIndicator(array $res, int $id): bool {
    foreach ($res['indicadores_detectados'] as $ind) {
        if ((int)$ind['id_indicador'] === $id) {
            return true;
        }
    }
    return false;
}

echo "=================================================================\n";
echo " SeniorShield - Testes de Regressão e Precisão do ISD (Auditoria)\n";
echo "=================================================================\n\n";

// =====================================================================
// PARTE 1: REGRESSÃO DOS 8 CASOS DE REFERÊNCIA DA SEÇÃO 5
// =====================================================================
echo "--- PARTE 1: Validação dos 8 Casos de Referência (Seção 5) ---\n";

// Caso 1: Mensagem cotidiana legítima
$c1 = $service->analisarMensagem("Boa tarde! A professora avisou que o trabalho deve ser entregue na sexta-feira. Não esqueçam de levar o material.");
assertCondition($c1['sucesso'] === true, "Caso 1: Processado com sucesso");
assertCondition($c1['pontuacao_isd'] <= 20, "Caso 1: ISD <= 20 (obtido: {$c1['pontuacao_isd']})");
assertCondition($c1['total_indicadores'] === 0, "Caso 1: Nenhum indicador falso detectado");
assertCondition($c1['classificacao'] === 'Risco muito baixo', "Caso 1: Classificado como Risco muito baixo");

// Caso 2: Alerta legítimo de segurança bancária
$c2 = $service->analisarMensagem("O banco nunca solicita sua senha ou código de confirmação por mensagem. Em caso de dúvida, use o aplicativo oficial.");
assertCondition($c2['sucesso'] === true, "Caso 2: Processado com sucesso");
assertCondition($c2['pontuacao_isd'] <= 20, "Caso 2: ISD <= 20 para aviso preventivo (obtido: {$c2['pontuacao_isd']})");
assertCondition(!hasIndicator($c2, 4), "Caso 2: Indicador 4 (Pedido de senha) NÃO deve ser detectado");
assertCondition(!hasIndicator($c2, 10), "Caso 2: Indicador 10 (Falso suporte) NÃO deve ser detectado");
assertCondition($c2['classificacao'] === 'Risco muito baixo', "Caso 2: Classificado como Risco muito baixo");

// Caso 3: Possível golpe de falso familiar
$c3 = $service->analisarMensagem("Oi mãe, meu celular quebrou e estou usando outro número. Preciso que você faça uma transferência urgente para esta conta.");
assertCondition($c3['sucesso'] === true, "Caso 3: Processado com sucesso");
assertCondition($c3['pontuacao_isd'] >= 41, "Caso 3: ISD representa risco significativo (obtido: {$c3['pontuacao_isd']})");
assertCondition(hasIndicator($c3, 7), "Caso 3: Indicador 7 (Falso familiar) detectado com sucesso");
assertCondition(hasIndicator($c3, 2), "Caso 3: Indicador 2 (Pedido de transferência) detectado com sucesso");
assertCondition(hasIndicator($c3, 1), "Caso 3: Indicador 1 (Urgência) detectado com sucesso");

// Caso 4: Falsa cobrança de entrega
$c4 = $service->analisarMensagem("Sua encomenda está retida. Pague uma taxa de liberação pelo link enviado para receber o pacote ainda hoje.");
assertCondition($c4['sucesso'] === true, "Caso 4: Processado com sucesso");
assertCondition($c4['pontuacao_isd'] >= 61, "Caso 4: ISD de alto risco/crítico (obtido: {$c4['pontuacao_isd']})");
assertCondition(hasIndicator($c4, 8), "Caso 4: Indicador 8 (Falsa entrega) detectado com sucesso");
assertCondition(hasIndicator($c4, 2), "Caso 4: Indicador 2 (Pedido de dinheiro/taxa) detectado com sucesso");
assertCondition(hasIndicator($c4, 3), "Caso 4: Indicador 3 (Link suspeito) detectado com sucesso");
assertCondition(hasIndicator($c4, 1), "Caso 4: Indicador 1 (Urgência) detectado com sucesso");

// Caso 5: Falso suporte bancário
$c5 = $service->analisarMensagem("Olá, sou do suporte técnico do seu banco. Instale este aplicativo e compartilhe o código de acesso para proteger sua conta.");
assertCondition($c5['sucesso'] === true, "Caso 5: Processado com sucesso");
assertCondition($c5['pontuacao_isd'] >= 41, "Caso 5: ISD de risco suspeito/alto (obtido: {$c5['pontuacao_isd']})");
assertCondition(hasIndicator($c5, 10), "Caso 5: Indicador 10 (Falso suporte/app) detectado com sucesso");
assertCondition(hasIndicator($c5, 4), "Caso 5: Indicador 4 (Pedido de código de acesso) detectado com sucesso");

// Caso 6: Ameaça de bloqueio com link
$c6 = $service->analisarMensagem("Sua conta será bloqueada hoje. Acesse o link encurtado enviado nesta mensagem e informe seus dados para evitar o bloqueio.");
assertCondition($c6['sucesso'] === true, "Caso 6: Processado com sucesso");
assertCondition($c6['pontuacao_isd'] >= 41, "Caso 6: ISD de risco suspeito/alto (obtido: {$c6['pontuacao_isd']})");
assertCondition(hasIndicator($c6, 6), "Caso 6: Indicador 6 (Ameaça de bloqueio) detectado com sucesso");
assertCondition(hasIndicator($c6, 3), "Caso 6: Indicador 3 (Link suspeito) detectado com sucesso");
assertCondition(!hasIndicator($c6, 4), "Caso 6: Indicador 4 (Pedido de senha) NÃO deve ser acionado por 'informe seus dados'");

// Caso 7: Falsa oferta de emprego
$c7 = $service->analisarMensagem("Parabéns! Você foi selecionado para uma vaga com salário alto. Pague uma taxa de cadastro para liberar sua contratação.");
assertCondition($c7['sucesso'] === true, "Caso 7: Processado com sucesso");
assertCondition($c7['pontuacao_isd'] >= 41, "Caso 7: ISD de risco suspeito/alto (obtido: {$c7['pontuacao_isd']})");
assertCondition(hasIndicator($c7, 9), "Caso 7: Indicador 9 (Falso emprego) detectado com sucesso");
assertCondition(hasIndicator($c7, 2), "Caso 7: Indicador 2 (Taxa de cadastro) detectado com sucesso");
assertCondition(!hasIndicator($c7, 5), "Caso 7: Indicador 5 (Falso prêmio) NÃO deve ser confundido com vaga de emprego");

// Caso 8: Premiação escolar legítima sem cobrança
$c8 = $service->analisarMensagem("A escola publicou o resultado do sorteio. Fui selecionado para receber um livro, e a organização confirmou que a entrega é gratuita e não existe nenhuma taxa.");
assertCondition($c8['sucesso'] === true, "Caso 8: Processado com sucesso");
assertCondition($c8['pontuacao_isd'] <= 20, "Caso 8: ISD <= 20 em contexto com gratuidade garantida (obtido: {$c8['pontuacao_isd']})");
assertCondition(!hasIndicator($c8, 5), "Caso 8: Indicador 5 (Falso prêmio) isento pela ausência de taxa e contexto escolar");
assertCondition(!hasIndicator($c8, 2), "Caso 8: Indicador 2 NÃO acionado pois não há cobrança");
assertCondition($c8['classificacao'] === 'Risco muito baixo', "Caso 8: Classificado como Risco muito baixo");

// =====================================================================
// PARTE 2: TESTES ADICIONAIS DAS 20 CATEGORIAS OBRIGATÓRIAS
// =====================================================================
echo "\n--- PARTE 2: 25 Cenários Adicionais Cobrindo as 20 Categorias ---\n";

// Categoria 1: Mensagem escolar comum
$t1 = $service->analisarMensagem("Lembramos que a reunião de pais e mestres do 3º ano ocorrerá na próxima terça às 19h no auditório da escola.");
assertCondition($t1['pontuacao_isd'] === 0, "Cat 1: Mensagem escolar comum sem termos de risco tem ISD 0");

// Categoria 2: Alerta legítimo de segurança institucional
$t2 = $service->analisarMensagem("Alerta de segurança: A Polícia Civil nunca solicita senhas, tokens ou transferências por telefone ou mensagem. Desconfie de cobranças.");
assertCondition($t2['pontuacao_isd'] <= 20 && !hasIndicator($t2, 4), "Cat 2: Alerta legítimo da polícia não aciona pedido de senha");

// Categoria 3: Pedido direto de senha malicioso
$t3 = $service->analisarMensagem("Sua conta bancária precisa de atualização imediata. Digite sua senha de 6 dígitos para validar o seu cartão.");
assertCondition(hasIndicator($t3, 4) && $t3['pontuacao_isd'] >= 25, "Cat 3: Pedido explícito de senha aciona Indicador 4 (peso 25)");

// Categoria 4: Pedido de código de confirmação
$t4 = $service->analisarMensagem("Enviamos um SMS para você. Envie o código de confirmação recebido para liberar seu acesso.");
assertCondition(hasIndicator($t4, 4), "Cat 4: Solicitação de código de confirmação aciona Indicador 4");

// Categoria 5: Falso suporte bancário
$t5 = $service->analisarMensagem("Central de segurança do banco: Identificamos movimentação atípica. Confirme uma operação com nosso atendente agora.");
assertCondition(hasIndicator($t5, 10), "Cat 5: Pretexto de suporte bancário aciona Indicador 10");

// Categoria 6: Compartilhamento de tela
$t6 = $service->analisarMensagem("Para concluir o procedimento de segurança, compartilhe sua tela com o suporte técnico.");
assertCondition(hasIndicator($t6, 10), "Cat 6: Pedido de compartilhamento de tela aciona Indicador 10");

// Categoria 7: Instalação de aplicativo suspeito
$t7 = $service->analisarMensagem("Instale este aplicativo de acesso remoto no seu celular para proteger sua conta contra invasores.");
assertCondition(hasIndicator($t7, 10), "Cat 7: Solicitação de instalação de aplicativo remoto aciona Indicador 10");

// Categoria 8: Falso familiar com número novo
$t8 = $service->analisarMensagem("Oi pai! Salve meu novo número na sua agenda. Meu telefone quebrou e preciso que você pague essa conta pra mim com urgência.");
assertCondition(hasIndicator($t8, 7) && hasIndicator($t8, 2), "Cat 8: Variação de falso parente aciona Indicador 7 e 2");

// Categoria 9: Falsa entrega com taxa alfandegária
$t9 = $service->analisarMensagem("Correios: Objeto retido na fiscalização. Efetue o pagamento de taxa alfandegária para liberar sua encomenda pendente.");
assertCondition(hasIndicator($t9, 8) && hasIndicator($t9, 2), "Cat 9: Falsa entrega com taxa alfandegária aciona Indicadores 8 e 2");

// Categoria 10: Falso emprego com comissão antecipada
$t10 = $service->analisarMensagem("Trabalho fácil para ganhar 500 por dia digitando textos. Vaga garantida com depósito para contratação.");
assertCondition(hasIndicator($t10, 9), "Cat 10: Oferta de trabalho fácil com depósito aciona Indicador 9");

// Categoria 11: Falso empréstimo com taxa antecipada
$t11 = $service->analisarMensagem("Empréstimo aprovado no valor de R$ 25.000,00 sem consulta ao SPC. Pague a taxa para liberar o empréstimo imediatamente.");
assertCondition(hasIndicator($t11, 9) && hasIndicator($t11, 2), "Cat 11: Empréstimo aprovado com taxa de liberação aciona Indicador 9");

// Categoria 12: Falso investimento com retorno garantido
$t12 = $service->analisarMensagem("Oportunidade única: investimento sem risco com lucro diário garantido e retorno acima do mercado. Multiplique seu dinheiro.");
assertCondition(hasIndicator($t12, 9), "Cat 12: Promessa de investimento com lucro diário garantido aciona Indicador 9");

// Categoria 13: Falso prêmio com taxa de resgate
$t13 = $service->analisarMensagem("Parabéns você ganhou um prêmio especial de 10 mil reais! Pague a taxa para liberar o prêmio e resgate agora.");
assertCondition(hasIndicator($t13, 5) && hasIndicator($t13, 2), "Cat 13: Falso prêmio que exige taxa aciona Indicador 5 e 2");

// Categoria 14: Prêmio legítimo sem cobrança
$t14 = $service->analisarMensagem("Resultado do concurso de poesia: você foi contemplado com o certificado. A emissão é totalmente gratuita e sem custo.");
assertCondition($t14['pontuacao_isd'] <= 20 && !hasIndicator($t14, 2), "Cat 14: Certificado legítimo totalmente gratuito e sem custo tem risco baixo");

// Categoria 15: Mensagem com gratuidade aparente mas com cobrança ativa
$t15 = $service->analisarMensagem("Ganhe um celular grátis da nossa promoção! Basta pagar a taxa de entrega de 45 reais pelo Pix para receber em casa.");
assertCondition(hasIndicator($t15, 2) && $t15['pontuacao_isd'] >= 40, "Cat 15: Brinde gratuito que cobra taxa de entrega NÃO é isento de risco");

// Categoria 16: Múltiplos sinais independentes de fraude
$t16 = $service->analisarMensagem("URGENTE: Sua conta foi bloqueada devido a suspeita de fraude. Acesse bit.ly/regularizar e digite sua senha e código sms agora.");
assertCondition($t16['pontuacao_isd'] >= 85 && $t16['classificacao'] === 'Risco crítico', "Cat 16: 4 sinais independentes atingem Risco crítico (obtido: {$t16['pontuacao_isd']})");

// Categoria 17: Variação com e sem acentos
$t17a = $service->analisarMensagem("URGÊNCIA: FAÇA UMA TRANSFERÊNCIA AGORA PELO PIX");
$t17b = $service->analisarMensagem("urgencia: faca uma transferencia agora pelo pix");
assertCondition($t17a['pontuacao_isd'] === $t17b['pontuacao_isd'], "Cat 17: Mensagens idênticas com e sem acentos produzem pontuação igual ({$t17a['pontuacao_isd']})");

// Categoria 18: Variação de maiúsculas e minúsculas mistas
$t18 = $service->analisarMensagem("uRgEnTe: ReSpOnDa ImEdIaTaMeNtE e EnViE o PiX");
assertCondition(hasIndicator($t18, 1) && hasIndicator($t18, 2), "Cat 18: Maiúsculas e minúsculas misturadas identificadas perfeitamente");

// Categoria 19: Espaçamento e pontuação variados
$t19 = $service->analisarMensagem("URGENTE  ...   Você   deve   fazer   um   pix   ...  imediatamente   !");
assertCondition(hasIndicator($t19, 1) && hasIndicator($t19, 2), "Cat 19: Espaços múltiplos e pontuação intermediária tratados corretamente");

// Categoria 20: Negativa e escopo preventivo institucional
$t20 = $service->analisarMensagem("Atenção cliente: O banco nunca solicita sua senha por e-mail ou WhatsApp. Em caso de dúvida, consulte sua agência.");
assertCondition($t20['pontuacao_isd'] <= 20 && !hasIndicator($t20, 4), "Cat 20: Orientação preventiva de não solicitar senha reconhecida");

// Categoria 21: Tentativa de bypass preventivo (mensagem mista/ardilosa)
$t21 = $service->analisarMensagem("O banco nunca pede senha por telefone. Porém, acesse bit.ly/recadastro e informe seu token imediatamente para evitar o bloqueio.");
assertCondition(hasIndicator($t21, 3) && $t21['pontuacao_isd'] >= 41, "Cat 21: Tentativa ardilosa com link falso e token é detectada mesmo com aviso no início");

// Categoria 22: Falso familiar alegando celular quebrado/água
$t22 = $service->analisarMensagem("Bênção mãe, troquei de número porque meu telefone quebrou. Preciso que você faça um pix urgente pra mim.");
assertCondition(hasIndicator($t22, 7) && hasIndicator($t22, 2) && hasIndicator($t22, 1), "Cat 22: Falso familiar com bênção e celular quebrado detectado");

// Categoria 23: Mensagem cotidiana de agendamento de saúde
$t23 = $service->analisarMensagem("Olá Maria, confirmamos sua consulta odontológica para quinta-feira às 15:30. Em caso de imprevisto, favor avisar com antecedência.");
assertCondition($t23['pontuacao_isd'] === 0 && $t23['classificacao'] === 'Risco muito baixo', "Cat 23: Mensagem cotidiana de saúde tem ISD 0");

// Categoria 24: Vaga de emprego legítima sem cobrança
$t24 = $service->analisarMensagem("Parabéns, você foi selecionado para a vaga de recepcionista. Compareça na segunda-feira às 9h para entrevista presencial com seu currículo.");
assertCondition($t24['pontuacao_isd'] <= 20 && !hasIndicator($t24, 2) && !hasIndicator($t24, 9), "Cat 24: Vaga de emprego legítima sem cobrança nem taxas não é penalizada");

// Categoria 25: Evento cultural com entrada franca e sem taxa
$t25 = $service->analisarMensagem("A feira cultural do bairro acontecerá no domingo. A entrega de ingressos é gratuita e não existe nenhuma taxa de participação.");
assertCondition($t25['pontuacao_isd'] <= 20 && $t25['total_indicadores'] === 0, "Cat 25: Evento cultural gratuito sem cobrança tem ISD 0");

echo "\n--- PARTE 3: Problema 2 — Entregas Legítimas vs Golpes com Taxas e Links ---\n";

// Categoria 26: Rastreamento legítimo com código de rastreio operacional (sem taxa/link suspeito)
$t26 = $service->analisarMensagem("Olá! Seu pedido foi postado. Acompanhe a entrega com o código de rastreio AA123456789BR.");
assertCondition($t26['pontuacao_isd'] === 0 && !hasIndicator($t26, 8), "Cat 26: Mensagem legítima de rastreamento com código operacional tem ISD 0 e não aciona ID 8");

// Categoria 27: Confirmação legítima de endereço sem link ou cobrança
$t27 = $service->analisarMensagem("Transportadora Express: Por favor, confirme o endereço para a entrega da sua encomenda: Rua das Flores, 123. Responda SIM.");
assertCondition($t27['pontuacao_isd'] === 0 && !hasIndicator($t27, 8), "Cat 27: Mensagem legítima de confirmação de endereço tem ISD 0 e não aciona ID 8");

// Categoria 28: Entrega sem cobrança
$t28 = $service->analisarMensagem("Aviso de entrega de transportadora: seu pacote chegará hoje. A entrega é totalmente gratuita e sem custo adicional.");
assertCondition($t28['pontuacao_isd'] === 0 && !hasIndicator($t28, 8) && !hasIndicator($t28, 2), "Cat 28: Aviso de entrega de transportadora gratuita e sem custo tem ISD 0");

// Categoria 29: Aviso legítimo de encomenda disponível para retirada gratuita
$t29 = $service->analisarMensagem("Correios: Sua encomenda está disponível para retirada na agência central. A retirada é gratuita no horário comercial.");
assertCondition($t29['pontuacao_isd'] === 0 && !hasIndicator($t29, 8) && !hasIndicator($t29, 2), "Cat 29: Encomenda disponível para retirada gratuita tem ISD 0");

// Categoria 30: Golpe de entrega com encomenda retida, taxa e link suspeito encurtado
$t30 = $service->analisarMensagem("Correios: Sua encomenda está retida na fiscalização. Acesse bit.ly/correios-taxa para pagar a taxa de liberação e receber o pacote.");
assertCondition(hasIndicator($t30, 8) && hasIndicator($t30, 3) && hasIndicator($t30, 2) && $t30['pontuacao_isd'] >= 70, "Cat 30: Golpe de entrega com retenção, taxa e link suspeito atinge alto risco (obtido: {$t30['pontuacao_isd']}) acionando IDs 8, 3 e 2");

// Categoria 31: Golpe de entrega com código de rastreio, retenção e cobrança via Pix
$t31 = $service->analisarMensagem("Aviso: Seu código de rastreio indica encomenda retida. Pague a taxa alfandegária de R$ 35,00 via Pix para liberação.");
assertCondition(hasIndicator($t31, 8) && hasIndicator($t31, 2) && $t31['pontuacao_isd'] >= 50, "Cat 31: Golpe de entrega com código de rastreio e taxa alfandegária aciona IDs 8 e 2");

// Categoria 32: Golpe de entrega com atualização de endereço e link suspeito encurtador
$t32 = $service->analisarMensagem("Transportadora: Entrega pendente. Acesse is.gd/rastreio-pacote e confirme o endereço para receber o pacote.");
assertCondition(hasIndicator($t32, 8) && hasIndicator($t32, 3) && $t32['pontuacao_isd'] >= 40, "Cat 32: Pedido de confirmação de endereço com link suspeito encurtador aciona IDs 8 e 3");

echo "\n=================================================================\n";
echo " Resultado dos Testes de Auditoria: {$passedTests} de {$totalTests} aprovados!\n";
if ($failedTests > 0) {
    echo " ATENÇÃO: {$failedTests} falhas detectadas!\n";
}
echo "=================================================================\n";

if ($failedTests > 0) {
    exit(1);
}

