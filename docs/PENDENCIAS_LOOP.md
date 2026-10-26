# Pendências e loop de execução — Prescia

**Data da auditoria:** 2026-09-22
**Commit auditado:** `83233e0` (`master`)
**Skills instaladas:** `phpstan-legacy-remediation` e `prescia-install-config`
**Estado das skills:** ambas validadas pelo `quick_validate.py`; as cópias instaladas em `/home/ubuntu/skills/` são idênticas às versões versionadas.

> **Atualização de estado — 2026-09-24:** o `master` foi sincronizado no SHA `8edc15bbfed112d0a082c5d7b00ec5709b4f1eb1`. As issues abertas consultadas são #216–#220, #224–#226, #239 e #240; a PR aberta #246 (`test: cover rejection of missing CSRF tokens`) é um lote independente, `mergeable`, mas `UNSTABLE`, e não foi misturada. O item 4 continua sendo o primeiro item não concluído e permanece bloqueado porque `php`, Composer, PHPUnit, PHPStan e Docker estão ausentes localmente. Os checks do SHA atual estão `completed/success` para PHP 8.3, PHP 8.4, PHP 8.5 e PHPStan em PHP 8.3. O ciclo foi registrado em `docs/RELATORIO_CICLO_2026-09-24.md`; não houve alteração funcional, expansão da baseline ou criação de issue artificial.

> **Fechamento — 2026-09-24:** a PR documental #247 foi mesclada após `mergeable=MERGEABLE`, `mergeStateStatus=CLEAN` e nove checks `completed/success` no SHA da PR. O `master` resultante é `f603a737f0c986473a5b51d6d4f61f76c4d38181`; seus quatro checks pós-merge — PHP 8.3, PHP 8.4, PHP 8.5 e PHPStan em PHP 8.3 — terminaram `completed/success`. O item 4 continua pendente e bloqueado pelo toolchain local ausente; nenhum item posterior foi promovido.

> **Atualização de estado — 2026-09-23:** o `master` está sincronizado no SHA `02e8a009819cf437c1c83c75ee80b573eabcff54`. O item 4 continua sendo o primeiro item não concluído e permanece bloqueado porque `php`, Composer, PHPUnit, PHPStan e Docker estão ausentes localmente. A evidência anterior confirmou 47 usos das APIs preparadas no escopo administrativo/componentes, sem chamadas DBO legadas executáveis; as ocorrências restantes são um `simpleQuery()` comentado e chamadas `fetch()` de templates. As issues abertas consultadas são #216–#220, #224–#226, #239 e #240; as PRs #227 e #228 continuam abertas e conflitantes (`mergeable=CONFLICTING`, `mergeStateStatus=DIRTY`). Os checks do SHA atual estão `completed/success` para PHP 8.3, PHP 8.4, PHP 8.5 e PHPStan em PHP 8.3. O ciclo foi registrado em `docs/RELATORIO_CICLO_2026-09-23.md`; não houve alteração funcional, expansão da baseline ou criação de issue artificial.

> **Atualização de estado — 2026-09-20:** a fotografia foi atualizada no `master` sincronizado neste ciclo. O item 4 continua sendo o primeiro item não concluído: a análise administrativa anterior não encontrou chamadas DBO legadas executáveis, mas a validação PHP/Composer/PHPUnit/PHPStan continua bloqueada pela ausência do toolchain local. A consulta atual não encontrou issues nem PRs abertas. O ciclo está registrado em `docs/RELATORIO_CICLO_2026-09-20.md`; não houve alteração de código, baseline ou issue.

> **Fechamento — 2026-09-20:** a documentação foi mesclada na PR #237. O `master` resultante é `4a2f3c33687aae5b286021a6096c2ac36548180f`; os workflows PHP 8.3/8.4/8.5 e PHPStan terminaram com `completed/success`. O item 4 continua pendente e bloqueado por ambiente local, sem promoção de itens posteriores.

