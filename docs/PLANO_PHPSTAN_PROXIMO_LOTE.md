# Plano do próximo lote de redução dos erros do PHPStan

**Issue:** [#43 — Atualizar o PHPStan e elevar gradualmente o nível de análise][5]  
**Commit de referência:** `22c3615`  
**Versão analisada:** PHPStan 2.x, nível 1  
**Status:** Planejamento aprovado para execução incremental  
**Autor:** **Manus AI**

**Execução iniciada:** o primeiro lote recebeu contratos PHPDoc explícitos nos payloads de `bi_adm` e `bi_stats`, documentando `$core` como contexto `CPrescia` e `$this` como contexto `CModule` quando o arquivo é incluído pelo módulo.

**Lote B iniciado:** os resultados `$r` e `$n` foram inicializados nos caminhos de consulta preparada e paginação de `CModule`, e os usos incorretos de `$code` e `$valor` em `CintlControl` foram corrigidos pelos parâmetros e propriedades correspondentes.

**Lote C iniciado:** foram adicionados contratos específicos para `dieFreakingThumbs()`, `adodb_daylight_sv()` e a classe dinâmica `CDBO_0`, todos identificados no relatório do CI como símbolos ausentes.

**Lote D — propriedades:** corrigidas as sete sobrescritas incompatíveis em `bi_adm`, `bi_bb`, `bi_dev`, `bi_labels` e `bi_undo`. O contrato de `admFolder` foi ampliado de forma explícita para `string|array<int, string>`, refletindo a normalização por `explode()` usada pelo runtime.

**Lote D — métodos:** alinhadas as chamadas de `CKTemplate`, `runclasses()`, `checkPermission()` e `checkHTML()`. As duas funções locais anteriormente chamadas `appendActs()` foram separadas em `appendAdminActs()` e `appendLogActs()` para eliminar a colisão de assinaturas durante a análise.

**Lote E iniciado:** adicionados contratos genéricos para `arrayToString()`, `extractUri()`, `listFiles()`, `xmlParamsParser()` e `adodb_daylight_sv()` nos arquivos procedurais e nos stubs do PHPStan. Os contratos preservam as diferenças entre listas, arrays associativos e o retorno `string|array` de `xmlParamsParser()`.

**Lote F oficializado:** a revisão da baseline passa a ser uma etapa independente, executada após a validação do Lote E. Nenhum diagnóstico novo poderá ser incluído na baseline para encobrir regressões.

**Diagnóstico inicial do Lote F:** a execução [PHP static analysis — execução 33582848768][6], no commit `4c8846b`, falhou com mais de 1000 diagnósticos. O formatter limitou a saída aos primeiros 1000 registros. A baseline contém `ignoreErrors: []`, portanto nenhum diagnóstico está sendo ocultado.

**Fase F1 — primeiro ciclo:** foram inicializados os resultados `$r` e `$n` nos fluxos de autenticação, sessão, carregamento de usuário e no teste de integração `presciatester`. A correção preserva a distinção entre resultado inexistente (`false`/`null`) e contagem vazia (`0`), sem alterar a decisão de sucesso das consultas.

**Fase F2 — primeiro ciclo:** as propriedades de conexão de `CDBO` usadas pelos drivers filhos foram alteradas de `private` para `protected`, preservando o encapsulamento contra consumidores externos e permitindo a herança prevista pelo driver. Também foram corrigidos dois acessos incorretos a `$this->fields` e um typo em `$ownerlink` no controle de autorização, substituindo-os pelo módulo e pela variável de fluxo corretos.

**Fase F2 — segundo ciclo iniciado:** `CPresciaVar::$headerControl` foi declarado como estado do controlador inicializado pelo construtor de `CPrescia`. Os payloads de `bi_adm` e `bi_stats` que recebem o módulo por `$this` deixaram de declarar o tipo genérico `CModule` e passaram a referenciar `mod_bi_adm` ou `mod_bi_stats`. A propriedade `hasStats` foi exposta como `protected` porque é consumida pelos payloads administrativos incluídos no contexto do módulo.

**Fase F2 — terceiro ciclo planejado:** o próximo ciclo tratará exclusivamente os acessos a propriedades que continuam ausentes após o commit `aac6fc9` e a execução [PHP static analysis — execução 33704528222][7]. O ciclo não alterará a baseline e não adicionará propriedades genéricas às classes apenas para reduzir a contagem do relatório.

**Fase F2 — terceiro ciclo, análise executada:** a execução [PHP static analysis — execução 33707202232][8], associada ao commit `4a711bb`, atingiu o limite do formatter de 1.000 diagnósticos. O recorte disponível contém 962 registros `variable.undefined`, além de `property.notFound`, `require.fileNotFound`, `class.notFound`, `function.notFound`, `constant.notFound` e outras categorias. Como o limite impede estimar o total real, os números serão tratados como limite inferior do relatório exibido.

**Fase F2 — terceiro ciclo, correção aplicada:** os acessos a `modules`, `template` e `dbo` no payload de `bi_stats` foram reclassificados como estado de `CPrescia` e passaram a usar `$this->parent`. O payload `bi_adm` passou a alterar `$core->layout`, e não uma propriedade inexistente de `mod_bi_adm`. No `bi_auth`, `action` passou a ser lido de `$this->parent`, enquanto os rótulos de e-mail foram convertidos em chaves literais de tradução (`account_welcome` e `account_activation_required`) em vez de propriedades não declaradas. Nenhuma entrada foi adicionada à baseline.

O recorte de propriedades confirmou os seguintes alvos para o terceiro ciclo: `mod_bi_adm::$layout`; `mod_bi_auth::$action`, `$account_activation_required` e `$account_welcome`; e `mod_bi_stats::$modules`, `$template` e `$dbo`. A investigação deve começar pelos payloads de `bi_stats`, porque `modules`, `template` e `dbo` aparentam pertencer ao núcleo `CPrescia`, e depois revisar os três estados de autenticação e o acesso a `layout` no administrador. Os 962 diagnósticos de variáveis indefinidas permanecem registrados para a Fase F1 e não serão misturados às correções de contrato deste ciclo.

### Plano de ação da Fase F2 — terceiro ciclo

O objetivo do terceiro ciclo é separar propriedades que pertencem ao núcleo `CPrescia` de propriedades que pertencem aos módulos concretos. O diagnóstico será corrigido no ponto em que o contrato estiver errado, e não no consumidor por meio de casts indiscriminados ou supressões.

| Ordem | Escopo | Diagnósticos observados | Ação prevista | Evidência exigida |
|---:|---|---|---|---|
| 1 | `bi_stats` | `template`, `dbo` e `modules` | Confirmar se o include executa com o módulo ou com o núcleo como `$this`; ajustar o PHPDoc ou usar `$core` explicitamente | Trecho do carregador e lista de call sites |
| 2 | `bi_adm` | `layout` | Verificar se o valor é estado do núcleo, do módulo ou uma dependência injetada; declarar no contrato correto somente se o módulo realmente o possuir | Declaração da classe e fluxo de inclusão |
| 3 | `bi_auth` | `action`, `account_welcome` e `account_activation_required` | Distinguir parâmetros locais, propriedades de configuração e estado do núcleo; corrigir o acesso ou declarar propriedades tipadas quando fizerem parte do módulo | Assinatura, inicialização e consumidores |
| 4 | Todos os payloads tratados | `property.notFound` residual | Reexecutar PHPStan e comparar por arquivo, classe, propriedade e linha | Relatório antes/depois e contagem por identificador |

#### Procedimento de execução

Primeiro, será criado um inventário dos diagnósticos `property.notFound` no commit `aac6fc9`, incluindo arquivo, linha, classe inferida pelo PHPStan e contexto real do `include` ou `include_once`. Em seguida, cada propriedade será classificada como **núcleo**, **módulo**, **parâmetro local**, **propriedade legítima não declarada** ou **referência incorreta**.

Depois da classificação, as correções serão aplicadas em commits pequenos. Um PHPDoc será alterado somente quando o escopo de execução comprovar o tipo concreto de `$this`. Uma propriedade será declarada somente na classe que a inicializa e consome como parte de seu estado. Uma referência será substituída por uma API pública ou pelo objeto correto quando o valor pertencer a outra camada.

O ciclo terminará com a execução do PHPStan, do PHPUnit e do teste de compatibilidade com PHP 8.3. Os resultados serão comparados com a execução `33704528222`. Diagnósticos de métodos ausentes, funções ausentes e arquivos não encontrados serão registrados para a Fase F3 ou para o lote de símbolos, mas não serão misturados a este ciclo salvo quando forem consequência direta de uma correção de contexto.

#### Regras de decisão

| Situação encontrada | Decisão |
|---|---|
| A propriedade é inicializada em `CPrescia` e consumida pelo payload | Usar `CPrescia` como contexto ou acessar a dependência por `$core`. |
| A propriedade é inicializada em `mod_bi_adm`, `mod_bi_stats` ou `mod_bi_auth` | Declarar o tipo na classe concreta e manter o PHPDoc do payload especializado. |
| O nome corresponde a um parâmetro ou variável local | Corrigir o acesso para usar a variável existente; não criar uma propriedade. |
| A propriedade é acessada por um include, mas é privada na classe proprietária | Preferir método protegido ou público com contrato claro; ampliar visibilidade somente se o include fizer parte do escopo legítimo da classe. |
| A origem não puder ser comprovada | Não alterar o código nem a baseline; registrar o caso para investigação separada. |

#### Critérios de aceite do terceiro ciclo

O ciclo será considerado concluído quando cada diagnóstico de propriedade tratado possuir uma classificação e uma correção justificadas. Os payloads modificados deverão declarar o contexto real de `$this`, sem introduzir variáveis globais artificiais. As propriedades novas ou alteradas deverão ter inicialização compatível com todos os caminhos de construção da classe. O PHPStan deverá apresentar redução nos diagnósticos `property.notFound` sem aumento equivalente de `method.notFound`, `variable.undefined` ou erros de inclusão. PHPUnit e o teste de compatibilidade PHP 8.3 deverão permanecer aprovados. A baseline deverá continuar sem novas entradas.

#### Métricas obrigatórias

O registro do ciclo deverá informar o commit analisado, a URL da execução do CI, o total reportado ou o limite do formatter, a contagem de `property.notFound` antes e depois, a quantidade de propriedades reclassificadas, a quantidade de propriedades declaradas, a quantidade de referências corrigidas e qualquer regressão introduzida. Se o formatter voltar a limitar a saída a `1000+`, esse valor será registrado como limite superior, e não como total exato.

## Conclusão executiva

A última execução global do PHPStan terminou com falha, mas as correções recentes reduziram o relatório para **287 diagnósticos**. O próximo lote não deve elevar o nível de análise. A prioridade é eliminar as causas estruturais que ainda geram a maior parte do relatório: contexto global ausente, chamadas de módulos/plugins sem contratos, variáveis indefinidas remanescentes e símbolos legados que ainda não possuem bootstrap ou stub confiável.

O relatório histórico do CI associado ao commit `22c3615` continha **2.126 diagnósticos**. O relatório atual do Lote F excedeu o limite de 1000 registros e, por isso, não deve ser usado para estimar o total real. A leitura dos registros exibidos confirma que `variable.undefined` é a categoria dominante, seguida por acessos a propriedades privadas ou ausentes, métodos e símbolos não encontrados. A estratégia deve tratar as causas por fluxo e contrato, não adicionar supressões globais.

| Categoria | Diagnósticos | Prioridade | Estratégia |
|---|---:|---:|---|
| Contexto global ou dinâmico (`$core`, `$this`) | 1.797 | P0 | Mapear includes, criar contratos de entrada e separar payload procedural de métodos de classe. |
| Variáveis indefinidas | 220 | P0 | Corrigir por fluxo, inicialização defensiva e validação de retorno. |
| Funções ausentes | 14 | P1 | Completar bootstrap/stubs somente após confirmar a assinatura real. |
| Classes ausentes | 7 | P1 | Registrar contratos de classes carregadas dinamicamente ou ajustar `scanFiles`. |
| Propriedades incompatíveis | 7 | P1 | Alinhar tipos de propriedades herdadas e sobrescritas. |
| Métodos e assinaturas | 3 | P1 | Corrigir chamadas incompatíveis ou ampliar contratos precisos. |
| Constantes ausentes | 3 | P1 | Definir no bootstrap estático quando forem configuração externa. |
| Tipos iteráveis e retornos | 1 | P2 | Completar tipos genéricos nas funções procedurais. |

### Estratégia operacional do Lote F

O Lote F será executado em ciclos curtos e mensuráveis. Cada ciclo deve partir do mesmo commit analisado pelo CI, preservar a baseline atual e produzir um relatório completo ou explicitamente limitado. Quando o formatter limitar a saída, a equipe deve registrar essa limitação e usar o identificador, arquivo e linha dos diagnósticos disponíveis sem inferir que o número exibido representa o total.

| Fase | Tratamento | Regra de decisão |
|---:|---|---|
| F1 | Variáveis indefinidas | Corrigir primeiro variáveis de banco, sessão, upload, imagem e requisição; inicializar no menor escopo comum e preservar `false`, `null`, lista vazia e valor válido. |
| F2 | Propriedades privadas ou ausentes | Confirmar a classe real do objeto; declarar a propriedade no contrato correto ou substituir o acesso por uma API pública. Não alterar visibilidade apenas para silenciar o PHPStan. |
| F3 | Métodos ausentes | Mapear a classe concreta e o carregamento dinâmico; corrigir o call site quando o método estiver incorreto ou adicionar uma declaração precisa quando o método existir em runtime. |
| F4 | Funções, classes e constantes ausentes | Localizar a origem real, ajustar `scanFiles` ou o bootstrap sem efeitos colaterais e criar stub somente quando o símbolo for opcional ou dinâmico. |
| F5 | Erros de inclusão e sintaxe de padrões | Corrigir caminhos, arquivos ausentes e expressões regulares inválidas no código ou na configuração; não criar `ignoreErrors` para problemas executáveis. |
| F6 | Baseline | Remover entradas somente quando o diagnóstico desaparecer e a correção estiver coberta por revisão ou teste. A baseline continua vazia até que um diagnóstico preexistente seja comprovado e justificado. |

Os diagnósticos de `variable.undefined` devem ser agrupados por arquivo, variável e fluxo de execução. Para cada correção, o registro deve indicar o valor inicial, a condição que garante a atribuição e o consumidor do valor. Os diagnósticos de contexto `$core` e `$this` devem continuar sendo tratados por contratos de payload e não por variáveis globais artificiais.

As categorias `property.private`, `property.notFound`, `method.notFound`, `class.notFound`, `function.notFound`, `constant.notFound`, `require.fileNotFound`, `includeOnce.fileNotFound`, `function.inner` e `constructor.unusedParameter` devem receber tarefas separadas quando a causa não puder ser resolvida no mesmo componente. Essa separação evita que uma alteração de tipagem esconda um problema de carregamento ou comportamento.

**Métrica de cada ciclo:** registrar o commit analisado, o total reportado ou o limite do formatter, a contagem por identificador, o número de erros fora da baseline, a quantidade de entradas removidas e o número de regressões. Quando o CI reportar `1000+`, a métrica deve ser registrada como limite superior e não como total exato.

## Escopo do próximo lote

### Lote A — Contrato de contexto para `$core` e `$this`

Os diagnósticos de contexto devem ser agrupados por diretório e não corrigidos com supressão global. Para cada payload, é necessário identificar se o arquivo é incluído por um controlador, executado diretamente ou avaliado dentro de uma classe. Arquivos incluídos devem receber uma anotação de contrato ou uma assinatura de função adaptadora. Código que depende de `$this` deve permanecer em classe ou ser convertido para receber explicitamente a dependência necessária.

A primeira entrega deve cobrir `prescia/plugins/bi_adm`, `prescia/plugins/bi_stats` e os payloads referenciados no relatório. O inventário deve registrar arquivo, variável, origem esperada e solução aplicada. O objetivo é reduzir a categoria sem criar uma variável global fictícia que possa mascarar defeitos de execução.

**Critério de aceite:** nenhum novo uso de `$this` fora de contexto de classe deve ser introduzido; os arquivos tratados devem possuir um contrato explícito; a contagem de diagnósticos de contexto deve diminuir sem aumento equivalente em erros de chamada.

### Lote B — Variáveis indefinidas por fluxo

As 220 ocorrências restantes devem ser agrupadas por variável e arquivo. Variáveis usadas como contadores, resultados de consulta ou acumuladores devem ser inicializadas no menor escopo comum antes do primeiro uso. Variáveis de upload, imagem e parâmetros de requisição devem ser derivadas de entradas validadas e receber valores padrão compatíveis com o contrato do consumidor.

O lote deve começar por variáveis que possam causar erro em produção: resultados de banco, identificadores de arquivo, valores de sessão e parâmetros de entrada. Cada correção deve preservar a distinção entre `false`, `null`, lista vazia e valor válido. A inicialização não deve esconder uma falha de consulta nem transformar entrada inválida em dado confiável.

**Critério de aceite:** as variáveis são definidas em todos os caminhos alcançáveis; os testes existentes continuam passando; cada correção possui uma verificação de fluxo ou teste de regressão quando a variável participa de autenticação, upload ou consulta.

### Lote C — Símbolos legados restantes

As funções ausentes, classes ausentes e constantes restantes devem ser resolvidas por classificação. Uma função usada em produção deve ser localizada e adicionada ao `scanFiles` ou ao bootstrap seguro. Uma função opcional de plugin deve receber um stub com assinatura compatível e uma justificativa. Classes carregadas dinamicamente devem receber contratos tipados ou configuração explícita de análise.

Não devem ser criados stubs genéricos com `mixed ...$arguments` quando a assinatura real estiver disponível. Essa prática reduz a qualidade da análise e pode esconder incompatibilidades entre plugins. O bootstrap não deve carregar configurações de produção, abrir conexões, iniciar sessões ou executar escrita em disco.

**Critério de aceite:** cada símbolo ausente possui origem documentada; o bootstrap continua sem efeitos colaterais; funções e classes efetivamente usadas pelo runtime têm contratos que refletem seus parâmetros e retornos.

### Lote D — Hierarquia de propriedades e métodos

Os sete diagnósticos de propriedades incompatíveis devem ser corrigidos alinhando as declarações das classes filhas com os tipos das classes base. Propriedades como `name`, `admFolder` e `customPermissions` devem ter um único contrato coerente na hierarquia. Quando o legado permite múltiplas formas, o tipo deve ser explicitamente ampliado e documentado, em vez de removido apenas para silenciar o PHPStan.

As chamadas de métodos incompatíveis devem ser revisadas junto com as assinaturas reais. O ajuste deve preservar compatibilidade entre módulos e plugins e deve incluir testes de instanciação ou carregamento dos componentes afetados.

**Critério de aceite:** não existem sobrescritas com tipos incompatíveis nos componentes tratados; chamadas de métodos usam a quantidade e os tipos de argumentos definidos pelo contrato; não são adicionados casts indiscriminados.

### Lote F — Revisão controlada da baseline

O Lote F consolida o resultado das correções dos Lotes A a E e revisa a baseline do PHPStan sem reduzir a qualidade da análise. A baseline é um registro temporário de diagnósticos conhecidos e justificados. Ela não deve ser usada para ocultar erros introduzidos por alterações recentes.

A primeira etapa consiste em executar o PHPStan no commit resultante do Lote E e salvar o relatório completo. A segunda etapa compara cada diagnóstico atual com a baseline, classificando-o como corrigido, preexistente, regressão ou não reproduzível. A terceira etapa remove da baseline os diagnósticos comprovadamente corrigidos. A quarta etapa registra separadamente os diagnósticos preexistentes que ainda exigem correção.

| Etapa | Atividade | Evidência obrigatória |
|---:|---|---|
| 1 | Executar PHPStan, PHPUnit e o teste de compatibilidade PHP 8.3 no mesmo commit | URLs das execuções e status dos jobs |
| 2 | Comparar o relatório atual com `phpstan-baseline.neon` | Contagem por identificador, arquivo e linha |
| 3 | Remover entradas referentes a diagnósticos corrigidos | Diff revisado da baseline |
| 4 | Classificar os diagnósticos restantes | Relatório de preexistentes, regressões e não reproduzíveis |
| 5 | Atualizar o plano e a Issue #43 | Commit e comentário técnico vinculados |

O Lote F não autoriza a inclusão automática de novos padrões `ignoreErrors`. Cada nova exceção deve conter uma justificativa, escopo de arquivo ou identificador específico, origem conhecida e uma condição para remoção. Diagnósticos de segurança, compatibilidade PHP 8.3 e erros introduzidos por commits recentes não podem ser adicionados à baseline.

**Métricas:** o relatório deve informar o total de diagnósticos, o total fora da baseline, a quantidade de entradas removidas, a quantidade de regressões e o número de símbolos ausentes. As métricas devem ser comparáveis com as execuções anteriores do CI.

**Critério de aceite:** a baseline não aumenta por causa de erros novos; todas as entradas removidas correspondem a diagnósticos comprovadamente corrigidos; não existem regressões fora da baseline sem uma tarefa registrada; o PHPUnit, o teste de compatibilidade PHP 8.3 e o PHPStan permanecem configurados no CI; e dois ciclos consecutivos do CI não apresentam aumento de diagnósticos fora da baseline antes da avaliação do nível 2.

## Ordem de execução

| Ordem | Entrega | Resultado esperado |
|---:|---|---|
| 1 | Inventário por arquivo dos 1.797 erros de contexto | Separar falsos positivos estruturais de usos realmente inválidos. |
| 2 | Correção dos payloads de `bi_adm` e `bi_stats` | Reduzir a maior categoria sem introduzir globais artificiais. |
| 3 | Variáveis indefinidas restantes | Remover riscos de execução e estabilizar o fluxo de análise. |
| 4 | Funções e classes ausentes | Completar contratos específicos e o `scanFiles`. |
| 5 | Propriedades e métodos incompatíveis | Consolidar a hierarquia tipada dos plugins. |
| 6 | Lote F — revisão controlada da baseline | Remover entradas corrigidas e classificar os diagnósticos restantes sem ocultar regressões. |
| 7 | Avaliação para nível 2 em diretórios modernizados | Elevar o nível somente onde o nível 1 estiver estável após dois ciclos consecutivos. |

## Regras de implementação

Cada commit deve tratar uma categoria ou um componente delimitado e referenciar a Issue #43. A baseline não deve ser ampliada para erros introduzidos por alterações novas. Stubs devem representar contratos reais e não substituir a correção de código executável. Correções de tipagem não devem ser misturadas com mudanças funcionais amplas, migração de SQL ou alterações de autenticação.

O CI deve continuar executando PHPUnit, o teste de compatibilidade PHP 8.3 e PHPStan no mesmo ambiente de PHP. A métrica de cada lote deve ser registrada pelo total de diagnósticos, pelos erros fora da baseline e pela quantidade de símbolos ausentes. A análise de nível 2 somente poderá começar depois de dois ciclos consecutivos sem aumento de erros fora da baseline nos diretórios modernizados.

## Evidências e referências

A contagem foi extraída do log da execução [PHP static analysis — execução 33580767192][4]. A compatibilidade de runtime foi validada separadamente pela execução [PHP 8.3 compatibility — execução 33580767190][3]. A configuração atual usa bootstrap, stubs, `scanFiles` e baseline conforme o arquivo `phpstan.neon.dist`.

[1]: https://phpstan.org/user-guide/discovering-symbols "PHPStan — Discovering Symbols"
[2]: https://phpstan.org/user-guide/config-reference "PHPStan — Configuration Reference"
[3]: https://github.com/leohmoraes/Prescia/actions/runs/33580767190 "Prescia — PHP 8.3 compatibility workflow run"
[4]: https://github.com/leohmoraes/Prescia/actions/runs/33580767192 "Prescia — PHP static analysis workflow run"
[5]: https://github.com/leohmoraes/Prescia/issues/43 "Prescia Issue #43 — Atualizar PHPStan e elevar gradualmente o nível de análise"
[6]: https://github.com/leohmoraes/Prescia/actions/runs/33582848768 "Prescia — PHP static analysis workflow run for baseline review"
[7]: https://github.com/leohmoraes/Prescia/actions/runs/33704528222 "Prescia — PHP static analysis workflow run after Fase F2 cycle two"

> Este documento define o próximo lote de execução. Ele não substitui o relatório do CI nem autoriza elevar o PHPStan ao nível 2 antes dos critérios de aceite serem atingidos.


### Fase F2 — ciclo atual: encapsulamento de propriedades administrativas

A execução do ciclo atual começou pela execução `34006340306`, associada ao commit `c8ec096`. O relatório confirmou quatro violações de visibilidade em payloads de `bi_adm`: três acessos protegidos a `mod_bi_adm::$hasStats` e um acesso privado a `$hasUndo`. O carregador confirma que os payloads são incluídos pelo próprio módulo administrativo, mas o acesso direto a propriedades privadas ou protegidas continua incompatível com o contrato de encapsulamento.

A correção aplicada substitui os acessos diretos por `hasStatsModule()` e `hasUndoModule()`, métodos públicos de leitura que preservam o estado interno das propriedades. A validação focalizada de `prescia/plugins/bi_adm/payload/content/default.php` e `prescia/plugins/bi_adm/payload/content/index.php` terminou sem erros no PHPStan, e os três arquivos modificados passaram no `php -l`.

| Métrica do ciclo | Resultado inicial | Resultado após o primeiro patch |
|---|---:|---:|
| Acessos `property.protected` tratados | 3 | 0 nos payloads focalizados |
| Acessos `property.private` tratados | 1 | 0 nos payloads focalizados |
| Propriedades tornadas públicas | 0 | 0 |
| Métodos públicos de leitura adicionados | 0 | 2 |
| Entradas novas na baseline | 0 | 0 |
| Regressões de sintaxe nos arquivos tratados | — | 0 |

O restante do relatório inclui diagnósticos independentes no próprio `module.php`, como variáveis indefinidas e uma função interna, que serão tratados em lotes separados da Fase F1 ou F3. Não serão adicionadas propriedades genéricas nem supressões à baseline para silenciar esses diagnósticos.


### Resultado da Fase F2 — ciclo de visibilidade administrativa

A execução [PHP static analysis — execução 34006455888][9], associada ao commit `d4d58ab`, não apresentou ocorrências de `property.private`, `property.protected` ou `property.notFound` no relatório disponível. A execução correspondente de compatibilidade PHP 8.3, [34006455901][10], terminou com sucesso.

O ciclo corrigiu quatro acessos diretos em payloads administrativos sem tornar propriedades internas públicas. A solução adicionou dois métodos públicos de leitura em `mod_bi_adm`, preservando `hasStats` como `protected` e `hasUndo` como `private`. A validação focalizada dos dois payloads modificados terminou com zero erros no PHPStan, e os arquivos alterados passaram na verificação sintática do PHP 8.3.

Com o conjunto de violações de visibilidade estabilizado, a próxima etapa é a **Fase F3 — métodos ausentes ou incompatíveis**. O primeiro inventário deve separar `method.notFound`, `method.private`, `method.protected` e `method.callable` de erros de funções globais ausentes. A análise deve partir do relatório do commit `d4d58ab` e manter a baseline vazia.

[9]: https://github.com/leohmoraes/Prescia/actions/runs/34006455888 "Prescia — PHP static analysis after F2 visibility correction"
[10]: https://github.com/leohmoraes/Prescia/actions/runs/34006455901 "Prescia — PHP 8.3 compatibility after F2 visibility correction"


### Fase F3 — primeiro ciclo: métodos do núcleo no payload bi_stats

O relatório da execução `34006455888` apresentou três diagnósticos `method.notFound` no arquivo `prescia/plugins/bi_stats/payload/content/module.php`: `loadAllModules()`, `loaded('STATSDAILY')` e `loaded($selmod)`. O carregador de `mod_bi_stats::onShow()` inclui o payload no contexto do módulo, mas o núcleo é disponibilizado como `$this->parent` e como `$core`.

A investigação confirmou que os métodos reais pertencem a `CPrescia`: `loadAllmodules()` e `loaded(string $moduleName, bool $noRaise = false)`. O primeiro patch adicionou o contrato de `$core` e transferiu as três chamadas para o objeto correto. O payload passou no PHPStan focalizado, no `php -l` e no `git diff --check`.

| Diagnóstico | Contexto incorreto | Correção |
|---|---|---|
| `mod_bi_stats::loadAllModules()` | Método chamado no módulo | `$core->loadAllmodules()` |
| `mod_bi_stats::loaded('STATSDAILY')` | Método chamado no módulo | `$core->loaded('STATSDAILY')` |
| `mod_bi_stats::loaded($selmod)` | Método chamado no módulo | `$core->loaded($selmod)` |

O próximo inventário F3 deve tratar métodos ausentes em `bi_adm/module.php` e payloads restantes, separando chamadas ao núcleo, chamadas ao módulo concreto e símbolos globais não encontrados. Funções locais, classes ausentes e constantes continuarão em lotes separados quando não forem consequência direta do contexto do método.


### Fase F3 — segundo ciclo: capitalização de método em bi_adm

O inventário do relatório associado ao commit `5f61356` não apresentou novos `method.notFound` em `bi_adm/module.php`, mas a revisão do call site identificou a chamada `loadAllModules()` com capitalização divergente da declaração real `CPrescia::loadAllmodules()`. Embora PHP trate nomes de métodos sem diferenciação de maiúsculas e minúsculas em runtime, a forma divergente prejudicava a análise estática e o contrato documentado.

A chamada foi normalizada para `$this->parent->loadAllmodules()`. A análise focalizada do arquivo ainda reporta diagnósticos independentes de `variable.undefined` e `function.inner`, mas não reporta `method.notFound`, `method.private`, `method.protected` ou `method.callable`. Esses diagnósticos restantes não serão misturados à Fase F3.


### Fase F4 — primeiro ciclo: classe dinâmica CDBO_0

O relatório da execução `34040207476` apresentou cinco ocorrências de `class.notFound`, incluindo quatro instanciações de `CDBO_0`. A origem foi confirmada em `index.php` e `prescia/index.php`: o nome da classe é montado dinamicamente a partir de `CONS_AFF_DATABASECONNECTOR`, e o driver legado correspondente não existe no checkout como `prescia/lib/dbo/0.php`. A classe-base real `CDBO` está em `prescia/lib/dbo/cdbo.php`, mas não deve ser executada pelo bootstrap do PHPStan.

A correção criou `tools/phpstan-dynamic-classes.php`, carregado por `scanFiles`, com um contrato explícito para `CDBO` e `CDBO_0`, incluindo o construtor de cinco parâmetros usado pelo runtime. O stub geral não contém mais essas classes, evitando conflito entre declarações. Após limpar o cache do PHPStan, a análise focalizada de `index.php` deixou de emitir `class.notFound` e `new.noConstructor` para `CDBO_0`; permanecem apenas caminhos de configuração ausentes e variáveis indefinidas já pertencentes a outros lotes.

| Métrica F4 | Antes | Após o patch |
|---|---:|---:|
| Diagnósticos `class.notFound` para `CDBO_0` | 4 | 0 |
| Diagnósticos `new.noConstructor` para `CDBO_0` | 4 | 0 |
| Arquivos de produção carregados no bootstrap | 0 | 0 |
| Novas entradas na baseline | 0 | 0 |

O próximo símbolo F4 deve ser selecionado pela origem: funções com implementação real devem ser adicionadas ao `scanFiles` ou bootstrap seguro; funções internas de payload não devem receber stubs genéricos sem antes avaliar sua refatoração para closure; classes opcionais devem receber contratos específicos.


### Fase F5 — primeiro ciclo: padrões regex configuráveis

O relatório da execução `34040430960` apresentou dois diagnósticos `regexp.pattern` em `prescia/lazyload/botprotect.php`. As constantes de blacklist e whitelist podem permanecer vazias na configuração, mas eram enviadas diretamente a `preg_match()`, criando padrões vazios inválidos para o PHP 8.3/PHPStan.

A correção normaliza cada configuração para um padrão local. Quando a lista está vazia, usa-se `/(?!)/`, uma expressão válida que nunca corresponde; quando existe configuração, o padrão original é preservado. A análise focalizada eliminou ambos os diagnósticos `regexp.pattern`. Permanece apenas o diagnóstico independente de contexto `$this` no mesmo arquivo, pertencente aos lotes de contratos/variáveis e não à Fase F5.

| Diagnóstico | Resultado |
|---|---:|
| `regexp.pattern` em blacklist | 0 após a correção |
| `regexp.pattern` em whitelist | 0 após a correção |
| Comportamento com padrão configurado | preservado |
| Comportamento com configuração vazia | padrão seguro sem correspondência |
| Novas entradas na baseline | 0 |

O próximo ciclo F5 deve tratar caminhos ausentes de forma conservadora: arquivos de configuração e drivers opcionais não devem ser inventados no runtime; bibliotecas legadas sem uso devem ser excluídas da análise por caminho documentado, enquanto includes necessários devem receber caminhos existentes e verificáveis.


### Correção adicional — funções locais recursivas em bi_cms

A execução `34040527721` ainda apresentava sete diagnósticos `function.notFound` e cinco `function.inner`. Quatro de `function.notFound` e duas ocorrências de `function.inner` vinham dos dois blocos recursivos `removeNull()` em `prescia/plugins/bi_cms/module.php`. Como essas funções eram declaradas dentro de métodos e só eram usadas pelo respectivo fluxo de construção da árvore CMS, a correção adequada não era um stub global.

Os dois blocos foram convertidos para closures recursivas locais com `use (&$removeNull)`. O PHPStan focalizado passou a reportar somente o diagnóstico de contexto `$this` já conhecido; não restaram ocorrências de `function.inner` ou `function.notFound` relacionadas a `removeNull`. A sintaxe PHP 8.3 também foi aprovada.

Essa redução será acompanhada no próximo workflow completo, pois a execução que originou o diagnóstico foi anterior ao patch. A baseline permanece inalterada.


### Lote de propriedades — driver CDBO_mysqli

O relatório `34040527721` concentrava aproximadamente 87 ocorrências de `property.notFound` em `prescia/lib/dbo/mysqli.php`. A revisão mostrou que os campos (`connection`, `log`, `debugmode`, `delayedconn`, `dbc`, `dbt`, `quickmode`, `errorRaised` e outros) já pertencem à classe-base `CDBO`; o problema era a ausência dos arquivos reais do driver no conjunto `scanFiles` do PHPStan.

A configuração agora descobre `prescia/lib/dbo/cdbo.php` e `prescia/lib/dbo/mysqli.php`. O contrato dinâmico `CDBO_0` continua separado em `tools/phpstan-dynamic-classes.php` e passa a estender a classe-base real, sem duplicar propriedades. A análise focalizada dos dois drivers terminou com `[OK] No errors`, eliminando os diagnósticos de propriedade desse arquivo. Nenhuma entrada foi adicionada à baseline.

O próximo lote deve repetir essa classificação para os demais arquivos com `property.notFound`, distinguindo propriedades herdadas que precisam de descoberta estática de propriedades realmente ausentes em módulos e payloads.


### Lote B — primeiro ciclo: contexto e paginação do bi_bb index

A verificação completa do commit `0237763` contabilizou 595 diagnósticos `variable.undefined`, com alta concentração nos payloads do `bi_bb`. O primeiro arquivo tratado foi `prescia/plugins/bi_bb/payload/content/index.php`, que apresentava referências indefinidas a `$this` e `$core` por depender de variáveis injetadas pelo include do módulo.

Foram adicionados contratos PHPDoc explícitos para `CPrescia $core` e `mod_bi_bb $this`, refletindo o contexto confirmado em `mod_bi_bb::onRender()`. A revisão também encontrou um acesso funcionalmente incorreto a `$this->templateParams['ipp']`: `templateParams` pertence ao núcleo e é preparado pelo motor de módulos. A referência foi corrigida para `$core->templateParams['ipp']`.

O PHPStan focalizado agora retorna `[OK] No errors` para o payload. Não foram adicionadas entradas à baseline e nenhuma variável global fictícia foi criada.


### Lote B — segundo ciclo: contrato do lazyload friendlyurl

Após o payload `bi_bb/index.php`, o ranking atualizado apontou `prescia/lazyload/friendlyurl.php` com 32 ocorrências. A investigação confirmou que o arquivo é incluído pelo método `CPrescia::friendlyurl($param)`, portanto `$this` representa o núcleo e `$param` é o array de opções fornecido pelo chamador.

Foram adicionados contratos PHPDoc para `CPrescia $this` e `array<string, mixed> $param`. A análise focalizada retornou `[OK] No errors`, eliminando todas as ocorrências `variable.undefined` do arquivo sem inicializar artificialmente opções obrigatórias ou alterar a lógica de consulta.


### Lote B — terceiro ciclo: contrato do lazyload fastclose

Após a correção de `friendlyurl.php`, o ranking do workflow `34041355257` apontou `prescia/lazyload/fastclose.php` com 31 referências indefinidas. O arquivo é incluído exclusivamente por `CPrescia::fastClose($action, $context)`, e as referências eram ao núcleo `$this` e ao parâmetro `$action` recebido pelo método.

Foram adicionados contratos PHPDoc para `CPrescia $this` e `string $action`. A análise focalizada terminou com `[OK] No errors`, sem inicializar estados de erro artificialmente e sem alterar o fluxo de encerramento rápido.


### Lote de caminhos — includes obrigatórios e integração CKEditor

A execução `34041574378` apresentava quatro diagnósticos `require.fileNotFound` nos entrypoints, referentes a `config/settings.php` e ao driver dinâmico `prescia/lib/dbo/0.php`, além de dois `includeOnce.fileNotFound` em `pages/_js/ckeditor/ckeditor.php` para integrações PHP legadas ausentes.

A correção não criou arquivos de produção fictícios. Nos dois entrypoints, os caminhos de configuração e driver agora são calculados em variáveis, verificados com `is_file()` e interrompem o bootstrap com `RuntimeException` descritiva quando a instalação não fornece uma dependência obrigatória. No CKEditor, a implementação é resolvida relativa a `__DIR__`, validada antes do include e também gera uma exceção explícita quando a integração não está instalada.

A análise focalizada dos três arquivos eliminou todos os diagnósticos de caminho ausente. Restaram somente sete referências a `$PAGE` em `index.php`, que pertencem ao próximo lote de variáveis de contexto. A baseline não foi alterada.


### Lote B — correção do buffer de saída PAGE

Após a remoção dos caminhos ausentes, o entrypoint ainda apresentava sete ocorrências `variable.undefined` para `$PAGE`. O valor é produzido por `renderCache()` ou `showTemplate()` quando a requisição não é de arquivo; porém, os hooks `onEcho`, o tratamento de saída inesperada, o honeypot, a compressão e o echo final ficam fora desse ramo.

A correção inicializa `$PAGE` como string vazia imediatamente antes do teste `servingFile`, no menor escopo comum a todos os consumidores. Os ramos de cache e template continuam substituindo esse valor pelo conteúdo real, enquanto o caminho de arquivo mantém um buffer definido antes do fechamento. O PHPStan focalizado de `index.php` agora retorna `[OK] No errors`, sem alteração da baseline.


### Lote B — agrupamento por diretório e primeiro arquivo bi_bb

A execução `34042207669` apresentou 494 diagnósticos `variable.undefined`. O agrupamento por diretório mostrou 255 ocorrências em `prescia/plugins`, 130 em `prescia/lazyload`, 40 em `pages/presciatester`, 32 em `pages/prescia`, 22 em `prescia/lib`, 9 em `pages/_newProjectTemplate`, 4 em `prescia/components` e 2 em `prescia/coreFull.php`.

O primeiro arquivo selecionado foi `prescia/plugins/bi_bb/payload/content/preview.php`, com 26 ocorrências. Todas eram referências ao `$core` injetado pelo include de `mod_bi_bb::onRender()`. Foram adicionados contratos PHPDoc para `CPrescia $core` e `mod_bi_bb $this`, sem alterar a lógica de preview ou inicializar estado artificial.

A análise PHPStan focalizada terminou com `[OK] No errors`; o arquivo também passou no PHP 8.3 syntax check. A baseline permanece inalterada.


### Lote B — segundo payload bi_bb: forum.php

O segundo arquivo priorizado em `prescia/plugins/bi_bb` foi `payload/content/forum.php`, com 23 diagnósticos `variable.undefined`. O carregador confirma que o arquivo é avaliado no contexto de `mod_bi_bb::onRender()`, recebendo `$core` como `CPrescia` e `$this` como `mod_bi_bb`. Foram adicionados contratos PHPDoc explícitos para ambos. Também foi inicializado `$sql` antes do `switch` de `operationmode`, preservando os três modos válidos e eliminando o diagnóstico de fluxo possivelmente não atribuído.

A análise PHPStan focalizada terminou com `[OK] No errors`; o arquivo também passou no PHP 8.3 syntax check. A baseline permanece inalterada.


### Lote B — terceiro payload bi_bb: profile.php

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/plugins/bi_bb/payload/content/profile.php`. O carregador `mod_bi_bb::onShow()` foi confirmado como origem do include, portanto `$core` recebeu o contrato PHPDoc `CPrescia`. A função `locateFile()` recebe `$ext` por referência; a variável foi inicializada no ramo autenticado imediatamente antes da chamada, preservando o contrato executável da função.

O PHPStan focalizado terminou com `[OK] No errors` e o arquivo passou no `php -l` do PHP 8.3. A baseline permaneceu inalterada. Com isso, `profile.php` deixa de ser pendência do lote bi_bb; o próximo alvo deve ser recalculado a partir de uma análise global atualizada.


### Lote B — lazy-load de scripts: script.php

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/lazyload/script.php`. O carregador `CPrescia::addScript(string $scriptname, array $parameters = [])` foi confirmado em `prescia/core.php`, estabelecendo `$this` como `CPrescia`, `$scriptname` como `string` e `$parameters` como `array<string, mixed>` no include.

Foram adicionados os três contratos PHPDoc correspondentes. A validação focalizada terminou com `[OK] No errors`, o `php -l` passou e `git diff --check` não encontrou problemas. A análise global caiu de 446 para 422 diagnósticos, redução de 24 ocorrências, sem alteração da baseline.


### Lote B — bi_adm/module.php: variáveis indefinidas

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/plugins/bi_adm/module.php` com foco exclusivo nas 20 ocorrências `variable.undefined`. O carregador `CPrescia::addPlugin()` foi confirmado como o contexto do include de nível superior, enquanto os métodos da classe permanecem no contexto de `mod_bi_adm`.

Foi adicionado o contrato PHPDoc de `CPrescia $this` para o código executado durante o carregamento. Os fluxos de `$mname`, `$sname` e `$id` receberam inicializações explícitas e seguras, preservando a lógica legada. A validação focalizada eliminou as 20 ocorrências `variable.undefined`; permanecem somente `function.inner` e `class.nameCase`, que serão tratados em lote estrutural separado. O arquivo passou no `php -l` e a baseline não foi alterada.


### Lote B — lazy-load de URL: udm.php

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/lazyload/udm.php`. O carregador `CPrescia::udm($param, $ignorePreVF)` foi confirmado em `prescia/core.php`, estabelecendo `$this` como `CPrescia`, `$param` como a lista de definições de despacho e `$ignorePreVF` como booleano.

Foram adicionados os contratos PHPDoc correspondentes. A validação focalizada terminou com `[OK] No errors`, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de 447 para 428 diagnósticos, redução de 19 ocorrências, sem alteração da baseline.


### Lote B — payload bi_stats: stats_ref.php

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/plugins/bi_stats/payload/content/stats_ref.php`. O carregador `mod_bi_stats::onShow()` foi confirmado como origem do include, com `$core` disponibilizado como contexto `CPrescia`.

Foi adicionado o contrato PHPDoc de `CPrescia $core`. A validação focalizada terminou com `[OK] No errors`, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de 428 para 409 diagnósticos, redução de 19 ocorrências, sem alteração da baseline.


### Lote B — payload bi_stats: stats_pathajax.php

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/plugins/bi_stats/payload/content/stats_pathajax.php`. O carregador `mod_bi_stats::onShow()` foi confirmado como origem do include, com `$core` disponibilizado como contexto `CPrescia`.

Foi adicionado o contrato PHPDoc de `CPrescia $core`. A validação focalizada terminou com `[OK] No errors`, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de 409 para 391 diagnósticos, redução de 18 ocorrências, sem alteração da baseline.


### Lote B — action presciatester: reset.php

A skill `phpstan-legacy-remediation` foi aplicada a `pages/presciatester/actions/reset.php`. O carregador de actions do núcleo foi confirmado em `CPrescia::checkActions()`, que inclui o arquivo no contexto do objeto `CPrescia`; portanto, as 17 ocorrências eram referências ao `$this` injetado.

Foi adicionado o contrato PHPDoc de `CPrescia $this`. A validação focalizada terminou com `[OK] No errors`, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de 391 para 374 diagnósticos, redução de 17 ocorrências, sem alteração da baseline.


### Lote B — payload bi_bb actions: default.php

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/plugins/bi_bb/payload/actions/default.php`. O carregador `mod_bi_bb::onCheckActions()` foi confirmado como origem do include, estabelecendo `$this` como `mod_bi_bb`.

Foi adicionado o contrato PHPDoc de `mod_bi_bb $this`. A validação focalizada terminou com `[OK] No errors`, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de 374 para 357 diagnósticos, redução de 17 ocorrências, sem alteração da baseline.


### Lote B — payload bi_bb content: default.php

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/plugins/bi_bb/payload/content/default.php`. O carregador `mod_bi_bb::onRender()` foi confirmado como origem do include, estabelecendo `$core` como `CPrescia` e `$this` como `mod_bi_bb`.

Foram adicionados os contratos PHPDoc dos dois contextos. A validação focalizada terminou com `[OK] No errors`, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de 357 para 340 diagnósticos, redução de 17 ocorrências, sem alteração da baseline.


### Lote B — lazy-load de e-mail: prepareMail.php

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/lazyload/prepareMail.php`. O carregador `CPrescia::prepareMail($name, $fillArray)` foi confirmado em `prescia/core.php`, estabelecendo `$this` como `CPrescia`, `$name` como string e `$fillArray` como array de preenchimento.

Foram adicionados os contratos PHPDoc correspondentes e o buffer `$template` foi inicializado antes dos ramos de template de e-mail, preservando o comportamento quando `automail.html` não existe. A validação focalizada terminou com `[OK] No errors`, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de 340 para 325 diagnósticos, redução de 15 ocorrências, sem alteração da baseline.


### Lote B — lazy-load de arquivos: readfile.php

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/lazyload/readfile.php`. O carregador `CPrescia::readfile($file, $ext, $exit, $filename, $forceAttach, $cachetime)` foi confirmado em `prescia/core.php`, estabelecendo `$this` como `CPrescia` e os parâmetros com seus contratos concretos.

Foram adicionados os contratos PHPDoc de `$this`, `$file`, `$ext`, `$exit`, `$filename`, `$forceAttach` e `$cachetime`. A validação focalizada terminou com `[OK] No errors`, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de 325 para 311 diagnósticos, redução de 14 ocorrências, sem alteração da baseline.


### Lote B — configuração da página prescia: config.php

A skill `phpstan-legacy-remediation` foi aplicada a `pages/prescia/_config/config.php`. O carregamento foi confirmado em `CPrescia::loadDomain()`, que requer o arquivo de configuração do site no contexto do objeto `CPrescia`.

Foi adicionado o contrato PHPDoc de `CPrescia $this`. A validação focalizada terminou com `[OK] No errors`, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de 311 para 299 diagnósticos, redução de 12 ocorrências, sem alteração da baseline.


### Lote B — lazy-load de consulta AJAX: ajaxQuery.php

A skill `phpstan-legacy-remediation` foi aplicada a `prescia/lazyload/ajaxQuery.php`. O carregador foi confirmado em `CPrescia::checkActions()`, que inclui o arquivo para a ação `ajaxquery` no contexto do objeto `CPrescia`.

Foi adicionado o contrato PHPDoc de `CPrescia $this`. A validação focalizada terminou com `[OK] No errors`, o `php -l` passou e `git diff --check` foi aprovado. A análise global atual caiu de 299 para 287 diagnósticos, redução de 12 ocorrências, sem alteração da baseline.


### Marco de acompanhamento — commit `eb1cc26`

A análise global mais recente reportou **287 diagnósticos**, uma redução de 12 em relação aos 299 anteriores. A redução veio da correção de `prescia/lazyload/ajaxQuery.php`, que agora passa no PHPStan focalizado sem erros. Os arquivos recentes `pages/prescia/_config/config.php` e `prescia/lazyload/ajaxQuery.php` também passaram na sintaxe PHP 8.3 e no `git diff --check`.

A análise completa ainda falha por pendências fora dos arquivos corrigidos. O agrupamento estrutural de `tools/phpstan-framework-stubs.php`, com 46 `return.missing`, permanece separado do lote de variáveis indefinidas. Nenhuma entrada foi adicionada à baseline.


## Lote estrutural — análise dos 46 `return.missing` em `tools/phpstan-framework-stubs.php`

A análise do relatório global do PHPStan identificou **46 diagnósticos `return.missing`** no arquivo de stubs. Esses diagnósticos não representam 46 caminhos de produção: todos vêm de declarações com tipo de retorno não `void` e corpo vazio (`{}`). O arquivo é referenciado exclusivamente por `stubFiles` em `phpstan.neon.dist` e não é carregado pela aplicação, portanto os corpos de retorno podem ser completados com valores sintéticos compatíveis sem introduzir efeitos colaterais em runtime.

### Composição confirmada

| Grupo | Quantidade | Linhas | Situação |
|---|---:|---|---|
| Funções da API principal | 34 | 11–52 | Retornos tipados, mas corpos vazios |
| `CPrescia::saveConfig()` | 1 | 59 | Método `mixed` sem retorno |
| Funções legadas adicionais | 11 | 194–204 | Retornos `mixed` sem retorno |
| Funções `void` | 3 | 15, 21 e 39 | Não geram `return.missing` e devem permanecer sem retorno |
| **Total** | **46** | — | Diagnósticos confirmados |

As três funções `void` — `dieFreakingThumbs()`, `adodb_daylight_sv()` e `removeBOM()` — não devem receber retornos artificiais. O foco é corrigir somente os 46 contratos não `void`.

### Plano de correção por ondas

| Onda | Escopo | Correção prevista | Critério de aceite |
|---:|---|---|---|
| 1 | Funções com retorno escalar | Adicionar valores neutros compatíveis: `''` para `string`, `0` para `int`, `0.0` para `float`, `false` quando o contrato real permitir ou um valor escalar representativo | Nenhum `return.missing`; nenhum `return.type` novo |
| 2 | Funções com retorno `array` ou união | Usar `[]` para arrays e escolher um valor compatível para `array|string`, preservando o tipo declarado e a inferência esperada pelos consumidores | Retorno aceito pelo PHPStan e sem alteração das assinaturas |
| 3 | Funções e métodos `mixed` | Usar `null` como sentinel explícito, sem inventar objetos, conexões, arquivos ou dados de produção | Nenhum efeito colateral e nenhum diagnóstico de tipo novo |
| 4 | Validação do stub no conjunto | Reexecutar PHPStan global e focalizar os arquivos que dependem de `arrayToString()`, `listFiles()`, `xmlParamsParser()`, `saveConfig()` e funções legadas | Redução exata de 46 diagnósticos; baseline sem novas entradas |

### Mapeamento de retornos sintéticos

Na primeira onda, as funções escalares deverão receber retornos compatíveis com suas assinaturas: `addslashes_EX()`, `arrayToString()`, `cWriteFile()`, `cleanString()`, `extractUri()`, `fd()`, `fv()`, `getmicrotime()`, `htmlentities_ex()`, `humanSize()`, `isMail()`, `listFiles()`, `makeDirs()`, `recursive_del()`, `removeSimbols()`, `stripHTML()`, `tomktime()` e `truncate()` devem retornar valores neutros do tipo declarado. `datecalc()`, `datecompare()`, `console()`, `cropImage()`, `locateAnyFile()`, `locateFile()`, `parseHTML()`, `quota()`, `resizeImage()`, `resizeImageCond()`, `scriptTime()`, `sendMail()`, `storeFile()` e `time_diff()` devem usar sentinelas compatíveis com `mixed` ou com o tipo escalar declarado. `xmlParamsParser()` deve retornar uma estrutura vazia compatível com `array|string`, preferencialmente `[]` para preservar a forma de parser. `CPrescia::saveConfig()` e as 11 funções legadas adicionais, todas com retorno `mixed`, devem retornar `null` explicitamente.

### Restrições

Não alterar `phpstan-baseline.neon`, não converter os stubs em implementações de produção, não executar filesystem, banco de dados, e-mail ou imagem, e não mudar assinaturas públicas apenas para silenciar o diagnóstico. Após a correção, comparar a análise global com o marco de **287 diagnósticos** do commit `eb1cc26`; a expectativa é reduzir esse número em 46, desde que nenhum diagnóstico dependente seja reclassificado.

### Sequência operacional

1. Criar um commit isolado apenas para completar os retornos dos stubs.
2. Validar a sintaxe do stub e a análise focalizada por configuração equivalente, sem carregá-lo como código de aplicação.
3. Executar o PHPStan global e registrar a contagem antes/depois por identificador.
4. Executar a suíte de compatibilidade PHP 8.3, pois o stub participa da configuração do CI.
5. Atualizar o relatório consolidado, o `CHANGELOG.md` e a Issue #44 com a redução comprovada.
6. Publicar o commit e acompanhar separadamente os workflows de PHPStan e PHP 8.3.


## Lote adicional — segurança, compatibilidade PHP 8.3 e SQL parametrizado

O commit `1127c0e` entregou um lote transversal solicitado pelas issues abertas do repositório. A implementação foi aplicada sem ampliar a baseline: CSRF passou a cobrir métodos mutáveis e rotação de token; cookies de sessão, autenticação persistente e logout receberam flags e invalidação adequadas; a ativação de contas usa aleatoriedade criptográfica; uploads e desserialização foram restringidos; e as consultas concatenadas identificadas em estatísticas e administração foram convertidas para prepared statements.

O lote também corrigiu variáveis indefinidas no núcleo e no módulo de autenticação, colocou o ownership dentro do escopo correto de `onMeta()`, e removeu warnings de `continue` ambíguo em `switch` nos payloads administrativos. A validação terminou com **23 testes e 2745 asserções aprovados**, lint PHP global sem warnings, PHPStan focalizado aprovado, `composer validate` aprovado e `composer audit` sem advisories. A análise global de nível 1 caiu para **219 diagnósticos**, ainda não sendo verde por pendências legadas fora do escopo tratado.

O próximo lote deve partir de um novo inventário desses 219 registros, priorizando variáveis indefinidas restantes e payloads com contexto dinâmico. Nenhuma nova entrada de `ignoreErrors` deve ser adicionada à baseline para mascarar esses diagnósticos.


## Lote concluído — contexto de `tcaptcha.php`

O primeiro alvo do inventário atual foi `prescia/lazyload/tcaptcha.php`. Os 12 diagnósticos eram exclusivamente de contexto procedural: `$this`, `$key` e `$checkStage` pertencem ao método `CPrescia::tCaptcha(string $key, bool $checkStage)`, que inclui o arquivo por `return include(CONS_PATH_SYSTEM."lazyload/tcaptcha.php")`.

A correção adicionou somente os contratos PHPDoc comprovados pelo call site. O PHPStan focalizado passou, a sintaxe PHP 8.3 passou, o PHPUnit permaneceu com 23 testes e 2745 asserções, e o inventário global caiu de 219 para 207 diagnósticos, em 53 arquivos. A baseline não foi expandida.

O próximo ciclo deve seguir o mesmo padrão: confirmar o carregador real, separar contratos de contexto de variáveis locais, validar focalizadamente e só então trabalhar em `datetime.php`, `bi_labels/payload/actions/config_labels.php` e `pages/presciatester/_config/config.php`.


## Resultado do primeiro lote da Issue #47

O primeiro lote tratou `datetime.php`, `bi_labels/payload/actions/config_labels.php` e `pages/presciatester/_config/config.php`. A abordagem confirmou os call sites antes de aplicar PHPDoc e inicializações locais: o payload de labels recebe `$core` de `mod_bi_labels::onCheckActions()`, a configuração recebe `$this` de `CPrescia::domainLoad()`, e os estados de data pertencem aos fluxos internos de `_adodb_getdate()`/`adodb_mktime()`.

Os três arquivos passaram no PHPStan focalizado e no lint PHP 8.3. PHPUnit permaneceu aprovado com 23 testes e 2745 asserções. A análise global caiu de 207 para 172 diagnósticos, em 50 arquivos, sem ampliar a baseline.

O próximo lote deve priorizar `prescia/lazyload/rss.php` e `prescia/plugins/bi_adm/payload/actions/import.php`, ambos com 10 diagnósticos, confirmando separadamente os contextos dinâmicos, os símbolos estruturais e os fluxos de variáveis locais antes de modificar o código.


## Resultado do segundo lote da Issue #47

O segundo lote tratou `prescia/lazyload/rss.php` e `prescia/plugins/bi_adm/payload/actions/import.php`. Os contratos foram confirmados pelos carregadores reais: `rss.php` é incluído por `CPrescia::rss()`, enquanto `import.php` é um payload de `mod_bi_adm` com `$core` injetado e carrega a classe global `CImporter` de `payload/importer.php`.

Além dos contratos PHPDoc, foram corrigidos três problemas concretos de fluxo: a validação de listas do RSS usava `&&` e uma variável inexistente; o importador instanciava `Cimporter` com capitalização diferente da classe real; e estados consumidos após ramos condicionais não tinham fallback. Também foi corrigido o índice de enumeração e o operador de atribuição do valor `n`.

Os dois arquivos passaram no PHPStan focalizado e no lint PHP 8.3. PHPUnit permaneceu aprovado com 23 testes e 2745 asserções. A análise global caiu de 172 para 152 diagnósticos, em 48 arquivos, sem ampliar a baseline.

O próximo ciclo deve começar por `prescia/plugins/bi_labels/payload/content/config_labels.php` (9 diagnósticos) e `prescia/plugins/bi_fm/payload/actions/affbi_fmset.php` (8), mantendo a separação entre contratos de payload e correções de lógica comprovadas.


## Resultado do terceiro lote da Issue #47

O terceiro lote tratou `prescia/plugins/bi_labels/payload/content/config_labels.php` e `prescia/plugins/bi_fm/payload/actions/affbi_fmset.php`. A origem de `$core` foi comprovada nos carregadores: `mod_bi_labels::onShow()` e `mod_bi_fm::onCheckActions()` atribuem `$core` por referência a `$this->parent` antes do include.

As duas correções adicionaram somente PHPDoc com o tipo concreto `CPrescia`, eliminando os 17 diagnósticos de contexto sem alterar o comportamento dos payloads. PHPStan focalizado, lint PHP 8.3, `git diff --check` e PHPUnit passaram. A análise global caiu de 152 para 135 diagnósticos, em 46 arquivos, sem ampliar a baseline.

O próximo lote deve selecionar entre os grupos de 7 diagnósticos: `pages/_newProjectTemplate/_config/config.php`, `pages/prescia/actions/contatogo.php`, `pages/presciatester/content/default.php`, `prescia/lib/sendMail.php` e `prescia/plugins/bi_undo/module.php`. A prioridade deve continuar sendo confirmar o carregador real antes de documentar contratos ou inicializar variáveis.


## Resultado do quarto lote da Issue #47

O quarto lote tratou `pages/_newProjectTemplate/_config/config.php`, `pages/prescia/actions/contatogo.php` e `pages/presciatester/content/default.php`. Os carregadores confirmaram `$this` como `CPrescia` em todos os três fluxos: configuração de domínio, ação de página e conteúdo default renderizado.

A correção adicionou somente contratos PHPDoc concretos para `$this`, eliminando os 21 diagnósticos de contexto sem alterar a lógica de configuração, CAPTCHA, contato, template ou renderização. PHPStan focalizado, lint PHP 8.3, `git diff --check` e PHPUnit passaram. A análise global caiu de 135 para 114 diagnósticos, em 43 arquivos, sem ampliar a baseline.

O próximo lote deve priorizar `prescia/lib/sendMail.php` e `prescia/plugins/bi_undo/module.php`, ambos com 7 diagnósticos, confirmando se os contextos são funções procedurais, métodos de classe ou includes dinâmicos antes da correção.


## Resultado do quinto lote da Issue #47

O quinto lote tratou `prescia/lib/sendMail.php` e `prescia/plugins/bi_undo/module.php`. Em `sendMail.php`, os delimitadores MIME foram inicializados no escopo comum antes dos ramos que os consomem. Em `bi_undo`, o carregamento do plugin foi documentado com o contexto `CPrescia`, a contagem `$n` recebeu fallback antes da consulta e as chaves `$keys` foram inicializadas antes dos loops de composição.

As correções eliminaram 14 diagnósticos sem supressões, casts artificiais ou crescimento da baseline. PHPStan focalizado, lint PHP 8.3, `git diff --check` e PHPUnit passaram. A análise global caiu de 114 para 100 diagnósticos, em 41 arquivos.

O próximo lote deve tratar `prescia/lazyload/feedReader.php`, `prescia/lazyload/fullSearch.php` e `prescia/plugins/bi_labels/payload/actions/config_labels_m.php`, com 6 diagnósticos cada. Esses arquivos devem ser classificados por contrato de include antes de aplicar PHPDoc ou inicializações de fluxo.

### Sexto lote executado — includes de lazyload e labels

O sexto lote da Issue #47 confirmou e documentou os contratos de entrada de `feedReader.php`, `fullSearch.php` e `config_labels_m.php`. Os dois primeiros recebem o contexto `$this` de `CPrescia` por meio dos métodos homônimos do núcleo; o terceiro recebe `$core` como `CPrescia` durante a execução da ação do plugin de labels. A correção foi restrita a PHPDoc, pois os parâmetros já são fornecidos pelas assinaturas dos métodos e pelo carregador do módulo.

O PHPStan focalizado e o lint PHP 8.3 passaram nos três arquivos. A análise global caiu de 100 para 82 diagnósticos, sem qualquer alteração em `phpstan-baseline.neon`. A distribuição atual é 75 `variable.undefined`, 2 `class.nameCase`, 2 `function.inner`, 2 `function.notFound` e 1 `isset.variable`. O próximo lote deve continuar a correção de `variable.undefined` por fluxo, sem misturar símbolos ausentes ou adicionar contratos artificiais.


### Inventário operacional dos 82 diagnósticos remanescentes

O inventário global mais recente registra 82 ocorrências em 39 arquivos: 75 `variable.undefined`, 2 `class.nameCase`, 2 `function.inner`, 2 `function.notFound` e 1 `isset.variable`. Entre as variáveis indefinidas, `$this` responde por 37 ocorrências, `$core` por 13 e `$sname` por 8. Esses três grupos devem ser investigados separadamente porque os dois primeiros representam contratos de includes dinâmicos, enquanto `$sname` aparece nos manifests e pode ser parâmetro ou estado local do carregador.

A ordem prática recomendada é: (1) contratos de `$this` e `$core`; (2) investigação dos quatro `payloadmanifest.php` que usam `$sname`; (3) arquivos com cinco ocorrências — `label_test.php`, `ajaxqueryunique.php`, `coreFull.php`, `pages/presciatester/actions/default.php` e `pages/prescia/content/default.php`; (4) correção de `Cimporter` para `CImporter`; (5) conversão das funções internas em `coreFull.php` e `bi_dev/module.php` para closures; (6) confirmação da origem de `dieFreakingThumbs()`; e (7) revisão do `isset($_POST)` em `cacheControl.php`.

Cada sublote deve registrar o arquivo, a variável ou símbolo, a origem real no carregador, a correção aplicada e a validação focalizada. Não devem ser usados defaults artificiais, stubs genéricos ou novas entradas na baseline sem comprovação do contrato de runtime.

### Sub lote prioritário — módulos BI com `$this`

As seis ocorrências de `$this` nos módulos BI devem ser resolvidas em conjunto: duas em `bi_bb/module.php` e uma em cada módulo `bi_cms`, `bi_groups`, `bi_seo` e `bi_stats`. O carregador `CPrescia::addPlugin()` inclui o arquivo antes de instanciar a classe `mod_*`, de modo que o contrato deve declarar `CPrescia $this` no nível superior. Não se deve declarar o `$this` como o módulo concreto nessa região nem mover o include para dentro da classe.

### Resultado do sub lote dos módulos BI

A correção dos cinco módulos BI foi concluída sem alterar o fluxo de carregamento. O PHPStan focalizado passou sem erros, o PHPUnit permaneceu com 23 testes e 2745 asserções aprovadas e o inventário global caiu de 82 para 75 diagnósticos. A baseline não foi alterada. O diagnóstico residual de `$frame` em `bi_bb/module.php` também foi resolvido por inicialização no menor escopo comum.

### Resultado do sétimo lote

O sétimo lote confirmou dois contratos de contexto: `$core` como `CPrescia` no payload `bi_labels/label_test.php` e `$this` como `CPrescia` no lazyload `ajaxqueryunique.php`. A correção eliminou 10 diagnósticos, reduziu o inventário global de 75 para 65 ocorrências e manteve a baseline sem novas entradas. O próximo agrupamento deve priorizar `coreFull.php`, `stats_rtajax.php` e os manifests que ainda usam `$sname`.

### Resultado do oitavo lote

Os quatro manifests BI foram tratados com o contrato `string $sname`, comprovado pelo loop de `CPrescia::applyMetaData()` que inclui cada manifest. O lote removeu sete ocorrências, reduziu o inventário global de 65 para 58 diagnósticos e manteve a baseline sem alterações. O único manifest ainda pendente é `bi_adm/payloadmanifest.php`, que deve ser tratado com os demais arquivos de contexto administrativo.

### Resultado do nono lote

O nono lote removeu os quatro diagnósticos de `$core` em `stats_rtajax.php` e o diagnóstico de `$sname` no manifest de `bi_adm`. Os carregadores foram confirmados antes da alteração, o PHPStan focalizado e o lint PHP 8.3 passaram, e o PHPUnit permaneceu aprovado. O inventário global caiu de 58 para 53 ocorrências, sem alteração da baseline. O próximo foco deve ser `coreFull.php`, que reúne os diagnósticos estruturais de funções internas e símbolo ausente.

### Resultado do décimo lote

`coreFull.php` foi corrigido sem ampliar a baseline. O lote separou uma referência incorreta de variável, inicializou o acumulador de permissões e substituiu a função interna recursiva por closure. A validação focalizada, o lint PHP 8.3 e o PHPUnit passaram; o inventário global caiu de 53 para 48 diagnósticos. O próximo lote deve continuar pelos arquivos com maior concentração de variáveis indefinidas, mantendo funções internas e símbolos ausentes separados quando não forem consequência direta do mesmo fluxo.

### Resultado do décimo primeiro lote

Os payloads default da ação do `presciatester` e do conteúdo da página `prescia` receberam contratos `CPrescia $this` após confirmação dos métodos `checkActions()` e `renderPage()`. O lote eliminou 10 diagnósticos, reduziu o inventário global de 48 para 38 ocorrências e manteve a baseline sem alterações. O próximo foco deve ser `pages/prescia/content/resources/reference.php`, `prescia/lazyload/cls.php` e `prescia/lib/ttree.php`, agrupando arquivos somente quando seus carregadores forem compatíveis.

### Resultado do décimo segundo lote

Os contratos de `reference.php` e `cls.php` foram confirmados pelos carregadores de página e seleção de domínio. O diagnóstico local de `$tag` em `ttree::echoHTML()` foi resolvido por inicialização no escopo comum. Como o seletor CLS usa o lazyload `builddomains()` dentro do include, a visibilidade do método foi ajustada de privada para pública, preservando a API efetivamente usada pelo framework. O lote reduziu o inventário de 38 para 28 diagnósticos, com PHPStan focalizado, lint e PHPUnit aprovados e baseline inalterada.

### Resultado do décimo terceiro lote

O lote administrativo eliminou 13 diagnósticos sem baseline: fluxos condicionais agora têm valores iniciais, nomes de variáveis e classes foram corrigidos, e o retorno do caso inválido em `laedit.php` ficou explícito. Os nove payloads passaram no PHPStan focalizado e no lint PHP 8.3, e o PHPUnit permaneceu aprovado. O inventário global caiu de 28 para 15 diagnósticos, concentrados agora em dez arquivos menores.

### Resultado do décimo quarto lote — inventário zerado

O último lote eliminou os cinco diagnósticos finais do PHPStan: a verificação redundante de `$_POST`, dois caminhos de cache não inicializados, o acumulador de permissões e a função interna de `bi_dev`. Somados aos ajustes de `options.php` e dos payloads de página/lazyload, o inventário global passou de 15 para **0 diagnósticos**. O PHPStan global, o PHPStan focalizado, o lint PHP 8.3 e o PHPUnit estão aprovados, e a baseline permaneceu inalterada.

### Verificação pós-merge da PR #58

A validação pós-merge confirmou dependências válidas, PHPUnit aprovado, PHPStan global zerado e lint PHP 8.3 aprovado em 348 arquivos. A baseline não foi alterada. Com a remediação PHPStan concluída, a próxima prioridade técnica deve retornar às issues de segurança abertas, começando pela conclusão dos prepared statements no CRUD genérico (`#28`, relacionada à #9).

### Frente de segurança iniciada: issue #28

Com o inventário PHPStan zerado, a execução autônoma retomou a issue de segurança de maior prioridade técnica: prepared statements no CRUD genérico. O primeiro lote cobre `UPDATE`, `DELETE`, rollback de uploads, flags de upload, URLs geradas e `MAX(id)` de chaves compostas. A continuação deve revisar todos os pontos de `simpleQuery()` em `CModule`, adicionar testes de regressão de SQL parametrizado e coordenar o fechamento das issues relacionadas #9, #28, #29 e #30.

### Segundo lote de segurança concluído

`autoPrune()` e a verificação de ciclos parentais agora não interpolam valores fornecidos por dados de módulo nas consultas. O próximo lote deve revisar `deleteAllFrom()` no núcleo e os consumidores de SQL dinâmico em plugins, adicionando testes de regressão específicos para valores contendo aspas e caracteres de controle.

### Terceiro lote de segurança concluído

`deleteAllFrom()` deixou de interpolar valores de chaves em operações de atualização e seleção. A sequência de cascata continua delegando a exclusão final a `runAction()`, preservando notificações e regras existentes. O próximo foco deve ser a cobertura de regressão para os valores parametrizados e a revisão dos SQLs dinâmicos restantes nos plugins.

### Quarto lote de segurança concluído

O plugin `bi_groups` não concatena mais IDs recebidos nas verificações de autorização de atualização e exclusão. O inventário de SQL dinâmico continua sendo revisado por consumidor, com prioridade para autenticação, administração e estatísticas.

### Compatibilidade PHP 8.3–8.5 corrigida

A matriz de CI agora possui um contrato de teste coerente com o próprio workflow: versões PHP 8.3, 8.4 e 8.5 são aceitas, sem relaxar para PHP 9 ou versões abaixo de 8.3. O próximo passo da Issue #202 é aguardar/reexecutar a matriz remota e registrar os resultados dos scanners e do build Docker.

### PHPStan PHP 8.4/8.5 corrigido

O fechamento do template foi ajustado de `unset($this->template)` para atribuição de `null`, eliminando a incompatibilidade de análise relacionada a propriedades potencialmente com hooks. A matriz remota deve ser reexecutada para confirmar PHP 8.3, 8.4 e 8.5, além do build Docker/Trivy no job PHP 8.3.

### Casts PHP 8.5 corrigidos

Os dois casts `(integer)` remanescentes no cálculo de anos ADODB foram normalizados para `(int)`, eliminando a deprecation específica do PHP 8.5. A nova execução da matriz deve concluir os três jobs e liberar o fechamento formal da Issue #202.
