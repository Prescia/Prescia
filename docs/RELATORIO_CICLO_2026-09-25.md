# Relatório do ciclo — 2026-09-25

## Escopo e base

Este ciclo foi executado sobre o `master` sincronizado de `leohmoraes/Prescia`, no SHA `2b237056362bbe4436ef9c49f8a2ba3a5957d7b9`. Foram consultados o estado Git, as issues e pull requests abertas, as skills versionadas em `skills/`, `docs/PENDENCIAS_LOOP.md`, `CHANGELOG.md`, `phpstan.neon.dist`, `phpstan-baseline.neon`, os relatórios recentes e os commits recentes.

A regra de seleção foi aplicada literalmente: escolher apenas o primeiro item real não concluído, confirmar call sites e contrato antes de alterar código, não misturar lotes independentes e não criar trabalho artificial quando o item estiver bloqueado.

## Estado do GitHub

As issues abertas consultadas são #216–#220, #224–#226, #239 e #240. A única PR aberta é a #246, `test: cover rejection of missing CSRF tokens`, com head `4cc312423d53518d15dc79f41dbc9836d7a11e95`. Ela pertence a um lote CSRF independente e não foi alterada ou misturada neste ciclo. A PR está `mergeable=CONFLICTING` e `mergeStateStatus=DIRTY`; os checks de PHP 8.3, 8.4 e 8.5 estão `completed/failure`, enquanto dependency review e PHPStan estão `completed/success`.

O `master` local foi atualizado com `fetch`, checkout e reset fast-forward para `origin/master`, sem alterações locais. Os checks do SHA `2b237056362bbe4436ef9c49f8a2ba3a5957d7b9` estão todos `completed/success`: PHP 8.3, PHP 8.4, PHP 8.5 e PHPStan em PHP 8.3.

## Seleção, call sites e contrato

O primeiro item não concluído continua sendo o item 4 do backlog: varrer os fluxos administrativos ainda não cobertos pelos lotes SQL registrados. A busca atual em `prescia/plugins/bi_adm` e `prescia/components` encontrou 44 usos de `queryPrepared`, `fetchPrepared` ou `getPreparedKeys`. As únicas ocorrências legadas encontradas são um `simpleQuery()` comentado em `prescia/components/module.php` e chamadas `fetch()` do mecanismo de templates em `bi_adm`; não há chamada DBO legada executável identificada neste escopo.

O contrato do item permanece parcialmente confirmado pela inspeção estática e pelos registros anteriores: valores devem seguir APIs preparadas, identificadores devem derivar de metadados internos e o fluxo de templates não é uma consulta DBO. A conclusão segura ainda exige validação executável e revisão contínua dos diagnósticos PHPStan nível 4.

Não há item simultaneamente pendente e desbloqueado neste ambiente. O item 4 continua bloqueado; os itens 5 e 6 dependem de ambiente PHP 8.3, banco/CI representativos e da conclusão do item 4, e o item 7 depende de evidência dos lotes anteriores. A PR #246 não é alternativa ao item selecionado.

## Bloqueio de ambiente

| Ferramenta | Estado observado |
|---|---|
| `php` | ausente |
| `composer` | ausente |
| `vendor/bin/phpunit` | ausente |
| `vendor/bin/phpstan` | ausente |
| `docker` | ausente |

O bloqueio impede `php -l`, PHPStan focalizado e global, PHPUnit, `composer validate`/auditoria, compatibilidade PHP 8.3 e validação Docker. Não é seguro implementar correção ou adicionar regressão funcional sem essa evidência executável.

## Implementação e documentação

Não foi alterado código PHP, teste funcional, configuração PHPStan ou `phpstan-baseline.neon`. A baseline continua com `ignoreErrors: []`; não foram usadas supressões, `mixed`, casts indiscriminados, stubs artificiais ou segredos em logs.

Foi criada somente esta atualização documental, acompanhada da atualização do backlog e do `CHANGELOG.md`. Ela registra o estado atual, a confirmação dos call sites, o bloqueio e os checks observados, sem promover pendências, interferir na PR #246 ou declarar o item 4 concluído.

## Validação

- `git fetch origin --prune`, checkout de `master` e sincronização para `origin/master`: concluídos.
- Issues e PRs abertas: consultadas.
- Skills versionadas de PHPStan e instalação/configuração: lidas.
- Call sites e contrato do item 4: revisados; nenhuma chamada DBO legada executável nova foi identificada.
- PHP, Composer, PHPUnit, PHPStan e Docker: indisponíveis localmente; não foram declarados como aprovados.
- Checks do SHA atual de `master`: `completed/success` para PHP 8.3, PHP 8.4, PHP 8.5 e PHPStan em PHP 8.3.
- PR #246: não mergeável, conflitante e com falhas nos testes da matriz PHP; não foi misturada.
- Baseline: sem expansão.
- `git diff --check`: executado antes da publicação desta documentação.

## Resultado e próximo passo

O ciclo está **bloqueado e documentado**, sem mudança funcional artificial. O próximo ciclo deve retomar o item 4 somente com PHP 8.3, Composer, PHPUnit e PHPStan disponíveis localmente ou com evidência executável equivalente em ambiente suportado. Deve repetir a confirmação dos call sites e do contrato, adicionar regressão somente se houver fluxo real a corrigir, validar globalmente e publicar uma PR pequena. Se o bloqueio persistir, deve registrar novo estado sem promover itens posteriores.

## Fechamento pós-merge

A PR documental #249 foi mesclada após confirmação de `mergeable=MERGEABLE`, `mergeStateStatus=CLEAN` e nove checks `completed/success` no SHA da PR `53cba92494cdc6e815970622a3c5f670bd9470e9`. O `master` resultante é `74eea614a0d45d7ee3cb077660ef81ac50b8a7af`; seus quatro checks pós-merge — PHP 8.3, PHP 8.4, PHP 8.5 e PHPStan em PHP 8.3 — terminaram `completed/success`. O item 4 permanece pendente e bloqueado; nenhum item posterior foi promovido.
