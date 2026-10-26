# Plano de ação para promoção ao PHPStan nível 4

**Projeto:** Prescia  
**Objetivo:** elevar a análise oficial do PHPStan do nível 3 para o nível 4 sem ampliar a baseline e sem introduzir regressões de runtime.  
**Estado de partida:** PHPStan 2.2.13, PHP 8.3.6, configuração oficial no nível 3, branch `fix/phpstan-level4-front-controllers`, commit `f04bacb`.  
**Issue de referência:** [#43 — Atualizar o PHPStan e elevar gradualmente o nível de análise][1]

## 1. Decisão executiva

A promoção deve ocorrer somente depois de uma sequência de lotes focalizados reduzir a análise exploratória de nível 4 a zero diagnósticos. A análise exploratória de nível 5 executada em 2026-09-14 encontrou 458 diagnósticos em 78 arquivos e 38 identificadores. Esse resultado não autoriza promover diretamente para o nível 5, mas fornece um mapa útil dos falsos positivos e dos contratos que precisam ser corrigidos antes da estabilização do nível 4.

O nível oficial permanecerá em 3 durante a remediação. Cada lote deverá ser analisado com `--level=4`, validado focalmente, integrado à análise global e documentado antes do próximo lote. A configuração oficial só será alterada no gate final, quando a execução global de nível 4 retornar zero erros de arquivo em dois ciclos consecutivos.

> **Regra central:** não adicionar entradas à `phpstan-baseline.neon` para fazer o nível 4 passar. A baseline não pode ocultar regressões, falhas de segurança, problemas de compatibilidade PHP 8.3 ou diagnósticos introduzidos pelo próprio lote.

## 2. Estado inicial e critérios de medição

| Item | Estado atual | Evidência |
|---|---:|---|
| Nível oficial | 3 | `phpstan.neon.dist` |
| Diagnósticos globais no nível 3 | 0 | Última validação global aprovada |
| Diagnósticos exploratórios no nível 5 | 458 | `file_errors=458` no JSON |
| Arquivos afetados no nível 5 | 78 | Contagem do JSON por arquivo |
| Identificadores no nível 5 | 38 | Agrupamento do JSON |
| Baseline | Sem novas entradas | `phpstan-baseline.neon` |
| Testes | 144 testes, 3.093 asserções, 2 skips | PHPUnit em PHP 8.3.6 |
| Front controllers no nível 4 | 0 diagnósticos | `index.php` e `prescia/index.php` |

Os números devem ser comparados por `file_errors`, por identificador, por arquivo e por linha. O campo `totals.errors` do formatter JSON não deve ser usado isoladamente, pois a execução observada reportou `errors: 0` e `file_errors: 458` enquanto os diagnósticos estavam presentes em `.files[].messages[]`.

## 3. Diagnósticos mais comuns esperados

Os diagnósticos abaixo são os mais prováveis durante a promoção do nível 3 para o nível 4, com base na análise exploratória de nível 5 e no histórico dos Lotes 1–3. Eles devem ser tratados como sinais para investigação do contrato real, e não como autorização automática para remover condições ou adicionar supressões.

| Categoria | Origem provável no Prescia | Risco principal | Tratamento recomendado |
|---|---|---|---|
| `smaller.alwaysFalse`, `greater.alwaysFalse` | Contadores, limites e resultados de consultas estreitados pelo analisador | Remoção indevida de caminhos válidos | Confirmar intervalos e preservar `false`, `null`, zero e listas vazias |
| `booleanAnd.alwaysFalse`, `booleanOr.alwaysTrue` | Guards de configuração, autenticação e payloads dinâmicos | Desativação acidental de validações | Revisar o fluxo real antes de alterar a condição |
| `equal.alwaysTrue`, `equal.alwaysFalse`, `notEqual.*` | Constantes literais, coerção de tipos e valores sentinela | Código morto aparente ou comparação semanticamente incorreta | Corrigir o contrato de origem e preferir comparações explícitas |
| `argument.type` | `mail()`, `number_format()`, `str_replace()` e chamadas com entrada externa | Falha em runtime ou normalização incompleta | Validar e normalizar na origem; evitar casts indiscriminados |
| `deadCode.unreachable` | `die()`, `exit()`, retornos estreitos e includes dinâmicos | Remoção de fluxo legítimo do framework | Confirmar carregadores e caminhos de exceção antes de remover código |
| `isset.offset`, `nullCoalesce.offset`, `foreach.emptyArray` | Arrays de formulário, sessão, consulta, JSON/XML e payloads | Avisos, chaves ausentes ou contratos de array incorretos | Documentar a forma real e normalizar no limite do payload |
| `argument.byRef` | Construtores e funções que recebem variáveis por referência | Erro de chamada com expressão temporária | Passar uma variável real e preservar o contrato de referência |
| Contexto de `$this` e `$core` | Includes procedurais dentro de `CPrescia` e módulos concretos | Métodos ou propriedades atribuídos ao objeto errado | Confirmar o include e documentar o tipo concreto sem criar globais artificiais |

As prioridades são: primeiro, contratos dinâmicos e fluxos de autenticação, autorização, SQL e e-mail; depois, arrays e resultados de consultas; por fim, comparações cosméticas e resíduos de módulos. O nível 4 não deve ser promovido enquanto esses diagnósticos estiverem apenas ocultos por `mixed`, `@phpstan-ignore` ou novas entradas na baseline.

## 4. Escopo técnico do nível 4

O nível 4 deverá ser tratado em cinco eixos. A ordem prioriza contratos que causam muitos diagnósticos derivados e riscos de comportamento executável.

| Ordem | Eixo | Alvos principais | Resultado esperado |
|---:|---|---|---|
| 1 | Contratos dinâmicos e constantes | `bi_stats`, `core.php`, módulos e payloads incluídos | Remover estreitamentos artificiais sem mascarar guards reais |
| 2 | Tipos de argumentos e retornos | `argument.type`, `argument.byRef`, chamadas de `mail`, `number_format`, `str_replace` | Chamadas compatíveis com os contratos nativos e locais |
| 3 | Fluxos impossíveis ou código morto | `smaller.alwaysFalse`, `greater.alwaysFalse`, `equal.alwaysTrue`, `booleanAnd.alwaysFalse`, `deadCode.unreachable` | Confirmar o fluxo real; corrigir código ou o contrato que está estreito |
| 4 | Arrays e offsets | `isset.offset`, `nullCoalesce.offset`, `foreach.emptyArray` | Normalizar formas de arrays e preservar entradas opcionais |
| 5 | Resíduos estruturais | diagnósticos restantes por arquivo e identificador | Zerar o nível 4 sem baseline nova |

## 4. Lotes de execução

### Lote 4.1 — Inventário reproduzível e contratos de configuração

**Objetivo:** estabelecer uma fotografia completa do nível 4 e separar falsos positivos causados por bootstrap, constantes dinâmicas e contexto de payload dos problemas executáveis.

**Ações:**

1. Reexecutar PHPStan nível 4 no commit de referência e salvar JSON bruto.
2. Gerar tabelas por identificador, arquivo, linha e mensagem.
3. Comparar os diagnósticos dos front controllers com os já resolvidos por `dynamicConstantNames`.
4. Revisar os contratos de `bi_stats` e `core.php`, que concentraram 56 e 31 diagnósticos no nível 5.
5. Confirmar cada constante ou valor de configuração com o carregador real antes de ajustar PHPDoc ou `dynamicConstantNames`.

**Aceite:** inventário versionado ou reproduzível, nenhum diagnóstico classificado apenas por suposição e nenhuma mudança na baseline.

### Lote 4.2 — `bi_stats` e payloads de analytics

**Objetivo:** tratar o maior agrupamento conhecido do nível 5 antes de tocar em componentes genéricos.

**Arquivos prioritários:**

- `prescia/plugins/bi_stats/payload/content/stats_analytics.php`
- `prescia/plugins/bi_stats/payload/content/stats_ref.php`
- `prescia/plugins/bi_stats/payload/content/stats_pathajax.php`
- `prescia/plugins/bi_stats/module.php`

**Ações:**

- Confirmar o contexto concreto de `$this`, `$core`, `$dbo`, `$template` e `$modules` em cada `include`.
- Corrigir tipos de consultas, agregadores, datas e contadores sem transformar resultados `false`, `null` e listas vazias em valores indistinguíveis.
- Revisar comparações constantes geradas pela análise de metadados e por parâmetros declarativos.
- Preservar as correções de SQL preparado já existentes.

**Aceite:** redução mensurável nos diagnósticos dos quatro arquivos, PHPStan focalizado nível 4 sem novos `variable.undefined`, `method.notFound` ou falhas de consulta, lint PHP 8.3 e testes de regressão de analytics aprovados.

### Lote 4.3 — Núcleo `CPrescia` e componentes compartilhados

**Objetivo:** corrigir os contratos que propagam tipos estreitos para módulos e payloads.

**Arquivos prioritários:**

- `prescia/core.php`
- `prescia/coreFull.php`
- `prescia/components/module.php`
- `prescia/components/cacheControl.php`
- `prescia/components/errorControl.php`
- `prescia/components/intlControl.php`

**Ações:**

- Revisar assinaturas e propriedades realmente inicializadas pelo construtor.
- Corrigir argumentos incompatíveis em `mail()`, `number_format()` e `str_replace()` no ponto de origem dos dados.
- Substituir comparações impossíveis somente quando a investigação confirmar que o ramo não é parte do contrato de runtime.
- Manter estados internos protegidos ou privados; preferir métodos de leitura quando payloads legítimos precisarem de acesso.

**Aceite:** nenhum novo erro de visibilidade, assinatura ou propriedade; componentes passam em PHPStan focalizado nível 4 e PHPUnit.

### Lote 4.4 — Autenticação e módulos sensíveis

**Objetivo:** priorizar diagnósticos que podem afetar autenticação, autorização, sessão e proteção contra abuso.

**Arquivos prioritários:**

- `prescia/plugins/bi_auth/authControl.php`
- `prescia/plugins/bi_auth/module.php`
- `prescia/plugins/bi_dev/module.php`
- `prescia/lazyload/botprotect.php`
- `prescia/lazyload/cron.php`

**Ações:**

- Corrigir tipos de entradas de sessão, cookies e parâmetros somente após confirmar validação e normalização.
- Preservar distinções entre ausência, falha, valor vazio e valor autenticado.
- Não remover guards de autenticação porque o analisador considera uma condição sempre verdadeira ou falsa.
- Adicionar regressão quando a alteração mudar um caminho de login, autorização, cron ou bot protection.

**Aceite:** PHPUnit e testes de compatibilidade PHP 8.3 aprovados; revisão explícita dos caminhos de falha; nenhuma mudança de segurança justificada apenas pela contagem PHPStan.

### Lote 4.5 — Arrays, offsets e diagnósticos remanescentes

**Objetivo:** tratar os diagnósticos de forma de arrays e concluir os resíduos do nível 4.

**Ações:**

- Declarar arrays genéricos somente quando sua forma for comprovada pelo produtor e pelos consumidores.
- Inicializar ou normalizar arrays no limite do payload, sem espalhar casts.
- Corrigir `isset()` e `??` quando a chave for opcional; remover verificações apenas quando a chave for obrigatória por contrato.
- Revisar `foreach` considerado vazio para distinguir coleção realmente vazia de tipo incorreto.

**Aceite:** zero diagnósticos globais de nível 4 e nenhuma regressão nos payloads administrativos, estatísticos, de autenticação ou de fórum.

### Lote 4.6 — Gate de promoção

A promoção do nível oficial ocorrerá em um commit separado e somente depois dos seguintes resultados:

1. PHPStan global nível 4 com `file_errors=0`.
2. Segundo PHPStan global nível 4 no mesmo commit, ou em um commit documental sem mudança de código, também com `file_errors=0`.
3. PHPUnit completo aprovado, mantendo os dois skips conhecidos e sem novas depreciações.
4. Lint de todos os arquivos PHP alterados aprovado.
5. Workflow de PHP 8.3 e workflow de PHPStan concluídos com `status=completed` e `conclusion=success`.
6. `phpstan-baseline.neon` sem crescimento.
7. Relatório, changelog e issue atualizados com SHA, métricas e URLs dos workflows.

Somente após esses critérios a linha `level: 3` será alterada para `level: 4` em `phpstan.neon.dist`, seguida de uma nova execução completa de CI.

## 5. Procedimento padrão de cada lote

Cada lote deverá seguir a mesma sequência operacional:

```bash
composer install --no-interaction --no-progress
vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --level=4 --no-progress --error-format=json > /tmp/phpstan-level4.json
vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --level=4 --no-progress --error-format=table
vendor/bin/phpunit --configuration phpunit.xml.dist --display-deprecations --display-phpunit-deprecations
php -l caminho/do/arquivo.php
git diff --check
```

Durante a remediação, o comando com `--level=4` é exploratório. A configuração oficial deve continuar em nível 3 até o gate final. O relatório JSON deve ser preservado fora do repositório ou convertido em uma tabela documental com contagens verificáveis.

Para cada diagnóstico, registrar:

| Campo | Obrigatório |
|---|---|
| Arquivo e linha | Sim |
| Identificador PHPStan | Sim |
| Contexto de execução | Sim |
| Causa confirmada | Sim |
| Correção aplicada | Sim |
| Validação focalizada | Sim |
| Impacto em segurança ou compatibilidade | Quando aplicável |
| Diagnósticos novos introduzidos | Sim |
| Baseline alterada | Sim, mesmo quando não alterada |

## 6. Política de decisões técnicas

- **Não usar `@phpstan-ignore`** para eliminar diagnósticos de código executável.
- **Não usar `mixed`** como solução padrão para payloads ou propriedades mal compreendidos.
- **Não criar objetos globais artificiais** apenas para satisfazer o analisador.
- **Não remover guards** por causa de `alwaysTrue` ou `alwaysFalse` sem confirmar o fluxo de runtime.
- **Não adicionar stubs genéricos** quando a assinatura real puder ser localizada.
- **Não adicionar novos `ignoreErrors`** à baseline durante a promoção do nível.
- **Separar correção de tipagem de mudanças funcionais amplas**, especialmente SQL, autenticação, upload e autorização.
- **Preservar a semântica de `false`, `null`, zero, string vazia e arrays vazios**.

## 7. Métricas e definição de pronto

O avanço entre lotes será medido pela tabela abaixo, atualizada no relatório consolidado:

| Métrica | Meta antes da promoção |
|---|---:|
| Diagnósticos globais no nível 4 | 0 |
| Diagnósticos `property.*` introduzidos | 0 |
| Diagnósticos `method.*` introduzidos | 0 |
| Diagnósticos `variable.undefined` introduzidos | 0 |
| Novas entradas na baseline | 0 |
| Novas depreciações PHPUnit | 0 |
| Falhas de lint nos arquivos alterados | 0 |
| Ciclos globais nível 4 consecutivos aprovados | 2 |
| Workflows PHP 8.3 e PHPStan verdes | 100% dos check-runs |

O plano será considerado concluído quando o nível 4 estiver configurado oficialmente, os dois ciclos globais estiverem verdes, a documentação estiver atualizada e a issue relacionada registrar o SHA promovido e os check-runs correspondentes. Os diagnósticos de nível 5 permanecerão como backlog separado e não serão usados para justificar uma promoção incompleta do nível 4.

## 8. Próximo passo imediato

O próximo trabalho deve ser o **Lote 4.1**: gerar o inventário reproduzível do nível 4, classificar os diagnósticos por contexto e confirmar os contratos dinâmicos de `bi_stats` e `core.php`. Nenhuma correção deve ser iniciada em componentes genéricos antes de o inventário identificar se o diagnóstico é consequência de configuração, contexto de payload ou código executável.

## Referências

[1]: https://github.com/leohmoraes/Prescia/issues/43 "Prescia Issue #43 — Atualizar PHPStan e elevar gradualmente o nível de análise"
[2]: https://phpstan.org/user-guide/config-reference "PHPStan — Configuration Reference"
[3]: https://phpstan.org/user-guide/discovering-symbols "PHPStan — Discovering Symbols"
[4]: https://github.com/leohmoraes/Prescia/pull/229 "Prescia Pull Request #229 — Front controllers"
