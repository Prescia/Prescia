# Changelog automatizado — 8 de setembro de 2026

Este documento foi gerado a partir do histórico Git do repositório Prescia no intervalo `v1.0..master`. O objetivo é oferecer uma visão consolidada dos commits recentes sem substituir o histórico detalhado do Git nem o changelog versionado principal.

**Intervalo analisado:** `v1.0..origin/master`  
**Período:** 28 de maio a 8 de setembro de 2026  
**Referência atual:** `ab5e2e1` — merge do PR #104  
**PRs recentes relacionados:** [#69][1], [#73][2], [#74][3], [#75][4], [#76][5], [#77][6], [#78][7], [#79][8], [#80][9], [#81][10], [#82][11], [#83][12], [#84][13], [#85][14], [#86][15], [#87][16], [#88][17], [#89][18], [#90][19], [#96][20], [#97][21], [#98][22], [#99][23], [#100][24], [#101][25], [#102][26], [#103][27], [#104][28] e [#105][29].

> **Atualização de estado — 2026-09-14:** este arquivo continua sendo uma fotografia do intervalo encerrado em 8 de setembro. A recomendação abaixo sobre remoção do CKFinder PHP4 foi superada pela modernização posterior: o inventário atual possui 83 arquivos PHP, não possui a árvore PHP4/PHP5 legada e usa o runtime moderno. Consulte `CKFINDER_REVIEW_2026-09-10.md` e `../reports/prescia-audit-2026-09-14.md` para a evidência vigente.

## Resumo executivo

O período foi dominado por uma campanha de **endurecimento de segurança**, modernização de compatibilidade com PHP 8.3 e ampliação da validação automatizada. Os commits mais recentes concentram-se em prepared statements para fluxos administrativos e de autenticação, proteção de uploads e downloads do CKFinder, contenção canônica de caminhos, segurança do Docker e recuperação de regressões no CI.

| Categoria de commit | Volume aproximado | Resultado consolidado |
|---|---:|---|
| Segurança | 40 | Redução da superfície de SQL injection, upload inseguro, traversal, XSS, brute force e exposição administrativa. |
| PHPStan e qualidade estática | 67 | Correção incremental de contratos, símbolos legados, variáveis indefinidas e configuração da análise. |
| Documentação | 24 | Planos de segurança, relatórios técnicos, requisitos de PHP 8.3 e resumos executivos. |
| Correções gerais | 18 | Correções de compatibilidade, sintaxe, inicialização de estado e comportamento legado. |
| Testes | 8 | Regressões para SQL parametrizado, compatibilidade PHP 8.3 e controles de segurança. |
| CI e build | 7 | Workflows de testes, PHPStan, auditoria de dependências, lint e build Docker. |

## Segurança

### SQL parametrizado e controle de entrada

Os fluxos de autenticação, autorização, estatísticas, administração, undo, linker, filtros AJAX, preview de fórum e chaves remotas foram migrados para valores tipados ou prepared statements. A mudança reduz a dependência de escaping manual e remove concatenações de valores controlados por requisição em consultas SQL.

Os principais commits recentes desse grupo são `808986d` para bootstrap e permissões de autenticação, `b6455cc` para limpeza e consultas do undo, `2f866f1` para undo administrativo e linker, `319ebf9` para lookups administrativos, `db01677` para estatísticas e `89a865a` para chaves remotas parametrizadas.[20] [21] [22] [24] [25] [27]

### CKFinder e uploads

A série de mudanças do CKFinder endureceu ACLs, configuração de produção, validação de extensões e MIME, callbacks, downloads, mutações de arquivos, contenção canônica de caminhos e publicação atômica. O lote mais recente, integrado pelo PR #104, portou para o conector PHP4 legado os controles de estrutura de upload, `is_uploaded_file()`, rejeição de MIME ativo, `isPathInside()` e reserva exclusiva de destinos com `fopen(..., 'x')`.[2] [3] [4] [5] [6] [8] [9] [10] [11] [12] [16] [28]

O runtime PHP4 do CKFinder continua tratado como legado e descontinuado para novos usos. O hardening reduz a exposição residual, mas não elimina a recomendação de migração definitiva para uma implementação suportada.

### Autenticação, sessão e respostas HTTP

O histórico também inclui limitação de tentativas de login, headers de segurança em camadas, cookies e sessão endurecidos, proteção CSRF, migração de hashes, remoção de códigos previsíveis e restrições sobre desserialização legada. Essas alterações estabelecem uma base de defesa em profundidade para os fluxos mutáveis da aplicação.

### Docker e superfície operacional

A imagem Docker passou a restringir arquivos e diretórios sensíveis, manter o código da aplicação como artefato não gravável pelo usuário web, reduzir permissões de diretórios e ocultar arquivos de configuração e desenvolvimento. O PR #102 adicionou uma camada específica para reduzir a superfície HTTP exposta pelo container.[26]

## Compatibilidade, testes e CI

A base passou a exigir e validar PHP 8.3, extensões necessárias e sintaxe PHP rastreada. O pipeline executa instalação com lockfile, auditoria de dependências, PHPUnit, lint, PHPStan e build Docker. Os commits `8ec5791`, `f1124b8` e `ecc96c5` restauraram e ampliaram a cobertura de compatibilidade e regressões para os caminhos de SQL preparado.[17] [18] [19]

A estratégia de PHPStan evoluiu para uma correção incremental sem crescimento automático do baseline. O histórico registra contratos explícitos para componentes injetados, correções de símbolos dinâmicos, inicialização de variáveis, tratamento de caminhos ausentes e remediações agrupadas por lote.

## Documentação e governança técnica

Foram adicionados o plano priorizado de segurança, relatórios consolidados de vulnerabilidades, planos de mitigação da dívida técnica PHPStan, requisitos de PHP 8.3, orientações de CI e o resumo executivo do hardening CKFinder PHP4. O PR #105 publicou esse resumo na pasta `docs/` para consulta interna.[23] [29]

## Mudanças de infraestrutura e manutenção

A base de desenvolvimento foi modernizada com Composer, PHPUnit, PHPStan 2.x, workflows do GitHub Actions e Docker baseado em PHP 8.3. Também foram corrigidas incompatibilidades sintáticas do PHP 8.3, headers PHP legados, contratos de métodos, símbolos dinâmicos e variáveis não inicializadas em vários componentes.

## Próximos pontos recomendados

A prioridade técnica remanescente **na data deste changelog** era reduzir ou remover o CKFinder PHP4 legado, mantendo o conector PHP5 como caminho preferencial durante a transição. Essa frente foi concluída posteriormente. As prioridades atuais são continuar a substituição de SQL legado por prepared statements, manter o PHPStan sem expansão da baseline e ampliar testes comportamentais para upload, traversal, MIME falso, symlinks e autorização administrativa.

## Metodologia de geração

A lista foi derivada de `git log v1.0..origin/master`, agrupada pelos prefixos convencionais dos commits (`security`, `test`, `docs`, `fix`, `ci`, `feat`, `refactor` e `build`) e correlacionada com os PRs integrados do repositório. A contagem por categoria é indicativa porque alguns commits de merge e commits com múltiplos objetivos pertencem a mais de uma área conceitual.

## Referências

[1]: https://github.com/leohmoraes/Prescia/pull/69 "PR #69 — descontinuar runtime PHP4 do CKFinder"
[2]: https://github.com/leohmoraes/Prescia/pull/73 "PR #73 — corrigir vulnerabilidades P0 no CKFinder"
[3]: https://github.com/leohmoraes/Prescia/pull/74 "PR #74 — conter paths canônicos nos handlers mutáveis do CKFinder"
[4]: https://github.com/leohmoraes/Prescia/pull/75 "PR #75 — endurecer downloads e callbacks do CKFinder"
[5]: https://github.com/leohmoraes/Prescia/pull/76 "PR #76 — validar contrato de upload do CKFinder"
[6]: https://github.com/leohmoraes/Prescia/pull/77 "PR #77 — publicar mutações do CKFinder atomicamente"
[7]: https://github.com/leohmoraes/Prescia/pull/78 "PR #78 — endurecer SQL e saída realtime do bi_stats"
[8]: https://github.com/leohmoraes/Prescia/pull/79 "PR #79 — endurecer formatos de upload e callbacks do CKFinder"
[9]: https://github.com/leohmoraes/Prescia/pull/80 "PR #80 — endurecer defaults de produção do CKFinder"
[10]: https://github.com/leohmoraes/Prescia/pull/81 "PR #81 — vincular ACL do CKFinder ao papel admin"
[11]: https://github.com/leohmoraes/Prescia/pull/82 "PR #82 — normalizar extensões na validação de imagens CKFinder"
[12]: https://github.com/leohmoraes/Prescia/pull/83 "PR #83 — reservar destinos de upload do CKFinder"
[13]: https://github.com/leohmoraes/Prescia/pull/84 "PR #84 — endurecer exportação CSV do bi_stats"
[14]: https://github.com/leohmoraes/Prescia/pull/85 "PR #85 — adicionar headers de segurança"
[15]: https://github.com/leohmoraes/Prescia/pull/86 "PR #86 — adicionar rate limiting de login"
[16]: https://github.com/leohmoraes/Prescia/pull/87 "PR #87 — endurecer limites de upload e download do CKFinder"
[17]: https://github.com/leohmoraes/Prescia/pull/88 "PR #88 — cobrir caminhos CRUD com chaves preparadas"
[18]: https://github.com/leohmoraes/Prescia/pull/89 "PR #89 — corrigir asserção de regressão SQL"
[19]: https://github.com/leohmoraes/Prescia/pull/90 "PR #90 — restaurar cobertura de compatibilidade PHP 8.3"
[20]: https://github.com/leohmoraes/Prescia/pull/96 "PR #96 — parametrizar bootstrap e permissões do bi_auth"
[21]: https://github.com/leohmoraes/Prescia/pull/97 "PR #97 — parametrizar limpeza e lookups do bi_undo"
[22]: https://github.com/leohmoraes/Prescia/pull/98 "PR #98 — parametrizar undo administrativo e linker"
[23]: https://github.com/leohmoraes/Prescia/pull/99 "PR #99 — documentar PHPStan e workflow de CI"
[24]: https://github.com/leohmoraes/Prescia/pull/100 "PR #100 — parametrizar lookups de conteúdo administrativo"
[25]: https://github.com/leohmoraes/Prescia/pull/101 "PR #101 — endurecer resolução e saída realtime do bi_stats"
[26]: https://github.com/leohmoraes/Prescia/pull/102 "PR #102 — restringir superfície HTTP do Docker"
[27]: https://github.com/leohmoraes/Prescia/pull/103 "PR #103 — parametrizar lookups de chaves remotas"
[28]: https://github.com/leohmoraes/Prescia/pull/104 "PR #104 — endurecer uploads legados CKFinder PHP4"
[29]: https://github.com/leohmoraes/Prescia/pull/105 "PR #105 — publicar resumo executivo do hardening CKFinder"
