# Auditoria do Prescia — 2026-09-14

## Conclusão executiva

O repositório foi sincronizado com `origin/master` no commit `71fa1c5` (`security: consolidate CKFinder modern runtime`). A árvore estava limpa após a sincronização. Todas as skills presentes no diretório `skills/` foram reinstaladas em `/home/ubuntu/skills/`.

A coleta determinística foi executada sem falhas. O PHPStan nível 3 não apresentou diagnósticos, o lint global passou, o `composer audit` não reportou vulnerabilidades e não foram identificadas pull requests abertas pelo coletor. As issues abertas relevantes são o plano de modernização CKFinder (#198, #201, #202 e #203); as etapas de remoção do PHP4 (#199) e migração do connector PHP5 (#200) já foram encerradas por commits recentes.

Como correção deste ciclo, foi implementada uma regressão determinística para impedir o retorno de árvores de runtime PHP4/PHP5 e seletores legados do CKFinder, atendendo ao primeiro item executável da Issue #202.

## Estado do repositório

| Item | Resultado |
|---|---|
| Branch | `master` |
| Commit analisado | `71fa1c59c4c990781cb845dd4a160ab203402d34` |
| Estado antes da alteração | Limpo e sincronizado com `origin/master` |
| Skills do repositório | Instaladas em `/home/ubuntu/skills/` |
| Arquivos PHP CKFinder inventariados | 83 |
| Diretórios `php4`/`php5` do connector | Ausentes |
| `core/ckfinder_php4.php` e `core/ckfinder_php5.php` | Ausentes |

## Issues e pull requests

As issues abertas identificadas no backlog remoto são:

- [#198 — CKFinder: modernizar os 120 arquivos sem PHP4/PHP5](https://github.com/leohmoraes/Prescia/issues/198), épico;
- [#201 — modernizar arquivos compartilhados, bootstrap, idiomas e plugins](https://github.com/leohmoraes/Prescia/issues/201), escopo de 47 arquivos após correção de inventário;
- [#202 — consolidar testes, scanners e matriz de compatibilidade PHP 8.3+](https://github.com/leohmoraes/Prescia/issues/202);
- [#203 — remover referências legadas do deploy e encerrar a migração](https://github.com/leohmoraes/Prescia/issues/203).

As issues #199 e #200 foram encerradas após os lotes de remoção do runtime PHP4 e consolidação do connector moderno. Nenhuma nova issue foi criada, pois o teste implementado pertence diretamente ao escopo da #202 e não duplica o backlog existente.

## Diagnósticos PHPStan nível 3

A análise executou 228 arquivos usando `phpstan.neon.dist` e terminou com código zero. Não há diagnósticos PHPStan nível 3 pendentes neste commit. O estado atual não justifica expansão da baseline.

## Correção implementada para a Issue #202

Foi criado `tests/CKFinderModernizationTest.php` com duas regressões:

1. percorre recursivamente `pages/_js/ckfinder` e falha se uma árvore `php4`/`php5` ou um entrypoint `ckfinder_php4.php`/`ckfinder_php5.php` voltar a ser versionado;
2. confirma que `CKFINDER_CONNECTOR_LIB_DIR` aponta para `./modern` e que os entrypoints não reintroduzem constantes ou seletores dos runtimes removidos.

A regressão verifica também a presença do bootstrap único `core/ckfinder.php` e de `core/connector/php/modern/Core/Connector.php`.

## Varredura de segurança e dependências

A coleta determinística terminou com código zero para todas as etapas. Não foram identificadas novas vulnerabilidades Composer. As buscas de SQL, entrada externa, sinks de saída, rede, execução de comandos e desserialização continuam sendo indicadores heurísticos; não foram classificados novos achados confirmados neste ciclo.

## Validação

| Verificação | Resultado |
|---|---|
| `php -l tests/CKFinderModernizationTest.php` | Aprovado |
| PHPUnit focalizado de segurança e CKFinder | **108 testes, 589 assertions, aprovado** |
| PHPStan focalizado do novo teste | `[OK] No errors` |
| PHPUnit completo | **144 testes, 3091 assertions, aprovado** |
| Deprecations | 2, preexistentes/registradas pelo runtime de testes |
| Skips | 2, conforme configuração existente |
| `git diff --check` | Aprovado |

## Próximos passos

A Issue #202 ainda não deve ser encerrada apenas com este teste: permanecem os scanners Semgrep/Gitleaks/Trivy e a matriz de compatibilidade previstos no plano. A Issue #201 também permanece aberta para os 47 arquivos compartilhados/configuração/idiomas/plugins. A Issue #203 depende da conclusão dessas etapas e de validação de deploy/staging.

## Referências

- [Issue #201](https://github.com/leohmoraes/Prescia/issues/201)
- [Issue #202](https://github.com/leohmoraes/Prescia/issues/202)
- [Issue #203](https://github.com/leohmoraes/Prescia/issues/203)
- [Workflow PHP 8.3](https://github.com/leohmoraes/Prescia/blob/master/.github/workflows/php83.yml)