> **Atualização de estado — 2026-09-21:** o `master` sincronizado está no SHA `ec993ec13bfbadccaa60d3b5cc278e8b82cd4e44`. As issues abertas são #216–#220, #224–#226, #239 e #240; as PRs #227 e #228 continuam abertas com estado de merge desconhecido. Os workflows pós-merge `35494113225` (compatibilidade) e `35494113208` (PHPStan) terminaram com `completed/success`. O item 4 continua sendo o primeiro pendente e está bloqueado porque `php`, Composer, PHPUnit, PHPStan e Docker não estão disponíveis localmente. O ciclo foi registrado em `docs/RELATORIO_CICLO_2026-09-21.md`; não houve alteração funcional, expansão da baseline, criação de issue, PR ou merge artificial.

> **Fechamento — 2026-09-21:** a PR #241 foi mesclada após quatro checks `completed/success` no SHA `d73d59d1634d5d2d094e0a8c51ce79d2f0fefd16`. O `master` resultante é `c8a08b8e5533c61b00e9c8b81647002de892d61c`; os workflows pós-merge `35567054119` (PHP 8.3/8.4/8.5, lint, Composer, Docker) e `35567054068` (PHPStan) terminaram com `completed/success`. O item 4 permanece pendente e bloqueado; a evidência final está em `docs/RELATORIO_CICLO_2026-09-21.md`.

## Estado atual

A árvore de trabalho local estava limpa e o `master` local estava alinhado a `origin/master`. A consulta ao GitHub não encontrou issues abertas no momento da auditoria. Portanto, as referências a issues nos planos antigos são **histórico de governança**, não evidência de que existam tickets abertos atualmente.

O PHPStan nível 1 aparece como zerado no histórico documentado, sem expansão da baseline. Os achados SEC-001, SEC-002 e SEC-003 da auditoria de 2026-09-13 já estão corrigidos no `master` e cobertos por `SecurityRegressionTest`; SEC-004 e SEC-006 foram analisados e permanecem protegidos por configuração/deployment. Não foi possível repetir PHP, Composer, PHPUnit, PHPStan ou Docker neste sandbox porque esses executáveis não estão instalados; a validação executável deve ser feita no CI ou em ambiente PHP 8.3.

## Backlog priorizado

| Ordem | Pendência | Local principal | Prioridade | Dependência | Critério de aceite |
|---:|---|---|---|---|---|
| 1 | Parametrizar os filtros SQL livres das APIs `getTags()` e `getArchieveDates()` | `prescia/plugins/bi_bb/module.php` | **Concluído** | Filtros estruturados sem call sites externos no repositório | Valores vinculados com tipos explícitos; regressões e evidência registradas no relatório de ciclo |
| 2 | Migrar a consulta principal do endpoint de thread | `prescia/plugins/bi_bb/payload/content/thread.php` e caminho `runContent()` | **Concluído** | Contrato de SQL-array confirmado no lote | Consulta preparada, paginação preservada e regressão contra concatenação antiga |
| 3 | Parametrizar os filtros da árvore parental em `getContents()` | `prescia/components/module.php` e consumidores administrativos | **Concluído** | Transporte de tipos/parâmetros compatível com o legado | Valores vinculados; identificadores estruturais continuam derivados de metadados |
| 4 | Fazer varredura de todos os fluxos administrativos ainda não cobertos pelos lotes SQL registrados | `prescia/plugins/bi_adm/**`, `prescia/components/**` | P1 | Concluir os três lotes P0 ou executar em paralelo por componente isolado | **Analisado em 2026-09-15:** não há chamadas DBO legadas executáveis no escopo; falta validação executável local e revisão contínua dos diagnósticos PHPStan nível 4 |
| 5 | Revalidar autenticação, CSRF, sessão, upload/CKFinder, serialização, debug/headers e rate limiting contra o estado atual | Planos `docs/PLANO_ACAO_SEGURANCA.md` e testes em `tests/` | P1 | Ambiente PHP 8.3 + MySQL/MariaDB descartável | Cada controle possui teste negativo ou evidência manual; itens não comprovados permanecem pendentes |
| 6 | Repetir a suíte completa em ambiente suportado | `.github/workflows/php83.yml` | P1 | Runner CI ou ambiente local com PHP 8.3, extensões e Docker | `composer validate`, `composer audit`, PHPUnit, lint, PHPStan, build e scan Docker concluídos com `completed/success` |
| 7 | Revisar e atualizar os planos históricos para refletir o estado pós-lotes SQL | `docs/RELATORIO_PHPSTAN_PROGRESSO.md`, `docs/PLANO_ACAO_SEGURANCA.md`, `CHANGELOG.md` | P2 | Cada lote deve produzir evidência de CI | Diferenciar explicitamente corrigido, analisado e pendente; não encerrar uma frente ampla por causa de um lote parcial |

