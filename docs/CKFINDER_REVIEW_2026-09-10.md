# Revisão CKFinder — 2026-09-10

> **Atualização de estado — 2026-09-14:** este relatório registra a fotografia histórica de 120 arquivos. Após a modernização posterior, o inventário atual contém **83 arquivos PHP**, a árvore `core/connector/php/php4` foi removida fisicamente e o runtime ativo usa exclusivamente `core/connector/php/modern`. Os itens abaixo que descrevem PHP4 como presente ou desativado no repositório devem ser lidos como histórico; a evidência vigente é `tests/CKFinderModernizationTest.php` e o changelog da modernização. Não há correção de código pendente identificada nesta revisão; este update corrige apenas a defasagem documental.

## Conclusão

A revisão original percorreu **120 arquivos PHP** presentes em `pages/_js/ckfinder`; todos passaram por `php -l` naquele ciclo. Depois, a modernização removeu os arquivos legados e o inventário atual passou a conter **83 arquivos PHP**. O entrypoint ativo fixa o runtime moderno, e o caminho PHP4 não existe nem é selecionável no runtime suportado pelo projeto. Os controles de upload, ACL, contenção canônica de caminhos, callbacks, downloads e tipos de recurso são cobertos pelas regressões de segurança existentes.

A análise PHPStan isolada foi executada em configuração temporária porque `phpstan.neon.dist` exclui deliberadamente o legado. O resultado produziu diagnósticos residuais apenas em includes relativos da árvore legada e em colisões de classes PHP4/PHP5 analisadas simultaneamente. Os diagnósticos de variáveis indefinidas nos handlers ativos foram corrigidos neste ciclo. Nenhuma entrada foi adicionada à baseline.

## Contexto de execução

`pages/_js/ckfinder/ckfinder.php` carrega o entrypoint moderno. `core/connector/php/constants.php` aponta `CKFINDER_CONNECTOR_LIB_DIR` para `./modern`. `config.php` exige sessão administrativa, atribui o papel `admin`, usa limites positivos para uploads e não anuncia o tipo Flash ativo.

A árvore PHP4 permanecia no repositório na data desta revisão, mas foi posteriormente removida como parte da modernização CKFinder. A remoção foi acompanhada por regressões que impedem o retorno do bootstrap, dos seletores e da árvore PHP4 ao runtime.

## Achados e correções deste ciclo

| Área | Evidência | Resultado |
| --- | --- | --- |
| Entry point | `ckfinder.php`, `constants.php` | PHP5 é o único runtime selecionável; PHP4 não é anunciado |
| Upload | `FileUpload.php` e regressões de upload | Contrato completo de `$_FILES`, MIME, tamanho, HTML e contenção canônica |
| Filesystem | handlers PHP5 e `Utils/FileSystem.php` | Operações mutáveis validam contenção de caminho e pais canônicos |
| Callback | `ErrorHandler/FileUpload.php`, `QuickUpload.php` | Valores codificados com JSON hex e headers `nosniff`/no-store |
| Download | `DownloadFile.php` | Content-Disposition seguro, nome controlado e `nosniff` |
| Imagem | `Thumbnail.php`, `imageresize/plugin.php`, `watermark/plugin.php` | Recursos inicializados com falha segura; parâmetros de resize validados |
| Configuração | `config.php` | Erros não são exibidos; modos de arquivo/diretório são restritivos; ACL exige `admin` |
| PHPStan | análise isolada temporária no ciclo original | Resíduos históricos classificados como includes relativos/runtime PHP4-PHP5; baseline intacta |

## Validação

| Verificação | Resultado |
| --- | --- |
| Arquivos PHP CKFinder | 120 no ciclo original; 83 no estado atual |
| `php -l` na árvore CKFinder | Aprovado em todos os arquivos |
| `phpstan.neon.dist` nível 3 | Aprovado sem diagnósticos |
| PHPUnit completo | Aprovado: 136 testes, 3.345 asserções; 2 skipped e 2 deprecações existentes |
| Composer audit | Nenhum advisory encontrado |
| `git diff --check` | Aprovado |
| Baseline PHPStan | Sem alteração |

## Status individual

