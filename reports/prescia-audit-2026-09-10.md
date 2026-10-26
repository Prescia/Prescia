# Auditoria do Prescia — 2026-09-10

## Conclusão executiva

A auditoria foi executada sobre o commit `7088f64` (`docs: add security and phpstan audit report (#186)`), no branch `master`, sincronizado com `origin/master`. O GitHub não apresenta **issues abertas** nem **pull requests abertas** no momento da consulta.

A coleta de indicadores de segurança foi concluída, mas a validação dinâmica e estática local ficou **impedida** pela ausência de `php`, `composer`, `vendor/bin/phpstan` e `vendor/bin/phpunit` no ambiente. Portanto, este relatório não declara PHPStan, lint, Composer Audit ou PHPUnit como aprovados ou reprovados; esses itens permanecem pendentes de execução em ambiente PHP 8.3 com dependências instaladas.

Não foram alterados arquivos de produção. Os resultados brutos estão em `reports/audit-work/` e são artefatos temporários de coleta.

## Estado do repositório

| Item | Resultado |
|---|---|
| Repositório | `leohmoraes/Prescia` |
| Branch | `master` |
| Commit analisado | `7088f64` |
| Sincronização | `master` alinhado com `origin/master` |
| Working tree antes do relatório | Limpa |
| Alteração desta auditoria | Apenas este relatório técnico |
| Baseline PHPStan | Sem crescimento nesta auditoria |

## Issues e pull requests

A consulta autenticada ao GitHub retornou zero resultados em ambas as categorias:

| Tipo | Estado | Resultado |
|---|---|---:|
| Issues abertas | `open` | 0 |
| Pull requests abertas | `open` | 0 |

