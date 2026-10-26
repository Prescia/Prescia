# Scanners de segurança — Prescia — 2026-09-14

## Escopo

Foram executados Semgrep e Gitleaks no commit `acac6b8` (`test: guard CKFinder modern runtime`), atualmente sincronizado com `origin/master`. O escopo excluiu `vendor/`, `.git/` e `reports/` da análise Semgrep. O Gitleaks analisou a árvore do repositório com redaction ativado.

Comandos principais:

```text
semgrep --config p/php --config p/security-audit --json --include '*.php' --include 'Dockerfile' --exclude vendor --exclude reports --exclude .git

gitleaks detect --source . --no-banner --redact --report-format json --exit-code 0
```

## Resultados executivos

| Scanner | Versão | Resultado |
|---|---:|---|
| Semgrep | 1.177.0 | 117 alertas heurísticos; 0 erros na execução PHP focada |
| Gitleaks | 8.24.2 | 0 segredos detectados |

A primeira execução ampla do Semgrep incluiu JavaScript e registrou um timeout na regra `javascript.lang.security.detect-eval-with-expression` sobre o bundle `pages/_js/ckeditor/ckeditor.js`. A execução focada em PHP e Dockerfile foi repetida e terminou com **117 resultados e 0 erros**, eliminando essa limitação de cobertura para o escopo solicitado.

## Classificação Semgrep

### 1. Nomes de arquivo e caminhos — 76 alertas

A regra `php.lang.security.injection.tainted-filename.tainted-filename` reportou 76 usos de nomes de arquivo derivados de requisição em CKFinder, plugins administrativos e file manager. Esses alertas não demonstram SSRF por si só: são operações locais de filesystem, não chamadas de rede.

A inspeção contextual confirmou barreiras relevantes no connector moderno, incluindo validação de nomes, verificação de arquivos ocultos, ACL, contenção com `isPathInside()` e publicação atômica em operações de cópia/movimentação. O resultado deve ser classificado como **falso positivo ou hardening pendente**, não como vulnerabilidade SSRF confirmada.

A recomendação é manter testes de traversal, symlink e contenção canônica no lote CKFinder. Não foi feita alteração de produção neste ciclo porque a mitigação já está centralizada e os alertas precisam de testes de integração com filesystem para demonstrar uma falha real.

### 2. SQL dinâmico — 19 alertas

A regra `php.lang.security.injection.tainted-sql-string.tainted-sql-string` reportou consultas em `core.php`, `ajaxQuery.php`, `ajaxqueryunique.php`, payloads administrativos, fórum e estatísticas.

A maior parte dos casos usa valores tipados, prepared statements ou identificadores derivados de metadata, e por isso é **heurística**. Dois pontos permanecem como hardening prioritário:

- `prescia/lazyload/ajaxqueryunique.php:24` valida que o campo existe no metadata, mas ainda concatena `$_REQUEST['field']` diretamente no SQL. A validação por pertencimento ao metadata reduz o risco, porém a consulta deve usar `quoteIdentifier()` central ou uma API de identificadores.
- `prescia/plugins/bi_bb/payload/content/forum.php` monta consultas com `$id` convertido para inteiro. Não há injeção textual provável, mas o fluxo deve migrar para valores preparados quando a API de conteúdo permitir, para reduzir falsos positivos e manter o contrato uniforme.

O alerta em `prescia/core.php:545` envolve SQL com tabela, alias, título e filtros preparados derivados de metadata. Não foi confirmado input externo transformado em identificador, mas a revisão deve garantir `quoteIdentifier()` para título, tabela, alias e chaves.

### 3. Saída refletida — 19 alertas

A regra `php.lang.security.injection.echoed-request.echoed-request` reportou:

- `bi_labels/payload/actions/config_labels_e.php`: valores de labels e `id` são retornados em formato delimitado por `|`;
- `bi_labels/payload/actions/config_labels_m.php`: nomes de campos e títulos de módulos retornados em formato de tokens;
- `bi_stats/payload/content/stats_refdet.php`: conteúdo de referer persistido é transformado em `<br/>` e devolvido;
- `bi_adm/payload/actions/import_sample.php`: nomes de campos são inseridos em uma amostra HTML;
- handlers de erro/upload do CKFinder, além de `pages/_js/optimizer.php`.

Não é possível classificar todos como XSS confirmado sem verificar o consumidor e o contexto de renderização. Contudo, `stats_refdet.php` merece prioridade: conteúdo de referer pode ser controlado por visitantes e é inserido em uma resposta HTML após apenas `explode()`/`implode()`. A saída deve aplicar escaping HTML (`htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`) antes de converter separadores em `<br/>`, ou retornar dados estruturados para o frontend.

Os endpoints de labels também devem ser revisados porque o formato delimitado não é um encoder de contexto. O parser cliente precisa ser confirmado antes de escolher escaping HTML, JavaScript ou texto.

### 4. Host em URL canônica — 2 alertas

`prescia/core.php:1215-1216` concatena `$_SESSION['CANONICAL']` em URLs de `og:image` e `image_src`. O valor é estabelecido pelo domínio/sessão do site durante o bootstrap, não por uma chamada de rede. O alerta é **potencial host injection/poisoning**, não SSRF confirmado. Ainda assim, a saída deve usar URL canônica derivada de configuração confiável e encoding apropriado para atributo HTML.

### 5. `phpinfo()` — 1 alerta

`prescia/console.php:236` expõe `phpinfo()` apenas pelo comando da console. O início da função exige nível de acesso administrativo (`$_SESSION[CONS_SESSION_ACCESS_LEVEL] >= 100`) quando há módulo de autenticação. O alerta é uma **exposição operacional de baixo/médio risco**, não uma exposição pública confirmada. Recomenda-se remover o comando em produção ou protegê-lo por uma flag de desenvolvimento explícita, além do controle administrativo existente.

## Gitleaks

O Gitleaks 8.24.2 não encontrou segredos na árvore analisada. O relatório JSON foi gerado com redaction e contém uma lista vazia (`[]`). Isso não substitui rotação de credenciais se algum segredo tiver sido exposto historicamente fora do estado atual; não há evidência de segredo detectável neste scan.

## Pendências recomendadas

| Prioridade | Ação | Issue sugerida |
|---:|---|---|
| P1 | Escapar ou estruturar a saída de `stats_refdet.php` e adicionar regressão com `<script>`/atributos | #201 |
| P1 | Centralizar `quoteIdentifier()` em `ajaxqueryunique.php` e auditar todos os campos dinâmicos restantes | #201 ou #9 |
| P2 | Confirmar o parser/consumidor dos endpoints de labels e aplicar encoder adequado | #201 |
| P2 | Desabilitar `phpinfo()` fora de modo desenvolvimento | #201 |
| P2 | Adicionar execução PHP focada do Semgrep ao CI; manter Gitleaks em rotina de segurança | #202 |
| P2 | Executar Trivy e matriz PHP 8.3+ conforme o plano | #202 |

Nenhuma vulnerabilidade crítica adicional foi confirmada somente pelos scanners. Foram identificadas **duas pendências de hardening que exigem correção**: o identificador de campo em `ajaxqueryunique.php` e a saída HTML de `stats_refdet.php`. Essas correções não foram aplicadas automaticamente nesta execução porque o pedido foi a verificação dos scanners; elas devem ser implementadas em lote próprio com testes de regressão.

## Artefatos locais

Os resultados brutos ficaram em `reports/scanner-work/` durante a análise e não foram adicionados ao commit por serem artefatos gerados. O relatório presente é o artefato versionável e reproduzível do ciclo.
