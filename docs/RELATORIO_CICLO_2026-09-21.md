# Relatório do ciclo — 2026-09-21

## Escopo e base

Este ciclo foi executado sobre o `master` sincronizado de `leohmoraes/Prescia`, no commit `ec993ec13bfbadccaa60d3b5cc278e8b82cd4e44`. Foram consultados o backlog `docs/PENDENCIAS_LOOP.md`, as skills versionadas `skills/phpstan-legacy-remediation/SKILL.md` e `skills/prescia-install-config/SKILL.md`, `CHANGELOG.md`, `phpstan.neon.dist`, `phpstan-baseline.neon`, o plano PHPStan, o relatório consolidado, os relatórios recentes e o estado atual de issues, pull requests e workflows.

A regra de seleção foi aplicada literalmente: escolher somente o primeiro item real e não concluído, confirmar call sites e contrato antes de alterar código e não misturar lotes.

## Estado do GitHub

A consulta encontrou as seguintes issues abertas: #216, #217, #218, #219, #220, #224, #225, #226, #239 e #240. As issues #216–#226 são a frente histórica de remediação PHPStan; #239 e #240 tratam de cobertura negativa de CSRF. Nenhuma issue foi criada, fechada ou alterada neste ciclo.

As PRs #227 (`fix/phpstan-level4-bi-stats`) e #228 (`fix/phpstan-level4-core`) continuam abertas, ambas com `mergeable=UNKNOWN` e `mergeStateStatus=UNKNOWN` no momento da consulta. Elas não foram incorporadas ao ciclo nem alteradas.

O `master` atual possui os workflows pós-merge `PHP compatibility matrix` (run `35494113225`) e `PHP static analysis` (run `35494113208`) para o SHA `ec993ec13bfbadccaa60d3b5cc278e8b82cd4e44`; ambos estão `completed/success`. O estado verde do `master` não remove o bloqueio do ambiente local para iniciar uma nova correção.

## Seleção, call sites e contrato

O primeiro item não concluído do backlog continua sendo o item 4: varrer os fluxos administrativos ainda não cobertos pelos lotes SQL registrados. A análise anterior, registrada nos ciclos de 2026-09-15 a 2026-09-20, examinou os 53 arquivos de `prescia/plugins/bi_adm` e os fluxos compartilhados em `prescia/components` e não encontrou chamadas DBO legadas executáveis; os consumidores encontrados usam `queryPrepared()`/`fetchPrepared()`.

Esse resultado não satisfaz sozinho o critério de aceite do item 4, que também exige validação executável contínua e revisão dos diagnósticos PHPStan nível 4. Não foi seguro escolher uma correção de código nem promover as issues de CSRF, porque isso mudaria o lote e exigiria confirmar contratos e executar regressões em ambiente PHP suportado.

## Bloqueio de ambiente

| Ferramenta | Estado observado |
|---|---|
| `php` | ausente |
| `composer` | ausente |
| `vendor/bin/phpunit` | ausente |
| `vendor/bin/phpstan` | ausente |
| `docker` | ausente |

O bloqueio impede lint PHP, PHPStan focalizado e global, PHPUnit, `composer validate`/auditoria, compatibilidade PHP 8.3 e validação Docker. O CI remoto continua sendo a fonte de validação executável, mas não há neste ciclo uma alteração funcional segura a publicar para dispará-lo.

## Implementação documental

Foram alterados somente este relatório, o backlog e o `CHANGELOG.md`. Nenhum arquivo PHP, teste funcional, configuração PHPStan ou `phpstan-baseline.neon` foi alterado. A baseline permanece com `ignoreErrors: []`; não foram usadas supressões, `mixed`, casts indiscriminados, stubs artificiais ou segredos em logs.

O item 4 permanece pendente e bloqueado. Os itens 5 e 6 também não foram promovidos: dependem de ambiente PHP 8.3 e banco/CI representativos. As issues de CSRF não foram tratadas como substitutas do primeiro item do backlog. Não houve criação de branch de implementação, commit funcional, PR ou merge artificial.

## Validação

- `git diff --check`: executado sem erros antes do registro documental.
- `git status`/diff: revisados; nenhuma alteração funcional foi introduzida.
- PHP, Composer, PHPUnit, PHPStan e Docker: indisponíveis localmente; não foram declarados como aprovados.
- Workflows do SHA atual de `master`: `completed/success` nas duas execuções pós-merge listadas acima.
- Baseline: sem expansão.

## Resultado e próximo passo

O ciclo está **bloqueado e documentado**. O próximo ciclo deve retomar o item 4 somente com PHP 8.3, Composer, PHPUnit e PHPStan disponíveis localmente ou com evidência executável equivalente produzida pelo CI; então deverá repetir a confirmação dos call sites e do contrato antes de qualquer alteração. Se o bloqueio persistir, deve registrar novo estado sem criar mudança artificial.

## Fechamento pós-merge

A PR [#241](https://github.com/leohmoraes/Prescia/pull/241), no SHA `d73d59d1634d5d2d094e0a8c51ce79d2f0fefd16`, ficou `MERGEABLE`/`CLEAN` com quatro check-runs `completed/success` e foi mesclada em 2026-09-21. O merge resultou no `master` SHA `c8a08b8e5533c61b00e9c8b81647002de892d61c`.

No SHA mesclado, os workflows pós-merge concluíram com sucesso: `PHP compatibility matrix`, run `35567054119`, e `PHP static analysis`, run `35567054068`. A matriz executou os testes PHP 8.3, 8.4 e 8.5; o workflow de análise executou PHPStan. O job PHP 8.3 também concluiu lint, auditoria Composer, build e scan Docker com sucesso. As anotações do runner sobre a futura migração do Ubuntu e a depreciação do Node.js 20 não são falhas do ciclo.

O item 4 permanece pendente e bloqueado para nova implementação local. A PR mesclada apenas registra o estado; não transforma a varredura administrativa em correção funcional nem promove itens posteriores.