| # | Arquivo | Status | Sintaxe |
| ---: | --- | --- | --- |
| 001 | `pages/_js/ckfinder/ckfinder.php` | corrigido e ativo; entrypoint/ACL revisados | `php -l` passou |
| 002 | `pages/_js/ckfinder/config.php` | corrigido e ativo; entrypoint/ACL revisados | `php -l` passou |
| 003 | `pages/_js/ckfinder/core/ckfinder_php4.php` | analisado sem alteração | `php -l` passou |
| 004 | `pages/_js/ckfinder/core/ckfinder_php5.php` | analisado sem alteração | `php -l` passou |
| 005 | `pages/_js/ckfinder/core/connector/php/connector.php` | analisado sem alteração | `php -l` passou |
| 006 | `pages/_js/ckfinder/core/connector/php/constants.php` | corrigido e ativo; PHP5 fixado | `php -l` passou |
| 007 | `pages/_js/ckfinder/core/connector/php/lang/bg.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 008 | `pages/_js/ckfinder/core/connector/php/lang/cs.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 009 | `pages/_js/ckfinder/core/connector/php/lang/cy.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 010 | `pages/_js/ckfinder/core/connector/php/lang/da.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 011 | `pages/_js/ckfinder/core/connector/php/lang/de.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 012 | `pages/_js/ckfinder/core/connector/php/lang/el.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 013 | `pages/_js/ckfinder/core/connector/php/lang/en.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 014 | `pages/_js/ckfinder/core/connector/php/lang/eo.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 015 | `pages/_js/ckfinder/core/connector/php/lang/es-mx.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 016 | `pages/_js/ckfinder/core/connector/php/lang/es.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 017 | `pages/_js/ckfinder/core/connector/php/lang/et.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 018 | `pages/_js/ckfinder/core/connector/php/lang/fa.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 019 | `pages/_js/ckfinder/core/connector/php/lang/fi.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 020 | `pages/_js/ckfinder/core/connector/php/lang/fr.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 021 | `pages/_js/ckfinder/core/connector/php/lang/gu.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 022 | `pages/_js/ckfinder/core/connector/php/lang/he.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 023 | `pages/_js/ckfinder/core/connector/php/lang/hi.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 024 | `pages/_js/ckfinder/core/connector/php/lang/hr.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 025 | `pages/_js/ckfinder/core/connector/php/lang/hu.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 026 | `pages/_js/ckfinder/core/connector/php/lang/it.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 027 | `pages/_js/ckfinder/core/connector/php/lang/ja.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 028 | `pages/_js/ckfinder/core/connector/php/lang/lt.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 029 | `pages/_js/ckfinder/core/connector/php/lang/lv.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 030 | `pages/_js/ckfinder/core/connector/php/lang/nb.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 031 | `pages/_js/ckfinder/core/connector/php/lang/nl.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 032 | `pages/_js/ckfinder/core/connector/php/lang/nn.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 033 | `pages/_js/ckfinder/core/connector/php/lang/no.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 034 | `pages/_js/ckfinder/core/connector/php/lang/pl.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 035 | `pages/_js/ckfinder/core/connector/php/lang/pt-br.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 036 | `pages/_js/ckfinder/core/connector/php/lang/ro.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 037 | `pages/_js/ckfinder/core/connector/php/lang/ru.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 038 | `pages/_js/ckfinder/core/connector/php/lang/sk.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 039 | `pages/_js/ckfinder/core/connector/php/lang/sl.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 040 | `pages/_js/ckfinder/core/connector/php/lang/sv.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 041 | `pages/_js/ckfinder/core/connector/php/lang/tr.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 042 | `pages/_js/ckfinder/core/connector/php/lang/vi.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 043 | `pages/_js/ckfinder/core/connector/php/lang/zh-cn.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 044 | `pages/_js/ckfinder/core/connector/php/lang/zh-tw.php` | analisado sem alteração; catálogo estático | `php -l` passou |
| 045 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CommandHandlerBase.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 046 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CopyFiles.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 047 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/CreateFolder.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 048 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DeleteFile.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 049 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DeleteFolder.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 050 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/DownloadFile.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 051 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/FileUpload.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 052 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/GetFiles.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 053 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/GetFolders.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 054 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/Init.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 055 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/LoadCookies.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 056 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/MoveFiles.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 057 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/QuickUpload.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 058 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFile.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 059 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/RenameFolder.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 060 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/Thumbnail.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 061 | `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/XmlCommandHandlerBase.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 062 | `pages/_js/ckfinder/core/connector/php/php4/Core/AccessControlConfig.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 063 | `pages/_js/ckfinder/core/connector/php/php4/Core/Config.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 064 | `pages/_js/ckfinder/core/connector/php/php4/Core/Connector.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 065 | `pages/_js/ckfinder/core/connector/php/php4/Core/Factory.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 066 | `pages/_js/ckfinder/core/connector/php/php4/Core/FolderHandler.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 067 | `pages/_js/ckfinder/core/connector/php/php4/Core/Hooks.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 068 | `pages/_js/ckfinder/core/connector/php/php4/Core/ImagesConfig.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 069 | `pages/_js/ckfinder/core/connector/php/php4/Core/Registry.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 070 | `pages/_js/ckfinder/core/connector/php/php4/Core/ResourceTypeConfig.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 071 | `pages/_js/ckfinder/core/connector/php/php4/Core/ThumbnailsConfig.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 072 | `pages/_js/ckfinder/core/connector/php/php4/Core/Xml.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 073 | `pages/_js/ckfinder/core/connector/php/php4/ErrorHandler/Base.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 074 | `pages/_js/ckfinder/core/connector/php/php4/ErrorHandler/FileUpload.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 075 | `pages/_js/ckfinder/core/connector/php/php4/ErrorHandler/Http.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 076 | `pages/_js/ckfinder/core/connector/php/php4/ErrorHandler/QuickUpload.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 077 | `pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 078 | `pages/_js/ckfinder/core/connector/php/php4/Utils/Misc.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 079 | `pages/_js/ckfinder/core/connector/php/php4/Utils/Security.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 080 | `pages/_js/ckfinder/core/connector/php/php4/Utils/XmlNode.php` | desativado no runtime suportado; lint aprovado | `php -l` passou |
| 081 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CommandHandlerBase.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 082 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CopyFiles.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 083 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CreateFolder.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 084 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFile.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 085 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DeleteFolder.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 086 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DownloadFile.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 087 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 088 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/GetFiles.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 089 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/GetFolders.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 090 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/Init.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 091 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/LoadCookies.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 092 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/MoveFiles.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 093 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/QuickUpload.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 094 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFile.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 095 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/RenameFolder.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 096 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/Thumbnail.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 097 | `pages/_js/ckfinder/core/connector/php/php5/CommandHandler/XmlCommandHandlerBase.php` | corrigido/revisado; handler ativo coberto por regressões | `php -l` passou |
| 098 | `pages/_js/ckfinder/core/connector/php/php5/Core/AccessControlConfig.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 099 | `pages/_js/ckfinder/core/connector/php/php5/Core/Config.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 100 | `pages/_js/ckfinder/core/connector/php/php5/Core/Connector.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 101 | `pages/_js/ckfinder/core/connector/php/php5/Core/Factory.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 102 | `pages/_js/ckfinder/core/connector/php/php5/Core/FolderHandler.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 103 | `pages/_js/ckfinder/core/connector/php/php5/Core/Hooks.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 104 | `pages/_js/ckfinder/core/connector/php/php5/Core/ImagesConfig.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 105 | `pages/_js/ckfinder/core/connector/php/php5/Core/Registry.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 106 | `pages/_js/ckfinder/core/connector/php/php5/Core/ResourceTypeConfig.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 107 | `pages/_js/ckfinder/core/connector/php/php5/Core/ThumbnailsConfig.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 108 | `pages/_js/ckfinder/core/connector/php/php5/Core/Xml.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 109 | `pages/_js/ckfinder/core/connector/php/php5/ErrorHandler/Base.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 110 | `pages/_js/ckfinder/core/connector/php/php5/ErrorHandler/FileUpload.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 111 | `pages/_js/ckfinder/core/connector/php/php5/ErrorHandler/Http.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 112 | `pages/_js/ckfinder/core/connector/php/php5/ErrorHandler/QuickUpload.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 113 | `pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 114 | `pages/_js/ckfinder/core/connector/php/php5/Utils/Misc.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 115 | `pages/_js/ckfinder/core/connector/php/php5/Utils/Security.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 116 | `pages/_js/ckfinder/core/connector/php/php5/Utils/XmlNode.php` | corrigido/revisado; runtime PHP5 ativo | `php -l` passou |
| 117 | `pages/_js/ckfinder/index.php` | analisado sem alteração | `php -l` passou |
| 118 | `pages/_js/ckfinder/plugins/fileeditor/plugin.php` | corrigido/revisado; plugin ativo sob ACL/configuração | `php -l` passou |
| 119 | `pages/_js/ckfinder/plugins/imageresize/plugin.php` | corrigido/revisado; plugin ativo sob ACL/configuração | `php -l` passou |
| 120 | `pages/_js/ckfinder/plugins/watermark/plugin.php` | corrigido/revisado; plugin ativo sob ACL/configuração | `php -l` passou |