## Loop de execução por lote

Executar um único item delimitado por ciclo. Não misturar remediação PHPStan, migração SQL, autenticação e refatoração funcional no mesmo commit.

1. **Selecionar:** escolher o primeiro item não bloqueado da tabela e limitar o escopo a um arquivo, método ou fluxo relacionado.
2. **Inventariar:** registrar commit-base, call sites, entrada externa, consulta atual, comportamento esperado e teste existente.
3. **Confirmar contexto:** rastrear o carregador real, a classe de `$this`/`$core`, a origem de identificadores estruturais e o contrato de paginação/retorno.
4. **Implementar a menor correção:** preferir `queryPrepared()` ou `fetchPrepared()` com tipos explícitos; nunca transformar entrada externa em identificador SQL; não ampliar a baseline.
5. **Adicionar regressão:** cobrir o contrato seguro e rejeitar a concatenação antiga. Para filtros, testar aspas simples/duplas, unicode, nulos, arrays inesperados e payloads clássicos.
6. **Validar focalizadamente:** executar PHPStan no grupo tratado, `php -l` nos arquivos alterados e `git diff --check`.
7. **Validar globalmente:** executar PHPUnit, PHPStan global, lint completo, auditoria de dependências e build/scan Docker no CI PHP 8.3.
8. **Documentar:** registrar corrigido, analisado e pendente no relatório do ciclo e no changelog quando a mudança for pública.
9. **Publicar:** criar commit pequeno e coerente; acompanhar todos os check-runs do SHA publicado. Não considerar o lote concluído com apenas um job verde.
10. **Decidir o próximo ciclo:** se todos os checks forem `completed/success`, remover o item do backlog e iniciar o próximo; se falhar, abrir um ciclo corretivo do mesmo item antes de avançar.

## Critérios de parada

Parar o loop e registrar bloqueio quando ocorrer qualquer uma destas condições:

- o contrato do SQL legado não puder ser confirmado sem risco funcional;
- a alteração exigir decisão de compatibilidade externa ou mudança de API pública;
- o CI revelar regressão em PHP 8.3, PHPUnit, PHPStan, dependências ou imagem;
- a correção depender de credencial, ambiente de staging ou banco representativo não disponível;
- houver tentação de silenciar o diagnóstico com `mixed`, `@phpstan-ignore` ou nova entrada na baseline;
- a pendência estiver documentada apenas em plano histórico, mas não houver evidência de que ainda existe no código atual.

## Estado do ciclo 2026-09-14

O repositório foi clonado em `/home/ubuntu/Prescia` e a fotografia original não possuía alterações de código pendentes. As skills versionadas foram instaladas e validadas. A revisão dos achados da auditoria confirmou que os três itens de segurança acionáveis já estão no `master`, com regressões em `tests/SecurityRegressionTest.php`; portanto, não foi criado um commit artificial de correção. A atualização documental do README foi publicada posteriormente na PR #210. A suíte local continua bloqueada pela ausência de PHP 8.3, Composer, PHPUnit, PHPStan e Docker; o CI remoto é a fonte de validação executável.

## Estado do ciclo 2026-09-15

