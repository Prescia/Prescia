# Relatório consolidado de progresso do PHPStan

**Projeto:** Prescia  
**Escopo:** compatibilidade com PHP 8.3 e redução incremental da dívida técnica identificada pelo PHPStan  
**Versão analisada:** PHPStan 2.2.13, nível 3
**Branch:** `master`  
**Commit de código do lote atual:** `4fa70ad3`
**Data de consolidação:** 8 de setembro de 2026
**Issue principal:** [#43 — Atualizar o PHPStan e elevar gradualmente o nível de análise][1]

## Atualização executável — 2026-09-14

Esta seção é a referência vigente para o estado do repositório. Após a análise exploratória de nível 3 retornar zero diagnósticos, o nível 3 foi promovido na configuração oficial. A análise foi executada localmente sobre a árvore correspondente ao `master`, usando PHP 8.3.6, Composer com o lockfile versionado e `phpstan.neon.dist` atualizado.

| Verificação | Resultado | Evidência |
|---|---|---|
| PHPStan global | **Aprovado** | PHPStan 2.2.13, nível 3, `vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --error-format=json`, `0 errors`, `0 file_errors` |
| PHPUnit completo | **Aprovado com observações** | 144 testes, 3.093 asserções, 2 deprecações e 2 testes pulados |
| Lint PHP | **Aprovado** | Todos os arquivos PHP rastreados passaram em `php -l`; 0 erros de sintaxe |
| Baseline | **Sem expansão nesta execução** | PHPStan terminou sem diagnósticos e `phpstan-baseline.neon` não foi alterado |
| Ambiente | **Reproduzido localmente** | PHP 8.3.6; dependências instaladas a partir de `composer.lock` |

O resultado atual substitui as referências anteriores que descreviam centenas de diagnósticos ou contagens menores de testes. As seções posteriores que mencionam rankings, commits e números antigos continuam preservadas como histórico de evolução e não devem ser usadas como fotografia do estado atual.

## Lote 2 — Sublote 2.1 — `prescia/core.php`

O Sublote 2.1 corrigiu dois problemas no núcleo: removeu o bloco inalcançável ao final de `addLink()` e transformou o teste de caminho de `_meta.xml` em uma verificação real com `is_file()`. A regressão foi coberta por `tests/CoreRegressionTest.php`, que protege a validação de arquivos ausentes e a leitura condicional de metadados. A baseline não foi expandida.

## Lote 1 — `bi_stats` — atualização da PR #227

O lote corrigiu a comparação de `strpos()` no filtro de IP, ajustou os loops de renderização para iterar sobre as coleções efetivamente acumuladas após o descarte de linhas inválidas e documentou os parâmetros de saída de `queryPrepared()` nos drivers DBO. `tests/BiStatsRegressionTest.php` cobre esses contratos, sem expansão da baseline.

## Lote 3 — front controllers

O escopo inicial dos front controllers (`index.php` e `prescia/index.php`) continha 23 diagnósticos nível 4, causados pela propagação de valores literais dos stubs de configuração para guards que são variáveis em runtime. A configuração PHPStan agora declara `CONS_AFF_ERRORHANDLER`, `CONS_AFF_ERRORHANDLER_NOWARNING`, `CONS_CACHE`, `CONS_DEVELOPER`, `CONS_ECONOMICMODE` e `CONS_HONEYPOT` em `dynamicConstantNames`. Isso mantém os valores determinísticos do bootstrap de análise, mas impede que o PHPStan elimine caminhos válidos de error handling, cache, developer mode e honeypot. O resultado focalizado do Lote 3 é zero diagnósticos, sem alteração do código de runtime e sem expansão da baseline.

## Resumo executivo

O projeto mantém uma análise estática incremental com **PHPStan 2.x no nível 3**, baseline sem supressões novas e contratos explícitos para os principais contextos dinâmicos do framework. Na execução de 2026-09-14, a análise global, o PHPUnit e o lint foram executados localmente em PHP 8.3.6 com resultado aprovado, com dois testes pulados já registrados pela configuração.

O progresso mais significativo ocorreu na separação entre o núcleo `CPrescia`, módulos concretos e payloads incluídos dinamicamente. Essa separação eliminou os diagnósticos de contexto em vários fluxos de administração, autenticação, fórum, cron e labels. Também foram corrigidos fluxos de variáveis indefinidas em listagens, ações de teste, cron e callbacks de módulos.

As contagens de centenas de diagnósticos e o limite histórico de 1.000 registros pertencem a execuções anteriores. Nesta execução de nível 3, o formatter JSON oficial retornou zero erros; a validação futura deve repetir a mesma configuração e registrar o SHA analisado antes de comparar resultados.

## Estado atual

| Área | Estado | Evidência |
|---|---|---|
| PHP 8.3 | **Aprovada na execução atual** | Lint global sem erros e `composer test` aprovado em PHP 8.3.6 |
| PHPStan focalizado nos arquivos corrigidos | **Aprovado** | Execução global oficial retornou `0 errors` e `0 file_errors` |
| PHPStan completo do repositório | **Aprovado** | PHPStan 2.2.13, nível 3, `phpstan.neon.dist`, JSON sem erros no commit analisado |
| PHPUnit | **Aprovado com observações** | `144 testes`, `3.093 asserções`, 2 deprecações e 2 skips |
| Baseline | **Sem novos ocultamentos** | `phpstan-baseline.neon` permanece sem entradas de `ignoreErrors` adicionadas durante os ciclos recentes |
| Branch e working tree | **Consolidado** | Árvore limpa; o relatório foi atualizado em branch documental para publicação no `master` |

## Linha do tempo das correções

A execução foi organizada em lotes pequenos, preservando a capacidade de atribuir cada redução a uma causa específica.

| Commit | Resultado principal |
|---|---|
| `3443111` | Inclusão dos stubs de símbolos legados restantes |
| `c0e924f` | Alinhamento dos tipos de propriedades de plugins |
| `9dfbd97` | Alinhamento de assinaturas e chamadas de métodos |
| `9a3ce69` | Inclusão de contratos genéricos para arrays e retornos procedurais |
| `4c8846b` | Formalização da revisão da baseline como Lote F |
| `4b4e3e0` | Definição da estratégia operacional da Fase F |
| `e43e1b3` | Inicialização de resultados de consultas de autenticação |
| `570cd43` | Correção de visibilidade de propriedades de conexão do driver |
| `aac6fc9` | Especialização dos contratos de payloads para módulos concretos |
| `fa6f0b6` | Registro do início da execução F2 e dos contratos de propriedades |
| `9badeff` | Contratos PHPDoc nos payloads principais de `bi_adm` |
| `682b21e` | Correção do fluxo de variáveis em `bi_adm/payload/content/list.php` |
| `8b1aad5` | Contrato `CPrescia` e proteção de `unset()` em `presciatester` |
| `7819e27` | Contrato do cron, correção de `$core` e chamada de erro inexistente |
| `9de1ff5` | Contratos e inicialização de `$ok` em `bi_bb/bbaction.php` |
| `ab55fef` | Substituição da função global `getuseravatar()` por closure local |
| `d21cfb8` | Contratos e correção de fluxo em `bi_labels/labels_print.php` |

Todos os commits recentes foram publicados em `master` e vinculados à Issue #43 por meio de `Refs #43`.

## Correções validadas por arquivo

As contagens abaixo distinguem o diagnóstico observado antes da correção, quando disponível, da validação focalizada posterior. O workflow histórico possuía diagnósticos duplicados por contexto e, em alguns casos, refletia um commit anterior ao último contrato aplicado; por isso, a coluna de validação local é a evidência principal de conclusão do arquivo.

| Arquivo ou fluxo | Problema tratado | Resultado focalizado |
|---|---|---|
| `prescia/plugins/bi_adm/payload/content/list.php` | `$compare`, `$name`, `$lastThumb`, `$numThumbs`, `$possibleField` e `$isLinker` | **0 erros** |
| `prescia/plugins/bi_adm/payload/content/edit.php` | Contexto de `$core` e `$this`; fluxo de `$itemList` em validação anterior | **0 erros** na validação focalizada |
| `prescia/plugins/bi_adm/payload/content/default.php` | Contratos de contexto; restam propriedades privadas/protegidas a tratar em lote distinto | Diagnósticos de propriedade, não de contexto |
| `pages/presciatester/actions/nextstep.php` | Contrato de `CPrescia` e quatro `unset.offset` | **0 erros** |
| `prescia/lazyload/cron.php` | `$forceCron`, `$this`, referência incorreta a `$core` e `$this->raise()` | **0 erros** |
| `prescia/plugins/bi_bb/payload/actions/bbaction.php` | Contratos de `CPrescia`/`mod_bi_bb` e `$ok` | **0 erros** |
| `prescia/plugins/bi_bb/payload/content/thread.php` | Contratos e função global `getuseravatar()` | **0 erros**; callback convertido em closure |
| `prescia/plugins/bi_labels/payload/content/labels_print.php` | Contratos, `fastClose()` no objeto correto e fluxo de `$lData` | **0 erros** |

Nos arquivos tratados, as validações locais foram executadas com PHP 8.3.6, PHPStan 2.2.13 e `php -l`. Os artefatos `vendor/` e `composer.lock` foram removidos após cada validação quando gerados somente para uso local.

## Evolução arquitetural

### Contratos de contexto

O framework usa payloads procedurais incluídos no escopo de controladores e módulos. O PHPStan não consegue inferir automaticamente todos esses contratos. O padrão adotado agora documenta explicitamente o contexto real:

```php
/** @var CPrescia $core Runtime payload context injected by the framework. */
/** @var mod_bi_bb $this Runtime module context injected by the framework. */
```

O contrato só é aplicado depois de verificar o carregador do arquivo. Propriedades pertencentes ao núcleo são acessadas por `$core` ou por `$this->parent`; propriedades pertencentes ao plugin permanecem em `$this` com o tipo concreto do módulo.

### Fluxos de variáveis

As correções de variáveis seguem três regras. Primeiro, a inicialização ocorre no menor escopo comum ao consumidor. Segundo, os valores `false`, `null`, zero e arrays vazios continuam semanticamente distintos. Terceiro, referências que apontavam para variáveis fora do escopo são corrigidas para o campo ou objeto efetivamente usado, em vez de receber uma inicialização artificial.

### Callbacks

A função global `getuseravatar()` foi convertida em uma closure local no payload `thread.php`. O mecanismo de `CKTemplate::fullpage()` aceita callbacks invocáveis, portanto a alteração remove o risco de colisão e redeclaração sem mudar o contrato de execução por registro.

### Símbolos e baseline

Stubs e bootstrap foram utilizados somente para símbolos cuja origem dinâmica foi confirmada. A baseline não foi usada para encobrir regressões. Diagnósticos de arquivos ausentes, classes dinâmicas, métodos inexistentes e constantes de configuração continuam separados das correções de variáveis e contratos.

## Diagnósticos ainda pendentes

A execução completa do PHPStan ainda apresenta falhas em outros componentes. O ranking mais recente disponível antes dos últimos commits apontava os seguintes grupos prioritários:

| Prioridade | Arquivo ou grupo | Diagnósticos observados | Classificação inicial |
|---:|---|---:|---|
| 1 | `prescia/lazyload/friendlyurl.php` | 32 | Variáveis e contexto de payload |
| 2 | `prescia/plugins/bi_bb/payload/content/index.php` | 31 | Contratos de `$core` e `$this` |
| 3 | `prescia/lazyload/fastclose.php` | 31 | Contexto do núcleo e fluxo de encerramento |
| 4 | `prescia/lazyload/script.php` | 24 | Contexto procedural e variáveis locais |
| 5 | `prescia/plugins/bi_adm/module.php` | 22 | Variáveis de módulo e contratos de propriedades |
| 6 | `prescia/plugins/bi_stats/payload/content/stats_ref.php` | 19 | Contratos do núcleo e dados de relatório |
| 7 | `prescia/lazyload/udm.php` | 19 | Variáveis de roteamento e contexto |
| 8 | `prescia/plugins/bi_stats/payload/content/stats_pathajax.php` | 18 | Contexto do núcleo em AJAX |
| 9 | `prescia/plugins/bi_bb/payload/content/profile.php` | 18 | Contratos de núcleo e módulo |
| 10 | `prescia/plugins/bi_bb/payload/actions/default.php` | 17 | Contratos de ação e fluxo local |

O ranking acima é uma fotografia do relatório da execução anterior ao commit `d21cfb8`. Ele deve ser recalculado após a conclusão dos workflows atuais antes de iniciar o próximo patch, pois as correções recentes removem diagnósticos de outros arquivos por efeito de contratos compartilhados.

Também permanecem categorias estruturais que não devem ser misturadas ao lote de variáveis:

| Categoria | Tratamento previsto |
|---|---|
| `method.notFound` | Fase F3; confirmar a classe concreta e corrigir chamadas inválidas |
| `require.fileNotFound` e `includeOnce.fileNotFound` | Fase F5; corrigir caminhos ou excluir bibliotecas legadas não pertencentes ao framework |
| `class.notFound` | Fase F4; revisar `CDBO_0`, `CDBO` e carregamento dinâmico |
| `function.notFound` | Fase F4; confirmar origem e assinatura antes de criar stubs |
| `constant.notFound` | Fase F4; distinguir configuração externa de constante realmente ausente |
| `property.private` e `property.protected` | Fase F2; preferir API ou contexto correto antes de ampliar visibilidade |
| `function.inner` | Revisar funções locais declaradas em payloads e migrar para closures quando apropriado |
| `regexp.pattern` | Fase F5; corrigir padrões executáveis, sem supressão |

## Metas e critérios de aceite para a próxima etapa

A próxima etapa deve começar com um relatório novo do CI associado ao commit `5f61356` ou ao commit subsequente que concluir a validação atual. O relatório deve separar os diagnósticos por identificador, arquivo e linha, e não deve tratar o limite de 1.000 registros como contagem total.

O próximo ciclo deve priorizar os payloads de `bi_bb` e os carregadores procedurais que ainda possuem contratos ausentes. Cada alteração deverá ser validada localmente com PHPStan focalizado, `php -l` e `git diff --check`. Após cada grupo coerente, deverão ser executados os workflows de análise estática e compatibilidade PHP 8.3.

A elevação para o nível 2 do PHPStan não deve ocorrer enquanto a análise de nível 1 continuar falhando por diagnósticos estruturais. O nível deve ser elevado somente depois de a baseline permanecer vazia, os fluxos dinâmicos principais possuírem contratos e os erros remanescentes estarem classificados em lotes independentes.

## Atualização da Fase F3

O primeiro ciclo da Fase F3 tratou três diagnósticos `method.notFound` no payload `prescia/plugins/bi_stats/payload/content/module.php`. O payload tentava chamar `loadAllModules()` e `loaded()` no objeto `mod_bi_stats`, embora esses métodos pertençam ao núcleo `CPrescia`.

| Diagnóstico | Correção aplicada | Validação |
|---|---|---|
| `mod_bi_stats::loadAllModules()` | Substituído por `$core->loadAllmodules()` | PHPStan focalizado sem erros |
| `mod_bi_stats::loaded('STATSDAILY')` | Substituído por `$core->loaded('STATSDAILY')` | PHPStan focalizado sem erros |
| `mod_bi_stats::loaded($selmod)` | Substituído por `$core->loaded($selmod)` | PHPStan focalizado sem erros |

O contrato explícito de `$core` foi adicionado ao payload. A validação com PHP 8.3 não encontrou erros de sintaxe. O commit `5f61356` foi publicado na branch `master` e vinculado à Issue #43. O próximo alvo da Fase F3 é o inventário de métodos ausentes em `bi_adm/module.php` e nos payloads restantes, mantendo funções globais ausentes e classes dinâmicas em lotes separados.

## Conclusão

O projeto possui agora uma base operacional adequada para continuar a migração para PHP 8.3 sem depender de supressões globais. Os contratos de contexto e os fluxos de variáveis corrigidos já foram validados isoladamente em oito arquivos prioritários, e a compatibilidade PHP 8.3 continua passando nos workflows recentes.

O objetivo imediato não é declarar o PHPStan global como verde, pois ainda existem diagnósticos em arquivos não tratados. O objetivo alcançado foi reduzir as causas estruturais mais repetitivas, preservar a baseline sem novos ocultamentos e estabelecer um processo reproduzível de correção por fluxo.

## Referências

[1]: https://github.com/leohmoraes/Prescia/issues/43 "Issue #43 — Atualizar o PHPStan e elevar gradualmente o nível de análise"
[2]: https://github.com/leohmoraes/Prescia/actions/runs/34006264571 "PHP static analysis — execução 34006264571"
[3]: https://github.com/leohmoraes/Prescia/actions/runs/34006264562 "PHP 8.3 compatibility — execução 34006264562"
[4]: https://github.com/leohmoraes/Prescia/actions/runs/34006130078 "PHP static analysis — execução 34006130078"
[5]: https://github.com/leohmoraes/Prescia/blob/master/docs/PLANO_PHPSTAN_PROXIMO_LOTE.md "Plano do próximo lote de redução dos erros do PHPStan"
[6]: https://github.com/leohmoraes/Prescia/blob/master/phpstan.neon.dist "Configuração do PHPStan"
[7]: https://github.com/leohmoraes/Prescia/commits/master "Histórico de commits do Prescia"
[8]: https://github.com/leohmoraes/Prescia "Repositório Prescia"

*Autor: Manus AI* 
*Documento consolidado a partir do histórico do repositório, do plano PHPStan e das execuções de CI disponíveis na data de consolidação.*


## Verificação completa após o lote CDBO_mysqli

Foi executada uma análise completa do repositório no commit `0237763`, com PHPStan 2.2.13 no nível 1, cache limpo e a configuração oficial de `phpstan.neon.dist`. A execução local encontrou **620 diagnósticos**, contra **716** na execução completa anterior `34040527721`, uma redução de **96 diagnósticos**.

| Categoria | Diagnósticos atuais |
|---|---:|
| `variable.undefined` | 595 |
| `require.fileNotFound` | 4 |
| `class.nameCase` | 4 |
| `function.notFound` | 3 |
| `function.inner` | 3 |
| `constant.notFound` | 3 |
| `includeOnce.fileNotFound` | 2 |
| `array.duplicateKey` | 2 |
| `unset.offset` | 1 |
| `missingType.iterableValue` | 1 |
| `isset.variable` | 1 |
| `constructor.unusedParameter` | 1 |
| **Total** | **620** |

A categoria `property.notFound` não aparece mais no relatório completo após a descoberta dos drivers `CDBO` e `CDBO_mysqli`. A análise completa ainda falha no nível 1, principalmente pelas 595 variáveis indefinidas e por símbolos/caminhos legados. A baseline não foi ampliada. O log integral está disponível localmente em `/tmp/phpstan-full-0237763.log`; o artefato temporário não é versionado.


## Atualização de 6 de setembro de 2026 — lote bi_bb

O commit `a1564d7` continuou a remediação de `variable.undefined` no plugin `bi_bb` e foi publicado na branch `master`, com referência à Issue [#44 — Reduzir diagnósticos restantes do PHPStan][9].

### `prescia/plugins/bi_bb/payload/content/forum.php`

O arquivo foi confirmado como payload incluído por `mod_bi_bb::onRender()`. Foram documentados os contratos de contexto `CPrescia $core` e `mod_bi_bb $this` com PHPDoc. Também foi inicializado `$sql` antes do `switch` de `operationmode`, mantendo os três modos válidos (`bb`, `blog` e `articles`) e eliminando o fluxo possivelmente não atribuído.

A validação focalizada terminou com **0 erros no PHPStan 2.2.13** e o arquivo passou no `php -l` do PHP 8.3. O resultado foi publicado no commit `a1564d7`.

### `prescia/plugins/bi_bb/payload/content/profile.php`

O arquivo foi analisado como o próximo alvo de maior impacto no diretório `bi_bb`. A investigação confirmou que ele é incluído dinamicamente por `mod_bi_bb::onShow()` quando a ação é `profile`, recebendo `$core` como contexto `CPrescia`. A recomendação técnica registrada é adicionar o PHPDoc de `$core` no início do payload e inicializar `$ext` antes da chamada por referência `locateFile($image, $ext)`.

Neste fechamento, `profile.php` **ainda não foi alterado nem commitado**. Portanto, o relatório registra a análise e o plano de correção, mas não declara o arquivo como concluído. A validação e a publicação de `profile.php` permanecem como o próximo passo do Lote B.

| Arquivo | Situação | Evidência |
|---|---|---|
| `bi_bb/payload/content/forum.php` | **Concluído** | PHPStan focalizado sem erros; `php -l` aprovado; commit `a1564d7` |
| `bi_bb/payload/content/profile.php` | **Analisado, pendente de implementação** | Contexto de inclusão confirmado; PHPDoc e `$ext` definidos como próxima correção |

A execução de compatibilidade PHP 8.3 do commit `a1564d7` passou em [34047768938][10]. A análise completa do PHPStan ainda falhou em [34047768949][11], como esperado enquanto os arquivos remanescentes do Lote B não forem corrigidos. A baseline continua sem novas entradas.

[9]: https://github.com/leohmoraes/Prescia/issues/44 "Issue #44 — Reduzir diagnósticos restantes do PHPStan"
[10]: https://github.com/leohmoraes/Prescia/actions/runs/34047768938 "PHP 8.3 compatibility — execução 34047768938"
[11]: https://github.com/leohmoraes/Prescia/actions/runs/34047768949 "PHP static analysis — execução 34047768949"


## Atualização adicional — aplicação da skill em `profile.php`

A skill `phpstan-legacy-remediation` foi aplicada ao próximo payload priorizado de `bi_bb`, `prescia/plugins/bi_bb/payload/content/profile.php`. A investigação confirmou que o arquivo é incluído dinamicamente por `mod_bi_bb::onShow()` quando a ação é `profile`, com `$core` disponível como `CPrescia`.

A correção adicionou o contrato PHPDoc de `CPrescia $core` e inicializou `$ext` no ramo autenticado antes da chamada por referência `locateFile($image, $ext)`. O PHPStan focalizado terminou com **0 erros** e o `php -l` do PHP 8.3 também foi aprovado. A baseline não recebeu entradas.

| Arquivo | Situação | Validação |
|---|---|---|
| `bi_bb/payload/content/profile.php` | **Concluído** | PHPStan focalizado sem erros; sintaxe PHP 8.3 aprovada |

O arquivo deixa de ser pendência do ranking histórico. O próximo alvo deverá ser escolhido após uma nova análise global, pois os contratos recentes de `preview.php`, `forum.php` e `profile.php` podem alterar a distribuição dos diagnósticos restantes.


## Atualização adicional — aplicação da skill em `script.php`

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/lazyload/script.php`. O contexto foi confirmado no método `CPrescia::addScript()`, que inclui o arquivo com `$this` como núcleo, `$scriptname` como nome do script e `$parameters` como array opcional.

Foram adicionados os contratos PHPDoc `CPrescia $this`, `string $scriptname` e `array<string, mixed> $parameters`. O PHPStan focalizado terminou com **0 erros**, o `php -l` passou e `git diff --check` foi aprovado. A análise global caiu de **446 para 422 diagnósticos**, uma redução de **24 ocorrências**, correspondente aos erros anteriormente atribuídos ao arquivo. A baseline permaneceu inalterada.

| Arquivo | Situação | Validação |
|---|---|---|
| `prescia/lazyload/script.php` | **Concluído** | PHPStan focalizado sem erros; PHP 8.3 syntax check aprovado; 24 diagnósticos removidos da análise global |

O próximo alvo deve ser recalculado a partir do relatório global de 422 diagnósticos. Os workflows de CI serão acompanhados após o push do commit desta atualização.


## Atualização adicional — `bi_adm/module.php`

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/plugins/bi_adm/module.php` com escopo limitado às 20 ocorrências `variable.undefined`. O contexto do include foi confirmado em `CPrescia::addPlugin()`, que avalia o código de configuração do módulo antes de instanciar `mod_bi_adm`.

A correção documentou `CPrescia $this` no escopo de carregamento e inicializou `$mname`, `$sname` e `$id` nos fluxos em que o PHPStan não podia provar uma atribuição. A análise focalizada passou a reportar somente os dois diagnósticos estruturais previamente separados: `function.inner` na função `mysort()` e `class.nameCase` na referência a `TTree`. O `php -l` e `git diff --check` foram aprovados; a baseline permaneceu inalterada.

| Arquivo | Situação | Validação |
|---|---|---|
| `prescia/plugins/bi_adm/module.php` | **Variáveis indefinidas concluídas** | 20 `variable.undefined` removidas; 2 diagnósticos estruturais restantes; sintaxe PHP 8.3 aprovada |

A análise global atual continua falhando e reporta 447 diagnósticos no total; essa contagem é registrada como métrica da execução atual e não como redução comparável ao relatório global anterior, que foi gerado em outro estado de dependências/artefato. Nenhuma entrada foi adicionada à baseline.


## Atualização adicional — aplicação da skill em `udm.php`

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/lazyload/udm.php`. A investigação confirmou que `CPrescia::udm($param, $ignorePreVF)` inclui o arquivo dinamicamente, com `$this` como `CPrescia`, `$param` como as definições de despacho de URL e `$ignorePreVF` como sinalizador booleano.

Foram adicionados os contratos PHPDoc `CPrescia $this`, `array<int, array<string, mixed>> $param` e `bool $ignorePreVF`. O PHPStan focalizado terminou com **0 erros**, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de **447 para 428 diagnósticos**, uma redução de **19 ocorrências**. A baseline permaneceu inalterada.

| Arquivo | Situação | Validação |
|---|---|---|
| `prescia/lazyload/udm.php` | **Concluído** | PHPStan focalizado sem erros; sintaxe PHP 8.3 aprovada; 19 diagnósticos removidos |

O próximo alvo deverá ser recalculado a partir do relatório global atual. Os workflows de CI serão acompanhados após o push.


## Atualização adicional — aplicação da skill em `stats_ref.php`

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/plugins/bi_stats/payload/content/stats_ref.php`. A investigação confirmou que `mod_bi_stats::onShow()` inclui o payload correspondente à ação atual e disponibiliza `$core` como instância de `CPrescia`.

Foi adicionado o contrato PHPDoc `CPrescia $core`. O PHPStan focalizado terminou com **0 erros**, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de **428 para 409 diagnósticos**, uma redução de **19 ocorrências**. A baseline permaneceu inalterada.

| Arquivo | Situação | Validação |
|---|---|---|
| `prescia/plugins/bi_stats/payload/content/stats_ref.php` | **Concluído** | PHPStan focalizado sem erros; sintaxe PHP 8.3 aprovada; 19 diagnósticos removidos |

O próximo alvo deve ser recalculado a partir do relatório global atual. Os workflows serão acompanhados após a publicação.


## Atualização adicional — aplicação da skill em `stats_pathajax.php`

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/plugins/bi_stats/payload/content/stats_pathajax.php`. A investigação confirmou que `mod_bi_stats::onShow()` inclui o payload correspondente à ação atual e disponibiliza `$core` como instância de `CPrescia`.

Foi adicionado o contrato PHPDoc `CPrescia $core`. O PHPStan focalizado terminou com **0 erros**, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de **409 para 391 diagnósticos**, uma redução de **18 ocorrências**. A baseline permaneceu inalterada.

| Arquivo | Situação | Validação |
|---|---|---|
| `prescia/plugins/bi_stats/payload/content/stats_pathajax.php` | **Concluído** | PHPStan focalizado sem erros; sintaxe PHP 8.3 aprovada; 18 diagnósticos removidos |

O próximo alvo deve ser recalculado a partir do relatório global atual. Os workflows serão acompanhados após a publicação.


## Atualização adicional — aplicação da skill em `reset.php`

A skill `phpstan-legacy-remediation` foi aplicada a `pages/presciatester/actions/reset.php`. A investigação confirmou que `CPrescia::checkActions()` inclui a action no contexto do núcleo, estabelecendo `$this` como `CPrescia`.

Foi adicionado o contrato PHPDoc `CPrescia $this`. O PHPStan focalizado terminou com **0 erros**, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de **391 para 374 diagnósticos**, uma redução de **17 ocorrências**. A baseline permaneceu inalterada.

| Arquivo | Situação | Validação |
|---|---|---|
| `pages/presciatester/actions/reset.php` | **Concluído** | PHPStan focalizado sem erros; sintaxe PHP 8.3 aprovada; 17 diagnósticos removidos |

O próximo alvo deverá ser recalculado a partir do relatório global atual. Os workflows serão acompanhados após a publicação.


## Atualização adicional — aplicação da skill em `bi_bb/actions/default.php`

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/plugins/bi_bb/payload/actions/default.php`. A investigação confirmou que `mod_bi_bb::onCheckActions()` inclui o payload no contexto do módulo, estabelecendo `$this` como `mod_bi_bb`.

Foi adicionado o contrato PHPDoc `mod_bi_bb $this`. O PHPStan focalizado terminou com **0 erros**, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de **374 para 357 diagnósticos**, uma redução de **17 ocorrências**. A baseline permaneceu inalterada.

| Arquivo | Situação | Validação |
|---|---|---|
| `prescia/plugins/bi_bb/payload/actions/default.php` | **Concluído** | PHPStan focalizado sem erros; sintaxe PHP 8.3 aprovada; 17 diagnósticos removidos |

O próximo alvo deverá ser recalculado a partir do relatório global atual. Os workflows serão acompanhados após a publicação.


## Atualização adicional — aplicação da skill em `bi_bb/content/default.php`

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/plugins/bi_bb/payload/content/default.php`. A investigação confirmou que `mod_bi_bb::onRender()` inclui o payload no contexto do módulo, disponibilizando `$core` como `CPrescia` e `$this` como `mod_bi_bb`.

Foram adicionados os contratos PHPDoc `CPrescia $core` e `mod_bi_bb $this`. O PHPStan focalizado terminou com **0 erros**, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de **357 para 340 diagnósticos**, uma redução de **17 ocorrências**. A baseline permaneceu inalterada.

| Arquivo | Situação | Validação |
|---|---|---|
| `prescia/plugins/bi_bb/payload/content/default.php` | **Concluído** | PHPStan focalizado sem erros; sintaxe PHP 8.3 aprovada; 17 diagnósticos removidos |

O próximo alvo deverá ser recalculado a partir do relatório global atual. Os workflows serão acompanhados após a publicação.


## Atualização adicional — aplicação da skill em `prepareMail.php`

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/lazyload/prepareMail.php`. A investigação confirmou que `CPrescia::prepareMail($name, $fillArray)` inclui o arquivo dinamicamente, disponibilizando `$this` como `CPrescia`, `$name` como string e `$fillArray` como array de preenchimento.

Foram adicionados os contratos PHPDoc correspondentes e inicializado `$template` antes do ramo que processa os campos POST, eliminando o fluxo possivelmente não atribuído. O PHPStan focalizado terminou com **0 erros**, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de **340 para 325 diagnósticos**, uma redução de **15 ocorrências**. A baseline permaneceu inalterada.

| Arquivo | Situação | Validação |
|---|---|---|
| `prescia/lazyload/prepareMail.php` | **Concluído** | PHPStan focalizado sem erros; sintaxe PHP 8.3 aprovada; 15 diagnósticos removidos |

O próximo alvo deverá ser recalculado a partir do relatório global atual. Os workflows serão acompanhados após a publicação.


## Consolidação dos arquivos corrigidos — lote de variáveis indefinidas

Até o commit `c278b77`, a aplicação da skill `phpstan-legacy-remediation` corrigiu e validou os seguintes arquivos. A tabela consolida os arquivos tratados desde o início do lote agrupado, incluindo os commits publicados anteriormente.

| Arquivo | Diagnósticos `variable.undefined` tratados | Contexto confirmado | Validação focalizada |
|---|---:|---|---|
| `prescia/plugins/bi_bb/payload/content/preview.php` | 26 | `CPrescia $core`, `mod_bi_bb $this` | PHPStan sem erros; PHP 8.3 aprovado |
| `prescia/plugins/bi_bb/payload/content/forum.php` | 23 | `CPrescia $core`, `mod_bi_bb $this`; `$sql` inicializado por fluxo | PHPStan sem erros; PHP 8.3 aprovado |
| `prescia/plugins/bi_bb/payload/content/profile.php` | 18 | `CPrescia $core`; `$ext` inicializado antes de `locateFile()` | PHPStan sem erros; PHP 8.3 aprovado |
| `prescia/lazyload/script.php` | 24 | `CPrescia $this`, `$scriptname`, `$parameters` | PHPStan sem erros; PHP 8.3 aprovado |
| `prescia/plugins/bi_adm/module.php` | 20 | `CPrescia $this` no carregamento; `$mname`, `$sname`, `$id` | 20 variáveis resolvidas; 2 diagnósticos estruturais restantes |
| `prescia/lazyload/udm.php` | 19 | `CPrescia $this`, `$param`, `$ignorePreVF` | PHPStan sem erros; PHP 8.3 aprovado |
| `prescia/plugins/bi_stats/payload/content/stats_ref.php` | 19 | `CPrescia $core` | PHPStan sem erros; PHP 8.3 aprovado |
| `prescia/plugins/bi_stats/payload/content/stats_pathajax.php` | 18 | `CPrescia $core` | PHPStan sem erros; PHP 8.3 aprovado |
| `pages/presciatester/actions/reset.php` | 17 | `CPrescia $this` no dispatcher de actions | PHPStan sem erros; PHP 8.3 aprovado |
| `prescia/plugins/bi_bb/payload/actions/default.php` | 17 | `mod_bi_bb $this` | PHPStan sem erros; PHP 8.3 aprovado |
| `prescia/plugins/bi_bb/payload/content/default.php` | 17 | `CPrescia $core`, `mod_bi_bb $this` | PHPStan sem erros; PHP 8.3 aprovado |
| `prescia/lazyload/prepareMail.php` | 15 | `CPrescia $this`, `$name`, `$fillArray`; `$template` inicializado | PHPStan sem erros; PHP 8.3 aprovado |

O conjunto representa **233 diagnósticos `variable.undefined` tratados** sem expansão da baseline. A análise global mais recente do commit `c278b77` ainda reporta **325 diagnósticos**, pois há pendências em outros arquivos e categorias, incluindo os 46 `return.missing` de `tools/phpstan-framework-stubs.php`. A análise focalizada de cada arquivo acima permanece aprovada; a falha global do workflow não invalida essas validações locais.


## Atualização adicional — aplicação da skill em `readfile.php`

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/lazyload/readfile.php`. A investigação confirmou que `CPrescia::readfile($file, $ext, $exit, $filename, $forceAttach, $cachetime)` inclui o arquivo dinamicamente, disponibilizando `$this` como `CPrescia` e os seis parâmetros da assinatura no escopo do lazy-load.

Foram adicionados os contratos PHPDoc correspondentes. O PHPStan focalizado terminou com **0 erros**, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de **325 para 311 diagnósticos**, uma redução de **14 ocorrências**. A baseline permaneceu inalterada.

| Arquivo | Situação | Validação |
|---|---|---|
| `prescia/lazyload/readfile.php` | **Concluído** | PHPStan focalizado sem erros; sintaxe PHP 8.3 aprovada; 14 diagnósticos removidos |

O próximo alvo deverá ser recalculado a partir do relatório global atual. Os workflows serão acompanhados após a publicação.


## Atualização adicional — aplicação da skill em `pages/prescia/_config/config.php`

A skill `phpstan-legacy-remediation` foi aplicada a `pages/prescia/_config/config.php`. A investigação confirmou que `CPrescia::loadDomain()` requer a configuração selecionada no contexto do objeto `CPrescia`, disponibilizando `$this` durante a avaliação do arquivo.

Foi adicionado o contrato PHPDoc `CPrescia $this`. O PHPStan focalizado terminou com **0 erros**, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de **311 para 299 diagnósticos**, uma redução de **12 ocorrências**. A baseline permaneceu inalterada.

| Arquivo | Situação | Validação |
|---|---|---|
| `pages/prescia/_config/config.php` | **Concluído** | PHPStan focalizado sem erros; sintaxe PHP 8.3 aprovada; 12 diagnósticos removidos |

O próximo alvo deverá ser recalculado a partir do relatório global atual. Os workflows serão acompanhados após a publicação.


## Atualização adicional — aplicação da skill em `ajaxQuery.php`

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/lazyload/ajaxQuery.php`. A investigação confirmou que `CPrescia::checkActions()` inclui o arquivo para a ação `ajaxquery`, disponibilizando `$this` como instância de `CPrescia`.

Foi adicionado o contrato PHPDoc `CPrescia $this`. O PHPStan focalizado terminou com **0 erros**, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de **299 para 287 diagnósticos**, uma redução de **12 ocorrências**. A baseline permaneceu inalterada.

| Arquivo | Situação | Validação |
|---|---|---|
| `prescia/lazyload/ajaxQuery.php` | **Concluído** | PHPStan focalizado sem erros; sintaxe PHP 8.3 aprovada; 12 diagnósticos removidos |

O próximo alvo deverá ser recalculado a partir do relatório global atual. Os workflows serão acompanhados após a publicação.


## Marco global — 287 diagnósticos restantes

A análise global executada após o commit `eb1cc26` reportou **287 diagnósticos** no total, contra 299 na execução anterior. A redução de **12 diagnósticos** corresponde à correção de todas as ocorrências `variable.undefined` identificadas em `prescia/lazyload/ajaxQuery.php`.

O arquivo corrigido passou no PHPStan focalizado, no `php -l` do PHP 8.3 e no `git diff --check`. A análise global ainda não está verde porque permanecem diagnósticos em outros arquivos, incluindo o agrupamento estrutural de `tools/phpstan-framework-stubs.php` com 46 ocorrências `return.missing`. A baseline continua sem novas entradas.

| Métrica | Estado atual |
|---|---:|
| Diagnósticos globais | **287** |
| Redução desde a execução anterior | **12** |
| Arquivos corrigidos no lote recente | `pages/prescia/_config/config.php`, `prescia/lazyload/ajaxQuery.php` |
| PHPStan focalizado dos arquivos recentes | **0 erros** |
| Baseline expandida | **Não** |


## Análise estrutural — 46 `return.missing` em `tools/phpstan-framework-stubs.php`

O relatório global de 287 diagnósticos contém **46 ocorrências `return.missing`** concentradas em `tools/phpstan-framework-stubs.php`. A inspeção confirmou que o arquivo é um stub exclusivo do PHPStan, referenciado por `stubFiles` em `phpstan.neon.dist`, e não é carregado pela aplicação.

Os diagnósticos estão distribuídos entre **34 funções da API principal**, **1 método `CPrescia::saveConfig()`** e **11 funções legadas adicionais**. As funções `void` `dieFreakingThumbs()`, `adodb_daylight_sv()` e `removeBOM()` não fazem parte da contagem e devem permanecer sem retornos artificiais.

A correção planejada será feita em ondas: primeiro retornos escalares e arrays, depois uniões como `array|string`, em seguida funções e métodos `mixed` com `null` explícito, e finalmente uma análise global para confirmar a redução exata. Os retornos serão sintéticos e compatíveis com as assinaturas; não serão executadas operações de filesystem, banco, e-mail ou imagem, e a baseline não será ampliada.

| Item | Estado |
|---|---|
| Diagnósticos analisados | **46 `return.missing`** |
| Arquivo | `tools/phpstan-framework-stubs.php` |
| Origem | Corpos vazios em declarações de stub com retorno não `void` |
| Correção imediata | Planejada, ainda não aplicada |
| Baseline | Sem alteração planejada |
| Marco de comparação | **287 diagnósticos globais**, commit `eb1cc26` |

A execução da correção será considerada concluída somente após validação focalizada equivalente, análise global, sintaxe PHP 8.3, compatibilidade no CI e comparação antes/depois por identificador.


## Atualização de 6 de setembro de 2026 — lote de segurança e compatibilidade

O commit `1127c0e` consolidou a correção de issues de segurança, compatibilidade PHP 8.3 e contratos PHPStan. O lote endureceu CSRF, cookies de sessão e login persistente, logout e migração de senhas; removeu debug acionável por cliente; bloqueou objetos em desserialização e extensões executáveis em uploads; e parametrizou as consultas mutáveis identificadas em estatísticas e administração.

Também foram corrigidos fluxos legados que produziam warnings no PHP 8.3: `continue` dentro de `switch` foi substituído por `continue 2` quando havia um `foreach` externo e por `break` quando o `switch` era o único escopo iterável. O cálculo de ownership do `bi_auth` foi recolocado dentro do módulo atualmente iterado, eliminando variáveis fora de escopo e corrigindo o uso do código de ativação.

| Verificação | Resultado |
|---|---|
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| Lint PHP global | **Aprovado sem warnings** |
| PHPStan focalizado | **Aprovado nos arquivos tratados** |
| PHPStan global, nível 1 | **219 diagnósticos remanescentes**, contra 287 no relatório anterior |
| Composer validate | **Aprovado** |
| Composer audit | **Nenhum advisory de segurança** |
| Baseline | **Sem novas entradas** |

Os diagnósticos globais remanescentes estão fora do escopo dos arquivos tratados e continuam classificados como dívida legada para o próximo lote. O lote atual não eleva o nível do PHPStan nem adiciona supressões.


## Atualização de 7 de setembro de 2026 — `tcaptcha.php`

O inventário global do commit `5c14031` encontrou 12 diagnósticos `variable.undefined` em `prescia/lazyload/tcaptcha.php`: `$checkStage`, `$key` e `$this` eram fornecidos pelo método `CPrescia::tCaptcha(string $key, bool $checkStage)`, mas o include procedural não documentava seu contexto.

A correção adicionou contratos PHPDoc concretos para `CPrescia $this`, `string $key` e `bool $checkStage`. Nenhum valor artificial foi introduzido e o comportamento de geração, validação e consumo único do CAPTCHA permaneceu inalterado.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado | **0 erros** |
| `php -l prescia/lazyload/tcaptcha.php` | **Aprovado** |
| `git diff --check` | **Aprovado** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **219 diagnósticos em 54 arquivos** |
| PHPStan global depois | **207 diagnósticos em 53 arquivos** |
| Redução | **12 diagnósticos** |
| Baseline | **Sem alteração** |

O próximo inventário deve priorizar `prescia/lib/datetime.php`, `prescia/plugins/bi_labels/payload/actions/config_labels.php` e `pages/presciatester/_config/config.php`, que permanecem com 12, 12 e 11 diagnósticos, respectivamente.


## Atualização de 7 de setembro de 2026 — primeiro lote da Issue #47

O primeiro lote do inventário global atual tratou os três maiores alvos inicialmente selecionados: `prescia/lib/datetime.php`, `prescia/plugins/bi_labels/payload/actions/config_labels.php` e `pages/presciatester/_config/config.php`.

Em `datetime.php`, foram inicializados no escopo de `_adodb_getdate()` os estados de ano, mês e quantidade de dias, além da tabela padrão usada por `adodb_mktime()`. A chamada opcional de `adodb_daylight_sv()` passou a ser protegida pela mesma verificação de disponibilidade usada para definir o estado do recurso. Nos dois payloads/configurações, foram documentados os contextos reais de `CPrescia` fornecidos pelos carregadores (`mod_bi_labels::onCheckActions()` e `CPrescia::domainLoad()`).

| Verificação | Resultado |
|---|---|
| PHPStan focalizado dos três arquivos | **0 erros** |
| PHP 8.3 lint | **Aprovado nos três arquivos** |
| `git diff --check` | **Aprovado** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **207 diagnósticos em 53 arquivos** |
| PHPStan global depois | **172 diagnósticos em 50 arquivos** |
| Redução | **35 diagnósticos** |
| Baseline | **Sem alteração** |

As categorias restantes são 161 `variable.undefined`, 3 `class.nameCase`, 3 `constant.notFound`, 2 `function.inner`, 2 `function.notFound` e 1 `isset.variable`. Os próximos maiores grupos são `prescia/lazyload/rss.php` e `prescia/plugins/bi_adm/payload/actions/import.php`, com 10 diagnósticos cada, seguidos de `prescia/plugins/bi_labels/payload/content/config_labels.php`, com 9.


## Atualização de 7 de setembro de 2026 — segundo lote da Issue #47

O segundo lote tratou `prescia/lazyload/rss.php` e `prescia/plugins/bi_adm/payload/actions/import.php`.

Em `rss.php`, foi documentado o contrato de include de `CPrescia::rss()`, incluindo `$this`, `$data`, `$echoHeader` e `$imgtitle`. Também foi corrigida a validação de cardinalidade para usar `$modules`, `$ilt`, `$it` e `$idesc`, rejeitando qualquer inconsistência e eliminando a referência incorreta a `$module`.

Em `import.php`, a classe foi alinhada à declaração real `CImporter`; os índices de enumeração passaram a usar `$regs[$c]`; o estado `$oldKey` foi inicializado antes do caminho que o consome; `$tempOk` recebeu valor padrão quando não há dados para executar; e o conteúdo original foi preservado no modo raw antes de qualquer transformação.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado dos dois arquivos | **0 erros** |
| PHP 8.3 lint | **Aprovado nos dois arquivos** |
| `git diff --check` | **Aprovado** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **172 diagnósticos em 50 arquivos** |
| PHPStan global depois | **152 diagnósticos em 48 arquivos** |
| Redução | **20 diagnósticos** |
| Baseline | **Sem alteração** |

As categorias restantes são 145 `variable.undefined`, 2 `class.nameCase`, 2 `function.inner`, 2 `function.notFound` e 1 `isset.variable`. O próximo alvo prioritário é `prescia/plugins/bi_labels/payload/content/config_labels.php`, com 9 diagnósticos, seguido de `prescia/plugins/bi_fm/payload/actions/affbi_fmset.php`, com 8.


## Atualização de 7 de setembro de 2026 — terceiro lote da Issue #47

O terceiro lote tratou os dois próximos arquivos prioritários de payload: `prescia/plugins/bi_labels/payload/content/config_labels.php` e `prescia/plugins/bi_fm/payload/actions/affbi_fmset.php`.

A investigação confirmou que ambos são incluídos dentro de métodos dos módulos concretos: `mod_bi_labels::onShow()` e `mod_bi_fm::onCheckActions()`. Nos dois casos, `$core` é uma referência ao `CPrescia` pai (`$this->parent`), portanto a correção foi documentar esse contrato real com PHPDoc, sem criar variáveis globais, casts ou defaults artificiais.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado dos dois arquivos | **0 erros** |
| PHP 8.3 lint | **Aprovado nos dois arquivos** |
| `git diff --check` | **Aprovado** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **152 diagnósticos em 48 arquivos** |
| PHPStan global depois | **135 diagnósticos em 46 arquivos** |
| Redução | **17 diagnósticos** |
| Baseline | **Sem alteração** |

As categorias restantes são 128 `variable.undefined`, 2 `class.nameCase`, 2 `function.inner`, 2 `function.notFound` e 1 `isset.variable`. Os próximos alvos de maior concentração têm 7 diagnósticos cada: `pages/_newProjectTemplate/_config/config.php`, `pages/prescia/actions/contatogo.php`, `pages/presciatester/content/default.php`, `prescia/lib/sendMail.php` e `prescia/plugins/bi_undo/module.php`.


## Atualização de 7 de setembro de 2026 — quarto lote da Issue #47

O quarto lote tratou os três próximos arquivos prioritários com sete diagnósticos cada: `pages/_newProjectTemplate/_config/config.php`, `pages/prescia/actions/contatogo.php` e `pages/presciatester/content/default.php`.

A investigação confirmou que os três arquivos são avaliados no contexto de `CPrescia`: a configuração é carregada por `CPrescia::domainLoad()`, a ação de contato é incluída pelo fluxo de ações da página e o conteúdo default é incluído por `CPrescia::renderPage()`. Cada arquivo recebeu apenas o contrato PHPDoc concreto para `$this`, sem introduzir defaults ou alterar o fluxo de página.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado dos três arquivos | **0 erros** |
| PHP 8.3 lint | **Aprovado nos três arquivos** |
| `git diff --check` | **Aprovado** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **135 diagnósticos em 46 arquivos** |
| PHPStan global depois | **114 diagnósticos em 43 arquivos** |
| Redução | **21 diagnósticos** |
| Baseline | **Sem alteração** |

As categorias restantes são 107 `variable.undefined`, 2 `class.nameCase`, 2 `function.inner`, 2 `function.notFound` e 1 `isset.variable`. Os próximos maiores alvos são `prescia/lib/sendMail.php` e `prescia/plugins/bi_undo/module.php`, com 7 diagnósticos cada, seguidos de `prescia/lazyload/feedReader.php`, `prescia/lazyload/fullSearch.php` e `prescia/plugins/bi_labels/payload/actions/config_labels_m.php`, com 6 cada.


## Atualização de 7 de setembro de 2026 — quinto lote da Issue #47

O quinto lote tratou `prescia/lib/sendMail.php` e `prescia/plugins/bi_undo/module.php`, os dois maiores alvos seguintes com sete diagnósticos cada.

Em `sendMail.php`, os delimitadores MIME `$bound` e `$bnext` passaram a ser inicializados antes do ramo que os atribui. Isso preserva o comportamento quando HTML ou anexos são usados e fornece valores neutros para a análise dos ramos alternativos. Em `bi_undo/module.php`, o contexto global de carregamento foi documentado como `CPrescia`, `$n` foi inicializado antes da consulta de undo e `$keys` passou a começar como string vazia antes dos loops que montam chaves de arquivos.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado dos dois arquivos | **0 erros** |
| PHP 8.3 lint | **Aprovado nos dois arquivos** |
| `git diff --check` | **Aprovado** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **114 diagnósticos em 43 arquivos** |
| PHPStan global depois | **100 diagnósticos em 41 arquivos** |
| Redução | **14 diagnósticos** |
| Baseline | **Sem alteração** |

As categorias restantes são 93 `variable.undefined`, 2 `class.nameCase`, 2 `function.inner`, 2 `function.notFound` e 1 `isset.variable`. Os próximos maiores alvos são `prescia/lazyload/feedReader.php`, `prescia/lazyload/fullSearch.php` e `prescia/plugins/bi_labels/payload/actions/config_labels_m.php`, com 6 diagnósticos cada.

## Atualização de 7 de setembro de 2026 — sexto lote da Issue #47

O sexto lote tratou `prescia/lazyload/feedReader.php`, `prescia/lazyload/fullSearch.php` e `prescia/plugins/bi_labels/payload/actions/config_labels_m.php`, com seis diagnósticos de variáveis indefinidas em cada arquivo.

A investigação confirmou que `feedReader.php` e `fullSearch.php` são includes avaliados dentro de `CPrescia::feedReader()` e `CPrescia::fullSearch()`, respectivamente. O payload `config_labels_m.php` é incluído pelo módulo de labels com `$core` apontando para o núcleo `CPrescia`. Os três arquivos receberam contratos PHPDoc explícitos, sem alterar o fluxo funcional nem expandir a baseline.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado dos três arquivos | **0 erros** |
| PHP 8.3 lint | **Aprovado nos três arquivos** |
| `git diff --check` | **Aprovado** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **100 diagnósticos em 41 arquivos** |
| PHPStan global depois | **82 diagnósticos em 39 arquivos** |
| Redução | **18 diagnósticos** |
| Baseline | **Sem alteração** |

As categorias restantes são 75 `variable.undefined`, 2 `class.nameCase`, 2 `function.inner`, 2 `function.notFound` e 1 `isset.variable`. O próximo passo é separar os diagnósticos restantes por causa, priorizando os arquivos com maior concentração de variáveis indefinidas e mantendo os diagnósticos de símbolos legados em lotes próprios.


## Inventário detalhado dos 82 diagnósticos remanescentes

A análise global do PHPStan 2.2.13, no nível 1, apresenta 82 ocorrências em 39 arquivos. A maior concentração está em `variable.undefined`, com 75 ocorrências. Dentro dessa categoria, 37 referências são ao contexto dinâmico `$this` e 13 ao contexto `$core`, totalizando 50 diagnósticos ligados aos includes procedurais do framework.

| Grupo | Quantidade | Interpretação | Tratamento previsto |
|---|---:|---|---|
| `$this` possivelmente indefinido | 37 | Contexto de módulo ou `CPrescia` não inferido pelo PHPStan | Confirmar o carregador e documentar o tipo real com PHPDoc |
| `$core` possivelmente indefinido | 13 | Núcleo injetado em ações e payloads | Confirmar a ação/módulo e documentar `CPrescia` |
| `$sname` possivelmente indefinido | 8 | Variável usada nos `payloadmanifest.php` | Confirmar se é parâmetro do carregador ou inicialização necessária |
| `$tag` | 3 | Variável local usada antes de atribuição garantida | Corrigir o fluxo mantendo o valor semântico |
| `$module` | 2 | Resultado ou referência de módulo | Inicializar ou validar o retorno do carregador |
| `$itemList` | 2 | Acumulador/lista de itens | Inicializar no menor escopo comum |
| Outras variáveis locais | 7 | `$using`, `$p`, `$monitorTxt`, `$hasSOME`, `$frame`, `$fm`, `$field`, `$content`, `$cacheMTFile` e `$cacheFile` | Revisar individualmente por fluxo |

Os arquivos com maior concentração de ocorrências são `prescia/plugins/bi_labels/payload/content/label_test.php`, `prescia/lazyload/ajaxqueryunique.php`, `prescia/coreFull.php`, `pages/presciatester/actions/default.php` e `pages/prescia/content/default.php`, com cinco diagnósticos cada. Em seguida aparecem `prescia/plugins/bi_stats/payload/actions/stats_rtajax.php` e `pages/prescia/content/resources/reference.php`, com quatro cada.

Os 7 diagnósticos restantes pertencem a categorias estruturais distintas de `variable.undefined`:

| Categoria | Quantidade | Arquivo(s) | Problema |
|---|---:|---|---|
| `class.nameCase` | 2 | `prescia/plugins/bi_adm/payload/actions/import_sample.php`; `prescia/plugins/bi_adm/payload/content/import_fields.php` | Referência a `Cimporter` com capitalização incorreta; a classe correta é `CImporter` |
| `function.inner` | 2 | `prescia/coreFull.php`; `prescia/plugins/bi_dev/module.php` | Funções nomeadas declaradas dentro de outro escopo; preferir closures locais |
| `function.notFound` | 2 | `prescia/coreFull.php` | `dieFreakingThumbs()` não foi localizada pelo PHPStan; confirmar origem antes de criar stub ou ajustar `scanFiles` |
| `isset.variable` | 1 | `prescia/components/cacheControl.php` | Uso redundante de `isset($_POST)` em um contexto onde `$_POST` já é conhecido pelo analisador |

Esse inventário separa contratos de contexto, variáveis locais e símbolos legados para evitar que uma correção de tipagem mascare um problema funcional. Nenhuma dessas ocorrências foi adicionada à baseline. O próximo lote deve priorizar os contratos `$this`/`$core` e os `payloadmanifest.php`, seguido pelos arquivos com maior concentração de variáveis locais.

### Detalhamento das ocorrências `$this` nos plugins BI

Das 37 ocorrências de `$this` classificadas no inventário, seis estão diretamente nos módulos de plugins BI: duas em `prescia/plugins/bi_bb/module.php` (linhas 3 e 4) e uma em cada arquivo `prescia/plugins/bi_cms/module.php`, `prescia/plugins/bi_groups/module.php`, `prescia/plugins/bi_seo/module.php` e `prescia/plugins/bi_stats/module.php` (linha 4). Esses arquivos são incluídos por `CPrescia::addPlugin()`, portanto o contrato correto do escopo de carregamento é `CPrescia $this`. Os módulos concretos (`mod_bi_bb`, `mod_bi_cms`, `mod_bi_groups`, `mod_bi_seo` e `mod_bi_stats`) somente são instanciados depois do include.

## Atualização de 7 de setembro de 2026 — lote dos módulos BI

O lote dos módulos BI corrigiu os contratos das seis ocorrências de `$this` em `bi_bb/module.php`, `bi_cms/module.php`, `bi_groups/module.php`, `bi_seo/module.php` e `bi_stats/module.php`. O carregador `CPrescia::addPlugin()` foi confirmado como origem do include, portanto cada arquivo recebeu o contrato `CPrescia $this`. No mesmo lote, `$frame` foi inicializado em `bi_bb/module.php`, removendo o diagnóstico de fluxo associado.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado dos cinco módulos | **0 erros** |
| PHP 8.3 lint | **Aprovado nos cinco módulos** |
| `git diff --check` | **Aprovado** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **82 diagnósticos em 39 arquivos** |
| PHPStan global depois | **75 diagnósticos em 34 arquivos** |
| Redução | **7 diagnósticos** |
| Baseline | **Sem alteração** |

## Atualização de 7 de setembro de 2026 — sétimo lote

O sétimo lote documentou o contexto `CPrescia` em `prescia/plugins/bi_labels/payload/content/label_test.php` e `prescia/lazyload/ajaxqueryunique.php`. O primeiro é incluído por `mod_bi_labels::onShow()` com `$core` apontando para o núcleo; o segundo é incluído diretamente por `CPrescia::checkActions()` com `$this` representando o núcleo.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado dos dois arquivos | **0 erros** |
| PHP 8.3 lint | **Aprovado nos dois arquivos** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **75 diagnósticos em 34 arquivos** |
| PHPStan global depois | **65 diagnósticos em 32 arquivos** |
| Redução | **10 diagnósticos** |
| Baseline | **Sem alteração** |

## Atualização de 7 de setembro de 2026 — oitavo lote

O oitavo lote documentou `$sname` nos quatro manifests de plugins BI que ainda apresentavam esse diagnóstico: `bi_auth/payloadmanifest.php` (três ocorrências), `bi_labels/payloadmanifest.php` (uma), `bi_permissions/payloadmanifest.php` (uma) e `bi_stats/payloadmanifest.php` (duas). A investigação confirmou que os manifests são incluídos dentro de `CPrescia::applyMetaData()`, no loop `foreach ($this->loadedPlugins as $sname => $plugin)`, portanto `$sname` é uma string fornecida pelo núcleo.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado dos quatro manifests | **0 erros** |
| PHP 8.3 lint | **Aprovado nos quatro manifests** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **65 diagnósticos em 32 arquivos** |
| PHPStan global depois | **58 diagnósticos em 28 arquivos** |
| Redução | **7 diagnósticos** |
| Baseline | **Sem alteração** |

## Atualização de 7 de setembro de 2026 — nono lote

O nono lote tratou `prescia/plugins/bi_stats/payload/actions/stats_rtajax.php` e `prescia/plugins/bi_adm/payloadmanifest.php`. O payload de Stats recebeu o contrato `CPrescia $core` para o núcleo usado na ação AJAX. O manifest administrativo recebeu o contrato `string $sname`, fornecido pelo loop de `CPrescia::applyMetaData()` que inclui os manifests dos plugins carregados.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado dos dois arquivos | **0 erros** |
| PHP 8.3 lint | **Aprovado nos dois arquivos** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **58 diagnósticos em 28 arquivos** |
| PHPStan global depois | **53 diagnósticos em 26 arquivos** |
| Redução | **5 diagnósticos** |
| Baseline | **Sem alteração** |

## Atualização de 7 de setembro de 2026 — décimo lote

O décimo lote tratou `prescia/coreFull.php`. A referência incorreta que dividia `$field` foi corrigida para usar `$rel[1]`, `$p` foi inicializado no loop de permissões de plugins e a função interna recursiva `dieFreakingThumbs()` foi convertida em closure recursiva local. Essas alterações removeram cinco diagnósticos — uma variável `$field`, uma variável `$p`, dois `function.notFound` e um `function.inner` — sem adicionar stub ou função global.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado de `coreFull.php` | **0 erros** |
| PHP 8.3 lint | **Aprovado** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **53 diagnósticos em 26 arquivos** |
| PHPStan global depois | **48 diagnósticos em 25 arquivos** |
| Redução | **5 diagnósticos** |
| Baseline | **Sem alteração** |

## Atualização de 7 de setembro de 2026 — décimo primeiro lote

O décimo primeiro lote documentou o contexto `CPrescia $this` em `pages/presciatester/actions/default.php` e `pages/prescia/content/default.php`. O primeiro arquivo é incluído pelo fluxo `CPrescia::checkActions()`; o segundo pelo fluxo `CPrescia::renderPage()`. Os contratos correspondem ao contexto real do carregador e não alteram a execução das páginas.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado dos dois arquivos | **0 erros** |
| PHP 8.3 lint | **Aprovado nos dois arquivos** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **48 diagnósticos em 25 arquivos** |
| PHPStan global depois | **38 diagnósticos em 23 arquivos** |
| Redução | **10 diagnósticos** |
| Baseline | **Sem alteração** |

## Atualização de 7 de setembro de 2026 — décimo segundo lote

O décimo segundo lote tratou `pages/prescia/content/resources/reference.php`, `prescia/lazyload/cls.php` e `prescia/lib/ttree.php`. Os dois primeiros receberam contratos `CPrescia $this`, confirmados pelos fluxos `renderPage()` e `domainLoad()`. Em `ttree::echoHTML()`, `$tag` foi inicializado antes dos caminhos que podem ignorar a tag. A validação também confirmou que `cls.php` usa legitimamente `builddomains()` durante o include; por isso o método lazyload foi exposto como API pública do núcleo, eliminando os acessos privados detectados pelo PHPStan.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado dos arquivos e núcleo relacionado | **0 erros** |
| PHP 8.3 lint | **Aprovado** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **38 diagnósticos em 23 arquivos** |
| PHPStan global depois | **28 diagnósticos em 20 arquivos** |
| Redução | **10 diagnósticos** |
| Baseline | **Sem alteração** |

## Atualização de 7 de setembro de 2026 — décimo terceiro lote

O décimo terceiro lote concentrou payloads administrativos e de labels. Foram inicializados fluxos condicionais em `ajaxmonitor.php`, `dirs.php` e `options.php`; corrigidos os acumuladores com capitalização inconsistente em `edit.php`; explicitado o retorno após o encerramento de módulo inválido em `laedit.php`; corrigida a capitalização de `CImporter` em dois payloads; e documentado o `$core` de `config_labels_e.php` e `rule.php`.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado dos nove payloads | **0 erros** |
| PHP 8.3 lint | **Aprovado nos nove payloads** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **28 diagnósticos em 20 arquivos** |
| PHPStan global depois | **15 diagnósticos em 10 arquivos** |
| Redução | **13 diagnósticos** |
| Baseline | **Sem alteração** |

## Atualização de 7 de setembro de 2026 — décimo quarto lote e fechamento do inventário

O décimo quarto lote concluiu a remediação dos diagnósticos restantes. Foram documentados os contextos dos payloads de conteúdo e lazyloads; `options.php` recebeu inicializações explícitas de `$using` e `$content`; `cacheControl.php` deixou de testar `isset($_POST)`, pois `$_POST` é uma superglobal sempre definida; os caminhos de cache de `intlControl.php` foram inicializados; `$hasSOME` foi inicializado em `bi_permissions`; e a função interna `appendErrors()` de `bi_dev` foi convertida em closure.

| Verificação | Resultado |
|---|---|
| PHPStan focalizado | **0 erros** |
| PHP 8.3 lint | **Aprovado** |
| PHPUnit | **23 testes, 2745 asserções, aprovado** |
| PHPStan global antes | **15 diagnósticos em 10 arquivos** |
| PHPStan global depois | **0 diagnósticos** |
| Baseline | **Sem alteração** |

Com este lote, a Issue #47 atinge o objetivo técnico de zerar os diagnósticos do PHPStan nível 1 sem expansão da baseline.

## Verificação pós-merge da PR #58 — 7 de setembro de 2026

Após o merge da PR #58, foi executada uma verificação completa no commit `633d820` (`Merge PR #58: zero remaining PHPStan diagnostics`). O ambiente utilizou PHP 8.3.6. `composer validate --strict` foi aprovado; a suíte PHPUnit concluiu com 23 testes e 2.745 asserções; o PHPStan global reportou zero diagnósticos; e todos os 348 arquivos PHP fora de `vendor/` passaram pelo lint de PHP 8.3. A baseline permaneceu sem diferenças, o cache temporário do PHPUnit foi removido e o working tree ficou limpo.

## Nova frente de segurança — prepared statements no CRUD genérico

Após a conclusão do PHPStan, a próxima prioridade aberta foi a issue #28, relacionada à #9. O primeiro lote migrou os `WHERE` dos fluxos `UPDATE` e `DELETE` de `CModule::runAction()` para `queryPrepared()`, incluindo os valores de chaves compostas. Também foram migradas as exclusões de rollback pós-upload, a atualização de flags de upload e a atualização de URLs geradas. A geração de IDs para chaves compostas passou a usar `fetchPrepared()`.

A alteração preserva os nomes de tabelas e colunas controlados pelo modelo, mas impede que valores de chaves, flags e URLs sejam interpolados no SQL. PHPStan focalizado, lint PHP 8.3, PHPUnit e `git diff --check` foram aprovados; a baseline permanece inalterada.

## Segundo lote de segurança — autoPrune e ciclos parentais

A continuação da issue #28 migrou as consultas de `CModule::autoPrune()` para `queryPrepared()`, incluindo a seleção dos registros antigos e a atualização do valor enum por chaves compostas. A verificação de ciclos parentais em `sqlParameter()` também passou a usar `fetchPrepared()`. PHPStan focalizado, lint PHP 8.3, PHPUnit e `git diff --check` foram aprovados, sem alteração da baseline.

## Terceiro lote de segurança — deleteAllFrom()

O terceiro lote da migração SQL parametrizou `CPrescia::deleteAllFrom()`. Tanto o caminho de zeragem (`UPDATE`) quanto o caminho de cascata (`SELECT`) agora usam `queryPrepared()`, mantendo as colunas e tabelas provenientes do modelo e transportando os valores de chave exclusivamente como parâmetros. PHPStan focalizado, lint PHP 8.3, PHPUnit e verificação de diff foram aprovados, sem alteração da baseline.

## Quarto lote de segurança — validações do plugin bi_groups

As consultas de nível em `mod_bi_groups::edit_parse()` passaram de `fetch()` com ID concatenado para `fetchPrepared()` nos fluxos de atualização e exclusão. PHPStan focalizado, lint PHP 8.3, PHPUnit e verificação de diff foram aprovados.

## Correção da matriz PHP 8.3–8.5

A execução CI do commit `d0b4a5f` revelou que `Php83CompatibilityTest` ainda exigia exatamente PHP 8.3, embora o workflow já validasse PHP 8.4 e 8.5. O teste foi ajustado para aceitar PHP 8.x a partir de 8.3 e abaixo de 8.6, mantendo as extensões obrigatórias. Em PHP 8.3 local, a suíte concluiu com 144 testes, 3.091 asserções, 2 deprecations e 2 skips existentes; PHPStan global e lint do teste foram aprovados.

## Correção do PHPStan em PHP 8.4/8.5

A execução da matriz após a PR #206 aprovou PHPUnit, lint e PHPStan em PHP 8.3, mas falhou em PHP 8.4 e 8.5 com `Cannot unset property CPresciaVar::$template because it might have hooks in a subclass` em `prescia/core.php:391`. O método `CPrescia::close()` passou a atribuir `null` à propriedade, liberando a referência do template sem usar `unset()` e mantendo compatibilidade com a análise das versões novas. Validação local em PHP 8.3: PHPUnit 144 testes/3093 asserções, PHPStan 0 e lint aprovado.

## Correção final de compatibilidade PHP 8.5

Após a correção do `unset()` em `CPrescia::close()`, a matriz ficou verde em PHP 8.3 e 8.4. O PHP 8.5 ainda reportou a deprecation de casts não padronizados `(integer)` em `prescia/lib/datetime.php:252-253`. Ambos foram substituídos pelo cast canônico `(int)`. A validação local em PHP 8.3 permaneceu aprovada: PHPUnit 144 testes/3093 asserções, PHPStan 0 e lint aprovado.

## Auditoria exploratória — PHPStan nível 5

Em 2026-09-14, foi executada uma análise exploratória global com PHPStan 2.2.13, PHP 8.3.6 e `--level=5`, usando a configuração vigente do nível 3 e a branch `fix/phpstan-level4-front-controllers` no commit `6cd5571`. O resultado foi de **458 diagnósticos em 38 identificadores**, distribuídos por 78 arquivos. O nível oficial permanece em 3 porque a promoção para o nível 5 neste momento faria a verificação de CI falhar.

| Identificador | Ocorrências | Interpretação inicial |
|---|---:|---|
| `smaller.alwaysFalse` | 69 | Fluxos estreitados por configurações literais, índices ou valores sentinela; requer confirmação do contrato de runtime. |
| `booleanAnd.alwaysFalse` | 41 | Condições compostas consideradas impossíveis pelo analisador; priorizar casos que possam ocultar guards legítimos. |
| `argument.type` | 39 | Tipos incompatíveis em chamadas, incluindo `mail()`, `number_format()` e `str_replace()`; tratar por contrato e validação de entrada. |
| `equal.alwaysTrue` | 34 | Comparações constantes ou efeito de stubs/configuração; separar falsos positivos de código morto real. |
| `greater.alwaysFalse` | 33 | Comparações numéricas estreitadas; revisar limites, contadores e resultados de consultas. |
| `deadCode.unreachable` | 25 | Trechos considerados inalcançáveis; confirmar includes, `die`/`exit` e contratos de retorno antes de remover código. |
| `isset.offset` | 21 | Acessos a chaves em arrays com forma excessivamente estreita; revisar PHPDoc e normalização de payloads. |

Os arquivos com maior concentração foram `prescia/plugins/bi_stats/payload/content/stats_analytics.php` (56), `prescia/core.php` (31), `prescia/plugins/bi_auth/authControl.php` (25), `prescia/plugins/bi_stats/module.php` (24) e `prescia/components/module.php` (22). O próximo ciclo recomendado é separar diagnósticos de tipos de argumentos e contratos de configuração dos diagnósticos de fluxo impossível, começando por `bi_stats` e `core`, sem alterar o nível oficial até que a análise focalizada e global retornem zero.

| Verificação | Resultado |
|---|---|
| Análise exploratória nível 5 | **458 erros de arquivo**; formatter JSON confirmou `file_errors=458` |
| Nível oficial | **Mantido em 3**; nenhuma alteração em `phpstan.neon.dist` |
| Baseline | **Sem expansão** |
| Correções de runtime | **Nenhuma nesta auditoria** |
