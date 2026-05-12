<?php
/**
 * Arquivo de Callback para o Módulo Mercado Pago PIX
 * Recebe e processa as notificações de pagamento do Mercado Pago.
 */

// Requer o arquivo de inicialização do WHMCS e funções essenciais
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Define o nome do módulo de gateway
$gatewayModuleName = basename(__FILE__, '.php');

// Carrega os parâmetros do gateway
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Verifica se o módulo está ativo
if (!$gatewayParams['type']) {
    die("Módulo não ativado");
}

// Pega os dados da notificação enviados pelo Mercado Pago
$notificationBody = file_get_contents('php://input');
$notificationData = json_decode($notificationBody, true);

// Responde ao Mercado Pago imediatamente para evitar retentativas e timeout
http_response_code(200);

// Verifica se a notificação é do tipo 'payment' e possui um ID válido
if (isset($notificationData['type']) && $notificationData['type'] === 'payment' && isset($notificationData['data']['id'])) {
    
    $paymentId = $notificationData['data']['id'];

    // --- VERIFICA O STATUS DO PAGAMENTO NA API DO MERCADO PAGO ---
    $accessToken = $gatewayParams['accessToken'];
    $apiUrl = "https://api.mercadopago.com/v1/payments/" . $paymentId;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $paymentData = json_decode($response, true);

    // --- PROCESSAMENTO DA FATURA ---
    if ($httpCode == 200 && $paymentData && $paymentData['status'] == 'approved') {
        $invoiceId = $paymentData['external_reference'];
        $transactionId = $paymentData['id'];
        $paymentAmount = $paymentData['transaction_amount'];
        $paymentFee = 0; // O Mercado Pago desconta a taxa do saldo final, o cliente paga o valor cheio.

        // Valida se a fatura existe e pertence a este gateway
        $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

        // Verifica se a transação já não foi processada anteriormente
        checkCbTransID($transactionId);

        // Log da transação com sucesso
        logTransaction($gatewayParams['name'], $paymentData, "Pagamento Aprovado");

        // Adiciona o pagamento à fatura no WHMCS
        addInvoicePayment(
            $invoiceId,
            $transactionId,
            $paymentAmount,
            $paymentFee,
            $gatewayModuleName
        );
    } else {
        // Log para pagamentos não aprovados, pendentes ou falhas na API
        logTransaction($gatewayParams['name'], [
            'Notification' => $notificationData,
            'API_Response' => $paymentData
        ], "Status Pendente, Recusado ou Falha na Verificação");
    }
} else {
    // Log de notificações que não são de pagamento (ex: merchant_order, etc)
    if (!empty($notificationBody)) {
        logTransaction($gatewayParams['name'], $notificationBody, "Notificação Ignorada (Não é tipo 'payment')");
    }
}
