<?php
/**
 * Arquivo de Callback para o Módulo Mercado Pago PIX
 * Recebe e processa as notificações de pagamento do Mercado Pago.
 */

// Requer o arquivo de inicialização do WHMCS
require_once __DIR__ . '/../../../init.php';

// Carrega as funções do gateway e do WHMCS
App::load_function('gateway');
App::load_function('invoice');

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

// Responde ao Mercado Pago imediatamente para evitar retentativas
http_response_code(200);

// Verifica se a notificação é do tipo 'payment'
if (isset($notificationData['type']) && $notificationData['type'] === 'payment') {
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
    curl_close($ch);

    $paymentData = json_decode($response, true);

    // --- PROCESSAMENTO DA FATURA ---
    
    if ($paymentData && $paymentData['status'] == 'approved') {
        $invoiceId = $paymentData['external_reference'];
        $transactionId = $paymentData['id'];
        $paymentAmount = $paymentData['transaction_amount'];
        $paymentFee = 0; // O Mercado Pago desconta a taxa do valor recebido.

        // Verifica se o ID da fatura é válido
        $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

        // Verifica se a transação já não foi processada
        checkCbTransID($transactionId);

        // Log da transação
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
        // Log para pagamentos não aprovados ou com erro
        logTransaction($gatewayParams['name'], $paymentData, "Pagamento não aprovado ou falha na verificação");
    }
}
