# Análise completa de segurança — Prescia

**Projeto:** `leohmoraes/Prescia`
**Data:** 13 de setembro de 2026
**Commit analisado:** `7e4f273` (`security: prepare cron maintenance queries`)
**Escopo:** código PHP de produção, plugins, endpoints administrativos, bibliotecas legadas, configuração, workflows, dependências bloqueadas, testes e arquivos rastreados.

> **Atualização de estado — 2026-09-14:** o relatório permanece uma fotografia do commit `7e4f273`. A constraint atual do `composer.json` é `>=8.3 <8.6`; a referência histórica a `<8.4` foi corrigida abaixo.

## Conclusão executiva

A análise não encontrou um novo caso confirmado de injeção SQL nos fluxos de produção examinados. Os principais sinks SQL do lote anterior estão usando `queryPrepared()` ou `fetchPrepared()`, e o commit analisado está alinhado com `origin/master`.

Foram identificados **três pontos que exigem tratamento ou confirmação adicional**. O primeiro é a presença de credenciais padrão `root`/`root` em uma configuração rastreada do ambiente `presciatester`. O segundo é a ausência de limites e validação numérica explícita no endpoint de teste de labels, que pode permitir consumo excessivo de CPU ou memória quando o endpoint estiver acessível. O terceiro é a composição de caminho de log a partir de `$_REQUEST['code']` no visualizador administrativo de logs; embora a rota exija nível de acesso máximo e domínio mestre, a entrada deve ser allowlisted para eliminar risco de traversal e leitura de logs fora do tenant.

Esses pontos são **achados de revisão**, não foram corrigidos neste ciclo. A análise não deve ser interpretada como certificação de segurança.

## Estado e evidências

O checkout foi sincronizado por fast-forward/reset seguro com `origin/master`. O estado final está limpo em `7e4f273`. O repositório contém workflows de PHP 8.3, PHPStan, CodeQL, revisão de dependências e auditoria semanal de segurança.

A configuração Composer declara PHP `>=8.3 <8.6`, PHPUnit 10.5 e PHPStan 2.2. O lockfile está versionado. Não há manifesto ou lockfile npm no repositório. As ferramentas locais `php`, `composer`, `phpstan`, `phpunit`, Semgrep, Trivy e Gitleaks não estavam instaladas no sandbox daquela auditoria; por isso, a validação executada localmente foi baseada em inspeção estática, buscas determinísticas, revisão dos workflows e documentação já versionada. Os workflows remotos continuam sendo a fonte de validação executável para PHP 8.3, PHPStan, Composer audit, CodeQL e Trivy.

## Matriz de achados

| ID | Severidade preliminar | Local | Situação | Próxima ação |
|---|---|---|---|---|
| SEC-001 | Alta se exposto fora de teste; média no escopo atual | `pages/presciatester/_config/config.php:27-30` | Credenciais padrão `root`/`root` rastreadas em configuração de testador | Remover defaults do arquivo rastreado e injetar credenciais por ambiente; confirmar que o diretório não é publicado em produção |
| SEC-002 | Média | `prescia/plugins/bi_labels/payload/content/label_test.php:4-12,22-33` | Dimensões de layout vêm diretamente de request e controlam aritmética e loops | Validar inteiros, limites máximos, valores positivos e orçamento total de células antes de renderizar |
| SEC-003 | Média, dependente do deployment | `prescia/plugins/bi_adm/payload/content/master_logs.php:5-11` | `code` é usado para compor caminho de log sem allowlist explícita | Aceitar somente códigos existentes em uma lista interna ou validar um identificador de tenant; resolver caminho real e exigir que permaneça sob `CONS_PATH_LOGS` |
| SEC-004 | Baixa/Média, pendente de confirmação | `pages/_js/ckfinder/config.php` e cópias de configuração rastreadas | Arquivos de configuração legados estão no repositório e precisam de revisão de exposição e defaults | Confirmar conteúdo, separar template de configuração de runtime e verificar que nenhum segredo real está versionado |
| SEC-005 | Informativa | `prescia/lib/loadURL.php` | SSRF recebeu controles fortes de esquema, DNS, IP público, portas, redirects e limite de resposta | Manter testes de rebinding e revisar também `fget()`/FTP, que tem contrato diferente de `loadURL()` |
| SEC-006 | Informativa | `pages/presciatester/actions/reset.php` e `nextstep.php` | Há SQL destrutivo e queries estáticas em um testador legado | Manter fora de produção por controle de deployment; adicionar guard explícito de ambiente se esse diretório puder ser publicado |

## Análise detalhada

### SEC-001 — credenciais padrão rastreadas

O arquivo `pages/presciatester/_config/config.php` define `CONS_DB_USER` como `root` e `CONS_DB_PASS` como `root` no ramo local. O arquivo parece pertencer ao ambiente de teste, e o código usa `CONS_ONSERVER` para separar o ramo online do ramo local. Ainda assim, credenciais previsíveis em um arquivo rastreado criam risco caso o diretório de teste seja publicado, caso o banco seja acessível pela rede ou caso o arquivo seja reutilizado como configuração de deployment.

A remediação recomendada é substituir os valores por variáveis de ambiente ou por um mecanismo de configuração fora do controle de versão. O arquivo versionado deve conter apenas placeholders sem valor operacional. Também é necessário confirmar que `pages/presciatester` não está exposto pelo servidor de produção.

### SEC-002 — limites ausentes no teste de labels

`label_test.php` copia `cols`, `rows`, margens, largura, altura e fonte de `$_REQUEST` sem conversão ou limites explícitos. Em seguida, `rows` e `cols` controlam loops aninhados e a quantidade de conteúdo gerado. Valores muito grandes podem causar consumo excessivo de CPU, memória ou tamanho de resposta. Valores negativos e valores não numéricos também produzem aritmética imprevisível.

