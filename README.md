# Prescia

Framework LAMP full-stack de alto nível para aplicações PHP e instalações que atendem vários sites e domínios a partir de um único deploy.

> **Estado do runtime:** o projeto é mantido para PHP 8.3. O workflow de compatibilidade também executa verificações em PHP 8.4 e 8.5. A suíte local exige PHP, Composer e as extensões descritas neste documento.

[![PHP compatibility](https://img.shields.io/badge/PHP-8.3%20%7C%208.4%20%7C%208.5-10243b)](.github/workflows/php83.yml)
[![License](https://img.shields.io/badge/license-New%20BSD-ef743d)](prescia/LICENSE)

## O que é

O Prescia é um framework LAMP — Linux, Apache, MySQL e PHP — criado para entregar sites e aplicações com alto nível de automação. O projeto nasceu de uma necessidade operacional: atender vários domínios na mesma instalação sem duplicar código, mantendo uma separação entre núcleo, páginas, módulos, plugins, templates e configurações por domínio.

O framework é a quarta iteração de um trabalho iniciado por volta de 2006 e foi usado historicamente em centenas de sites. Sua arquitetura é procedural e modular, com um núcleo responsável pelo ciclo da requisição, cache, templates, autenticação, módulos, plugins e integração com banco de dados.

O Prescia não pretende reproduzir a convenção de um framework MVC moderno. Quem chega de Laravel, Symfony ou outro framework deve começar pela [Referência de Uso](docs/Usage_reference.md), que explica o fluxo de dados, o carregamento de conteúdo e o papel de `actions/`, `content/`, módulos e plugins.

## Para que serve

O Prescia é adequado para os seguintes cenários:

| Cenário | Como o Prescia ajuda |
|---|---|
| Vários sites em uma instalação | O mapa de domínios associa cada host a uma página e a uma configuração sem duplicar o núcleo. |
| Sites com CMS e administração | Plugins e módulos concentram conteúdo, permissões, árvores, callbacks e telas administrativas. |
| Aplicações PHP legadas em modernização | O núcleo mantém contratos históricos enquanto a migração para PHP 8.3, prepared statements e testes ocorre em lotes pequenos. |
| Projetos que precisam de um fluxo de requisição explícito | O ciclo entre front controller, página, módulo, action, content, template e cache é documentado e rastreável. |
| Operação com Docker ou Apache | O repositório fornece Dockerfile, Compose, templates de configuração e workflows de validação. |

O projeto não é um pacote PHP genérico para ser instalado apenas com `composer require`. O Composer fornece as ferramentas de desenvolvimento e teste; a aplicação é executada a partir da estrutura do repositório e de suas configurações de domínio.

## Requisitos

### Runtime da aplicação

- PHP 8.3.
- Extensões `mysqli`, `pdo_mysql` e `mbstring`.
- Apache com `mod_rewrite` habilitado; outro servidor web pode ser usado somente quando reproduzir a superfície pública esperada pelo projeto.
- MySQL 8.0 ou servidor compatível.
- Permissão de escrita somente nos diretórios de runtime, especialmente `_temp/`, logs, cache e backups.

### Desenvolvimento e validação

- Composer.
- Docker Engine e Docker Compose para o fluxo conteinerizado.
- Git para obter o repositório e acompanhar alterações.
- PHP 8.3 para executar `php -l`, PHPUnit, PHPStan e o script de migração localmente.

A matriz do GitHub Actions também verifica PHP 8.4 e 8.5. Isso não substitui a leitura de `composer.json`, do workflow e do ambiente de produção antes de trocar o runtime principal.

## Instalação rápida

### 1. Obter o código

```bash
git clone https://github.com/Prescia/Prescia.git
cd Prescia
```

### 2. Criar as configurações locais

Os arquivos `.original` são templates versionados. Copie-os apenas quando os arquivos de destino ainda não existirem:

```bash
cp config/domains.original config/domains
cp config/settings.php.original config/settings.php
```

Não substitua uma configuração existente sem criar um backup timestampado. Em produção, prefira variáveis de ambiente, secret mounts ou um mecanismo de configuração fora do Git.

### 3. Configurar o domínio e o ambiente

Edite `config/domains` para associar os domínios às páginas do projeto. Edite `config/settings.php` para definir, conforme o ambiente:

- `CONS_MASTERPASS` e `CONS_MASTERMAIL`.
- Banco principal ou sobrescritas `CONS_OVERRIDE_DB`, `CONS_OVERRIDE_DBUSER` e `CONS_OVERRIDE_DBPASS`.
- Domínios mestres em `CONS_MASTERDOMAINS`.
- Timezone, cache, modo econômico, logs e limites operacionais.

Nunca publique senhas, tokens, chaves SMTP, certificados privados ou strings de conexão completas no Git, em issues ou em relatórios.

### 4. Preparar os diretórios graváveis

Crie os diretórios exigidos pelo runtime e valide a instalação pelo contrato de `checkinstall()` documentado na [Referência de Código](docs/Code_reference.md#core). O processo web deve escrever somente nas áreas necessárias, como `_temp/_logs`, `_temp/_cache` e `_temp/_backups`.

Não use `chmod -R 777`. Mantenha código, configurações, documentação, testes e backups fora da superfície HTTP.

### 5. Apontar o servidor web

Configure o DocumentRoot para a superfície pública correta e habilite `mod_rewrite`, `headers` e `expires` quando aplicável. Depois, acesse o domínio configurado e valide a página inicial, uma rota inexistente, cache, logs, backup e conexão com o banco.

## Skill de configuração

O repositório contém a skill versionada [`skills/prescia-install-config/SKILL.md`](skills/prescia-install-config/SKILL.md). Ela é o guia operacional para instalação, configuração e validação em Docker ou Apache/PHP/MySQL.

Use a skill quando precisar:

1. Escolher entre Docker Compose, Apache/Nginx manual, desenvolvimento, staging e produção.
2. Levantar domínios, domínio canônico, e-mail, timezone, banco e permissões.
3. Criar backups antes de alterar configurações.
4. Configurar `domains` e `settings.php` sem substituir valores personalizados.
5. Proteger segredos e aplicar permissões mínimas.
6. Validar sintaxe, conectividade, roteamento, login administrativo e isolamento HTTP.

A skill exige que decisões materiais — domínio, credenciais, usuário do serviço, reinício de containers e mudanças fora do repositório — sejam confirmadas antes de serem aplicadas. Ela nunca deve ser usada para registrar senhas em arquivos versionados.

## Usando Docker

Docker Compose é o caminho recomendado para desenvolvimento e validação inicial.

### Subir a aplicação

```bash
docker compose config
docker compose up -d --build
docker compose ps
```

A aplicação fica disponível em [http://localhost:8080](http://localhost:8080). O Compose vincula a aplicação e o MySQL a `127.0.0.1` por padrão; não exponha essas portas publicamente sem revisar firewall, proxy, credenciais e TLS.

### Serviços e credenciais de desenvolvimento

| Serviço | Endereço dentro da rede Compose | Porta local | Função |
|---|---|---:|---|
| `web` | aplicação Apache/PHP | `8080` | Serve o Prescia. |
| `db` | `db:3306` | `3306` | MySQL 8.0 persistido no volume `db_data`. |

O Compose contém valores de desenvolvimento para o banco:

| Variável | Valor local do Compose |
|---|---|
| Banco | `prescia` |
| Usuário | `prescia_user` |
| Senha | `prescia_pass` |
| Senha root | `prescia_root` |

Esses valores **não são adequados para produção**. Troque-os antes de qualquer exposição externa e não os reutilize em ambientes reais.

Dentro do container web, o host do banco é `db`, não `localhost`. Quando necessário, configure as constantes de sobrescrita em `config/settings.php`:

```php
define("CONS_OVERRIDE_DB", "prescia");
define("CONS_OVERRIDE_DBUSER", "prescia_user");
define("CONS_OVERRIDE_DBPASS", "prescia_pass");
```

### Logs, shell e encerramento

```bash
# acompanhar logs
docker compose logs -f

# consultar somente o serviço web
docker compose logs -f web

# abrir um shell no container web
docker compose exec web bash

# parar os serviços
docker compose down

# parar e remover também os dados do MySQL (destrutivo)
docker compose down -v
```

Use `docker compose down -v` somente quando aceitar perder o volume local `db_data`.

### Considerações de produção

Para produção, use uma imagem revisada, segredos por ambiente, TLS terminado em proxy confiável, banco externo ou volume administrado, backups testados, permissões mínimas e `CONS_DEVELOPER` desabilitado. Confirme também que `config/`, `prescia/`, `tests/`, `tools/`, `docs/`, `.env`, logs e dumps não podem ser servidos pelo Apache.

O guia complementar está em [`DOCKER.md`](DOCKER.md).

## Como usar

O fluxo básico de uma requisição é:

```text
front controller → domínio/página → core → módulo/plugin → action ou content → template → cache/resposta
```

Uma página normalmente organiza código em:

| Caminho | Responsabilidade |
|---|---|
| `pages/<site>/config.php` | Configuração e plugins do site. |
| `pages/<site>/actions/` | Operações acionadas por requisição, formulário ou callback. |
| `pages/<site>/content/` | Conteúdo e composição de dados para renderização. |
| `prescia/` | Núcleo, componentes, plugins e bibliotecas compartilhadas. |
| `templates/` ou templates de plugin | Apresentação e tags interpretadas pelo Template Core. |
| `config/domains` | Associação entre domínio e site/página. |
| `_temp/` | Cache, logs, arquivos temporários e backups. |

Comece pela [Referência de Uso](docs/Usage_reference.md) para entender dataflow, plugins, templates, callbacks, metadata XML e estruturas em árvore. Consulte a [Referência de Código](docs/Code_reference.md) quando precisar localizar propriedades, métodos ou contratos do Core, CoreFull, Template Core e Module.

## Testes e qualidade

Instale as dependências e execute a suíte principal:

```bash
composer install --no-interaction --no-progress
composer validate --strict
composer test
composer analyse
```

Também é possível chamar as ferramentas diretamente:

```bash
vendor/bin/phpunit --configuration phpunit.xml.dist
vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress
```

O projeto mantém uma baseline do PHPStan para diagnósticos históricos. A baseline **não deve crescer automaticamente**. Um diagnóstico novo deve ser corrigido no código, justificado em documentação ou acompanhado por uma issue específica.

Para uma checagem de sintaxe isolada:

```bash
php -l caminho/do/arquivo.php
```

O workflow [`php83.yml`](.github/workflows/php83.yml) executa compatibilidade, testes, lint, PHPStan, auditoria Composer e build Docker. Um único job verde não é suficiente para declarar o SHA completamente validado; todos os check-runs obrigatórios devem estar concluídos com sucesso.

## Migração assistida para PHP 8.3

O script [`tools/migrate_php83.php`](tools/migrate_php83.php) faz uma varredura conservadora e, por padrão, executa somente um dry-run:

```bash
php tools/migrate_php83.php
```

Depois de revisar o relatório em `_temp/php83-migration-report.md`, aplique somente alterações mecânicas com backup timestampado:

```bash
php tools/migrate_php83.php --apply
```

O script não modifica automaticamente senhas, SQL, CSRF, sessão, upload ou autorização. Essas frentes exigem implementação específica, revisão e testes.

## Segurança operacional

O Prescia recebe correções de segurança em lotes pequenos. Os princípios obrigatórios são:

- Validar toda entrada externa.
- Usar prepared statements para valores SQL.
- Manter nomes de tabela e coluna derivados de metadados internos.
- Resolver e comparar caminhos canônicos antes de operações de filesystem.
- Restringir upload por estrutura, MIME, tamanho, conteúdo e destino.
- Não expor stack traces, configurações ou segredos.
- Não aumentar a baseline para silenciar regressões.
- Manter o testador `pages/presciatester` fora da superfície de produção.

Consulte o [Plano de Ação de Segurança](docs/PLANO_ACAO_SEGURANCA.md), o [backlog operacional](docs/PENDENCIAS_LOOP.md) e os relatórios em [`docs/`](docs/).

## Estrutura do projeto

```text
.
├── config/                 # templates e configurações globais
├── docs/                   # documentação técnica e relatórios
├── pages/                  # sites, templates, actions e content
├── prescia/                # núcleo, componentes, plugins e bibliotecas
├── public/                 # superfície pública quando usada pelo deploy
├── tests/                  # PHPUnit e regressões de segurança
├── tools/                  # migração e auxiliares de validação
├── Dockerfile
├── docker-compose.yml
├── composer.json
└── phpunit.xml.dist
```

## Documentações e templates

A documentação canônica fica em [`docs/`](docs/). Os guias técnicos detalhados estão em inglês; este README reúne o caminho de entrada em português.

| Documento ou template | Para que serve |
|---|---|
| [`docs/Usage_reference.md`](docs/Usage_reference.md) | Dataflow, cache, plugins, templates, callbacks, metadata e funções de uso. |
| [`docs/Code_reference.md`](docs/Code_reference.md) | API do Template Core, Core, CoreFull e Module. |
| [`docs/faq.md`](docs/faq.md) | CMS, módulos, plugins, 404, manutenção e EconomicMode. |
| [`docs/bots.md`](docs/bots.md) | Blocklist de user-agents e proteção contra bots. |
| [`docs/PLANO_ACAO_SEGURANCA.md`](docs/PLANO_ACAO_SEGURANCA.md) | Plano de correções de autenticação, sessão, upload, Docker e PHP 8.3. |
| [`docs/PLANO_PHPSTAN_NIVEL_1.md`](docs/PLANO_PHPSTAN_NIVEL_1.md) | Diagnóstico e sequência inicial de remediação estática. |
| [`docs/PLANO_PHPSTAN_PROXIMO_LOTE.md`](docs/PLANO_PHPSTAN_PROXIMO_LOTE.md) | Próximos diagnósticos PHPStan e seus critérios. |
| [`docs/PENDENCIAS_LOOP.md`](docs/PENDENCIAS_LOOP.md) | Backlog operacional, critérios de parada e ciclo automático por lote. |
| [`config/domains.original`](config/domains.original) | Template do mapa de domínios e sites. |
| [`config/settings.php.original`](config/settings.php.original) | Template de configurações globais do runtime. |
| [`pages/_newProjectTemplate/`](pages/_newProjectTemplate/) | Estrutura de referência para criar uma nova página/site. |
| [`skills/prescia-install-config/SKILL.md`](skills/prescia-install-config/SKILL.md) | Procedimento operacional de instalação, configuração, backups e validação. |
| [`DOCKER.md`](DOCKER.md) | Fluxo Docker complementar e troubleshooting. |

Os arquivos de texto legados em `docs/*_refference.txt`, `faq.txt` e `bots2015.txt` permanecem apenas para referência histórica. Prefira as versões Markdown.

## Solução rápida de problemas

| Sintoma | Primeira verificação |
|---|---|
| CMS ou URL virtual retorna 404 | Consulte [FAQ — CMS](docs/faq.md#cms) e valide o site associado em `config/domains`. |
| Aplicação exibe manutenção ou 503 | Consulte [FAQ — Maintenance](docs/faq.md#maintenance-pages), logs e disponibilidade do MySQL. |
| Hook de plugin não dispara | Consulte [FAQ — Modules & Plugins](docs/faq.md#modules--plugins) e confirme o carregamento em `config.php`. |
| Crawler recebe 403 | Consulte [Bot Blocklist](docs/bots.md) e o comportamento de `CONS_BOTPROTECT`. |
| Container web não conecta ao banco | Use o host `db`, confira `docker compose ps` e valide as constantes de banco. |
| `_temp` não pode ser gravado | Confirme ownership, grupo e permissões mínimas do processo web; não use `777` como correção permanente. |
| PHPStan aponta diagnóstico novo | Reproduza com `composer analyse`, corrija a causa e não amplie a baseline automaticamente. |

## Contribuição e ciclo de manutenção

Antes de abrir uma alteração, leia as instruções do repositório em [`.github/copilot-instructions.md`](.github/copilot-instructions.md). Separe mudanças por lote: não misture migração PHPStan, SQL, autenticação, upload e refatoração funcional no mesmo commit.

O ciclo recomendado é inventariar o contexto, confirmar call sites, aplicar a menor correção, adicionar regressão, executar validações focalizadas, rodar a suíte global, documentar o resultado e publicar uma branch. Só considere um lote concluído quando a PR estiver mergeada e os checks do SHA mergeado estiverem concluídos com sucesso.

## Licença

O Prescia é distribuído sob a [licença New BSD / BSD-new](prescia/LICENSE).

## Novas seções recomendadas

As seções abaixo são candidatas para uma próxima evolução da documentação, porque dependem de decisões de produto ou de evidência operacional adicional:

1. **Tutorial do primeiro site:** criar uma página mínima, configurar um domínio local, renderizar um content e publicar um template.
2. **Arquitetura visual:** incluir um diagrama do ciclo da requisição e das relações entre Core, Module, Plugin, Action, Content e Template.
3. **Configuração por ambiente:** documentar um exemplo seguro de desenvolvimento, staging e produção sem valores secretos versionados.
4. **Matriz de compatibilidade:** registrar versões suportadas de PHP, MySQL, Apache, extensões e Docker em cada release.
5. **Runbook de deploy e rollback:** explicar backup, migração, smoke tests, rollback e verificação pós-deploy.
6. **Guia de criação de plugin:** descrever hooks, ciclo de vida, convenções de arquivos, permissões e testes.
7. **Guia de observabilidade:** centralizar logs, alertas, métricas SSRF, slow queries e sinais de erro.
8. **Política de breaking changes:** registrar contratos públicos, deprecações, janela de suporte e migrações necessárias.

## Referências

[1]: https://github.com/Prescia/Prescia "Repositório oficial do Prescia"
[2]: https://www.php.net/supported-versions.php "Versões de PHP suportadas"
[3]: https://docs.docker.com/compose/ "Documentação oficial do Docker Compose"
[4]: https://getcomposer.org/doc/ "Documentação oficial do Composer"
[5]: https://keepachangelog.com/en/1.1.0/ "Keep a Changelog"
[6]: https://semver.org/ "Semantic Versioning"
