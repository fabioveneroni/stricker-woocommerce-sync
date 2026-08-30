=== Stricker WooCommerce Sync ===
Conecta o catálogo da Stricker ao WooCommerce.

== Estado ==
Versão 0.1.1 de arquitetura/MVP:
- Interface administrativa em português do Brasil.
- Menu próprio "Stricker Sync", visível mesmo sem WooCommerce ativo.
- Client ID e Access Key.
- Access Key nunca é devolvida ao navegador depois de salva.
- Criptografia da Access Key no banco usando uma chave derivada do wp_salt().
- Teste de autenticação.
- Cliente REST isolado para permitir ajuste do contrato exato da API.
- Estrutura inicial preparada para categorias, importação em lotes e WP-Cron.

== Observação ==
Antes de usar em produção, confirme a URL base REST e o formato exato dos parâmetros/resposta de autenticação no ambiente Stricker fornecido para a conta.

== Versão 0.2.0 ==
- REST/HTTPS alinhado ao manual Stricker.
- URL padrão configurada para o endpoint HTTPS do manual.
- Consulta inicial de ProductTypes/Categorias.
- Tela de diagnóstico mostra o retorno quando o parser ainda não reconhece o formato.

== Versão 0.2.1 ==
- Corrigida a persistência da Access Key: o valor informado agora é sanitizado sem ser descartado antes da criptografia.
- Access Key continua armazenada criptografada no banco e nunca é devolvida ao navegador.
- Teste de conexão agora exibe "Conexão validada com sucesso!" quando a autenticação retorna um session token válido.
- Adicionado submenu "Categorias" no painel Stricker Sync, habilitando a consulta de ProductTypes.