## Pendências explícitas

Os includes relativos que o PHPStan não consegue resolver a partir da raiz do repositório pertencem ao carregamento histórico baseado em `CKFINDER_CONNECTOR_LIB_DIR` e não foram reescritos para evitar alteração de compatibilidade do connector. A colisão de classes entre as árvores PHP4 e PHP5 também é uma limitação da análise simultânea de dois runtimes mutuamente exclusivos. A análise de PHP4 não deve ser usada como evidência de suporte ativo.

A validação de comportamento real do connector ainda requer homologação com sessão administrativa, diretório de uploads isolado e configuração do servidor que impeça execução de scripts na árvore de arquivos. A remoção dos arquivos PHP4 requer confirmação de que nenhum deploy externo depende desse runtime.

## Referências

[1]: https://github.com/leohmoraes/Prescia/issues/67 "Issue 67 — revisão completa do CKFinder"

[2]: https://github.com/leohmoraes/Prescia/blob/master/pages/_js/ckfinder/core/connector/php/constants.php "Constantes e seleção do runtime CKFinder"

[3]: https://github.com/leohmoraes/Prescia/blob/master/phpstan.neon.dist "Configuração PHPStan do Prescia"

[4]: https://github.com/leohmoraes/Prescia/blob/master/tests/SecurityRegressionTest.php "Regressões de segurança do Prescia"

**Issue:** [#67][1]

**Autor:** Manus AI

**Data:** 2026-09-10
