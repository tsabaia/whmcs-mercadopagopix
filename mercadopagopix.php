<?php
/**
 * Módulo de Gateway de Pagamento PIX com Mercado Pago para WHMCS
 *
 * @copyright Copyright (c) 2024
 * @license https://www.gnu.org/licenses/gpl-2.0.html GPLv2
 */

if (!defined("WHMCS")) {
    die("Este arquivo não pode ser acessado diretamente.");
}

/**
 * Define os metadados do módulo.
 *
 * @return array
 */
function mercadopagopix_MetaData()
{
    return array(
        'DisplayName' => 'Mercado Pago - PIX',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 * Define os campos de configuração do gateway.
 *
 * @return array
 */
function mercadopagopix_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Mercado Pago - PIX',
        ),
        'accessToken' => array(
            'FriendlyName' => 'Access Token',
            'Type' => 'password',
            'Size' => '50',
            'Description' => 'Seu Access Token de Produção do Mercado Pago. Encontre em: <a href="https://www.mercadopago.com.br/developers/panel/credentials" target="_blank">Credenciais</a>.',
        ),
        'pixExpirationMinutes' => array(
            'FriendlyName' => 'Tempo de Expiração do PIX (minutos)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '30',
            'Description' => 'Tempo em minutos que o QR Code do PIX ficará válido para pagamento.',
        ),
    );
}

/**
 * Gera o código de pagamento para exibir na fatura.
 *
 * @param array $params Parâmetros da fatura
 * @return string HTML para exibição
 */
function mercadopagopix_link($params)
{
    // Parâmetros do Gateway
    $accessToken = $params['accessToken'];
    $expirationMinutes = (int)$params['pixExpirationMinutes'] ?: 30;

    // Parâmetros da Fatura
    $invoiceId = $params['invoiceid'];
    $amount = number_format($params['amount'], 2, '.', '');
    $currency = $params['currency'];

    // Parâmetros do Cliente
    $client = new WHMCS\User\Client($params['clientdetails']['userid']);
    $clientDetails = $client->getDetails();
    $clientNameParts = explode(' ', $clientDetails['fullname']);
    $firstName = array_shift($clientNameParts);
    $lastName = implode(' ', $clientNameParts);

    // URL do Sistema e de Callback
    $systemUrl = $params['systemurl'];
    $callbackUrl = $systemUrl . '/modules/gateways/callback/mercadopagopix.php';

    // --- CHAMADA À API DO MERCADO PAGO PARA CRIAR O PAGAMENTO ---
    
    $apiUrl = 'https://api.mercadopago.com/v1/payments';

    $payload = [
        'transaction_amount' => (float)$amount,
        'description' => "Pagamento Fatura #" . $invoiceId,
        'payment_method_id' => 'pix',
        'payer' => [
            'email' => $clientDetails['email'],
            'first_name' => $firstName,
            'last_name' => $lastName,
        ],
        'notification_url' => $callbackUrl,
        'external_reference' => $invoiceId,
        'date_of_expiration' => date('c', time() + ($expirationMinutes * 60)),
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
        'X-Idempotency-Key: WHMCS-INV-' . $invoiceId . '-' . time() // Chave de idempotência para evitar pagamentos duplicados
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true);

    // --- TRATAMENTO DA RESPOSTA E EXIBIÇÃO DO HTML ---

    if ($httpCode == 201 && isset($responseData['point_of_interaction']['transaction_data'])) {
        $qrCodeBase64 = $responseData['point_of_interaction']['transaction_data']['qr_code_base64'];
        $qrCode = $responseData['point_of_interaction']['transaction_data']['qr_code'];
        $expirationTimestamp = strtotime($responseData['date_of_expiration']);

        $htmlOutput = '
        <div style="text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 8px; max-width: 450px; margin: 20px auto; background-color: #f9f9f9;">
            <h3 style="margin-top: 0;">Pague com PIX para confirmar</h3>
            <p>1. Abra o app do seu banco e escaneie o código abaixo:</p>
            <img src="data:image/png;base64,' . $qrCodeBase64 . '" alt="PIX QR Code" style="max-width: 250px; margin: 15px auto; display: block;">
            
            <p style="margin-top: 20px;">2. Ou use o PIX Copia e Cola:</p>
            <div style="display: flex; margin-top: 10px;">
                <input type="text" id="pixCode" value="' . htmlspecialchars($qrCode) . '" readonly style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px 0 0 4px; background-color: #fff;">
                <button onclick="copyPixCode()" style="padding: 8px 12px; border: 1px solid #007bff; background-color: #007bff; color: white; border-radius: 0 4px 4px 0; cursor: pointer;">Copiar</button>
            </div>
            <p id="copyFeedback" style="font-size: 12px; color: green; height: 15px;"></p>
            
            <div id="countdown" style="margin-top: 20px; font-size: 14px; color: #d9534f;"></div>
        </div>

        <script>
            function copyPixCode() {
                var copyText = document.getElementById("pixCode");
                copyText.select();
                copyText.setSelectionRange(0, 99999); // Para dispositivos móveis
                document.execCommand("copy");
                
                var feedback = document.getElementById("copyFeedback");
                feedback.textContent = "Código copiado!";
                setTimeout(function() { feedback.textContent = ""; }, 3000);
            }

            var expirationTime = ' . $expirationTimestamp . ' * 1000;
            var countdownElement = document.getElementById("countdown");

            function updateCountdown() {
                var now = new Date().getTime();
                var distance = expirationTime - now;
                
                if (distance < 0) {
                    clearInterval(x);
                    countdownElement.innerHTML = "PIX EXPIRADO. Por favor, atualize a página para gerar um novo código.";
                    return;
                }
                
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                countdownElement.innerHTML = "Este código expira em: " + minutes + "m " + seconds + "s";
            }

            updateCountdown();
            var x = setInterval(updateCountdown, 1000);
        </script>';

        return $htmlOutput;
    } else {
        // Log do erro e mensagem para o cliente
        logTransaction($params['name'], $responseData, 'Erro ao gerar PIX');
        return '<div class="alert alert-danger">Ocorreu um erro ao gerar o PIX. Por favor, tente novamente mais tarde ou contate o suporte.</div>';
    }
}
