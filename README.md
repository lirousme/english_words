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
`/words` para `index.php`; arquivos existentes, como CSS e APIs, continuam
acessíveis diretamente. Em Apache, habilite `mod_rewrite` e use
`AllowOverride FileInfo Options` (ou `AllowOverride All`) para que URLs internas
não retornem 404 antes de chegarem ao Front Controller.

Não defina `APP_BASE_PATH` em instalações comuns. Quando houver proxy reverso,
defina-o com o caminho público real, por exemplo `APP_BASE_PATH=/english_words`.

- Páginas:
/index.php
/pages/
    login.html
    words.html
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
  - Selecione o provedor no `.env` com `AI_PROVIDER=gemini` ou `AI_PROVIDER=openrouter`.
  - Para Gemini, configure `GEMINI_API_KEY`, `GEMINI_API_URL` e `GEMINI_TRANSLATION_MODEL`.
  - Para OpenRouter, configure `OPENROUTER_API_KEY` (ou `API_KEY`), `OPENROUTER_API_URL` e
    `OPENROUTER_TRANSLATION_MODEL`. Este último deve ser o identificador de um modelo disponível
    na sua conta OpenRouter.
  - Ao descobrir uma palavra, uma única solicitação à IA selecionada retorna as traduções e
    as 10 frases de exemplo de cada tradução. O botão "Gerar mais" permanece como
    recuperação para traduções antigas ou incompletas, sem fazer uma solicitação por
    tradução durante a descoberta.

- Geração de áudios TTS:
  - API Key do Google Cloud, fica no arquivo .env (GOOGLE_CLOUD_API_KEY).
  - define('GOOGLE_CLOUD_API_KEY', envValue('GOOGLE_CLOUD_API_KEY', ''));
- ID das vozes:
  - Português: 'pt-BR-Chirp3-HD-Algieba'
  - Inglês: 'en-GB-Chirp3-HD-Algieba'
 
- Armazenamento de áudios e imagens:
- Os áudios e imagens devem ser convertidos para Base64 e salvos no banco de dados.

## Cadastro manual e importação de traduções

Na página **Words**, abra uma palavra para adicionar manualmente uma tradução, sua
classe gramatical e uma frase em inglês com sua correspondente em português. Se
já houver a mesma tradução para aquela palavra, a nova frase é anexada à tradução
existente.

Também é possível importar um CSV sem cabeçalho. Cada linha deve ter exatamente
quatro colunas, nesta ordem: `palavra`, `tradução`, `frase em inglês`, `frase em
português`. O arquivo pode usar vírgula ou ponto e vírgula como separador; valores
que contêm o separador devem ser colocados entre aspas. A primeira linha é sempre
tratada como dado, não como cabeçalho. A importação cria palavras e traduções que
ainda não existirem e anexa as frases às traduções já existentes para a mesma
palavra. Traduções importadas recebem inicialmente a classe **substantivo**,
pois o formato de quatro colunas não inclui a classe gramatical.
