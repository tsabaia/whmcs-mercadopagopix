# whmcs-mercadopagopix
Modulo de Pagamento do MercadoPago PIX para WHMCS

Crie uma pasta chamada mercadopagopix dentro do caminho /seuwhmcs/modules/gateways/ e envie os arquivos do modulo para dentro da pasta mercadopagopix

Acesse o WHMCS e vá até "Configuração > Pagamentos > Gateways de Pagamento" no seu WHMCS.

Clique na aba "All Payment Gateways" e procure por "Mercado Pago - PIX". Clique para ativar.

Insira suas Credenciais:

Na aba "Manage Existing Gateways", insira seu Access Token de produção do Mercado Pago. Você pode encontrá-lo no Painel de Desenvolvedores do Mercado Pago.

Configure o tempo de expiração do PIX, se desejar. O padrão é 30 minutos.

Salve as alterações.

Configure o Webhook (Recomendado):

No seu painel do Mercado Pago, vá para Webhooks.

Adicione uma nova URL de webhook para o modo Produção.

A URL será: https://SEUSITE.com/modules/gateways/callback/mercadopagopix.php (substitua SEUSITE.com pelo seu domínio).

Nos eventos, selecione apenas "Pagamentos" (payment).

Salve a configuração.

Seu gateway de pagamento PIX com Mercado Pago está pronto para ser usado!
