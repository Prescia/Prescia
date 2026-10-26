# Relatório consolidado de vulnerabilidades críticas — Issue #67

**Projeto:** `leohmoraes/Prescia`
**Issue de origem:** [#67 — revisão completa dos 114 arquivos PHP legados do CKFinder][1]
**Issue complementar do plugin de estatísticas:** [#70 — SQL Injection e XSS no `bi_stats`][2]
**Data de consolidação:** 8 de setembro de 2026
**Autor:** Manus AI

## 1. Sumário executivo

A revisão registrada na Issue #67 identificou duas superfícies de risco prioritárias: o **CKFinder legado**, que contém fluxos de autenticação, autorização, upload e escrita de arquivos, e o plugin **`bi_stats`**, que concatena entradas em consultas SQL e retorna dados persistidos sem um contrato de saída seguro.

O achado de maior urgência é o fluxo `setres` do `bi_stats`. O valor recebido em `$_REQUEST['res']` é aceito com base apenas em comprimento e depois interpolado em consultas de leitura e escrita. Esse padrão deve ser tratado como **SQL Injection de alta severidade até a correção e o teste de exploração negativa**.

No CKFinder, a combinação de exposição de erros, ACL padrão amplo, aceitação de tipos de arquivo ativos, permissões de filesystem permissivas e código legado PHP4/PHP5 cria uma superfície de comprometimento relevante. Alguns pontos dependem da confirmação de implantação, dos call sites e do comportamento do servidor; portanto, este relatório distingue achados confirmados no código de riscos classificados como **alta prioridade de confirmação**.

A Issue #67 permanece aberta. A correção de CSP e o hardening de SSRF foram tratados anteriormente no [PR #64][3]. A descontinuação da runtime PHP4 foi preparada no [PR #69][4], que ainda estava aberto no momento desta consolidação. As correções específicas do `bi_stats` foram planejadas na [Issue #70][2], mas ainda não foram implementadas.

## 2. Classificação consolidada

| ID | Componente | Vulnerabilidade ou risco | Evidência | Severidade inicial | Status |
|---|---|---|---|---|---|
| C-01 | `bi_stats` | SQL Injection no fluxo `setres` | `prescia/plugins/bi_stats/module.php:45-53` | **Crítica/Alta** | Não corrigida; Issue #70 |
| C-02 | `bi_stats` | XSS potencial por saída de dados persistidos sem tipo seguro em `stats_rtajax` | `prescia/plugins/bi_stats/payload/actions/stats_rtajax.php:12-17` | **Alta** | Não corrigida; depende também de contexto de navegador e ACL |
| C-03 | `bi_stats` | Endpoint de tempo real sem autorização explícita comprovada | `stats_rtajax.php:7-21` | **Alta** | Pendente de confirmação de roteamento e correção |
| C-04 | `bi_stats` | Alteração de estado via `$_REQUEST` sem método POST/CSRF explícitos | `module.php:43-53` | **Alta** | Não corrigida; Issue #70 |
| C-05 | CKFinder | Exposição de erros detalhados em produção | `pages/_js/ckfinder/config.php:47-48` | **Alta** | Corrigida na PR #80 com `display_errors=0`; deploy/runtime ainda requer confirmação |
| C-06 | CKFinder | ACL padrão amplo para todas as operações | `pages/_js/ckfinder/config.php:141-154` | **Alta** | Corrigida no lote da PR #81 com papel `admin` explícito; testes de integração de autorização permanecem recomendados |
| C-07 | CKFinder | Upload e possível publicação de tipos ativos | configurações de recursos em `config.php` e handlers PHP5 | **Alta/Crítica** | Tipo e serving precisam ser confirmados |
| C-08 | CKFinder | Permissões de arquivos e diretórios permissivas | `ChmodFiles=0775`, `ChmodFolders=0775` | **Alta** | Corrigida na PR #80 para `0640`/`0750`; ownership e deploy ainda requerem confirmação |
| C-09 | CKFinder | Renderização sem escaping contextual em HTML/URL | `ckfinder_php4.php` e `ckfinder_php5.php` | **Alta** | Potencial; origem dos valores deve ser confirmada |
| C-10 | CKFinder | Runtime PHP4 e código de connector legado disponível | `core/ckfinder_php4.php`, `core/connector/php/php4/*` | **Alta** | Descontinuação proposta no PR #69; ainda não mesclada |
| C-11 | `bi_stats` | CSV sem proteção explícita contra formula injection | `payload/actions/stats_export.php:42-50` | **Média/Alta** | Corrigida no lote da PR #84 com `fputcsv`, validação de data, tipagem numérica e headers seguros |
| C-12 | `bi_stats` | Cookies de rastreamento sem atributos de segurança explícitos | `module.php:420-428`, `440-449` | **Média/Alta** | Não corrigida; Issue #70 |
| C-13 | `bi_stats` | Concatenação SQL em métodos auxiliares com contratos de entrada incompletos | `module.php:106-179` | **Alta se alcançável por entrada externa** | Call sites pendentes de classificação |
| C-14 | `bi_stats` | Persistência sem limites uniformes de User-Agent, referer e caminhos | `module.php:257-263`, `286-388` | **Média/Alta** | Não corrigida; risco de abuso de armazenamento e exposição |

A classificação é inicial. Severidade final deve considerar exposição pública, autenticação necessária, impacto dos dados, privilégio do processo PHP e controles do servidor web.

## 3. Achados críticos e de alta prioridade

### 3.1 C-01 — SQL Injection no `bi_stats.setres`

O fluxo acionado quando `action` é `setres` verifica apenas se `$_REQUEST['res']` possui mais de seis caracteres. Em seguida, o valor é colocado em `$_SESSION[CONS_USER_RESOLUTION]` e interpolado em `SELECT`, `INSERT` e `UPDATE` sobre a tabela `statsres`.

Comprimento não é validação de formato. O atacante pode enviar aspas, operadores, comentários ou estruturas inesperadas. Como o mesmo dado alcança consultas de leitura e escrita, o impacto potencial inclui bypass de lógica, alteração de estatísticas e, dependendo dos privilégios do usuário de banco e da API legada, comprometimento de outras consultas.

A correção deve exigir POST, rejeitar arrays, validar uma allowlist de resolução com limites explícitos, converter dimensões para inteiros positivos e usar prepared statements. A sessão somente deve ser alterada depois da validação completa. Testes devem cobrir valores válidos, valores ausentes, arrays, separadores, aspas, backslashes, valores negativos, limites excedidos e payloads de injeção.

**Status:** vulnerabilidade não corrigida. O plano de correção está na Issue #70 [2].

### 3.2 C-02 — XSS e saída insegura em `stats_rtajax`

O endpoint consulta um registro por IP e imprime diretamente `agent` e `fullpath`. Esses dados podem ser influenciados por clientes por meio de User-Agent, referer e caminhos registrados. A resposta não define explicitamente `Content-Type: text/plain; charset=utf-8` nem `X-Content-Type-Options: nosniff`.

Sem um tipo de conteúdo seguro, navegadores ou proxies podem interpretar a resposta em um contexto diferente do pretendido. Se valores armazenados contiverem marcação ou código, a exposição pode resultar em XSS quando um operador abrir a resposta em um navegador vulnerável ao sniffing ou quando a resposta for incorporada por uma interface administrativa.

A correção deve confirmar a autorização do endpoint, definir o tipo textual e `nosniff`, controlar cache, limitar dados retornados e serializar ou escapar os campos conforme o formato de saída. O endpoint não deve usar um IP conhecido como substituto de autenticação e autorização.

**Status:** risco de alta prioridade não corrigido. A confirmação final de explorabilidade depende do roteamento e da forma de consumo da resposta. O plano está na Issue #70 [2].

### 3.3 C-03 — Autorização não comprovada no endpoint de tempo real

`stats_rtajax.php` recebe um IP e consulta estatísticas de visitantes, mas o arquivo não contém uma verificação própria de sessão, papel ou permissão. A segurança pode depender do contexto de carregamento do framework, porém essa dependência não foi comprovada na revisão.

Se o endpoint for alcançável diretamente ou por uma action que não aplique ACL, um usuário não autorizado poderá consultar dados operacionais de visitantes. O risco aumenta porque o retorno inclui horário de entrada, último contato, User-Agent e caminho percorrido.

A correção deve documentar o call site real, exigir autenticação e autorização explícitas no limite administrativo e impedir que IP seja tratado como credencial. Testes negativos devem chamar o endpoint sem sessão, com sessão de baixo privilégio e com IPs arbitrários.

### 3.4 C-04 — Alteração de estado sem método e CSRF explícitos

O fluxo `setres` usa `$_REQUEST`, que combina fontes HTTP distintas, para alterar sessão e banco. Não há no trecho revisado uma exigência explícita de POST, rejeição de valores múltiplos ou validação CSRF.

Mesmo após a correção de SQL Injection, uma página de terceiro poderá tentar induzir um navegador autenticado a alterar a resolução do usuário, caso o roteamento aceite requisições cross-site e os cookies sejam enviados.

A correção deve exigir POST, validar token CSRF conforme o mecanismo central do framework e rejeitar entradas que não sejam strings escalares válidas.

### 3.5 C-05 — Exposição de erros no CKFinder

A configuração do CKFinder habilita `error_reporting(E_ALL)` e `display_errors=1`. Em produção, mensagens de erro podem revelar caminhos absolutos, nomes de arquivos, configuração, stack traces, consultas, extensões habilitadas e dados de requisição.

Esse vazamento facilita exploração das vulnerabilidades de upload e filesystem e pode expor credenciais quando erros atravessarem camadas de configuração. A correção deve desabilitar `display_errors` em produção, enviar detalhes apenas para logs protegidos e testar respostas de erro sem dados internos.

### 3.6 C-06 — ACL padrão amplo no CKFinder

A configuração analisada concede view, create, rename, delete e upload ao papel curinga `*`. O controle efetivo depende integralmente de `CheckAuthentication()` e da correta inicialização de sessão, incluindo `$_SESSION['CODE']` e o nível de acesso.

Um ACL curinga amplia o impacto de qualquer falha de autenticação, fixação de sessão, configuração incorreta ou inclusão direta do connector. O princípio do menor privilégio exige permissões explícitas por papel e por recurso. Operações destrutivas e upload devem ser negadas por padrão e liberadas somente para papéis administrativos necessários.

### 3.7 C-07 — Upload e publicação de tipos ativos no CKFinder

As configurações e recursos revisados permitem extensões como `swf`, `html`, `htm`, `xml` e `js` em fluxos relacionados a arquivos e conteúdo HTML. A aceitação de tipos ativos é perigosa quando o diretório de upload é servido pelo mesmo origin e o servidor respeita o Content-Type ou permite execução de scripts.

O impacto potencial inclui XSS persistente, roubo de sessão, defacement e, em configurações inseguras, execução de código. A confirmação exige verificar se esses arquivos podem ser enviados por cada papel, se são servidos diretamente, qual Content-Type é usado, se há `Content-Disposition: attachment`, se o diretório possui execução PHP desabilitada e se uploads estão isolados em origin sem privilégios.

A mitigação preferível é negar tipos ativos, permitir somente extensões e MIME necessários, verificar conteúdo real, renomear arquivos, impedir sobrescrita, bloquear execução e servir downloads como anexos de um domínio sem cookies.

### 3.8 C-08 — Permissões de filesystem permissivas no CKFinder

`ChmodFiles=0775` e `ChmodFolders=0775` atribuem permissões de escrita ao grupo e, para diretórios, execução ao grupo e outros conforme a semântica do modo. O risco depende do usuário do servidor, do grupo compartilhado e da localização dos arquivos.

Permissões amplas aumentam o impacto de contas comprometidas, processos no mesmo host e erros de traversal ou symlink. Devem ser substituídas por modos mínimos compatíveis com a operação, com diretório de upload fora da raiz executável, ownership dedicado e testes de symlink, caminhos absolutos, `..`, null bytes, Unicode e colisão de nomes.

### 3.9 C-09 — Renderização sem escaping contextual no CKFinder

As runtimes PHP4 e PHP5 concatenam propriedades como `BasePath`, `Width`, `Height`, `ClassName`, `Id`, funções e tipos em HTML e URLs. A origem dos valores não foi completamente localizada nos call sites revisados.

Se qualquer propriedade puder ser influenciada por usuário ou configuração administrativa não confiável, pode ocorrer injeção de atributo, URL ou JavaScript. A correção deve aplicar escaping HTML em atributos, codificação de componentes de URL e allowlists para nomes de callback, dimensões e classes. O risco deve ser confirmado com testes de instância contendo aspas, entidades, URLs externas, `javascript:` e quebras de linha.

### 3.10 C-10 — Runtime PHP4 e connector legado

O repositório contém uma runtime PHP4 e um conjunto duplicado de handlers PHP4 para autenticação, upload, operações de arquivo, download, thumbnails e erros. A runtime possui código de 2012 e está fora do requisito moderno do projeto.

Manter essa seleção disponível permite reativar uma implementação sem o hardening aplicado ao caminho PHP5. A descontinuação foi preparada no [PR #69][4], que altera o entrypoint para rejeitar PHP inferior a 5 e fixa o connector PHP5-compatible. O risco permanece pendente até o merge, a remoção ou o isolamento efetivo dos arquivos legados e a confirmação de que nenhum ambiente operacional depende deles.

## 4. Riscos adicionais de alta prioridade no `bi_stats`

O `stats_export.php` gera CSV por concatenação. Embora os campos observados sejam principalmente datas e contadores, a implementação não possui uma proteção explícita para células que comecem com `=`, `+`, `-` ou `@`. Se campos textuais forem adicionados, a abertura em planilhas poderá resultar em formula injection. A saída deve usar escaping RFC 4180, neutralização de fórmulas e headers `attachment` e `nosniff`.

O módulo cria cookies de rastreamento sem atributos `Secure`, `HttpOnly` e `SameSite` explícitos. Esses cookies não parecem ser credenciais, mas devem receber atributos compatíveis com HTTPS, consumo por JavaScript e política cross-site definida.

Os métodos `getCounter()` e `getHits()` concatenam filtros e intervalos em SQL. O risco depende dos call sites. Todo valor de origem externa deve ser removido da concatenação por prepared statements ou allowlist. Valores internos devem ter contrato documentado e limites de tipo.

O módulo também persiste User-Agent, referer e caminhos derivados. Limites de tamanho, normalização de encoding e minimização de dados são necessários para reduzir abuso de armazenamento, logs excessivos e exposição de dados de navegação.

## 5. Controles de segurança já aplicados fora do escopo pendente

O [PR #64][3] foi mesclado e incluiu CSP com nonce por requisição, headers de segurança e hardening de SSRF em `loadURL.php`, incluindo validação de protocolos, portas, redes privadas, resolução de endereços e prevenção de re-resolução durante a conexão.

Essas correções reduzem superfícies específicas, mas não corrigem os achados do CKFinder nem o fluxo `bi_stats.setres`. O fato de uma proteção global existir não deve ser usado como substituto para validar ACL, upload, SQL ou saída textual nos componentes legados.

## 6. Plano de correção priorizado

| Prioridade | Ação | Evidência de conclusão |
|---:|---|---|
| P0 | Corrigir SQL Injection em `setres` | Prepared statements ou contrato seguro equivalente, testes negativos e revisão de diff |
| P0 | Bloquear exposição pública/indevida do CKFinder até fechar autenticação, ACL e upload | Testes de acesso anônimo, baixo privilégio e deploy com diretório isolado |
| P0 | Remover `display_errors` do CKFinder em produção | Teste de erro sem caminhos, stack traces ou configuração na resposta |
| P1 | Corrigir `stats_rtajax` com autorização explícita, `text/plain`, `nosniff` e saída controlada | Testes de autorização, XSS e headers |
| P1 | Negar tipos ativos e isolar uploads do CKFinder | Testes de extensão, MIME, serving, execução e download |
| P1 | Completar descontinuação da runtime PHP4 | PR #69 mesclado, branch removida e nenhum route público para PHP4 |
| P1 | Corrigir permissões e proteção contra traversal/symlink | Testes de filesystem e inspeção do usuário/grupo do serviço |
| P2 | Corrigir CSV, cookies, limites de logs e filtros auxiliares do `bi_stats` | Testes de formula injection, atributos de cookie e limites |
| P2 | Incluir CKFinder em análise isolada PHPStan/lint | Relatório focalizado sem novos diagnósticos críticos e sem crescimento da baseline |

## 7. Estado de validação

A revisão estática e a inspeção dos call sites foram realizadas. Nenhum arquivo foi modificado durante a consolidação deste relatório. No ambiente local usado para as revisões, PHP, Composer, PHPUnit e PHPStan não estavam disponíveis; portanto, as validações de sintaxe, análise estática focalizada e testes de runtime permanecem pendentes de CI ou de um ambiente PHP configurado.

A baseline do PHPStan não recebeu entradas novas. A Issue #67 registra os lotes como analisados, mas pendentes quando não houve correção, teste de runtime ou decisão operacional. Esse status deve ser preservado para evitar que análise seja confundida com remediação.

## 8. Conclusão

O risco mais imediato é a combinação de **SQL Injection no `bi_stats.setres`** e **superfície de upload/autorização legada do CKFinder**. O segundo grupo pode permitir XSS persistente ou comprometimento de conteúdo se o diretório de upload estiver publicado no mesmo origin. A exposição de erros e o ACL curinga aumentam o impacto de falhas nesses fluxos.

A sequência recomendada é bloquear ou restringir o CKFinder em produção enquanto os controles de autenticação, ACL e upload não forem comprovados, corrigir o SQL Injection e o endpoint `stats_rtajax`, e somente depois concluir a revisão detalhada dos handlers PHP5. Nenhum item deste relatório deve ser marcado como resolvido apenas por documentação; a conclusão exige código, testes e evidência de execução.

## Referências

[1]: https://github.com/leohmoraes/Prescia/issues/67 "Issue #67 — revisão completa dos 114 arquivos PHP legados do CKFinder"
[2]: https://github.com/leohmoraes/Prescia/issues/70 "Issue #70 — corrigir SQL Injection e XSS no plugin bi_stats"
[3]: https://github.com/leohmoraes/Prescia/pull/64 "PR #64 — CSP com nonces e hardening SSRF"
[4]: https://github.com/leohmoraes/Prescia/pull/69 "PR #69 — descontinuar runtime PHP4 do CKFinder"
