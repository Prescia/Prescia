# Plano de ação priorizado para correção das vulnerabilidades

**Projeto:** Prescia  
**Base:** auditoria do commit `68a430a`  
**Objetivo:** reduzir imediatamente o risco de comprometimento da aplicação, corrigir os controles de autenticação e entrada, e estabelecer uma base verificável de segurança e compatibilidade com PHP 8.3.

> **Atualização de estado — 2026-09-14:** este plano preserva a priorização da auditoria original. A frente CKFinder evoluiu depois da sua redação: o inventário atual possui 83 arquivos PHP, a árvore PHP4/PHP5 legada foi removida e a evidência vigente está em `tests/CKFinderModernizationTest.php`, `docs/CKFINDER_REVIEW_2026-09-10.md` e `reports/prescia-audit-2026-09-14.md`. Os números e estados abaixo continuam históricos quando não houver nota de atualização explícita.

## 1. Estratégia de execução

A correção deve seguir uma abordagem **defense-in-depth**: primeiro limitar a exposição e impedir os caminhos de comprometimento mais graves; depois corrigir autenticação, sessão e entrada; em seguida reduzir a superfície de ataque do servidor; por fim automatizar testes e controles para evitar regressões.

Durante as fases 1 e 2, recomenda-se manter a aplicação fora da Internet pública ou restringir o acesso administrativo por VPN/allowlist, exigir HTTPS e preservar cópias de segurança verificadas antes de alterar o esquema ou o formato das credenciais. Não se deve considerar uma issue concluída apenas porque o código foi alterado; cada item precisa de teste negativo, revisão e evidência de execução em ambiente limpo.

## 2. Priorização executiva

