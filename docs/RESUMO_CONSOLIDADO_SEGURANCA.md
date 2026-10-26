# Resumo Consolidado das Correções de Segurança

**Projeto:** Prescia  
**Repositório:** `leohmoraes/Prescia`  
**Branch verificada:** `master`  
**Data:** 7 de setembro de 2026  
**Responsável:** Manus AI

## Conclusão executiva

Foram aplicadas e publicadas dez frentes de segurança no branch `master`. O trabalho reduziu a exposição a **injeção de SQL**, **bypass de autorização**, **path traversal**, **vazamento de estado de segurança** e **uso inseguro de entradas administrativas**. Também foram reforçados fluxos de sessão, autenticação e uploads.

A última validação local consolidada aprovou **33 testes e 2787 asserções**, PHPStan sem erros, lint PHP 8.3 e `git diff --check`. O working tree está limpo e sincronizado com `origin/master` no commit `14df265`.

> **Síntese:** os principais sinks de SQL dinâmico identificados nas frentes executadas foram parametrizados ou passaram a receber valores tipados e escapados pelo driver. Os controles de autorização e de contenção de arquivos foram reforçados. Ainda existem issues abertas para lotes futuros.

## Frentes aplicadas

| Frente | Correção | Commit |
|---|---|---|
| Autenticação e sessão | Parametrização da atualização de histórico e preferências do usuário autenticado, removendo dados de sessão concatenados em SQL. | [`bab88c1`][1] |
| Preview do fórum `bi_bb` | Parametrização das consultas de preview para fórum e thread, removendo interpolação direta de IDs enviados por POST. | [`0bef666`][2] |
| RBAC e rotas AJAX | Permissão obrigatória de seleção, allowlist de campos, consultas preparadas e manutenção do safety mode nas rotas AJAX. | [`1d45bc0`][3] |
| Importação administrativa | Restauração do estado anterior de `safety` após processamento com `ignoreErrors`. | [`aa98833`][4] |
| Uploads e file manager | Remoção de filename bruto em operações de exclusão, allowlist de diretórios e contenção canônica com `realpath()`. | [`1ca7a3b`][5] |
| Filtros SQL centrais | Escape pelo driver ou conversão tipada em filtros remotos, AJAX, referências administrativas, fórum e grupos. | [`c442b54`][6] |
| Telemetria `bi_stats` | Sanitização de página, referer, domínio, páginas acumuladas e browser antes das queries legadas. | [`31df78c`][7] |
| Plugin `bi_undo` | Migração de seleção, exclusão, restauração de chaves e gravação de histórico para consultas preparadas. | [`62aa4e1`][8] |
| Consumidores administrativos de `getKeys` | Validação positiva com `FILTER_VALIDATE_INT` nas rotas `undo.php` e `multipleundo.php`. | [`14df265`][9] |
| CRUD genérico e cascatas | Parametrização de predicados de chaves, `autoPrune()`, ciclos parentais e exclusões em cascata. | [`7fb2169`][10] [`89c4818`][11] [`dfa06e3`][12] |

## Detalhamento técnico

### Injeção de SQL

A estratégia combinou `queryPrepared()` e `fetchPrepared()` para valores dinâmicos. Nos pontos legados em que a estrutura da consulta ainda é montada por metadados, o escape é delegado ao driver por meio de `addslashes_EX()` com o objeto de banco ativo.

IDs provenientes de requisições foram convertidos explicitamente para inteiros. Campos de consulta AJAX passaram a ser limitados aos metadados permitidos. Nomes de tabelas e colunas continuam sendo derivados de metadados do framework, e não de valores livres do cliente.

As correções cobriram autenticação, fórum, filtros administrativos, seleção AJAX, autorização de grupos, estatísticas, histórico de undo e rotas administrativas de undo. Também foram tratados predicados do CRUD genérico, limpeza automática, ciclos parentais e exclusões em cascata.

### Autorização e estado de segurança

As rotas AJAX revisadas verificam a permissão de seleção do módulo antes de consultar dados. A rota de unicidade não expõe SQL em mensagens de erro. A rota de preenchimento deixou de desligar o safety mode antes de executar conteúdo.

O importador administrativo preserva e restaura o estado original de `safety`. Essa proteção impede que uma operação tolerante a erros altere o nível de proteção das operações posteriores na mesma requisição.