No commit `9e581b7`, foi feita a varredura dos 53 arquivos PHP de `prescia/plugins/bi_adm` e dos fluxos compartilhados em `prescia/components`. Não foram encontradas chamadas DBO executáveis a `query()`, `fetch()` ou `simpleQuery()`; as ocorrências `fetch()` restantes carregam templates e a ocorrência de `simpleQuery()` está comentada. Os SQLs administrativos examinados usam `queryPrepared()`/`fetchPrepared()`, com valores externos em placeholders e identificadores derivados de metadados. Os testes estáticos existentes em `tests/SecurityRegressionTest.php` já cobrem os principais contratos preparados. O item foi analisado, mas não marcado como concluído porque PHP, Composer, PHPUnit, PHPStan e Docker não estão disponíveis localmente. A documentação foi publicada na PR #230, mesclada no SHA `8d09991552f0c8e44da880f8bbd3eeb2e468fc54`; os workflows pós-merge `34935479495` e `34935479688` concluíram com `completed/success`. Evidência detalhada: `docs/RELATORIO_CICLO_2026-09-15.md`.
No commit `9e581b7`, foi feita a varredura dos 53 arquivos PHP de `prescia/plugins/bi_adm` e dos fluxos compartilhados em `prescia/components`. Não foram encontradas chamadas DBO executáveis a `query()`, `fetch()` ou `simpleQuery()`; as ocorrências `fetch()` restantes carregam templates e a ocorrência de `simpleQuery()` está comentada. Os SQLs administrativos examinados usam `queryPrepared()`/`fetchPrepared()`, com valores externos em placeholders e identificadores derivados de metadados. Os testes estáticos existentes em `tests/SecurityRegressionTest.php` já cobrem os principais contratos preparados. O item foi analisado, mas não marcado como concluído porque PHP, Composer, PHPUnit, PHPStan e Docker não estão disponíveis localmente. A documentação foi publicada na PR #230, mesclada no SHA `8d09991552f0c8e44da880f8bbd3eeb2e468fc54`; os workflows pós-merge `34935479495` e `34935479688` concluíram com `completed/success`. Evidência detalhada: `docs/RELATORIO_CICLO_2026-09-15.md`.

## Estado do ciclo 2026-09-16

O repositório foi sincronizado em `master` no commit `20ae614`. O primeiro item não concluído continua sendo o item 4, mas não há ambiente local para confirmar runtime, lint, PHPUnit ou PHPStan: `php`, `composer`, `vendor/bin/phpunit` e `vendor/bin/phpstan` estão ausentes; Docker também não está disponível no shell efetivo. As PRs abertas #227 e #228 foram consultadas e estão `mergeable=CONFLICTING`/`mergeStateStatus=DIRTY`, portanto não foram misturadas ao ciclo. Não houve alteração de PHP, testes funcionais, baseline ou criação de issue. O registro foi publicado na PR #232, que recebeu 9 checks `completed/success` e foi mesclada; o SHA resultante de `master` é `999a08e0a20df2ffbb27e0bdedcecc09e02157c0`, e o workflow pós-merge #35062355570 concluiu com `success`, incluindo PHP 8.3/8.4/8.5, PHPStan e build/scan Docker no job PHP 8.3. O ciclo permanece bloqueado para correção de código e está documentado em `docs/RELATORIO_CICLO_2026-09-16.md`.

## Primeiro ciclo recomendado

Começar pelo item 1, `getTags()`/`getArchieveDates()`, somente após confirmar se essas APIs possuem consumidores fora deste repositório. Se não houver consumidores, preservar compatibilidade com um adaptador que converta filtros permitidos em estrutura tipada ou marcar a API como legado sem call sites e avançar para o item 2. O item 2 é o próximo alvo de maior risco no código executado pelo endpoint de thread.

## Evidências desta auditoria