| Ordem | Prioridade | Issues | Tema | Prazo operacional sugerido | Critério de saída |
|---:|---|---|---|---|---|
| 1 | P0 — bloqueador | [#11][11], [#13][13] | Senha mestre e armazenamento de senhas | Imediato | Não existem credenciais previsíveis, em texto puro ou em logs; login testado com hash moderno |
| 2 | P0 — bloqueador | [#9][9], [#15][15], [#17][17] | SQL injection, CSRF e sessão | Imediato | Fluxos mutáveis protegidos e autenticação sem query concatenada ou sessão fixável |
| 3 | P1 — muito alto | [#16][16] | Upload/CKFinder legado | Após contenção de autenticação | Upload removido/atualizado, autorizado, isolado e incapaz de executar scripts |
| 4 | P1 — muito alto | [#19][19], [#21][21] | Serialização e container | Curto prazo | Persistência segura e código/configuração não graváveis pelo usuário web |
| 5 | P1 — alto | [#23][23], [#24][24], [#26][26] | Hardening, XSS e brute force | Curto prazo | Debug desligado, headers definidos, saída contextual e limitação de login |
| 6 | P1 — qualidade obrigatória | [#25][25] | PHP 8.3 e CI | Antes da publicação | CI reproduzível em PHP 8.3, com lint, testes e análise estática verdes |

## 3. Fase 0 — contenção e preparação

Antes de modificar o código, deve-se limitar a superfície de exposição. O acesso ao painel administrativo deve ser restrito por rede ou autenticação adicional temporária, o HTTPS deve ser obrigatório e os logs atuais devem ser preservados para investigação. Em seguida, deve-se verificar se logs ou arquivos de configuração já contêm senhas, tokens ou chaves; qualquer segredo potencialmente exposto deve ser revogado e substituído.

Também é necessário criar um ambiente de staging isolado com uma cópia anonimizada do banco, PHP 8.3, MySQL/MariaDB e uma configuração limpa. O ambiente deve permitir reproduzir login, logout, administração, upload, download, edição e exclusão sem depender de serviços externos reais.

**Saída da fase:** inventário de segredos e endpoints, backup testado, staging funcional e lista de contas administrativas que precisam de redefinição de credencial.

## 4. Fase 1 — corrigir autenticação e credenciais

### 4.1 Remover a senha mestre previsível — Issue [#11][11]

Eliminar o override global baseado em padrões como `master{CODE}{DAY}` e qualquer credencial padrão. A administração inicial deve usar uma senha definida de forma única durante a instalação, armazenada somente como hash. O código de instalação e os logs devem ser revisados para garantir que senha, token, código de recuperação ou segredo nunca sejam impressos.

O teste de aceite deve criar uma instalação nova, procurar segredos em logs e respostas HTTP e comprovar que padrões antigos não concedem acesso.

### 4.2 Migrar senhas para hashes modernos — Issue [#13][13]

Introduzir `password_hash()` com `PASSWORD_DEFAULT` ou Argon2id e autenticar exclusivamente com `password_verify()`. A migração deve ser gradual: após um login válido contra o formato antigo, validar o legado apenas em uma camada temporária controlada e regravar imediatamente o hash moderno; contas sem login devem ser obrigadas a redefinir a senha.

A migração deve incluir política de comprimento e recuperação segura, sem registrar a senha em logs, sessões, cookies ou mensagens de erro. Após a janela de migração, remover o caminho legado e invalidar hashes antigos.

**Dependência:** esta etapa precisa ser coordenada com [#9][9], pois a consulta de autenticação deve ser parametrizada antes de ser considerada pronta.

## 5. Fase 2 — proteger entrada, autorização e sessão

### 5.1 Substituir SQL concatenado — Issue [#9][9]

Mapear todas as consultas que recebem dados de `GET`, `POST`, `REQUEST`, cookies ou headers. Substituir concatenação por prepared statements parametrizados e centralizar a API do driver. Identificadores estruturais, como nomes de tabela e coluna, devem vir de allowlists internas, nunca diretamente da requisição.

O conjunto de testes deve incluir aspas simples e duplas, unicode, valores nulos, arrays inesperados e payloads clássicos de SQL injection. Os erros devem ser registrados internamente sem retornar SQL, credenciais ou caminhos ao cliente.

### 5.2 Implementar CSRF — Issue [#15][15]

Criar tokens criptograficamente imprevisíveis por sessão, incluir o token em todos os formulários mutáveis e validá-lo no servidor com comparação em tempo constante. Operações de alteração, exclusão, upload, mudança de senha, permissões e configurações devem aceitar somente métodos mutáveis apropriados; GET deve ser somente leitura.

O teste de aceite deve repetir cada operação sem token, com token incorreto, token de outra sessão e token expirado, esperando rejeição uniforme sem alteração no banco.

### 5.3 Endurecer sessão e cookies — Issue [#17][17]

Configurar cookies com `Secure`, `HttpOnly` e `SameSite=Lax` ou `Strict`, com escopo de caminho e domínio mínimos. Regenerar o ID de sessão depois da autenticação e de elevações de privilégio, invalidar a sessão no logout e remover dados sensíveis da sessão. Tokens persistentes devem ser aleatórios, armazenados de forma não reversível no servidor, revogáveis e vinculados ao contexto necessário.

O teste deve verificar os atributos `Set-Cookie`, impedir reutilização do ID pré-login e garantir que logout invalida sessões e tokens persistentes.

## 6. Fase 3 — eliminar caminhos de execução e escrita perigosos

### 6.1 Remover ou atualizar CKFinder e isolar uploads — Issue [#16][16]

A decisão preferencial é remover o CKFinder legado se ele não for essencial. Se o recurso for mantido, atualizar para uma versão suportada, exigir autenticação e autorização no endpoint, aplicar allowlist de extensões e MIME real, renomear arquivos, limitar tamanho e armazenar os objetos fora do document root.

O diretório de upload deve impedir execução de PHP, CGI e equivalentes. Os testes devem tentar extensões alternativas (`.phtml`, `.phar`), duplas extensões, conteúdo MIME falso, traversal, arquivos enormes e nomes Unicode. Também devem verificar que um arquivo enviado nunca é interpretado pelo servidor.

### 6.2 Remover `unserialize()` inseguro — Issue [#19][19]

Converter estruturas simples para JSON. Nos pontos em que a serialização PHP for inevitável, usar `allowed_classes => false` ou uma allowlist mínima e validar o tipo e o esquema do resultado. Cache e arquivos persistidos devem ter permissões mínimas, integridade verificável e tratamento seguro de corrupção.

O teste deve fornecer dados malformados e payloads com classes não permitidas, confirmando que não há instanciação, execução ou fatal error exposto.

### 6.3 Corrigir permissões e imagem Docker — Issue [#21][21]

Separar o document root (`public/`) de código, configurações, logs e dados. A imagem final deve copiar somente os artefatos necessários; o código e as configurações devem ser legíveis, mas não graváveis, pelo usuário web. Apenas diretórios de cache, log e upload devem permitir escrita, com permissões mínimas e ownership específico.

Adicionar usuário não-root quando compatível com Apache, fixar versões base por digest quando possível, remover ferramentas desnecessárias da imagem e executar scan de vulnerabilidades no build.

## 7. Fase 4 — hardening da aplicação e resistência operacional

### 7.1 Desligar debug e adicionar headers — Issue [#23][23]

Definir configuração de produção com `display_errors=Off`, `log_errors=On`, mensagens genéricas para o cliente e logs protegidos fora da árvore pública. O modo de desenvolvimento deve exigir ativação explícita e não deve depender de parâmetros de requisição.

Adicionar headers compatíveis com a aplicação, incluindo `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, CSP progressiva e HSTS somente quando todo o serviço estiver corretamente servido por HTTPS. Testar scripts, imagens, frames e integrações antes de aplicar uma CSP restritiva.

### 7.2 Corrigir XSS — Issue [#24][24]

Tratar escaping como responsabilidade do contexto de saída: HTML textual e atributos devem usar `htmlspecialchars()` com charset definido; JavaScript, CSS, URL e SQL precisam de mecanismos próprios. Campos que permitem HTML devem passar por uma biblioteca de sanitização com allowlist explícita de tags, atributos e esquemas.

Criar testes para HTML armazenado, refletido, atributos, URLs, SVG, entidades codificadas e payloads com quebra de contexto. A filtragem regex existente não deve ser considerada controle suficiente.

### 7.3 Implementar rate limiting de login — Issue [#26][26]

Aplicar limites por conta e origem, com backoff progressivo e mensagens que não permitam enumeração de usuários. Registrar eventos de falha sem senha ou token, monitorar ataques distribuídos e avaliar MFA para contas administrativas. O mecanismo deve evitar que o bloqueio de uma conta seja usado como negação de serviço contra terceiros.

## 8. Fase 5 — PHP 8.3, testes e prevenção de regressões

### Issue [#25][25]

Atualizar o container para PHP 8.3 e documentar extensões obrigatórias. Migrar short tags para `<?php` e `<?=`, eliminando a necessidade de `short_open_tag`. Adicionar Composer, PHPUnit, lint de todos os arquivos, PHPStan ou Psalm, PHPCS, scan de segredos e scan da imagem Docker.

O workflow deve executar pelo menos: lint; testes unitários; testes de autenticação, sessão, CSRF, upload e autorização; análise estática; verificação de dependências; e build do container. O job de integração deve usar MySQL/MariaDB descartável e uma configuração limpa.

A issue somente deve ser encerrada quando o pipeline verde for reproduzível em PHP 8.3 e a documentação indicar claramente versão, extensões, permissões e procedimento de instalação.

## 9. Ordem de pull requests e dependências

| PR sugerido | Conteúdo | Issues | Dependências |
|---:|---|---|---|
| PR 1 | Staging, configuração segura, rotação de segredos e testes-base | #11, #23 | Nenhuma |
| PR 2 | Hashes modernos e remoção da senha mestre | #11, #13 | PR 1 |
| PR 3 | Prepared statements e tratamento seguro de erros | #9 | PR 1; necessário para fechar PR 2 |
| PR 4 | CSRF, sessão e cookies | #15, #17 | PR 2 e PR 3 |
| PR 5 | Upload/CKFinder e serialização | #16, #19 | PR 4 para autorização consistente |
| PR 6 | Docker hardening | #21 | PR 5 para definir diretórios de dados |
| PR 7 | XSS, headers e rate limiting | #23, #24, #26 | PR 4; CSP após inventário do frontend |
| PR 8 | PHP 8.3 e CI completo | #25 | Pode iniciar em paralelo, mas o aceite final depende dos PRs anteriores |

## 10. Verificação final antes da produção

A liberação deve ser bloqueada se qualquer um dos seguintes itens falhar: login com hash moderno; redefinição de senha; ausência de segredo em logs; prepared statements nos fluxos de entrada; rejeição de CSRF; regeneração e invalidação de sessão; upload não executável; rejeição de `unserialize()` perigoso; permissões mínimas no container; debug desligado; headers esperados; escaping contextual; rate limiting; lint e testes em PHP 8.3.

Depois da implantação, monitorar falhas de login, rejeições CSRF, uploads recusados, erros 4xx/5xx, tentativas de acesso a arquivos sensíveis e alterações inesperadas no sistema de arquivos. Manter plano de rollback, mas não restaurar credenciais ou imagens vulneráveis sem antes revogar os segredos afetados.

## 11. Definição de concluído

Uma vulnerabilidade estará corrigida quando houver implementação revisada, teste automatizado ou evidência de teste manual documentada, análise negativa do caminho antigo, atualização da documentação e issue vinculada ao PR. As issues P0 só devem ser fechadas após validação em staging com dados representativos e aprovação de revisão de segurança.

## Referências

[9]: https://github.com/leohmoraes/Prescia/issues/9 "Issue #9 — Substituir SQL concatenado por prepared statements"
[11]: https://github.com/leohmoraes/Prescia/issues/11 "Issue #11 — Remover senha mestre previsível e exposta em logs"
[13]: https://github.com/leohmoraes/Prescia/issues/13 "Issue #13 — Migrar armazenamento de senhas para hashes modernos"
[15]: https://github.com/leohmoraes/Prescia/issues/15 "Issue #15 — Implementar proteção CSRF"
[16]: https://github.com/leohmoraes/Prescia/issues/16 "Issue #16 — Atualizar ou remover CKFinder legado e isolar uploads"
[17]: https://github.com/leohmoraes/Prescia/issues/17 "Issue #17 — Endurecer cookies e ciclo de vida da sessão"
[19]: https://github.com/leohmoraes/Prescia/issues/19 "Issue #19 — Remover unserialize inseguro ou restringir classes"
[21]: https://github.com/leohmoraes/Prescia/issues/21 "Issue #21 — Corrigir permissões e superfície do container Docker"
[23]: https://github.com/leohmoraes/Prescia/issues/23 "Issue #23 — Desligar debug em produção e adicionar headers de segurança"
[24]: https://github.com/leohmoraes/Prescia/issues/24 "Issue #24 — Substituir sanitização HTML por escaping contextual"
[25]: https://github.com/leohmoraes/Prescia/issues/25 "Issue #25 — Atualizar container, remover short tags e criar CI de compatibilidade"
[26]: https://github.com/leohmoraes/Prescia/issues/26 "Issue #26 — Implementar rate limiting específico para login"

## Registro de execução — preview do fórum

Em 2026-09-07, o fluxo de preview de `bi_bb` foi corrigido: os IDs de fórum e tópico passaram a ser vinculados por `queryPrepared()` e não são mais concatenados em SQL. Foi adicionado teste de regressão estático para impedir a reintrodução da concatenação direta. Permanecem pendentes os demais fluxos de entrada listados na Fase 2, especialmente autenticação, CSRF e sessão.

## Atualização do lote SQL bi_bb/admin — 2026-09-08

O primeiro lote parametrizou a contagem de mensagens do `bi_bb` e os filtros de chaves simples e múltiplas do editor administrativo, com regressões automatizadas e CI PHP 8.3 verde. Permanecem pendentes as APIs de filtro SQL livre sem call sites, os lookups de link da importação administrativa e os filtros de opções relacionadas; cada um deve receber um contrato preparado próprio antes da alteração.

O segundo lote concluiu os lookups de links da importação e os filtros das opções relacionadas não-parentais no editor, incluindo o valor selecionado e os pré-requisitos. Permanecem pendentes as árvores parentais, cuja API `getContents()` ainda não transporta parâmetros preparados, e as APIs de filtro livre sem call sites.

## Atualização do ciclo 2026-09-08

Foi removido o uso de `eval()` em `prescia/lib/zipfile.php`, substituído por `pack('V', ...)` para serialização determinística do timestamp DOS, com teste de regressão do cabeçalho ZIP. A alteração é um lote independente de hardening e não encerra a frente de SQL parametrizado. O próximo lote deve continuar a revisão dos filtros legados em plugins, preservando nomes de tabela/coluna derivados de metadados internos e transportando valores externos por parâmetros preparados.

## Atualização do ciclo autônomo — 2026-09-10

> Os números de testes e o estado de CKFinder descritos nesta subseção são uma fotografia do ciclo de 2026-09-10. Para o estado posterior, consulte `../reports/prescia-audit-2026-09-14.md`; a validação posterior registrou 144 testes, 3.091 asserções e o inventário moderno de 83 arquivos PHP.

O lote de PHPStan nível 3 da issue #185 foi concluído sem alteração de baseline. Foram corrigidos os contratos de `numrows`, resultados vazios em preview/auth/dev e shapes agregados de `bi_stats`. A análise global no nível 3 não reporta diagnósticos, e a suíte completa executa 135 testes com 3.340 asserções, duas deprecações existentes e dois testes pulados documentados pela configuração.

A frente de SQL genérico avançou nos filtros do `ajaxQuery`, nos selected markers dos linkers administrativos e nos reads de metadados do bootstrap. Os valores externos passaram a viajar por placeholders e os identificadores continuam derivados de metadados internos. A varredura de consumidores não encontrou chamadas de `query()` ou `fetch()` genéricas fora do driver e dos caminhos de reset/teste; `simpleQuery()` restante está restrito a DDL e agregações internas com identificadores citados ou a cron controlado.

A frente de sanitização passou a encaminhar `cleanHTML()`, `stripHTML()` e escaping de template ao serviço `Prescia\\Services\\Sanitizer`. O serviço aplica allowlist DOM para HTML permitido, remove atributos de evento e restringe esquemas de link. Os consumidores de criação de diretórios foram migrados para `Prescia\\Services\\FileService`, mantendo `safe_mkdir()` apenas como wrapper legado sem consumidores de produção.

A frente SSRF foi reforçada com rejeição de hosts numéricos e nomes curtos, eliminação do fallback DNS IPv4 incompleto e cobertura determinística para re-resolução, IPv6 privado e representações alternativas. O contrato operacional de eventos, métricas, alertas, retenção e homologação está em `docs/SSRF_MONITORING_RUNBOOK.md`.

A revisão CKFinder confirmou 120 arquivos PHP, todos aprovados por `php -l`; o entrypoint ativo fixa o connector PHP5 e o runtime PHP4 não é selecionável. Os handlers PHP5 cobertos por regressões mantêm ACL administrativa, allowlists de upload, contenção de caminhos canônicos, validação de MIME/tamanho, callbacks codificados e downloads com headers seguros. A análise PHPStan global continua excluindo o legado por configuração deliberada; uma execução focalizada deve usar um arquivo PHPStan temporário separado e seus diagnósticos devem ser classificados antes de alterar a baseline.

O inventário acima é histórico. Após a modernização posterior, o runtime ativo usa a árvore moderna, as árvores PHP4/PHP5 legadas foram removidas e a regressão vigente está em `tests/CKFinderModernizationTest.php`.

As validações do ciclo são: `composer audit --locked` sem advisories, lint PHP completo sem erros, PHPStan global nível 1 e nível 3 sem diagnósticos, PHPUnit completo verde, `git diff --check` limpo e regressões de segurança verdes. As pendências que exigem ambiente externo permanecem limitadas à execução dos cenários DNS controlados de homologação e à publicação efetiva dos limiares no agregador de logs de produção.
