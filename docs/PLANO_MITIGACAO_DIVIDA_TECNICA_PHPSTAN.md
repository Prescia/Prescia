# Plano de ação para mitigação da dívida técnica identificada pelo PHPStan

## Resumo executivo

A execução do PHPStan em PHP 8.3, no commit `a7e0df5`, terminou com aproximadamente 480 diagnósticos e falhou. A maior parte dos achados está concentrada em quatro causas-raiz: símbolos globais não descobertos pelo analisador, variáveis de saída não inicializadas, contratos dinâmicos sem tipos explícitos e problemas reais de qualidade no código legado. O plano deve tratar primeiro a configuração de análise e a descoberta de símbolos. Em seguida, deve corrigir os riscos de execução mais prováveis nos componentes de banco, módulos, autenticação e arquivos. O nível de análise deve subir gradualmente, sem introduzir uma grande quantidade de supressões permanentes.

O objetivo não é zerar os diagnósticos em uma única alteração. O objetivo é estabelecer uma linha de base reproduzível, reduzir falsos positivos, eliminar erros de runtime prioritários e impedir que novos problemas sejam introduzidos.

## Evidências observadas

O workflow [PHP static analysis](https://github.com/leohmoraes/Prescia/actions/runs/33568335377) reportou erros distribuídos principalmente nas categorias abaixo.

| Categoria | Exemplos observados | Interpretação |
|---|---|---|
| Funções globais não descobertas | `cWriteFile`, `listFiles`, `cleanString`, `addslashes_EX`, `datecalc`, `removeSimbols`, `safe_mkdir`, `makeDirs`, `locateFile`, `sendMail` | O PHPStan não está recebendo um bootstrap ou stubs que descrevam as funções carregadas pelo framework. Alguns casos podem ser símbolos realmente ausentes. |
| Variáveis indefinidas | `$r`, `$n`, `$ext`, `$keys`, `$code`, `$valor` | Parte decorre do padrão legado de passar variáveis por referência ao driver de banco; outra parte pode causar notices, resultados incorretos ou falhas em caminhos de erro. |
| Classes não descobertas | `CPrescia`, `CPresciaFull`, `CKTCexternal`, `xmlHandler`, `ttree` | O carregamento dinâmico e os includes do framework não estão modelados na configuração do analisador. |
| Métodos e propriedades dinâmicos | `object::saveConfig()`, `mod_bi_auth::langOut()`, `CModule::$templateParans` | O código depende de objetos sem contratos declarados, nomes possivelmente digitados incorretamente e propriedades criadas dinamicamente. |
| Estruturas inconsistentes | chaves duplicadas `quick_reference` | Pode causar sobrescrita silenciosa de configuração e comportamento diferente do esperado. |
| Versão da ferramenta | PHPStan 1.12.x desatualizado | A análise perde correções e recursos disponíveis na série atual; a atualização deve ocorrer depois de estabilizar o bootstrap. |

Os números acima são categorias do log e não devem ser somados como se cada ocorrência representasse um defeito independente. O mesmo símbolo pode aparecer em vários arquivos porque o framework é carregado de forma dinâmica.

## Priorização

A prioridade considera risco de execução, alcance arquitetural e custo de correção.

| Fase | Prioridade | Resultado esperado | Critério de saída |
|---|---:|---|---|
| 0 | P0 | Linha de base confiável do PHPStan | O analisador identifica os arquivos e símbolos reais do framework sem varrer artefatos legados indevidos. |
| 1 | P0 | Bootstrap e contratos dos componentes centrais | Funções e classes carregadas pelo bootstrap deixam de aparecer como desconhecidas; símbolos realmente ausentes permanecem visíveis. |
| 2 | P1 | Variáveis de banco e retornos de consulta seguros | Não há `$r`/`$n`/`$ext` indefinidos nos caminhos executáveis principais. |
| 3 | P1 | Contratos explícitos para `CModule` e drivers | Métodos, propriedades e tipos dos componentes principais são declarados ou encapsulados em interfaces. |
| 4 | P1 | Correção de inconsistências funcionais | Chaves duplicadas, nomes incorretos e chamadas a métodos inexistentes são eliminados. |
| 5 | P2 | Modernização gradual das APIs legadas | Sanitização, arquivos, templates e autenticação usam APIs com tipos e responsabilidades explícitos. |
| 6 | P2 | Aumento progressivo do nível de análise | O nível do PHPStan sobe de 0 para 1, 2 e além sem regressão no CI. |

## Fase 0 — Tornar a análise confiável

O arquivo `phpstan.neon.dist` atualmente analisa `index.php`, `prescia`, `pages` e `tests`, mas não declara um `bootstrapFiles` que carregue as funções e classes globais do framework. A primeira tarefa é identificar o entrypoint real de produção e criar um bootstrap exclusivo para análise, sem executar conexão com banco, envio de e-mail, cron, uploads ou efeitos colaterais.

O bootstrap deve carregar apenas definições seguras, constantes, funções utilitárias e stubs. Quando um include tiver efeitos colaterais inevitáveis, deve ser criado um arquivo de declaração separado com assinaturas compatíveis. O diretório `pages/_js/ckfinder` deve continuar excluído até que o conector legado tenha uma estratégia própria de análise.

Também deve ser criada uma configuração de baseline temporária, versionada e revisada. Cada erro incluído na baseline precisa conter uma justificativa e uma issue; novos erros não podem ser aceitos silenciosamente. A configuração deve manter `reportUnmatchedIgnoredErrors: true` assim que a baseline estiver estabilizada.

**Critérios de aceite:** o teste de descoberta de arquivos PHP deixa de retornar uma lista vazia; o PHPStan mostra o arquivo e a linha para cada achado; a baseline não contém erros de sintaxe nem supressões sem justificativa; a análise de `prescia/components` é reproduzível localmente e no Actions.

## Fase 1 — Registrar funções e classes do framework

As funções mais repetidas devem ser catalogadas por arquivo de origem e contrato: `cWriteFile`, `listFiles`, `cleanString`, `addslashes_EX`, `datecalc`, `removeSimbols`, `safe_mkdir`, `makeDirs`, `locateFile`, `locateAnyFile`, `sendMail`, `time_diff`, `isMail`, `resizeImage` e `resizeImageCond`. Para cada símbolo, deve ser tomada uma decisão explícita: corrigir include, declarar stub, ou implementar/substituir a função.

As classes `CPrescia`, `CPresciaFull`, `CKTCexternal`, `xmlHandler` e `ttree` devem ter seus arquivos de definição identificados e incluídos no bootstrap de análise. O carregamento dinâmico por nome de módulo deve ser substituído gradualmente por um mapa de classes ou por interfaces conhecidas pelo analisador.

**Critérios de aceite:** os avisos de símbolos desconhecidos desaparecem dos componentes centrais; qualquer símbolo ainda não resolvido possui uma issue específica e não é mascarado por um `ignoreErrors` global.

## Fase 2 — Corrigir variáveis de saída e contratos de banco

Os diagnósticos repetidos para `$r` e `$n` indicam que as variáveis usadas como saída por `query()`, `fetch()` e métodos equivalentes não são inicializadas antes das chamadas. Cada caminho deve declarar explicitamente o tipo esperado, por exemplo `?array $result` ou `int $rowCount`, conforme o driver realmente retornar.

A API de banco deve ser revisada para eliminar parâmetros de saída sem contrato. O resultado ideal é uma classe ou DTO com status, linhas e erro, mas a migração pode começar inicializando variáveis e adicionando tipos de retorno aos métodos existentes. A alteração deve ser coordenada com a migração de prepared statements da Issue #9.

A variável `$ext` deve ser definida junto ao fluxo de upload e validada contra uma lista de extensões permitidas. Variáveis como `$keys`, `$code` e `$valor` devem ser inicializadas no ponto em que entram no fluxo ou substituídas por argumentos explícitos.

**Critérios de aceite:** nenhum caminho de banco em `module.php`, `authControl.php`, `mysqli.php` e `cdbo.php` usa variáveis de saída não inicializadas; testes cobrem resultado vazio, erro de consulta e múltiplas linhas; uploads não dependem de extensão implícita.

## Fase 3 — Tipar os componentes principais

### `prescia/components/module.php`

`CModule` deve receber propriedades declaradas para `parent`, `dbo`, `fields`, `keys`, `options`, `dbname` e `templateParans`, corrigindo também o nome da propriedade se `templateParans` for um erro de digitação de `templateParams`. Métodos como `getKeys()`, `sqlParameter()`, `runAction()`, `autoPrune()` e `get_base_sql()` devem documentar parâmetros, valores de retorno e invariantes.

A refatoração de SQL parametrizado deve continuar separando identificadores controlados pelo metamodelo de valores recebidos da requisição. O retorno estruturado de `sqlParameter()` deve ganhar um tipo comum, como `SqlParameter`, para impedir que alguns chamadores tratem arrays como strings.

### `prescia/lib/dbo/cdbo.php` e `prescia/lib/dbo/mysqli.php`

As interfaces dos drivers devem declarar contratos compatíveis para `queryPrepared()`, `fetchPrepared()`, `query()`, `fetch()` e `simpleQuery()`. Falhas devem retornar uma forma consistente, sem depender de índices possivelmente inexistentes em `$this->log`.

### Componentes de suporte

`authControl.php` deve declarar o tipo do objeto de módulo e do resultado de autorização. `cacheControl.php` deve separar cache ausente, cache inválido e erro de armazenamento. `intlControl.php` deve declarar que conversões de data podem retornar `false`. `errorControl.php` e `headerControl.php` devem ter métodos públicos com tipos explícitos.

**Critérios de aceite:** os componentes centrais não geram erros de propriedades ou métodos indefinidos; os retornos `false`, `null`, arrays e objetos são tratados explicitamente; a API de banco possui documentação de tipos.

## Fase 4 — Corrigir defeitos funcionais descobertos

As duas chaves duplicadas `quick_reference` devem ser localizadas e consolidadas. Não se deve simplesmente remover uma delas sem comparar os valores e documentar a decisão.

A chamada `mod_bi_auth::langOut()` deve ser confrontada com a API real do módulo. Se o método correto tiver outro nome, todos os consumidores devem ser atualizados; se o método for necessário, deve ser declarado no contrato do módulo.

A chamada `saveConfig()` em um `object` deve ser substituída por uma interface conhecida ou por uma verificação de tipo antes da chamada. O objetivo é evitar chamadas dinâmicas que falhem em runtime.

**Critérios de aceite:** não existem chaves duplicadas nas configurações; chamadas a métodos inexistentes são eliminadas; os testes de autenticação, configuração e notificações cobrem os caminhos alterados.

## Fase 5 — Reduzir dívida técnica de APIs legadas

As funções globais de sanitização e manipulação de arquivos devem ser agrupadas em serviços pequenos, com tipos explícitos e testes. O código de HTML deve separar sanitização contextual de escaping para armazenamento ou saída. O código de upload deve validar MIME, extensão, tamanho, nome e diretório de destino.

As consultas legadas restantes devem seguir a Issue #9. Depois de `getKeys()`, devem ser migrados `autoPrune()`, ciclos parentais, listagens, paginação, `bi_undo` e consultas administrativas. A análise estática não substitui testes de injeção, autorização e controle de propriedade.

O PHPStan deve ser atualizado de `^1.12` para a série atual somente após a linha de base estar sob controle. A atualização deve ocorrer em commit separado, com comparação do número de diagnósticos antes e depois.

## Fase 6 — Governança no CI

O workflow deve executar, nesta ordem, validação do Composer, descoberta de arquivos, lint PHP, PHPUnit e PHPStan. A análise deve falhar para novos erros, mas pode usar uma baseline temporária para o legado já catalogado. O CI deve publicar o relatório como artefato para facilitar triagem.

A métrica mínima de acompanhamento é o número de erros fora da baseline por componente. A cada pull request, esse número não pode aumentar. A cada sprint, a baseline deve ser reduzida e os itens P0 e P1 devem ser tratados antes de novos recursos que ampliem o uso de APIs dinâmicas.

## Sequenciamento sugerido de issues

| Ordem | Issue sugerida | Escopo |
|---:|---|---|
| 1 | Configurar bootstrap e descoberta do PHPStan | Corrigir o conjunto vazio de arquivos e resolver símbolos carregados pelo framework. |
| 2 | Declarar contratos do banco | Tipar drivers, resultados, erros e variáveis `$r`/`$n`. |
| 3 | Tipar `CModule` e separar contratos dinâmicos | Corrigir propriedades, métodos e retorno estruturado de `sqlParameter()`. |
| 4 | Corrigir variáveis de upload e caminhos de erro | Resolver `$ext`, `$keys`, `$code`, `$valor` e resultados vazios. |
| 5 | Corrigir configurações duplicadas e métodos inexistentes | Resolver `quick_reference`, `langOut()` e `saveConfig()`. |
| 6 | Concluir SQL parametrizado | Continuar a Issue #9 com `getKeys()`, `autoPrune()`, DELETE, listagens e plugins. |
| 7 | Modernizar funções globais e sanitização | Reduzir acoplamento e melhorar testabilidade dos componentes auxiliares. |
| 8 | Atualizar PHPStan e elevar o nível gradualmente | Remover a baseline por grupos e impedir regressões. |

## Critérios globais de conclusão

O plano será considerado concluído quando o workflow identificar todos os arquivos PHP pretendidos, executar a suíte sem testes arriscados, concluir o PHPStan sem erros fora da baseline e operar com PHPStan atualizado. Os componentes `module`, `mysqli`, `cdbo`, `authControl`, `cacheControl`, `intlControl`, `errorControl` e `headerControl` deverão possuir contratos documentados e testes para seus caminhos críticos.

A ausência de erros do PHPStan, isoladamente, não comprova segurança. A conclusão também requer os testes de prepared statements, CSRF, hashes de senha, sessão, desserialização segura e uploads definidos nas issues de segurança existentes.

## Referências

[1]: https://phpstan.org/user-guide/discovering-symbols "PHPStan — Discovering Symbols"
[2]: https://phpstan.org/user-guide/config-reference "PHPStan — Configuration Reference"
[3]: https://phpstan.org/user-guide/baseline "PHPStan — Baseline"
[4]: https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php "PHP Manual — MySQLi Prepared Statements"
[5]: https://www.php.net/manual/en/language.types.declarations.php "PHP Manual — Type Declarations"
