# Relatório interno resumido — PR #229

**Pull request:** [#229 — fix: model dynamic front controller configuration][1]  
**Projeto:** Prescia  
**Branch de origem:** `fix/phpstan-level4-front-controllers`  
**Branch de destino:** `master`  
**Merge commit:** `2b04d68550b8fe8beff2e4b69eb4d853acbaba54`  
**Data do merge:** 15 de setembro de 2026, 02:00 UTC  
**Estado:** Merge concluído

## Resumo executivo

A PR #229 iniciou o Lote 3 da remediação incremental do PHPStan, focado nos front controllers `index.php` e `prescia/index.php`. A alteração principal não modificou o comportamento de runtime. Ela informou ao PHPStan que determinadas constantes de configuração são dinâmicas, evitando que o analisador trate guards válidos como caminhos impossíveis.

A PR também consolidou a auditoria exploratória do nível 5 e documentou o plano de ação para a futura promoção do nível 3 ao nível 4.

## Alterações realizadas

| Arquivo | Alteração | Impacto |
|---|---|---|
| `phpstan.neon.dist` | Adicionadas seis constantes a `dynamicConstantNames` | Preserva a análise de caminhos dinâmicos de error handling, cache, developer mode, economic mode e honeypot |
| `docs/RELATORIO_PHPSTAN_PROGRESSO.md` | Registrados o Lote 3 e a auditoria de nível 5 | Mantém métricas, limites e pendências rastreáveis |
| `docs/PLANO_ACAO_PHPSTAN_NIVEL4.md` | Adicionado plano completo para promover o PHPStan do nível 3 ao 4 | Define lotes, critérios de aceite, gates e política de baseline |
| `CHANGELOG.md` | Atualizado o histórico público do projeto | Registra a alteração de configuração e a auditoria técnica |

Nenhum arquivo de runtime foi alterado pela PR. Nenhuma entrada foi adicionada à `phpstan-baseline.neon`.

## Resultado técnico

O inventário focalizado dos front controllers continha 23 diagnósticos no nível 4. Após a configuração das constantes dinâmicas, a análise focalizada passou a retornar zero diagnósticos.

A análise exploratória posterior no nível 5 identificou 458 diagnósticos em 78 arquivos e 38 identificadores. Esse resultado não promoveu o nível oficial, que permanece em 3. Os diagnósticos foram classificados como backlog para os lotes de remediação do nível 4.

## Validações

| Verificação | Resultado |
|---|---|
| PHPStan nível 4 nos front controllers | **Aprovado — 0 diagnósticos** |
| PHPStan global nível 3 | **Aprovado — 0 erros de arquivo** |
| PHPUnit | **Aprovado — 144 testes, 3.093 asserções e 2 skips** |
| Depreciações PHPUnit/PHP | **Nenhuma observada na validação da PR** |
| Lint PHP dos arquivos avaliados | **Aprovado** |
| `git diff --check` | **Aprovado** |
| Baseline | **Sem expansão** |

## Validação pós-merge

Os quatro check-runs do merge commit `2b04d68` foram concluídos com sucesso:

| Check | Estado | Resultado |
|---|---|---|
| PHP 8.3 tests | Concluído | **Sucesso** |
| PHP 8.4 tests | Concluído | **Sucesso** |
| PHP 8.5 tests | Concluído | **Sucesso** |
| PHPStan on PHP 8.3 | Concluído | **Sucesso** |

## Limitações e pendências

A PR não conclui a promoção do PHPStan ao nível 4. O nível oficial permanece em 3 porque a análise exploratória nível 5 ainda apresenta 458 diagnósticos. Esses diagnósticos não foram ocultados na baseline.

O próximo trabalho recomendado é o Lote 4.1: gerar o inventário reproduzível do nível 4 e revisar os contratos dinâmicos de `bi_stats` e `core.php`, seguidos pelos lotes de argumentos, fluxos impossíveis, arrays e módulos sensíveis.

## Commits incluídos

| Commit | Descrição |
|---|---|
| `6cd5571` | Modela constantes dinâmicas dos front controllers |
| `f04bacb` | Registra a auditoria exploratória do PHPStan nível 5 |
| `dc56d69` | Adiciona o plano de ação para o PHPStan nível 4 |
| `e65a740` | Documenta os diagnósticos mais comuns esperados no nível 4 |

## Referências

[1]: https://github.com/leohmoraes/Prescia/pull/229 "Prescia Pull Request #229 — Front controllers"
[2]: https://github.com/leohmoraes/Prescia/actions/runs/34919461706 "Prescia — PHP static analysis after merge"
[3]: https://github.com/leohmoraes/Prescia/actions/runs/34919461762 "Prescia — PHP compatibility matrix after merge"
