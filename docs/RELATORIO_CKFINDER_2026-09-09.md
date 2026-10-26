# Revisão sistemática do CKFinder — 2026-09-09

**Repositório:** [leohmoraes/Prescia](https://github.com/leohmoraes/Prescia)  
**Issue:** [#67](https://github.com/leohmoraes/Prescia/issues/67)  
**Escopo:** connector PHP5, caminho PHP4 legado, entrypoints, configuração, handlers de mutação, upload, plugins e processamento de imagens.

## Inventário

O pacote contém **36 arquivos PHP5** no connector, **36 arquivos PHP4** legados e três plugins PHP prioritários: `fileeditor`, `imageresize` e `watermark`. O PHPStan global exclui a árvore `pages/_js/ckfinder`, de modo que a validação do pacote requer lint e testes isolados em CI.

## Vulnerabilidades e riscos encontrados

| ID | Severidade | Superfície | Evidência | Impacto | Tratamento |
|---|---|---|---|---|---|
| CKF-01 | Alta | Callbacks de upload | `ErrorHandler/FileUpload.php` e `QuickUpload.php` montam JavaScript por concatenação e escaping apenas de aspas simples. | XSS em respostas de upload com nomes, URLs ou mensagens controláveis. | Substituir por serialização JavaScript segura e adicionar payloads com aspas, barra invertida, newline e `</script>`. |
| CKF-02 | Alta | Upload PHP5/PHP4 | O fluxo legado usa validação parcial de `$_FILES` antes de `move_uploaded_file()`. | Estados inválidos, mensagens inconsistentes e superfície de upload não validada de forma uniforme. | Validar estrutura, erro, tamanho, arquivo temporário e upload HTTP antes do processamento; remover ou desativar PHP4. |
| CKF-03 | Alta | Tipos de recurso e `FileEditor` | A configuração permite extensões ativas como HTML, JS, XML e SWF; `SaveFile` grava conteúdo enviado. | Stored XSS ou execução em mesmo origin se a árvore for servida diretamente. | Reduzir allowlist ou isolar uploads em origem sem scripts; limitar conteúdo editável. |
| CKF-04 | Alta | Filesystem e handlers de mutação | Verificações de existência/escrita ocorrem separadas de copy, move, rename, mkdir e delete. | TOCTOU, symlink e escrita fora da raiz em condições concorrentes ou deploys permissivos. | Canonicalizar e revalidar no ponto da operação; usar criação/substituição atômica e testes de symlink. |
| CKF-05 | Alta | ACL e configuração | ACL padrão usa `role='*'` com view/create/rename/delete/upload. | Qualquer sessão autenticada com acesso ao connector pode receber capacidade excessiva. | Aplicar menor privilégio por papel e recurso; confirmar uso em produção. |
| CKF-06 | Alta | Exposição de erros e permissões | Configuração histórica usa `display_errors=1` e modos `0775`, com `umask(0)`. | Vazamento de caminhos/configuração e permissões de escrita/execução excessivas. | Já tratado parcialmente em PRs anteriores; validar deploy e manter regressões. |
| CKF-07 | Alta | `ImageResize` | `width`, `height` e `overwrite` são lidos sem validação completa; há regex duplicada para `newWidth`. | Bypass de limites, comportamento inesperado e DoS por dimensões/decodificação. | Validar inteiros positivos, limites, overwrite por allowlist e falhas de imagem. |
| CKF-08 | Alta | `ImageResizeInfo` e GD | `getimagesize()` é usado sem verificar retorno; plugins não impõem limite de pixels/memória. | Warnings, respostas inconsistentes e consumo elevado de CPU/memória. | Falha segura para imagem inválida e limites de pixels/bytes. |
| CKF-09 | Média | `Watermark` | Plugin não bloqueia inclusão direta e aceita caminho de source configurável; resultados de gravação não são verificados. | Execução fora do bootstrap, marca d'água ausente ou arquivo parcialmente escrito. | Bloquear inclusão direta, canonicalizar source e validar saída/limpeza. |
| CKF-10 | Média | CSRF e dispatch | Comandos são despachados por GET/POST e mutações dependem principalmente de sessão/ACL. | Operações mutáveis podem ser acionadas por requisições cross-site. | Exigir método POST e token CSRF no contrato do connector. |
| CKF-11 | Média | PHP4 legado | O caminho PHP4 duplica handlers vulneráveis e pode ser reativado em deploy legado. | Correções PHP5 não protegem a variante antiga. | Desativar/remover formalmente a runtime PHP4 ou criar hardening equivalente com testes compatíveis. |

## Lote corretivo iniciado

Este ciclo inicia correções de baixo risco e alta verificabilidade no plugin PHP5: validação completa de dimensões e `overwrite`, tratamento de imagem inválida e bloqueio de inclusão direta do watermark. As correções serão publicadas em PR separada, com regressão estática e CI PHP 8.3. O hardening de upload, callbacks, filesystem e desativação PHP4 permanece em lotes subsequentes.

## Status

A issue **#67 permanece aberta**: a revisão foi executada e os achados foram listados, mas o escopo ainda não atende aos critérios de encerramento enquanto CKF-01–CKF-05, CKF-07–CKF-11 não tiverem correção, desativação formal ou aceite documentado. Nenhuma nova issue foi criada porque todos os achados estão cobertos por #67 ou pelas frentes relacionadas #24, #65, #66 e #68.

## Validação

A revisão estrutural foi concluída. PHP, Composer, Docker e PHPStan não estão disponíveis localmente; lint, PHPUnit e PHPStan isolado serão executados pelo GitHub Actions. Nenhuma alteração no baseline é permitida para mascarar os achados.

## Atualização do plano CKF-MODERN-2026 — lote PHP4

Em 2026-09-14 foi executada a primeira fase do plano registrado nas issues [#198](https://github.com/leohmoraes/Prescia/issues/198) e [#199](https://github.com/leohmoraes/Prescia/issues/199). Os 36 arquivos do connector PHP4 e o bootstrap `core/ckfinder_php4.php` foram removidos do produto. O entrypoint deixou de carregar a verificação/fallback específico de PHP4, e `SecurityRegressionTest` passou a exigir a ausência da árvore PHP4 e do bootstrap legado.

O inventário PHP do CKFinder caiu de 120 para 83 arquivos. `git diff --check` passou e nenhuma referência funcional a PHP4 permanece fora das próprias asserções que verificam sua ausência. PHP, PHPUnit e PHPStan não estão instalados no ambiente local; a validação de lint, testes e análise estática deve ocorrer na CI PHP 8.3 antes do merge. Permanecem como próximos lotes a consolidação da variante PHP5 para uma implementação sem nomenclatura/runtime legado, a modernização dos 48 arquivos compartilhados/plugins e a validação de deploy.

Em seguida, a árvore de 36 arquivos anteriormente denominada PHP5 foi consolidada em `core/connector/php/modern`, o bootstrap foi renomeado para `core/ckfinder.php`, e todas as regressões foram atualizadas para os novos caminhos. A constante de modo legado foi removida e a validação de extensões de imagem deixou de depender de versões históricas. O inventário permanece com 83 arquivos PHP, agora com 36 arquivos no connector moderno e sem as árvores PHP4/PHP5. O lote requer CI PHP 8.3 verde antes do merge.

## Atualização do loop — lote de upload e callbacks

A revisão do código atual confirmou que parte de CKF-01 e CKF-02 já havia sido corrigida em lotes anteriores: os callbacks PHP5 usam `json_encode()` com flags hexadecimais, e `FileUpload` valida estrutura de `$_FILES`, códigos `UPLOAD_ERR_*`, `is_uploaded_file()`, tamanho e MIME ativo. O lote atual adiciona regressão explícita para esses controles e rejeita estruturas malformadas no comando `CopyFiles`, evitando warnings e coerções de arrays para strings em `name`, `type`, `folder` e `options`.

Esses controles não encerram a issue #67: a variante PHP4, os handlers de filesystem, CSRF, ACL, isolamento de conteúdo ativo e limites de imagem ainda permanecem no escopo aberto.

## Atualização do loop — contenção centralizada de diretórios

O `FolderHandler` PHP5 agora valida o caminho do recurso e o caminho de thumbnails com `isPathInside()` imediatamente após sua composição, antes de criar diretórios ou permitir que handlers prossigam. Isso reduz a dependência de verificações distribuídas e transforma uma configuração/caminho inválido em erro explícito. O controle não elimina todas as condições de corrida entre validação e operação; os handlers de mutação ainda requerem testes de symlink e revalidação no ponto de escrita/renomeação.

## Encerramento do ciclo — PRs #140–#142

As três PRs corretivas foram mescladas após CI verde:

- **#140** — validação de dimensões, `overwrite`, imagem inválida e bootstrap do watermark; merge `f417b47d3e9e040ead6b9d0fbfbf83e5cc6a8c13`.
- **#141** — validação estrutural de entradas do `CopyFiles` e regressões de upload/callbacks; merge `6e75df5a3e6fd5c60b5a36da64c650ee651dbe01`.
- **#142** — contenção centralizada de caminhos de recursos e thumbnails; merge `29d6445770d9c25556624860328077572cdd383d`.

A issue #67 permanece aberta. O ciclo não encontrou lacuna independente para nova issue e foi encerrado com todas as pendências remanescentes registradas no relatório geral.
