# CKFinder — Relatório final de modernização

## Escopo e resultado

A migração CKFinder foi concluída no `master` com uma implementação moderna do connector PHP, sem árvores de runtime PHP4/PHP5 e sem seletores ou fallbacks funcionais para essas versões. O inventário atual contém 83 arquivos PHP no conjunto CKFinder: 36 arquivos do connector moderno e 47 arquivos compartilhados, configuração, idiomas e plugins.

O bootstrap ativo é `pages/_js/ckfinder/core/ckfinder.php`, o entrypoint é `pages/_js/ckfinder/ckfinder.php` e a biblioteca ativa é `pages/_js/ckfinder/core/connector/php/modern/Core/Connector.php`. O teste `CKFinderModernizationTest` rejeita diretórios e entrypoints legados e confirma a seleção exclusiva da biblioteca moderna.

## Evidências de validação

| Verificação | Resultado |
|---|---|
| Composer validate | Aprovado |
| PHPUnit PHP 8.3 | 144 testes, 3.093 asserções; 2 deprecations e 2 skips existentes |
| Matriz PHP 8.3 | Aprovada |
| Matriz PHP 8.4 | Aprovada |
| Matriz PHP 8.5 | Aprovada |
| PHPStan global | 0 erros |
| Lint dos arquivos PHP rastreados | Aprovado |
| Build Docker PHP 8.3 | Aprovado |
| Trivy — vulnerabilidades CRITICAL/HIGH bloqueantes | Aprovado |
| Semgrep | Executado; alertas heurísticos revisados sem vulnerabilidade crítica confirmada |
| Gitleaks | 0 segredos encontrados |

A execução final da matriz foi o workflow `34857201776`, no commit `11367029d45686f5865736a4138449dd8126f0dd8`, com os três jobs concluídos com sucesso.

## Correções adicionais de compatibilidade

Durante a validação foram corrigidos três problemas descobertos pela matriz: o teste de runtime passou a aceitar PHP 8.3–8.5; `CPrescia::close()` deixou de usar `unset($this->template)` para evitar o diagnóstico de property hooks em PHP 8.4+; e dois casts `(integer)` foram normalizados para `(int)` em `prescia/lib/datetime.php` para eliminar deprecations do PHP 8.5.

## Segurança e manutenção

Os hardenings apontados pelos scanners em `prescia/lazyload/ajaxqueryunique.php` e `prescia/plugins/bi_stats/payload/content/stats_refdet.php` estão presentes no `master`. Consultas sensíveis do CRUD genérico, `autoPrune()`, ciclos parentais, cascatas de relacionamentos e verificações de autorização do plugin `bi_groups` usam parâmetros separados dos valores SQL.

Com a matriz, os scanners, os testes de inventário e o build Docker aprovados, não permanecem pendências técnicas abertas no escopo da modernização CKFinder. O deploy de produção deve seguir o procedimento operacional normal do projeto, usando exclusivamente a imagem e o connector modernos validados neste relatório.