### Uploads e path traversal

O gerenciamento de arquivos deixou de confiar em nomes brutos enviados pelo cliente. Diretórios recebidos passam por allowlist de segmentos permitidos. A verificação de segurança compara caminhos canônicos resolvidos por `realpath()`, bloqueando segmentos `..`, prefixos enganosos e escapes via symlink.

O armazenamento também mantém bloqueios para bytes NUL, extensões executáveis e destinos fora do diretório permitido.

### Sessão e autenticação

O lote inicial reforçou CSRF, cookies de sessão, logout, rotação de sessão e migração de senhas. O fluxo de autenticação passou a usar consultas parametrizadas nas atualizações persistentes associadas ao usuário autenticado.

### Injeção de comandos

A auditoria não encontrou usos de `shell_exec`, `system`, `passthru`, `proc_open` ou `popen` alimentados por entrada externa. Um `eval()` legado foi localizado em código de conversão de data, mas não recebe dados da requisição no fluxo analisado.

## Evidências de validação

| Verificação | Resultado |
|---|---|
| PHPUnit | **33 testes, 2787 asserções — aprovado** |
| PHPStan | **0 erros** na última execução |
| Lint PHP 8.3 | Aprovado nos arquivos tratados |
| `git diff --check` | Aprovado |
| Composer audit | Nenhum advisory registrado no lote de segurança documentado |
| Baseline PHPStan | Não ampliada pelos lotes de segurança documentados |
| Working tree | Limpo em `master` e sincronizado com `origin/master` |

Os workflows remotos dos commits até `62aa4e1` foram concluídos com sucesso. Os workflows do commit `14df265` foram disparados e estavam `queued` na última consulta: [PHP 8.3 compatibility][13] e [PHP static analysis][14].

## Pendências e limites

Este resumo não afirma que todas as issues de segurança do repositório foram encerradas. Permanecem abertas frentes relacionadas à parametrização completa de listagens e paginação genéricas, consumidores adicionais de `getKeys`, testes de regressão SQL, rate limiting de login, escaping contextual de HTML e endurecimento do container Docker.

O módulo `bi_stats` ainda contém SQL legado estruturalmente dinâmico fora do sublote publicado. Os valores de telemetria tratados foram sanitizados, mas a migração integral para prepared statements permanece como melhoria futura.

A auditoria não substitui testes de integração com MySQL real nem avaliação externa de segurança. As regressões atuais são predominantemente estáticas e de contrato, complementadas por PHPStan, lint, PHPUnit e workflows do GitHub Actions.

## Referências

[1]: https://github.com/leohmoraes/Prescia/commit/bab88c1 "Atualização de sessão autenticada"
[2]: https://github.com/leohmoraes/Prescia/commit/0bef666 "Consultas parametrizadas do preview do fórum"
[3]: https://github.com/leohmoraes/Prescia/commit/1d45bc0 "RBAC nas rotas AJAX"
[4]: https://github.com/leohmoraes/Prescia/commit/aa98833 "Restauração do safety após importação"
[5]: https://github.com/leohmoraes/Prescia/commit/1ca7a3b "Correção de path traversal no file manager"
[6]: https://github.com/leohmoraes/Prescia/commit/c442b54 "Endurecimento de consultas SQL orientadas por entrada"
[7]: https://github.com/leohmoraes/Prescia/commit/31df78c "Sanitização de SQL de telemetria do bi_stats"
[8]: https://github.com/leohmoraes/Prescia/commit/62aa4e1 "Parametrização das consultas do bi_undo"
[9]: https://github.com/leohmoraes/Prescia/commit/14df265 "Validação de identificadores nas rotas undo"
[10]: https://github.com/leohmoraes/Prescia/commit/7fb2169 "Predicados parametrizados do CRUD genérico"
[11]: https://github.com/leohmoraes/Prescia/commit/89c4818 "Auto-prune e ciclos parentais parametrizados"
[12]: https://github.com/leohmoraes/Prescia/commit/dfa06e3 "Limpeza de relacionamentos em cascata parametrizada"
[13]: https://github.com/leohmoraes/Prescia/actions/runs/34160237839 "Workflow PHP 8.3 do commit 14df265"
[14]: https://github.com/leohmoraes/Prescia/actions/runs/34160237691 "Workflow PHP static analysis do commit 14df265"
