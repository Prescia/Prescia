# Correções de segurança — 26/10/2026

Base: `730792b40ee86a2072a9239bc4aa18dfeadeb5fa` (`master`).
Relatório público: https://github.com/leohmoraes/Prescia/issues/253.
Estado: correções implementadas e verificadas nesta branch para revisão por pull request, vinculada à issue #253. Acompanhar os checks remotos, o merge e a implantação pelo GitHub.

## Alterações

- **SEC-01:** `optimizer.php` usa somente GET/HEAD, recebe parâmetros de `$_GET` e resolve arquivos JS/CSS dentro de `__DIR__` por caminho canônico. Rejeita caminhos absolutos, traversal, tipos inesperados, arquivos de outra extensão e symlinks externos. Erros retornam 404 sem refletir a entrada e com `no-store`; respostas usam `nosniff`. A compressão fica a cargo de PHP/Apache. `jquery.js` aponta explicitamente para a versão revisada.
- **SEC-02:** a ação `delete.php` exige POST, valida CSRF antes de carregar o módulo ou executar a exclusão e lê as chaves somente de `$_POST`. O painel envia um formulário POST com o token de sessão e as chaves, em vez de navegar por GET. A autorização do módulo permanece no fluxo existente.
- **SEC-03:** `GetIP()` usa somente `REMOTE_ADDR` validado, ignora cabeçalhos de encaminhamento e não reutiliza o cache remoto de sessão. Em instalações com proxy, configurar os proxies confiáveis no servidor HTTP para que este forneça o `REMOTE_ADDR` verificado; a aplicação não confia diretamente em cabeçalhos. Limitação preexistente: o armazenamento do limitador é local ao nó e o contador continua sendo por conta/IP; proteção distribuída exige armazenamento compartilhado.
- **SEC-04:** removidos jQuery 1.11.1/2.1.1 e atualizada a cópia 1.7.1 da galeria para **3.7.1**. O carregador Bootstrap usa essa versão. Escolhida a última versão 3.x publicada no [catálogo oficial](https://releases.jquery.com/jquery/), para limitar a quebra de APIs do legado. O SHA-256 foi conferido com o SRI oficial `/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=`. O teste de compatibilidade detectou uso de `.live()` no ColorBox 1.3.19; atualizado para **1.6.4**, com CSS, imagens e licença oficiais do [upstream](https://github.com/jackmoore/colorbox/tree/1.6.4). Esse plugin está arquivado: a atualização resolve a compatibilidade exercitada, mas não substitui uma modernização completa do CKFinder. `docs/vendor-assets.json` registra versões, origens e hashes dos scripts revisados.

## Validação executada

Runtime isolado Swoole CLI com PHP **8.4.25**, obtido da distribuição oficial do projeto em `swoole/swoole-cli`. Composer instalado isoladamente, usando o lock sem atualização; scripts e plugins desabilitados durante a instalação. Nenhuma instalação global.

| Verificação | Resultado |
|---|---|
| PHPUnit 10.5.64 | **158 testes, 3.258 asserções; 2 ignorados** |
| Testes ignorados | Integração MySQL: banco não configurado |
| PHPStan 2.2.13, configuração oficial nível 3 | **Sem erros; baseline inalterada** |
| Lint PHP | **326 arquivos, sem erros** |
| Composer audit | **Sem advisories aplicáveis e sem pacotes abandonados** |
| Smoke JavaScript em jsdom 26.1.0 | Bootstrap alert; ColorBox inicialização/abertura/fechamento; exclusão POST com chaves compostas, token e cancelamento aprovados |
| `git diff --check` | Aprovado |

Os testes novos executam o resolvedor real de assets e os endpoints em subprocessos PHP. Conferem rejeição de leitura externa, symlinks, entradas malformadas, GET na exclusão, tokens ausentes/inválidos/antigos e prevalência exclusiva dos dados POST. O teste com núcleo simulado registra as chamadas de mutação; não substitui uma transação em MySQL. Há também regressão integrando `GetIP()` ao limitador, com variação de cabeçalhos e cache adulterado, e verificação de integridade dos scripts vendorizados.

Não foram executados Docker/Apache real (daemon indisponível), matriz PHP 8.3/8.5, integração MySQL ou navegação completa de um painel com dados reais. Os testes DOM não verificam layout visual de todos os plugins. Essas validações ficam para o CI/staging antes do merge e da publicação.

## Aplicação e operação

O patch entregue contém também arquivos novos e alterações binárias dos assets. Aplicar sobre o commit-base com `git apply --check prescia-security-fixes-2026-10-26.patch` e depois `git apply prescia-security-fixes-2026-10-26.patch`.

Ao publicar, invalidar eventuais caches públicos das respostas antigas de `optimizer.php`. Se houver evidência de leitura indevida de configuração, rotacionar as credenciais expostas. Integrações externas que chamavam exclusão via GET devem migrar para POST com token CSRF.