A correção deve validar cada parâmetro como inteiro, aplicar limites superiores coerentes com o produto e rejeitar combinações cujo produto `rows * cols` exceda um orçamento seguro. A regressão deve cobrir valores ausentes, negativos, não numéricos e dimensões máximas.

### SEC-003 — caminho de logs dependente de request

`master_logs.php` faz uma verificação de autorização de nível 99 em domínio mestre, mas depois concatena `$_REQUEST['code']` diretamente a `CONS_PATH_LOGS`. O valor não é usado em SQL, porém controla acesso a um caminho de filesystem. O risco depende do comportamento de `cReadFile`, do canonical root e da configuração do servidor; uma entrada com separadores de caminho ou traversal pode alcançar arquivos de outro contexto se não houver validação anterior.

A correção recomendada é não aceitar um caminho arbitrário. O endpoint deve aceitar apenas um código de tenant resolvido a partir da lista de domínios/configuração carregada pelo sistema. Como defesa adicional, o caminho final deve ser normalizado com `realpath()` e comparado com o diretório-base permitido.

### SEC-004 — arquivos de configuração rastreados

Há arquivos chamados `config.php` em diretórios de páginas e no CKFinder. O inventário de nomes não prova que contenham segredos, mas esses arquivos são pontos de risco porque configurações PHP frequentemente carregam credenciais ou habilitam funcionalidades administrativas. O próximo passo é revisar o conteúdo de todos os arquivos rastreados e separar templates de configuração de configurações efetivas.

### SEC-005 — cliente HTTP remoto

`prescia/lib/loadURL.php` valida esquema, host, portas, DNS, IPs públicos, rebinding, tamanho de resposta e ausência de redirects. A conexão usa o IP previamente validado e preserva o nome do host para TLS/SNI. Esse fluxo aparenta estar bem endurecido e deve permanecer coberto por testes de DNS rebinding, IPv4/IPv6, portas, redirects e limites.

A função separada `fget()` usa FTP e não herda automaticamente as mesmas garantias. Ela deve ser tratada em revisão dedicada caso receba URL ou host derivado de entrada externa.

### SEC-006 — SQL destrutivo no testador

`pages/presciatester/actions/reset.php` executa `TRUNCATE` e `DROP TABLE`, enquanto `nextstep.php` executa consultas estáticas para validar o framework. Esses usos não foram classificados como sinks de produção porque pertencem ao testador. Mesmo assim, são operações destrutivas se o endpoint for publicado ou se o ambiente compartilhar credenciais com dados reais.

A recomendação é impedir o carregamento desse diretório em produção, exigir uma constante de ambiente de teste e verificar explicitamente o nome do banco antes de permitir reset.

## Controles que apresentaram evidência positiva

O repositório possui `composer.lock`, auditoria semanal de dependências, CodeQL, Dependency Review, PHPStan e testes de compatibilidade PHP 8.3. O workflow de segurança usa `persist-credentials: false`, permissões de conteúdo somente leitura e artefato de evidência com retenção definida.

Os fluxos SQL revisados recentemente usam parâmetros preparados. O driver oferece `queryPrepared()` e `fetchPrepared()`. Os nomes dinâmicos de tabelas que permanecem necessários em manutenção são tratados por `quoteIdentifier()` antes de compor SQL estrutural.

`loadURL()` implementa uma decisão fail-closed para hosts inválidos, DNS sem resolução pública, endereços privados, portas não permitidas, redirecionamentos implícitos e respostas acima do limite.

## Limitações da análise

Não foram executados scanners locais de Semgrep, Trivy ou Gitleaks porque não estão instalados. Não foi possível executar PHPUnit, PHPStan ou `php -l` localmente porque PHP e dependências Composer não estão disponíveis no sandbox. Os workflows do GitHub devem ser consultados no SHA correspondente antes de declarar qualquer correção futura concluída.

A análise não incluiu exploração ativa, teste de autenticação em ambiente implantado, revisão de regras de firewall, inspeção de segredos no histórico completo do Git, varredura de imagem Docker com Trivy local ou teste dinâmico de endpoints. Portanto, os achados de caminho e exposição precisam de confirmação no deployment.

## Plano de remediação recomendado

O primeiro lote deve remover credenciais padrão rastreadas e adicionar uma regressão que rejeite defaults operacionais. O segundo deve endurecer `label_test.php` com validação numérica, limites e orçamento de renderização. O terceiro deve allowlistar o código de tenant no visualizador de logs e adicionar testes de traversal. Em paralelo, deve-se confirmar que o testador e as configurações legadas não são publicados em produção.

Após cada lote, é necessário executar lint, PHPUnit focalizado, PHPStan, auditoria SQL, CodeQL/CI e a varredura semanal de dependências. Nenhum achado deve ser marcado como corrigido antes de os checks do SHA publicado terminarem com sucesso.

## Referências

[1]: https://github.com/leohmoraes/Prescia/blob/master/prescia/lib/loadURL.php "Prescia loadURL SSRF controls"

[2]: https://github.com/leohmoraes/Prescia/blob/master/prescia/plugins/bi_labels/payload/content/label_test.php "Prescia label test endpoint"

[3]: https://github.com/leohmoraes/Prescia/blob/master/prescia/plugins/bi_adm/payload/content/master_logs.php "Prescia administrative master logs endpoint"

[4]: https://github.com/leohmoraes/Prescia/blob/master/pages/presciatester/_config/config.php "Prescia tester configuration"

[5]: https://github.com/leohmoraes/Prescia/tree/master/.github/workflows "Prescia security workflows"

[6]: https://owasp.org/www-project-top-ten/ "OWASP Top 10"
