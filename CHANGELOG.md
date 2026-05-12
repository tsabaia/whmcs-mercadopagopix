## [1.1.0] - 2026-05-12

### Adicionado
- **Logs Detalhados:** Adicionada captura de erros de conexão (`curl_error`) e respostas completas da API no *Gateway Log* do WHMCS, facilitando o diagnóstico de instabilidades na comunicação com o Mercado Pago.

### Modificado
- **Performance:** Otimização na captura de dados do cliente. O módulo agora consome as informações injetadas nativamente pelo WHMCS (`$params['clientdetails']`), eliminando consultas redundantes ao banco de dados pela classe `WHMCS\User\Client`.
- **Compatibilidade WHMCS:** Atualizada a forma de carregamento das funções internas no arquivo de callback, substituindo o obsoleto `App::load_function()` pelo padrão oficial e mais seguro (`require_once`).
- **UX / Front-end:** Atualização do script de "Copiar PIX" para utilizar a API moderna `navigator.clipboard.writeText`, mantendo um *fallback* seguro para navegadores mais antigos que ainda dependem do `document.execCommand`.