A Issue [#43 — Atualizar o PHPStan e elevar gradualmente o nível de análise](https://github.com/leohmoraes/Prescia/issues/43) está **fechada**. Seus comentários registram a conclusão da PR #184 e a ausência de diagnósticos no PHPStan nível 2 naquele ciclo. O plano local ainda contém referências históricas à Issue #43 e deve ser tratado como documentação de contexto, não como backlog aberto atual.

## Diagnósticos PHPStan nível 3

A execução planejada foi:

```bash
vendor/bin/phpstan analyse --level=3 --error-format=raw
```

Resultado: **não executada**, com impedimento `vendor/bin/phpstan: No such file or directory`. Como `composer` também não está disponível, não foi possível instalar as dependências nesta coleta.

A documentação histórica registra diagnósticos anteriores de `variable.undefined`, contratos dinâmicos de `$core`/`$this`, símbolos legados, métodos ausentes e caminhos de inclusão. Esses números não foram tratados como métricas atuais, pois correspondem a commits e execuções anteriores. O próximo ciclo deve executar o PHPStan no commit atual e substituir essas fotografias históricas por uma contagem reproduzível.

## Varredura de segurança

As buscas abaixo são **indicadores**, não confirmações automáticas de vulnerabilidade.

| Indicador coletado | Ocorrências aproximadas | Interpretação inicial |
|---|---:|---|
| Chamadas genéricas `query`/`simpleQuery` | 51 | Inclui várias asserções dos testes; requer separação entre produção e testes. |
| SQL com interpolação segundo a heurística | 256 | Parte usa `queryPrepared()` e concatena identificadores de metadados; requer confirmação de allowlist e origem. |
| Superglobais | 684 | Esperado em framework web legado; requer revisão por fluxo e sink. |
| Sinks de saída | 878 | Inclui templates, mensagens fixas e métodos de escape; requer classificação por contexto. |
| Chamadas de rede/arquivo | 4 | Inclui `loadURL()`, POP3 e leituras locais; SSRF requer testes focalizados. |
| Execução de comandos | 0 | Nenhum `exec`, `system`, `shell_exec`, `passthru`, `proc_open` ou `popen`. |
| `unserialize()`/`eval()` | 0 | Nenhum resultado na busca atual. |
| Marcadores TODO/FIXME/HACK/XXX | 67 | Pendências técnicas e comentários de manutenção; não são vulnerabilidades por si só. |

### Itens prioritários para revisão

**SQL e identificadores dinâmicos.** A varredura encontrou concatenações em plugins como `bi_undo` e `bi_stats`, especialmente com propriedades como `dbname`, `title`, `keys` e listas construídas por `implode()`. O changelog registra uma migração ampla para execução preparada, mas valores usados como nomes de tabela, coluna, alias, `ORDER BY` ou estrutura SQL não podem ser protegidos apenas por placeholders. Deve-se confirmar, em cada call site, que esses valores vêm exclusivamente de metadados internos validados e que não recebem entrada externa livre. É uma pendência de confirmação contextual, não uma vulnerabilidade confirmada pela regex.

**XSS e saída contextual.** Há saídas diretas e atribuições de template em payloads de estatística, labels, administração e CMS. Permanecem necessárias validações focalizadas para `label_template`, `id`, `name`, `content`, `imgpath` e valores de referer, distinguindo HTML, atributo, URL, JavaScript e texto simples.

**SSRF e chamadas externas.** `prescia/lib/loadURL.php` usa `stream_socket_client()`. O changelog registra controles recentes de DNS, IP público, limites e eventos de rejeição, mas os testes não puderam ser executados neste ambiente. Deve-se validar em PHP 8.3 esquema inválido, credenciais embutidas, redirecionamento, resolução para IP privado, múltiplas respostas DNS, timeout, tamanho de resposta e TLS.

**Entradas administrativas e de arquivo.** A busca encontrou parâmetros de labels, file manager, preview, importação e administração. Os fluxos devem continuar validando autorização, CSRF, tipos, allowlists de campos e contenção canônica de caminhos. Os resultados são indicadores que exigem rastreamento até o sink.

**Exposição de diagnóstico.** `index.php` e `prescia/index.php` contêm saídas de warnings/errors condicionadas ao modo de desenvolvimento. Deve ser confirmado que produção não habilita esses handlers nem expõe caminhos, linhas ou mensagens internas.

## Varredura de otimização e pendências técnicas

Os marcadores mais relevantes concentram-se em compatibilidade com chaves múltiplas, importação e processamento de imagens:

| Área | Arquivos indicados | Pendência |
|---|---|---|
| Chaves múltiplas | `bi_adm` e `bi_dev` | Caminhos explicitamente marcados como não suportando múltiplas chaves. |
| Importação | `prescia/plugins/bi_adm/payload/importer.php` | O código registra que a importação ainda não está concluída. |
| Reordenação/edição | `reorder.php`, `edit.php`, `options.php`, `list.php` | Limitações registradas para múltiplas chaves ou links complexos. |
| Upload/arquivos | `bi_undo/module.php`, `components/module.php` | Nota sobre arquivos enviados armazenados com chave incorreta; requer plano seguro. |
| Estatísticas | `bi_stats` | Consultas agregadas devem ser avaliadas com profiling antes de otimizações. |
| CMS/logging | `core.php`, `bi_cms` | TODO sobre logging de estatísticas e processamento de conteúdo/cache. |

Nenhuma otimização foi aplicada durante a auditoria. A confirmação de N+1, consultas dentro de loops ou custos excessivos requer profiling ou testes de integração com dados representativos.

## Verificações impedidas

| Verificação | Resultado | Motivo |
|---|---|---|
| PHPStan nível 3 | Não executada | `vendor/bin/phpstan` ausente |
| Composer Audit | Não executada | `composer` ausente |
| Composer show | Não executada | `composer` ausente |
| Lint PHP global | Não executado | `php` ausente |
| PHPUnit focado | Não executado | `vendor/bin/phpunit` ausente |
| Testes completos | Não executados | `php`/dependências ausentes |

Esses impedimentos devem ser reproduzidos em ambiente PHP 8.3 com as extensões declaradas pelo projeto. A instalação de ferramentas não foi feita nesta auditoria para evitar alterar o ambiente e introduzir artefatos não solicitados.

## Próximo ciclo recomendado

1. Executar `composer install --no-interaction --no-progress` em ambiente PHP 8.3 compatível.
2. Executar PHPStan no nível configurado e no nível 3 sem alterar `phpstan.neon.dist`, salvando a saída completa.
3. Executar `php -l` em todos os arquivos PHP e `vendor/bin/phpunit`, separando falhas, deprecations e skips.
4. Executar `composer audit --no-interaction` e registrar o estado do lockfile.
5. Reclassificar SQL por origem do identificador, uso de prepared statements e allowlist de metadados.
6. Fazer revisão focalizada de XSS nos payloads de labels, CMS e estatísticas.
7. Validar testes SSRF de `loadURL()` e controles de upload/caminhos em PHP 8.3.
8. Atualizar o plano PHPStan para marcar referências históricas já resolvidas, sem reabrir a Issue #43.
9. Criar nova issue somente se a execução atual produzir diagnóstico reproduzível sem issue correspondente; não há duplicata aberta a reutilizar.

## Referências e artefatos

- Commit analisado: [`7088f64`](https://github.com/leohmoraes/Prescia/commit/7088f64)
- Issue histórica fechada: [#43](https://github.com/leohmoraes/Prescia/issues/43)
- Plano PHPStan: [`docs/PLANO_PHPSTAN_PROXIMO_LOTE.md`](../docs/PLANO_PHPSTAN_PROXIMO_LOTE.md)
- Relatório de progresso: [`docs/RELATORIO_PHPSTAN_PROGRESSO.md`](../docs/RELATORIO_PHPSTAN_PROGRESSO.md)
- Artefatos brutos locais: `reports/audit-work/`

Comandos de coleta usados:

```bash
git fetch origin --prune
git switch master
git reset --hard origin/master
/home/ubuntu/skills/prescia-security-phpstan-audit/scripts/run_audit.sh \
  /home/ubuntu/Prescia /home/ubuntu/Prescia/reports/audit-work
```
