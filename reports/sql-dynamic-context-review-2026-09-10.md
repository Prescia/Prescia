# Revisão contextual de SQL dinâmico — Prescia — 2026-09-10

## Escopo e conclusão

Esta revisão cobre os resultados da auditoria no commit `5b3279c`, publicado em `origin/master`, com foco na pendência de SQL dinâmico. O objetivo foi distinguir **valores de dados**, que devem ser parametrizados, de **identificadores e fragmentos estruturais**, que não podem ser protegidos por placeholders e precisam ser derivados de metadados confiáveis, validados e, quando aplicável, citados por uma API de identificadores.

**Conclusão:** não foi confirmado, nesta revisão estática, um caminho em que um valor direto de `GET`, `POST`, `REQUEST`, cookie ou header seja concatenado sem controle em um valor SQL de negócio. A maior parte dos fluxos de entrada usa `queryPrepared()`/`fetchPrepared()` para valores. Entretanto, há uma superfície residual relevante: `sqlarray_echo()` apenas concatena fragmentos; vários identificadores são interpolados sem uma validação central explícita; e existem chamadas legadas de `query()`/`simpleQuery()` em bootstrap de esquema, cron e rotinas de desenvolvimento.

A Issue [#9 — Substituir SQL concatenado por prepared statements](https://github.com/leohmoraes/Prescia/issues/9) e a Issue [#94 — migrate remaining generic module SQL reads and writes](https://github.com/leohmoraes/Prescia/issues/94) permanecem abertas e cobrem este trabalho.

## Modelo de ameaça usado

| Categoria | Regra de segurança |
|---|---|
| Valores (`id`, texto, datas, filtros, conteúdo) | Sempre devem ser placeholders com tipos explícitos. |
| Tabelas, colunas, aliases, chaves e ordenação | Não aceitam placeholders; devem vir de allowlists/metadados internos validados e ser citados por função específica. |
| Predicados/configuração (`AUTOCLEAN`, `WHERE`, `SET`) | Não são seguros apenas por serem configuração; devem ter gramática restrita ou ser construídos por API estruturada. |
| SQL estruturado (`SELECT`, `FROM`, `WHERE`, `ORDER`, `LIMIT`) | O builder deve validar cada fragmento por categoria; `implode()`/concatenação genérica não constitui validação. |
| Entrada externa | Deve ser convertida para tipos/enumerações antes de chegar ao builder; o nome de campo nunca deve ser aceito diretamente da requisição. |

## Fronteira da API de banco

`CDBO_mysqli::queryPrepared()` faz corretamente o binding dos parâmetros: verifica se a quantidade de tipos corresponde à quantidade de parâmetros, prepara a instrução, chama `bind_param()` e executa. Isso protege **valores**.

Contudo, `CDBO::sqlarray_echo()` apenas serializa arrays:

```php
$sqlout = "SELECT ".implode(",",$sql["SELECT"])." FROM (".implode(",",$sql["FROM"]).")";
...
$sqlout .= " ORDER BY ".implode(",",$sql["ORDER"]);
```

Assim, chamar `queryPrepared(sqlarray_echo($sql), ...)` não valida os itens de `SELECT`, `FROM`, `LEFT`, `GROUP`, `HAVING`, `ORDER` ou `LIMIT`. A segurança estrutural depende integralmente dos consumidores. Esse é o principal ponto arquitetural a corrigir ou encapsular.

## Fluxos classificados

### 1. Fluxos de negócio preparados — risco residual de identificadores

Os seguintes grupos usam valores parametrizados e, na revisão, não apresentaram concatenação direta de entrada externa em valores:

| Grupo | Evidência contextual | Classificação |
|---|---|---|
| Administração (`bi_adm`) | IDs e seleções usam tipos `i`/`s`, arrays de parâmetros e placeholders; testes impedem antigos `query()` e `implode()` de valores. | **Baixo para valores; revisão estrutural pendente** |
| Autenticação (`bi_auth`) | Login, preferências, histórico, grupos e IDs usam `queryPrepared()`; tabelas vêm dos módulos configurados. | **Baixo para valores; alta importância por estar no perímetro de autenticação** |
| Fórum (`bi_bb`) | IDs de fórum/tópico/autor e datas são parametrizados; testes cobrem preview e contagens. | **Baixo para valores; revisão estrutural pendente** |
| Estatísticas (`bi_stats`) | Filtros de data, página, IP, idioma e contadores foram migrados para prepared statements. | **Baixo para valores; maior volume de identificadores dinâmicos** |
| Friendly URL, RSS, full search e UDM | Valores de requisição passam por parâmetros; campos e tabelas são derivados de módulo/metadados. | **Baixo para valores; dependência forte de integridade dos metadados** |
| `getRemotePreparedKeys()` | Constrói `rmodule->name.field=?` e vincula o valor conforme o tipo do campo. | **Contrato correto, mas sem quote/allowlist central de identificadores** |

A conclusão de baixo risco não significa que os identificadores sejam automaticamente seguros. Os testes atuais verificam sobretudo que padrões antigos de valores concatenados não retornem. Eles não demonstram que cada `dbname`, `name`, `title`, `keys`, alias, ordem ou cláusula tenha passado por uma allowlist central.

### 2. `prescia/coreFull.php::check_sql()` — prioridade alta de revisão estrutural

O bootstrap de esquema executa SQL cru para criar e atualizar tabelas. Os valores são derivados de módulos e campos carregados pela configuração da aplicação, e não de uma requisição direta no trecho analisado. Ainda assim, a construção tem vários pontos frágeis:

| Trecho | Fragmento | Problema contextual |
|---|---|---|
| 653–664 | `SHOW TABLES LIKE ...`; `CREATE TABLE ... $module->dbname ...` | O nome da tabela é metadata-derived e parcialmente citado; tipos SQL vêm de `CONS_XML_SQL`. |
| 676–700 | `SHOW FIELDS`; `ALTER TABLE ... ADD ... $campo[CONS_XML_SQL]` | Nome do campo é citado, mas a definição SQL é inserida como fragmento livre. |
| 719–722 | `PRIMARY KEY (".implode(",",$module->keys).")` | Chaves não são citadas individualmente. |
| 737 | `ADD UNIQUE ($name)` | Nome de índice/coluna não é citado. |
| 751 | `ADD INDEX ($name)` | Mesmo problema para índice não único. |

**Classificação:** não é uma injeção confirmada por entrada HTTP, mas é uma superfície de injeção por configuração/metadados e uma violação da separação entre identificador e SQL. O impacto é elevado porque `check_sql()` executa DDL e pode modificar o esquema. A correção deve introduzir funções como `quoteIdentifier()` e validação de nomes contra `^[A-Za-z_][A-Za-z0-9_]*$`, além de validar a gramática permitida para tipos/definições SQL. Não se deve tentar resolver esse caso com placeholders, pois placeholders não funcionam para nomes de tabela, coluna ou DDL.

### 3. `prescia/lazyload/cron.php` — prioridade alta por executar ações destrutivas

O cron monta:

```php
DELETE FROM {$module->dbname} WHERE {$module->options[CONS_MODULE_AUTOCLEAN]}
SELECT * FROM {$module->dbname} WHERE {$module->options[CONS_MODULE_AUTOCLEAN]}
REPAIR TABLE {$mods}
OPTIMIZE TABLE {$mods}
```

A origem aparente é configuração de módulo, não entrada direta do usuário. Porém, `CONS_MODULE_AUTOCLEAN` é um predicado SQL inteiro e é executado periodicamente; não há evidência no trecho de um parser ou allowlist de operadores, campos, funções e limites. Um módulo/configuração comprometido pode transformar a limpeza em leitura, atualização indireta ou exclusão ampla. Além disso, `dbname` é concatenado em `FROM`, `REPAIR TABLE` e `OPTIMIZE TABLE`.

**Classificação:** risco de integridade/execução de SQL por configuração; não confirmado como SQL injection remoto. O caminho deve ser migrado para uma DSL limitada de autoclean (por exemplo, campo permitido + operador permitido + valor tipado) e para uma lista de identificadores citados. A operação destrutiva deve ter testes que provem que a configuração não pode introduzir `;`, comentários, subconsultas ou múltiplas instruções.

### 4. `prescia/plugins/bi_dev/module.php` — prioridade média, superfície de desenvolvimento

A rotina de integridade do plugin de desenvolvimento monta consultas usando:

- `$module->dbname`;
- `implode(',', $keys)` e `implode(',', $desired)`;
- `$setStatement` derivado de campos de upload;
- `$field` e `$keys` construídos a partir de metadados e nomes de arquivos;
- valores de chaves extraídos de nomes de arquivos.

A maior parte dos valores de chave passa por nomes encontrados no armazenamento e não é diretamente parametrizada. O plugin é de desenvolvimento e pode estar desabilitado em produção, mas a própria saída contém operações de `UPDATE` e exclusão de arquivos. A recomendação é não considerar o contexto “dev” uma barreira de segurança: exigir autorização forte, remover SQL cru e construir os updates com identificadores previamente validados e valores como parâmetros.

### 5. `prescia/coreFull.php` e `cron.php` — chamadas legadas não preparadas

A busca fora da camada de banco encontrou chamadas de `query()`/`simpleQuery()` principalmente em:

- `coreFull.php::check_sql()`;
- `lazyload/cron.php`;
- comentários e testes, que não são sinks de produção.

A substituição mecânica por `queryPrepared()` não é suficiente para DDL, nomes de tabela, índices e predicados configuráveis. O lote deve primeiro separar:

1. DML com valores, para placeholders;
2. SQL estrutural, para builder/quote de identificadores;
3. configuração expressiva, para parser/DSL restrita;
4. rotinas administrativas de manutenção, para allowlist explícita e autorização.

## Falsos positivos e limites da varredura

A heurística encontrou aproximadamente 256 linhas porque considera qualquer `$` em uma linha que contenha `SELECT`, `INSERT`, `UPDATE` ou `DELETE`. Isso inclui chamadas já preparadas, tabelas legítimas derivadas de metadados e SQL estruturado sem valores externos. Portanto, o número não equivale a 256 vulnerabilidades.

Da mesma forma, os testes estáticos em `SecurityRegressionTest.php` são úteis contra regressões conhecidas, mas não provam a segurança de todos os identificadores. Eles devem ser complementados por testes de propriedades:

- nome de tabela/campo inválido no metadata deve ser rejeitado antes da execução;
- campo vindo de requisição não deve ser aceito mesmo que tenha formato de identificador;
- `AUTOCLEAN` deve rejeitar ponto e vírgula, comentários, subconsultas e operadores não permitidos;
- listas vazias devem gerar SQL seguro (`IN (NULL)` ou caminho sem execução), nunca SQL inválido;
- nomes com acento, hífen, backtick, ponto ou NUL devem ser rejeitados ou tratados por uma política explícita.

## Priorização recomendada

| Prioridade | Ação | Motivo |
|---:|---|---|
| P0 | Criar `quoteIdentifier()`/allowlist central e usar em tabela, coluna, alias, chave, índice e ordenação. | `sqlarray_echo()` não oferece validação estrutural. |
| P0 | Reescrever `check_sql()` com identificadores validados e parser de definições SQL. | Executa DDL e recebe vários fragmentos de metadata. |
| P0 | Reescrever autoclean do cron usando DSL tipada e impedir SQL livre na configuração. | Caminho periódico e destrutivo. |
| P1 | Migrar manutenção `REPAIR/OPTIMIZE` para lista validada de tabelas. | Evita fragmentos de tabela livres em comandos administrativos. |
| P1 | Migrar `bi_dev` para builder parametrizado e limitar o plugin a administradores. | Update com campos e filtros construídos dinamicamente. |
| P1 | Ampliar testes de regressão para identificadores e configuração malformada. | Os testes atuais cobrem principalmente valores e padrões antigos. |
| P2 | Instrumentar consultas e medir N+1 após a migração. | Não misturar otimização com correção de segurança. |

## Critério de encerramento das issues #9 e #94

As issues não devem ser encerradas apenas porque os valores de entrada foram parametrizados. O aceite deve exigir:

- inventário dos consumidores de `dbname`, `name`, `title`, `keys`, aliases e cláusulas de ordem/limite;
- validação ou quote central para identificadores;
- ausência de SQL cru em DML de entrada e em rotinas administrativas não justificadas;
- parser restrito para predicados configuráveis, especialmente autoclean;
- testes negativos para requisição, metadata corrompido e configuração malformada;
- execução de PHPUnit, lint, PHPStan e testes de integração em PHP 8.3;
- evidência de que nenhum SQL, segredo ou caminho interno é devolvido ao cliente em caso de erro.

## Arquivos e comandos de referência

- `prescia/lib/dbo/mysqli.php` — binding de valores em `queryPrepared()`.
- `prescia/lib/dbo/cdbo.php` — `sqlarray_echo()` sem validação estrutural.
- `prescia/coreFull.php` — DDL e sincronização de esquema.
- `prescia/lazyload/cron.php` — autoclean e manutenção de tabelas.
- `prescia/plugins/bi_dev/module.php` — integridade e updates construídos dinamicamente.
- `prescia/components/module.php` — `getRemotePreparedKeys()` e contratos de metadata.
- `tests/SecurityRegressionTest.php` — regressões atuais de SQL parametrizado.

A análise foi estática. PHP, Composer, PHPStan e PHPUnit não estavam instalados no ambiente da auditoria, portanto a validação executável permanece pendente.

## Implementação realizada após a revisão

Foi adicionada a função central `CDBO::quoteIdentifier()` em `prescia/lib/dbo/cdbo.php`. Ela aceita somente identificadores simples no formato `[A-Za-z_][A-Za-z0-9_]*` e retorna o nome envolvido pelo delimitador SQL configurado. Valores continuam obrigatoriamente no caminho de prepared statements; a função não deve ser usada para receber entrada de usuário sem uma allowlist de contexto.

Também foi adicionada `CPresciaAutoClean` em `prescia/lib/autoClean.php`. A DSL aceita somente:

```text
<campo> <operador> NOW() - INTERVAL <inteiro positivo> <MINUTE|HOUR|DAY|WEEK|MONTH|YEAR>
```

O campo precisa existir no metadata do módulo. O operador e a unidade são enumerados pelo parser, o intervalo é convertido para inteiro positivo e a expressão é rejeitada quando contém ponto e vírgula, comentários, subconsultas, múltiplas instruções ou campos não declarados. O cron diário e horário agora usam essa DSL, `quoteIdentifier()` para o nome da tabela e `queryPrepared()` para a execução.

Foram adicionados testes em `tests/SqlSafetyTest.php` cobrindo identificadores válidos e inválidos, formatos de autoclean existentes e rejeição de SQL livre/campos fora do metadata.

A validação executável permanece pendente neste ambiente porque PHP, Composer, PHPUnit e PHPStan não estão instalados. Foram feitas verificações estáticas do diff e os testes foram preparados para PHPUnit 10.5/PHP 8.3.

### Validação após a implementação

A validação executável foi concluída em PHP 8.3.6, com PHPUnit 10.5.64 e PHPStan 2.2.13:

| Verificação | Resultado |
|---|---|
| Lint global (`prescia`, `tests`, `tools`) | Aprovado |
| PHPUnit completo | **101 testes, 528 assertions, aprovado** |
| PHPUnit focado em segurança/DSL | Aprovado |
| PHPStan focalizado nos arquivos alterados | `[OK] No errors` |
| `git diff --check` | Aprovado |
| PHPStan global nível 3 | Ainda apresenta diagnósticos preexistentes em outros arquivos; não relacionados a este lote |
