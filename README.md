# 💳 Módulo WHMCS Mercado Pago - PIX

Um módulo de gateway de pagamento **gratuito e de código aberto** (Open-Source) para WHMCS, desenvolvido para gerar cobranças via PIX utilizando a API do Mercado Pago. 

Este projeto foi criado sem fins lucrativos para ajudar a comunidade de hosts e e-commerces a integrarem pagamentos instantâneos sem depender de módulos pagos ou pesados.

## ✨ Funcionalidades e Diferenciais

* **100% Gratuito:** Sem custos de licença ou mensalidade do módulo.
* **Auto-Baixa Automática:** O módulo envia dinamicamente a `notification_url` na requisição. O Mercado Pago avisa seu WHMCS assim que o cliente paga, e a fatura é dada como paga automaticamente.
* **Performance Otimizada:** Não realiza consultas desnecessárias ao banco de dados do WHMCS, utilizando as variáveis nativas injetadas pelo sistema.
* **UX Moderna (Copia e Cola):** Utiliza a API `navigator.clipboard` moderna para o botão "Copiar PIX", com *fallback* seguro para navegadores antigos.
* **Logs Integrados:** Registra falhas de comunicação (cURL) e retornos da API diretamente no *Gateway Log* do WHMCS para facilitar o suporte.

## 🛠️ Requisitos

* **WHMCS:** Versão 8.0 ou superior (recomendado).
* **PHP:** 7.4, 8.1 ou 8.2.
* **Conta Mercado Pago:** Credenciais de Produção (Access Token).

## 🚀 Como Instalar

1. Faça o download da última versão no repositório.
2. Descompacte os arquivos e envie para o diretório raiz do seu WHMCS. Os arquivos já estão na estrutura correta:
   * `mercadopagopix.php` vai para `/modules/gateways/`
   * `callback/mercadopagopix.php` vai para `/modules/gateways/callback/`
3. Acesse o painel do seu WHMCS: **Opções (Engrenagem) > Pagamentos > Portais de Pagamento**.
4. Na aba "Todos os Portais", ative o **Mercado Pago - PIX**.
5. Insira o seu **Access Token** (obtido no [Painel de Desenvolvedor do Mercado Pago](https://www.mercadopago.com.br/developers/panel/credentials)).
6. Salve as alterações.

## 💡 Observação sobre Webhooks

**Você não precisa configurar Webhooks manualmente no painel do Mercado Pago!** O próprio módulo já informa a URL de callback correta a cada transação gerada. Certifique-se apenas de que seu WHMCS não está bloqueando requisições externas no firewall ou Cloudflare na rota `/modules/gateways/callback/mercadopagopix.php`.

## 🤝 Contribuição

Contribuições são muito bem-vindas! Se você encontrou um bug ou tem uma sugestão de melhoria:

1. Faça um *Fork* do projeto
2. Crie uma *Branch* para sua modificação (`git checkout -b feature/MinhaNovaFeature`)
3. Faça o *Commit* das suas alterações (`git commit -m 'Adicionando uma nova feature'`)
4. Faça o *Push* para a *Branch* (`git push origin feature/MinhaNovaFeature`)
5. Abra um *Pull Request*

## 📄 Licença

Este projeto está licenciado sob a licença [GPLv2](LICENSE).
