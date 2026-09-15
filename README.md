- Estilização: TailwindCSS, darkmode.
- Linguagens: PHP, JS, HTML.
- Banco de dados: MySQL, dados de acesso ficam no arquivo .env (DB_HOST, DB_NAME, DB_USER, DB_PASS).
- Todos os dados da tabela "users" devem ficar criptografados e a chave de descriptografia no arquivo ".env".
- Padrão arquitetural: Front Controller, combinado com Routing/Dispatcher.
- App Shell responsivo com Sidebar persistente.
- Endereço dos arquivo do app: public_html/english_words/
- public_html/english_words/app.index é o arquivo do Front Controller.

## Publicação e rotas

O servidor precisa apontar o diretório público para esta pasta e permitir a
leitura do `.htaccess`. Ele encaminha URLs como `/login`, `/criar-conta` e
`/dashboard` para `index.php`; arquivos existentes, como CSS e APIs, continuam
acessíveis diretamente. Em Apache, habilite `mod_rewrite` e use
`AllowOverride FileInfo Options` (ou `AllowOverride All`) para que URLs internas
não retornem 404 antes de chegarem ao Front Controller.

Não defina `APP_BASE_PATH` em instalações comuns. Quando houver proxy reverso,
defina-o com o caminho público real, por exemplo `APP_BASE_PATH=/english_words`.

- Páginas:
/index.php
/pages/
    login.html
    dashboard.html
/api/

<?php
$route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
switch ($route) {...}
?>

Front Controller Pattern
→ Router
→ Dispatcher
→ Static HTML Pages

Arquitetura MPA (Multi-Page Application).

- Geração de respostas em texto:
  - API Key do Gemini, fica no arquivo .env (GEMINI_API_KEY).
  - define('GEMINI_API_KEY', envValue('GEMINI_API_KEY', ''));
  - define('GEMINI_API_URL', envValue('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models'));
  - define('GEMINI_TRANSLATION_MODEL', envValue('GEMINI_TRANSLATION_MODEL', 'gemini-3.5-flash-lite'));

- Geração de áudios TTS:
  - API Key do Google Cloud, fica no arquivo .env (GOOGLE_CLOUD_API_KEY).
  - define('GOOGLE_CLOUD_API_KEY', envValue('GOOGLE_CLOUD_API_KEY', ''));
- ID das vozes:
  - Português: 'pt-BR-Chirp3-HD-Algieba'
  - Inglês: 'en-GB-Chirp3-HD-Algieba'
 
- Armazenamento de áudios e imagens:
- Os áudios e imagens devem ser convertidos para Base64 e salvos no banco de dados.
