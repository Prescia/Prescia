# Plano de manutenção do framework Prescia

**Projeto:** `leohmoraes/Prescia`
**Data:** 13 de setembro de 2026
**Estado de referência:** `master` em `055adcd`
**Autor:** Manus AI

> **Atualização de estado — 2026-09-14:** este plano permanece um documento de execução criado em `055adcd`. Desde então, os resultados dos scanners foram versionados em `../reports/`, a documentação de instalação/configuração foi expandida no README e a PR #210 foi mesclada. As referências abaixo a “próximo marco” devem ser lidas como o plano original; o próximo ciclo agora é revalidar os artefatos no CI/staging e atualizar este plano com o SHA correspondente.

## Objetivo

A migração dos fluxos de Legacy SQL de produção foi concluída em lotes pequenos, com regressões e validação remota. Este documento transforma as recomendações seguintes em um plano operacional priorizado. O objetivo é preservar os controles existentes, confirmar a segurança do deployment real e reduzir a probabilidade de regressões futuras.

> **Regra de conclusão:** uma tarefa só deve ser marcada como concluída quando houver evidência executável, documentação atualizada e, para alterações de código, checks obrigatórios verdes no SHA publicado.

## Visão de prioridades

| Prioridade | Horizonte | Itens | Resultado esperado |
|---|---|---|---|
| **P0 — imediata** | Próximo ciclo | 1, 2 e 3 | Bloqueios automáticos contra regressões e confirmação dos controles básicos de produção |
| **P1 — alta** | Próximas 1–2 semanas | 4 e 5 | Evidência dinâmica de segurança e validação do deployment real |
| **P2 — contínua** | A cada mudança | 6 e 7 | Configuração documentada, auditável e resistente a mudanças |
| **P3 — recorrente** | Semanal, mensal e trimestral | 8 | Manutenção preventiva e reauditoria periódica |

## 1. Manter o CI como barreira obrigatória — P0

O CI deve permanecer como requisito obrigatório para qualquer alteração de código. Os checks mínimos são testes PHP 8.3, compatibilidade PHP 8.3, PHPStan, análise estática e Dependency Review. CodeQL, Composer audit, Trivy e a auditoria semanal de segurança devem continuar ativos conforme os workflows existentes.

A configuração deve impedir merge quando um check obrigatório falhar ou permanecer inconclusivo. A baseline do PHPStan não deve crescer para ocultar diagnósticos novos. Cada lote de segurança deve consultar os check-runs do SHA publicado e do SHA mergeado.

**Critério de conclusão:** workflows ativos, branch protegida, cinco checks obrigatórios verdes e nenhuma expansão não justificada de `phpstan-baseline.neon`.

## 2. Formalizar a política para novo SQL — P0

Todo novo código de aplicação deve usar `queryPrepared()` ou `fetchPrepared()` para valores externos. Valores devem manter seus tipos (`i`, `s`, `d` ou `b`) e a mesma ordem dos placeholders. Listas `IN` devem validar cada item e gerar um placeholder por valor. Identificadores dinâmicos de tabela ou coluna devem ser derivados de metadados internos ou de allowlists explícitas.

`query()`, `fetch()` e `simpleQuery()` devem ser tratados como APIs excepcionais. As exceções atuais são o bootstrap estrutural do framework e operações estáticas protegidas do `presciatester`. Cada exceção deve permanecer documentada e coberta por uma justificativa de contexto.

**Critério de conclusão:** checklist incorporado à revisão de código, regressão de segurança no mesmo lote e auditoria SQL sem novo sink externo não classificado.

## 3. Isolar o `presciatester` do código de produção — P0

O `presciatester` já exige configuração por ambiente, possui guard para operações destrutivas e está bloqueado na superfície Apache da imagem. Esses controles devem ser confirmados em cada deployment. O diretório não deve ser publicado em produção, e o banco de testes deve ser independente de qualquer banco com dados reais.

A manutenção deve verificar `PRESCIA_DB_USER`, `PRESCIA_DB_PASSWORD`, `CONS_ONSERVER=false` e `CONS_DB_BASE=presciatester` no ambiente autorizado. O endpoint `reset.php` deve continuar sujeito a todos esses controles. Uma evolução recomendada é mover o testador para um pacote ou deployment separado.

**Critério de conclusão:** teste de deployment comprova que o diretório não é acessível em produção e que o reset falha fora do ambiente de teste.

## 4. Executar scanners e testes dinâmicos — P1

A auditoria local foi limitada pela ausência de PHP, Composer, PHPStan, PHPUnit, Docker, Semgrep, Trivy e Gitleaks no sandbox. A próxima execução deve ocorrer no CI ou em staging controlado e incluir PHPUnit completo, PHPStan completo, `php -l`, Composer audit, Semgrep, Gitleaks e Trivy na imagem Docker.

