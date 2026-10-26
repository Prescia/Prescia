# Relatório do ciclo — 2026-09-16

## Escopo e base

Este ciclo foi executado sobre `master` no commit `20ae614`, sincronizado com `origin/master`. O backlog operacional foi lido junto com as skills versionadas `phpstan-legacy-remediation` e `prescia-install-config`, `CHANGELOG.md`, `phpstan.neon.dist`, `phpstan-baseline.neon`, o plano do próximo lote e o relatório do ciclo anterior.

A regra de seleção foi aplicada literalmente: escolher apenas o primeiro item não bloqueado, sem misturar lotes nem criar trabalho artificial.

## Estado do GitHub

A consulta encontrou as issues abertas já existentes no repositório, incluindo a frente PHPStan incremental (#216–#220) e demais pendências registradas (#224–#226). Não foram criadas issues.

As PRs abertas são:

| PR | Título | Estado |
|---:|---|---|
| [#228](https://github.com/leohmoraes/Prescia/pull/228) | `fix: correct core metadata and link handling` | `mergeable=CONFLICTING`, `mergeStateStatus=DIRTY` |
| [#227](https://github.com/leohmoraes/Prescia/pull/227) | `fix: reduce bi_stats PHPStan level 4 diagnostics` | `mergeable=CONFLICTING`, `mergeStateStatus=DIRTY` |

Essas branches conflitantes não foram alteradas, rebaseadas, fechadas ou incorporadas a este ciclo.

## Seleção do backlog e bloqueio

O primeiro item ainda não concluído é o item 4: varredura dos fluxos administrativos ainda não cobertos pelos lotes SQL registrados. O relatório de 2026-09-15 já documentou a análise estática do escopo, sem chamadas DBO legadas executáveis identificadas, mas deixou o item pendente porque faltava validação executável.

O bloqueio permanece material neste ambiente:

- `php`: ausente;
- `composer`: ausente;
- `vendor/bin/phpunit`: ausente;
- `vendor/bin/phpstan`: ausente;
- `docker`: também não está disponível no shell efetivo desta execução, apesar de haver referência histórica a Docker nos relatórios.

Sem PHP 8.3, Composer, PHPUnit e PHPStan não é possível confirmar o contrato em runtime, executar a validação focalizada/global, repetir a suíte ou produzir evidência suficiente para uma correção segura. O item 5 depende de ambiente PHP/MySQL representativo; o item 6 depende de runner/CI; e o item 7 depende da evidência dos lotes e do CI. Portanto, nenhum item posterior é não bloqueado.

## Implementação

Nenhum arquivo PHP, teste funcional, configuração PHPStan ou baseline foi alterado. Não foram adicionadas entradas a `phpstan-baseline.neon`, nem usadas supressões, casts indiscriminados ou stubs artificiais. Não foi inventada issue, chamada externa ou contrato de runtime.

## Validação

`git diff --check` foi executado e permaneceu sem saída de erro antes da publicação deste registro. A contagem do escopo administrativo continua registrada no ciclo anterior; não foi repetida uma análise que exigiria PHP/PHPStan. A baseline permanece mínima, com `ignoreErrors: []`.

As validações indisponíveis foram registradas como bloqueio, não como sucesso: `composer validate`, `composer test`, `composer analyse`, `php -l`, PHPUnit, PHPStan focalizado/global, Docker e os testes de compatibilidade PHP 8.3.

## Resultado e próximo passo

O ciclo foi **bloqueado e documentado**, sem correção de código artificial. O próximo ciclo deve começar somente quando houver ambiente PHP 8.3 com Composer, PHPUnit e PHPStan, ou quando o CI fornecer evidência atual suficiente para selecionar e validar o próximo lote. A ordem permanece: retomar o item 4; somente após sua validação decidir se o item 5 ou o lote PHPStan é o primeiro alvo aplicável.

A PR documental [#232](https://github.com/leohmoraes/Prescia/pull/232) foi aberta, recebeu 9 check-runs `completed/success` e foi mesclada com `mergeable/clean` em 2026-09-16. O SHA resultante de `master` é `999a08e0a20df2ffbb27e0bdedcecc09e02157c0`. O workflow pós-merge de compatibilidade [#35062355570](https://github.com/leohmoraes/Prescia/actions/runs/35062355570) concluiu com `success`; os jobs PHP 8.3, PHP 8.4, PHP 8.5, Composer, lint, PHPUnit/PHPStan e, no PHP 8.3, build/scan Docker, concluíram com sucesso. Os avisos do GitHub Actions sobre Node.js 20 depreciado não foram falhas. A PR documentou o bloqueio; seus checks não substituem a ausência do toolchain local para corrigir código.
