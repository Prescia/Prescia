# Plano de redução dos erros do PHPStan — nível 1

**Issue principal:** [#43 — Atualizar PHPStan e elevar gradualmente o nível de análise](https://github.com/leohmoraes/Prescia/issues/43)  
**Baseline:** PHPStan 2.x, nível 1, PHP 8.3  
**Data:** 2026-09-01

## Objetivo

O objetivo desta etapa é tornar a análise PHPStan nível 1 reproduzível e útil como controle de regressão. A prioridade não é silenciar o relatório, mas separar problemas de descoberta do framework, contratos ausentes e defeitos reais de código. Cada correção deve reduzir o número de diagnósticos fora da baseline sem introduzir novas supressões genéricas.

## Diagnóstico atual

O workflow [PHP static analysis](https://github.com/leohmoraes/Prescia/actions/runs/33573327549) executou PHPStan 2.x no nível 1 e falhou com aproximadamente **4.028 diagnósticos reportados**. A contagem foi obtida do log do job e deve ser repetida após cada lote de alterações.

| Grupo | Ocorrências aproximadas | Interpretação | Prioridade |
|---|---:|---|---|
| Constantes globais ausentes | 1.548 | O analisador não conhece constantes definidas durante o bootstrap da aplicação. | P0 |
| Funções globais ausentes | 289 | O conjunto de funções carregadas e os stubs não cobrem o legado usado pelos módulos. | P0 |
| Variáveis indefinidas | 165 | Possíveis defeitos reais em fluxos de upload, erro, autenticação e processamento de arquivos. | P1 |
| Classes/tipos ausentes | 22 | Classes legadas, plugins e estruturas dinâmicas não possuem contrato reconhecido. | P1 |
| Outros diagnósticos | 2.004 | Tipos mistos, propriedades dinâmicas, chamadas incompatíveis e efeitos secundários das causas anteriores. | P1/P2 |

As contagens são indicadores de triagem, não uma baseline. Mensagens duplicadas podem aparecer quando o mesmo símbolo é usado em vários arquivos. O relatório completo deve ser preservado como artefato do CI em uma etapa posterior.

## Estratégia de execução

A correção será feita em lotes pequenos e independentes. Cada lote deve alterar o código, adicionar ou ajustar testes quando houver comportamento observável, executar a análise no CI e registrar a redução de diagnósticos. A baseline permanecerá sem novas entradas genéricas. Uma supressão só será aceita quando o comportamento for deliberadamente dinâmico, houver justificativa no código ou na issue vinculada e existir um plano de remoção.

### Fase 0 — tornar a medição confiável

Antes de corrigir centenas de mensagens, o workflow deve publicar o relatório do PHPStan como artefato e registrar a contagem por arquivo e por identificador de erro. O comando de análise deve continuar sem `--generate-baseline`; a geração da baseline fica restrita a uma ação manual e revisada.

**Critérios de aceite:** o job mostra a versão do PHPStan, salva o relatório textual, executa com PHP 8.3 e falha para qualquer erro novo. O relatório permite comparar o número total e a distribuição por componente entre commits.

### Fase 1 — completar o bootstrap estático

As constantes de configuração e as funções procedurais são carregadas em runtime por uma cadeia de includes que o PHPStan não percorre de forma confiável. O arquivo `tools/phpstan-framework-stubs.php` deve ser ampliado somente com declarações fiéis às assinaturas observadas. Constantes devem ser declaradas com tipos ou valores representativos quando isso for necessário para inferência. Funções devem indicar tipos de parâmetros e retorno sem alterar o runtime.

A configuração deve separar `bootstrapFiles` para código seguro de carregar durante a análise de `stubFiles` para contratos declarativos. Arquivos com efeitos colaterais, acesso a banco, envio de e-mail, sessão ou escrita em disco não devem ser executados como bootstrap.

**Critérios de aceite:** os símbolos globais usados por `core.php`, `coreFull.php`, `console.php`, módulos e plugins são reconhecidos; nenhuma função real é duplicada no runtime; os stubs não usam `mixed` como solução indiscriminada; os diagnósticos de símbolos ausentes caem substancialmente.

### Fase 2 — corrigir variáveis indefinidas

Os fluxos de upload e erro devem inicializar variáveis antes de ramificações condicionais e usar valores nulos explícitos quando a ausência for válida. O lote deve começar por `$r`, `$n`, `$ext`, `$code` e `$valor`, citados nos logs anteriores, e continuar pelos caminhos de `storeFile`, gerenciadores de imagem, importação e tratamento de erros.

A correção deve preservar o comportamento funcional. Não se deve apenas atribuir string vazia para satisfazer o analisador quando a ausência tiver significado; nesse caso, o tipo deve ser `?string`, `?int` ou uma estrutura de resultado explícita.

**Critérios de aceite:** não há diagnósticos `Undefined variable` nos componentes tratados; uploads inválidos e falhas de escrita continuam retornando erros controlados; testes cobrem pelo menos extensão ausente, arquivo não encontrado e resposta de erro incompleta.

### Fase 3 — declarar propriedades e contratos dos componentes centrais

As classes `CModule`, banco de dados, controle de autenticação, cache, internacionalização, erros e headers devem declarar as propriedades que hoje são criadas dinamicamente. Propriedades obrigatórias devem ser inicializadas no construtor ou em um método de inicialização claramente tipado. Propriedades opcionais devem ser nullable e verificadas antes do uso.

Chamadas dinâmicas de módulos e plugins devem ser isoladas em adaptadores ou interfaces pequenas. O código que resolve um nome de plugin deve retornar um contrato conhecido, em vez de propagar `object` ou `mixed` por todo o framework.

**Critérios de aceite:** o nível 1 não reporta propriedades inexistentes nos componentes tratados; os métodos públicos críticos possuem parâmetros e retornos documentados; os pontos inevitavelmente dinâmicos estão concentrados e testados.

### Fase 4 — corrigir tipos de dados e retornos

Depois que símbolos e propriedades forem conhecidos, os diagnósticos restantes devem ser corrigidos por fluxo. Arrays de registros devem receber formas documentadas ou DTOs locais. Retornos de banco devem distinguir `false`, `null`, lista vazia e registro. Conversões de entrada devem validar `$_GET`, `$_POST`, `$_FILES` e `$_SESSION` antes de uso.

Os métodos de prepared statements devem conservar contratos explícitos para SQL, tipos e parâmetros. Essa fase não deve reintroduzir concatenação de SQL apenas para simplificar tipos.

**Critérios de aceite:** os avisos de acesso a offsets, argumentos incompatíveis e retornos inconsistentes diminuem por componente; os testes de banco e autenticação continuam cobrindo os caminhos críticos.

### Fase 5 — reduzir a baseline e preparar o nível 2

Somente após as fases anteriores a baseline poderá ser introduzida ou reduzida por grupos pequenos. Cada entrada deve indicar arquivo, identificador e justificativa. O nível 2 será habilitado primeiro em `prescia/services`, `prescia/lib/dbo`, `tests` e componentes já tipados, antes de abranger todo o legado.

**Critérios de aceite:** a baseline diminui em cada lote; nenhum pull request aumenta a quantidade de erros fora da baseline; o nível 2 passa nos diretórios modernizados; o restante do legado possui issues específicas.

## Ordem recomendada de issues

| Ordem | Escopo | Resultado esperado |
|---:|---|---|
| 1 | Bootstrap e stubs globais | Remover a causa-raiz das constantes e funções ausentes. |
| 2 | Variáveis indefinidas em upload/erro | Eliminar defeitos potenciais e reduzir falsos positivos subsequentes. |
| 3 | Propriedades de `CModule` e componentes centrais | Criar contratos estáveis para o CRUD e plugins. |
| 4 | Tipos de banco e prepared statements | Garantir contratos seguros para consultas e resultados. |
| 5 | Sanitizer e FileService | Expandir a migração dos consumidores globais para serviços tipados. |
| 6 | Baseline por componente | Remover supressões obsoletas e bloquear regressões. |
| 7 | Nível 2 incremental | Validar o método antes de elevar a análise do repositório inteiro. |

## Regras de revisão

Uma alteração não deve misturar correção de tipos com mudança funcional ampla. Cada commit deve indicar o grupo de diagnósticos tratado e a issue correspondente. O CI deve executar PHPUnit e PHPStan na mesma versão de PHP. Falhas causadas por símbolos ausentes devem gerar correção de bootstrap ou stub; não devem ser ocultadas com `ignoreErrors` global.

## Critério de conclusão da etapa

A etapa será considerada concluída quando a análise nível 1 for reproduzível no CI, o relatório for publicado como artefato, os diagnósticos de símbolos ausentes estiverem resolvidos ou vinculados a contratos específicos, as variáveis indefinidas críticas forem eliminadas e a quantidade de erros fora da baseline não aumentar entre pull requests. A elevação para nível 2 será uma etapa posterior, baseada em métricas e não apenas na passagem ocasional do workflow.

## Referências

[1]: https://phpstan.org/user-guide/discovering-symbols "PHPStan — Discovering Symbols"

[2]: https://phpstan.org/user-guide/config-reference "PHPStan — Configuration Reference"

[3]: https://phpstan.org/user-guide/baseline "PHPStan — Baseline"

[4]: https://github.com/leohmoraes/Prescia/actions/runs/33573327549 "Prescia — PHP static analysis workflow run"

[5]: https://github.com/leohmoraes/Prescia/issues/43 "Prescia Issue #43 — Atualizar PHPStan e elevar gradualmente o nível de análise"

---

**Autor:** Manus AI

**Status:** Plano proposto para execução incremental.
> **Nota:** este documento não altera a baseline nem substitui a análise do CI; ele define a ordem de correção e os critérios para aceitar cada lote.
