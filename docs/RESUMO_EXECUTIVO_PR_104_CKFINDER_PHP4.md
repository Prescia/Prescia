# Resumo executivo — PR #104

**Projeto:** Prescia  
**Tema:** Hardening do upload legado CKFinder PHP4  
**PR:** [#104 — security: harden legacy CKFinder PHP4 uploads][1]  
**Branch:** `security/ckfinder-php4-hardening`  
**Commit de merge:** `ab5e2e1912b9fe0f0d39c5b9a13a0d70c370f38c`  
**Data de integração:** 8 de setembro de 2026  
**Responsável pela documentação:** Manus AI

## Conclusão executiva

O PR #104 foi integrado com sucesso ao branch `master` e reduziu a exposição do conector legado CKFinder PHP4 a ataques relacionados a upload de arquivos, traversal de caminhos e condições de corrida na criação de destinos. A implementação alinhou o fluxo PHP4 aos controles de segurança já existentes no handler PHP5, sem alterar o contrato funcional de nomes, extensões permitidas, redimensionamento ou callbacks do CKFinder.

A mudança é relevante porque o conector PHP4 permanecia com controles inferiores aos do PHP5. O fluxo legado aceitava estruturas incompletas em `$_FILES`, não confirmava que o arquivo havia sido efetivamente enviado via HTTP, usava a combinação vulnerável `file_exists()` seguida de `move_uploaded_file()` e não verificava se o caminho final permanecia dentro do diretório autorizado. O lote corrigiu esses pontos e adicionou regressões para impedir sua reintrodução.

## Escopo e risco tratado

O trabalho concentrou-se exclusivamente no caminho de upload do conector PHP4 e nos utilitários de filesystem necessários para sua proteção. A auditoria estática inicial mapeou handlers de upload, operações de escrita, cópia, movimentação e remoção, além das verificações de nome e contenção de caminhos do CKFinder PHP4 e PHP5.[2]

| Área | Situação anterior | Controle aplicado | Risco reduzido |
|---|---|---|---|
| Estrutura do upload | O handler PHP4 verificava apenas a presença do nome do arquivo. | Validação dos cinco campos esperados de `$_FILES`, tipos dos valores e tamanho não negativo. | Entradas incompletas, malformadas ou manipuladas. |
| Origem do arquivo | O fluxo PHP4 não confirmava explicitamente um upload HTTP válido. | Uso de `is_uploaded_file()` para uploads sem erro. | Processamento de arquivos locais ou caminhos arbitrários apresentados como upload. |
| Conteúdo ativo | A validação era dependente do fluxo legado de extensões e detecção HTML. | Detecção MIME com `finfo`, bloqueio de MIME ativo e rejeição de conteúdo HTML. | Upload de PHP, JavaScript, HTML, SVG ativo e formatos executáveis no navegador. |
| Limite de tamanho | A verificação dependia da configuração de validação após redimensionamento. | Rejeição do arquivo acima do limite configurado antes da gravação. | Consumo excessivo de armazenamento e processamento. |
| Caminho de destino | O PHP4 não possuía `isPathInside()`. | Resolução canônica de base, destino existente ou diretório pai, com verificação de prefixo de diretório. | Path traversal, symlinks e gravação fora da área autorizada. |
| Concorrência na criação | Uso de `file_exists()` antes de `move_uploaded_file()`. | Reserva exclusiva com `fopen($sFilePath, 'x')`, seguida de movimentação e limpeza em caso de erro. | Race condition e sobrescrita ou colisão de nomes. |
| Permissões | Aplicação direta do modo configurado. | Aplicação restrita por máscara `0770`. | Permissões excessivas no arquivo enviado. |
| Regressão | Não havia cobertura específica do PHP4 para os limites portados. | Teste estático dedicado ao handler PHP4 e ao helper de filesystem. | Reintrodução silenciosa do comportamento inseguro. |

## Implementação entregue

O arquivo `pages/_js/ckfinder/core/connector/php/php4/CommandHandler/FileUpload.php` recebeu a validação completa da estrutura de upload, tratamento explícito dos códigos de erro, confirmação de upload HTTP, validações de tamanho e MIME e contenção canônica do destino. O fluxo de reserva de nomes passou a ser atômico, evitando a janela entre verificar a existência e criar o arquivo.

O arquivo `pages/_js/ckfinder/core/connector/php/php4/Utils/FileSystem.php` recebeu o helper `isPathInside()`. O helper resolve o diretório autorizado com `realpath()`. Para destinos ainda inexistentes, resolve o diretório pai e reconstrói o candidato com o nome-base. A comparação final exige que o candidato seja o próprio diretório autorizado ou esteja sob seu separador de diretório, evitando falsos positivos de prefixo.

O arquivo `tests/SecurityRegressionTest.php` recebeu cobertura que verifica a presença dos controles no handler PHP4, a disponibilidade do helper, a reserva exclusiva do destino, a liberação do handle e a ausência do padrão antigo baseado em `file_exists()`.

Também foi incluído o relatório de auditoria estática [CKFINDER_PHP4_STATIC_AUDIT.md](CKFINDER_PHP4_STATIC_AUDIT.md), que registra os arquivos analisados e os sinks de escrita identificados antes da implementação.

## Validação e evidências

A validação local reproduziu os principais gates do pipeline. A suíte PHPUnit terminou com 68 testes e 2.991 assertions aprovadas. A análise estática PHPStan não apresentou erros. O lint foi executado em todos os arquivos PHP rastreados. A auditoria do Composer não encontrou advisories de segurança conhecidos.

Após a integração, os workflows do GitHub foram executados sobre o commit de merge. O workflow de compatibilidade PHP 8.3, incluindo instalação de dependências, auditoria Composer, testes, lint, PHPStan e build Docker, terminou com sucesso. O workflow de análise estática e a revisão de dependências também terminaram com sucesso.[1]

| Verificação | Resultado |
|---|---|
| PHPUnit local | Aprovado — 68 testes e 2.991 assertions |
| PHPStan local | Aprovado — sem erros |
| Lint PHP local | Aprovado |
| `composer audit --locked` | Aprovado — sem advisories |
| Dependency review no GitHub | Aprovado |
| PHPStan no GitHub | Aprovado |
| Compatibilidade PHP 8.3 no GitHub | Aprovado |
| Build Docker no GitHub | Aprovado |

O CI exibiu uma anotação não bloqueante sobre a depreciação do Node.js 20 em uma action do GitHub Actions. Essa anotação não afetou o resultado do PR e não está relacionada ao código alterado neste lote.

## Resultado operacional

O PR foi integrado ao `master` por squash merge. O branch remoto foi publicado e permaneceu alinhado ao commit que originou a integração. Não foram identificadas falhas pós-merge nem necessidade de correção adicional imediata.

A alteração não substitui a recomendação estratégica de retirar ou atualizar o CKFinder legado. Ela reduz o risco do caminho PHP4 enquanto esse componente permanecer disponível. O PHP4 continua sendo uma superfície de manutenção e compatibilidade distinta do runtime moderno da aplicação.

## Recomendações de acompanhamento

A primeira recomendação é manter o uso do conector PHP5 como caminho preferencial e impedir novas dependências funcionais no conector PHP4. Qualquer correção futura de segurança no handler PHP5 deve ser avaliada para portabilidade ao PHP4 enquanto o legado permanecer ativo.

A segunda recomendação é planejar a desativação ou substituição definitiva do CKFinder PHP4. O hardening atual reduz riscos de entrada e gravação, mas não transforma um componente legado em uma dependência moderna ou plenamente suportada.

A terceira recomendação é preservar a auditoria estática como evidência de segurança e ampliar gradualmente os testes comportamentais de upload para cobrir extensões duplas, MIME falso, arquivos SVG, nomes Unicode, symlinks, traversal e falhas de escrita em ambiente isolado.

## Rastreabilidade

A implementação foi derivada dos controles já validados no handler PHP5 e publicada em PR separado do PR #103, que tratou a parametrização das chaves remotas e já estava integrado ao `master`. O conjunto de mudanças do PR #104 está registrado no commit de implementação `ba36741` e no commit de merge `ab5e2e1`.[1]

## Referências

[1]: https://github.com/leohmoraes/Prescia/pull/104 "PR #104 — security: harden legacy CKFinder PHP4 uploads"

[2]: https://github.com/leohmoraes/Prescia/commit/ba367411ec8adc69e45abb9d5ef159a6c7011e64 "Commit de implementação e relatório de auditoria estática"

[3]: https://github.com/leohmoraes/Prescia/commit/ab5e2e1912b9fe0f0d39c5b9a13a0d70c370f38c "Commit de merge do PR #104"