A varredura de segredos deve incluir o histórico Git, não apenas o working tree. Falhas devem gerar issues com evidência reproduzível, sem ampliar a baseline para esconder resultados.

**Critério de conclusão:** artefatos dos scanners arquivados, resultados classificados e cada achado vinculado a uma issue ou aceitação formal de risco.

## 5. Validar o deployment real — P1

A segurança de filesystem e exposição HTTP depende da configuração efetiva do servidor. Deve-se confirmar que `pages/presciatester`, `config`, `prescia`, `tests`, `tools` e `docs` não estão acessíveis indevidamente. Também devem ser verificadas as permissões de `_temp`, logs, cache, backups e uploads.

A validação deve incluir CKFinder, isolamento entre tenants, leitura de logs, bloqueio de uploads executáveis, bloqueio de extensões duplas e comportamento de caminhos canonizados. O teste deve ser feito em staging com autorização explícita, sem usar dados de produção.

**Critério de conclusão:** relatório de staging com matriz de rotas permitidas e negadas, evidência de permissões e confirmação de isolamento entre tenants.

## 6. Manter a proteção SSRF — P2

`loadURL()` e `fget()` possuem decisões fail-closed para protocolos, DNS, endereços privados, portas, redirects, rebinding, timeout e tamanho de resposta. Esses contratos devem permanecer cobertos por regressões para IPv4, IPv6, DNS misto, DNS rebinding, hosts curtos, portas não permitidas, redirects e destinos FTP privados.

Todo novo consumidor dessas funções deve ser revisado quanto à origem da URL. Não se deve introduzir um cliente HTTP ou FTP alternativo sem reproduzir as mesmas garantias de validação.

**Critério de conclusão:** suíte SSRF verde e revisão documentada de cada novo call site.

## 7. Atualizar documentação e configuração operacional — P2

O relatório de segurança, os relatórios de ciclo, o changelog e as instruções de deployment devem refletir o estado real do código. A documentação deve distinguir três situações: corrigido e validado, analisado sem alteração e pendente de confirmação dinâmica.

As configurações efetivas devem permanecer fora do controle de versão quando contiverem valores operacionais. Templates rastreados devem conter somente placeholders ou variáveis de ambiente. Alterações de configuração devem possuir revisão de segurança e não devem depender de defaults previsíveis.

**Critério de conclusão:** cada release possui changelog, relatório de validação, referência ao SHA e instruções de configuração reproduzíveis.

## 8. Executar manutenção preventiva contínua — P3

A manutenção deve combinar verificações por mudança, semanais, mensais e trimestrais. O objetivo é detectar regressões antes que elas se acumulem em uma nova auditoria ampla.

| Frequência | Atividade | Evidência |
|---|---|---|
| A cada PR | CI completo, diff check, testes de segurança e revisão de SQL | Checks do SHA e revisão aprovada |
| Semanal | Auditoria de dependências, CodeQL, Gitleaks e Trivy | Artefatos do workflow e issues classificadas |
| Mensal | Revisão de permissões, uploads, configurações e endpoints administrativos | Checklist de operação |
| Trimestral | Reauditoria OWASP, testes dinâmicos e revisão do deployment | Relatório técnico versionado |
| Após mudança de infraestrutura | Revalidação de Docker, Apache, logs, uploads e isolamento de tenants | Relatório de mudança e evidências de staging |

**Critério de conclusão:** todas as execuções têm responsável, data, artefato e tratamento explícito para falhas.

## Sequência recomendada

A ordem de execução deve ser: primeiro manter os bloqueios automáticos e formalizar a política de SQL; depois confirmar o isolamento do testador; em seguida executar scanners e testes dinâmicos; então validar o deployment real; por fim manter SSRF, documentação e cadências preventivas.

Essa ordem prioriza controles que impedem regressões antes de investir em validações mais custosas. Nenhuma tarefa deve ser marcada como concluída apenas por inspeção estática quando o próprio requisito depende do ambiente de deployment.

## Estado atual e próximos marcos

No momento da elaboração deste plano, o repositório não possuía issues ou pull requests abertas. O último lote de código daquela fotografia foi mergeado no SHA `95370e8`, e a documentação de referência estava no SHA `055adcd`. Os resultados posteriores dos scanners estão em `../reports/`; o próximo marco operacional é revalidá-los no CI ou em staging e registrar o SHA e o ambiente efetivamente usados.

## Referências

[1]: https://github.com/leohmoraes/Prescia/blob/master/docs/SECURITY_AUDIT_2026-09-13.md "Análise completa de segurança do Prescia"

[2]: https://github.com/leohmoraes/Prescia/tree/master/.github/workflows "Workflows de CI e segurança do Prescia"

[3]: https://owasp.org/www-project-top-ten/ "OWASP Top 10"

[4]: https://github.com/leohmoraes/Prescia/blob/master/docs/RELATORIO_CICLO_2026-09-08.md "Relatório do ciclo de remediação do Prescia"