- Skill versionada: `skills/phpstan-legacy-remediation/SKILL.md`.
- Relatório de progresso PHPStan: `docs/RELATORIO_PHPSTAN_PROGRESSO.md`.
- Plano do próximo lote: `docs/PLANO_PHPSTAN_PROXIMO_LOTE.md`.
- Plano de segurança: `docs/PLANO_ACAO_SEGURANCA.md`.
- CI principal: `.github/workflows/php83.yml`.
- Relatório do ciclo atual: `docs/RELATORIO_CICLO_2026-09-17.md`.
- APIs livres identificadas por busca no commit auditado: `getTags()`, `getArchieveDates()` e `getContents()`.

> Este documento é um backlog operacional. Cada ciclo deve atualizar o status e anexar a evidência antes de declarar a pendência concluída.

> **Atualização de estado — 2026-09-25:** o `master` foi sincronizado no SHA `2b237056362bbe4436ef9c49f8a2ba3a5957d7b9`. As issues abertas consultadas são #216–#220, #224–#226, #239 e #240; a PR aberta #246 é um lote CSRF independente, está `mergeable=CONFLICTING`/`mergeStateStatus=DIRTY` e falha nos testes PHP 8.3, 8.4 e 8.5, portanto não foi misturada. O item 4 continua sendo o primeiro item não concluído e permanece bloqueado porque `php`, Composer, PHPUnit, PHPStan e Docker estão ausentes localmente. A inspeção confirmou 44 usos das APIs preparadas no escopo administrativo/componentes e nenhuma chamada DBO legada executável nova; os checks do SHA atual estão `completed/success` para PHP 8.3, PHP 8.4, PHP 8.5 e PHPStan em PHP 8.3. O ciclo foi registrado em `docs/RELATORIO_CICLO_2026-09-25.md`; não houve alteração funcional, expansão da baseline ou criação de issue artificial.

> **Fechamento — 2026-09-25:** a PR documental #249 foi mesclada após nove checks `completed/success` no SHA `53cba92494cdc6e815970622a3c5f670bd9470e9`. O `master` resultante é `74eea614a0d45d7ee3cb077660ef81ac50b8a7af`; os quatro checks pós-merge — PHP 8.3, PHP 8.4, PHP 8.5 e PHPStan em PHP 8.3 — terminaram `completed/success`. O item 4 continua pendente e bloqueado; nenhum item posterior foi promovido.
> **Atualização de estado — 2026-09-26:** o `master` foi sincronizado no SHA `12574e3a3982c6898e3f114f549bb16bdae1d185`. A consulta atual não encontrou issues abertas; a PR aberta #246 é um lote CSRF independente, está `mergeable=false`/`mergeable_state=dirty` e falha nos testes PHP 8.3, 8.4 e 8.5, portanto não foi misturada. O item 4 continua sendo o primeiro item não concluído e permanece bloqueado porque `php`, Composer, PHPUnit, PHPStan e Docker estão ausentes localmente. A inspeção estática confirmou as APIs preparadas no escopo administrativo/componentes, sem nova chamada DBO legada executável; `simpleQuery()` permanece apenas comentado e `fetch()` corresponde ao mecanismo de templates. Os checks do SHA atual estão `completed/success` para PHP 8.3, PHP 8.4, PHP 8.5 e PHPStan em PHP 8.3. O ciclo foi registrado em `docs/RELATORIO_CICLO_2026-09-26.md`; não houve alteração funcional, expansão da baseline, criação de issue ou PR artificial.
> **Fechamento — 2026-09-26:** a PR documental #251 foi mesclada após `mergeable=true`, `mergeable_state=clean` e nove checks `completed/success` no SHA `a927e21f3ba353abd2cd4ca8094784fe6ba44e51`. O `master` resultante é `920bbc7f434703194725dd38a1cd5f74adde6fe1`; seus quatro checks pós-merge — PHP 8.3, PHP 8.4, PHP 8.5 e PHPStan em PHP 8.3 — terminaram `completed/success`. O item 4 permanece pendente e bloqueado pela ausência do toolchain local; nenhum item posterior foi promovido.
