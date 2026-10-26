---
name: phpstan-legacy-remediation
description: Remediação incremental de diagnósticos PHPStan em frameworks PHP legados migrados para PHP 8.3, especialmente código com includes dinâmicos, módulos, plugins e payloads procedurais. Use para analisar, tipar, validar, documentar e publicar correções de variable.undefined, property.notFound, method.notFound, símbolos ausentes e problemas relacionados sem expandir a baseline.
license: Complete terms in LICENSE.txt
---

# PHPStan Legacy Remediation

Use esta skill para reduzir dívida técnica PHPStan com correções pequenas, verificáveis e compatíveis com PHP 8.3. Preserve o comportamento do framework, trate a causa no contexto correto e mantenha a baseline sem novos ocultamentos.

## Fluxo obrigatório

Execute as etapas na ordem:

1. **Ler o estado do projeto.** Consulte o plano PHPStan, o relatório consolidado, `phpstan.neon.dist`, `phpstan-baseline.neon`, o `CHANGELOG.md`, o status Git e os commits recentes.
2. **Obter evidência atual.** Use o relatório completo do PHPStan ou execute a análise. Registre commit, versão, nível, total ou limite do formatter e categorias.
3. **Agrupar diagnósticos.** Separe por identificador, diretório, plugin, arquivo, variável e linha. Priorize riscos executáveis e arquivos com maior concentração relacionada.
4. **Confirmar o contexto.** Rastreie cada `include`, `include_once`, carregador de payload e método do módulo. Determine a classe real de `$this`, a origem de `$core` e se cada propriedade pertence ao núcleo, módulo ou payload.
5. **Aplicar a menor correção.** Use PHPDoc concreto, inicialização no menor escopo comum, correção do objeto proprietário ou API explícita. Não crie objetos globais artificiais nem use `mixed` para silenciar o analisador.
6. **Validar focalizadamente.** Execute PHPStan no arquivo ou grupo, `php -l` em todos os arquivos alterados e `git diff --check`.
7. **Validar o conjunto.** Execute PHPUnit quando aplicável e a análise global somente após a validação focalizada. Preserve os workflows de compatibilidade PHP 8.3 e PHPStan.
8. **Documentar.** Atualize `CHANGELOG.md`, o plano e o relatório consolidado. Diferencie claramente corrigido, analisado e pendente.
9. **Publicar.** Faça commit pequeno, referencie a issue, execute `git push` e acompanhe os workflows com `gh run list`.
10. **Registrar o resultado.** Informe commit, arquivos, validações, estado dos workflows, baseline e próximo alvo.

## Contratos para payloads dinâmicos

Somente adicione PHPDoc depois de confirmar o carregador real. Para payload incluído no escopo do framework, use a classe concreta:

```php
/** @var CPrescia $core Contexto do núcleo injetado pelo framework. */
/** @var mod_bi_bb $this Contexto do módulo que inclui o payload. */
```

Adapte `mod_bi_bb` à classe comprovada no call site. Não declare `$this` se o arquivo não o usar. Prefira `$core` para estado e métodos de `CPrescia`, `$this->parent` quando o módulo expõe o núcleo por essa propriedade e `$this` somente para estado do módulo.

## Regras por diagnóstico

### `variable.undefined`

- Agrupe as ocorrências por fluxo, não apenas por nome.
- Inicialize no menor escopo comum ao primeiro consumidor.
- Preserve a diferença semântica entre `false`, `null`, zero, string vazia e lista vazia.
- Inicialize variáveis passadas por referência antes da chamada, respeitando o contrato da função.
- Não inicialize consulta, upload ou autenticação com valor que esconda erro.
- Verifique todos os ramos alcançáveis, inclusive `switch` sem `default`.

### `property.notFound`, `property.private` e `property.protected`

- Confirme a classe concreta do objeto.
- Corrija o objeto acessado quando a propriedade pertence a outra camada.
- Prefira getter ou método público/protegido com contrato claro.
- Declare a propriedade somente na classe que realmente a inicializa e consome.
- Não torne estado interno público apenas para reduzir erros.

### `method.notFound` e assinaturas

- Localize a declaração real e compare maiúsculas, parâmetros e classe proprietária.
- Corrija o call site quando o método pertence a `CPrescia` ou ao módulo pai.
- Não adicione métodos fictícios nem casts indiscriminados.

### Funções, classes e constantes ausentes

- Localize a origem real antes de criar stub.
- Use `scanFiles` para classes carregadas dinamicamente quando isso representar o runtime.
- Crie stubs precisos somente para símbolos opcionais ou dinâmicos, com assinatura real.
- Mantenha o bootstrap sem conexões, sessões, configuração de produção ou escrita em disco.

### `function.inner`, caminhos e regex

- Converta funções locais de payload em closures quando houver risco de redeclaração.
- Corrija caminhos com validação explícita de arquivo, sem criar placeholders de produção.
- Corrija padrões executáveis vazios ou inválidos; não use supressões.

## Comandos de validação

Execute a análise focalizada com o mesmo PHPStan do projeto:

```bash
composer install --no-interaction --no-progress
vendor/bin/phpstan analyse path/to/file.php --error-format=table --no-progress
php -l path/to/file.php
git diff --check
```

Depois execute os testes definidos pelo projeto:

```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse --no-progress
```

Remova `vendor/` e `composer.lock` se foram gerados apenas para a validação local e não forem artefatos versionados do projeto.

## Documentação obrigatória

Ao concluir um lote, registre:

- arquivo e diagnóstico tratado;
- origem confirmada do contexto;
- correção aplicada e justificativa;
- resultado do PHPStan focalizado e `php -l`;
- resultado do PHPStan global, PHPUnit e PHP 8.3 quando executados;
- commit, issue e URL dos workflows;
- contagem antes/depois quando disponível;
- baseline, que não deve receber nova entrada por erro introduzido.

Use linguagem precisa: um arquivo analisado mas não modificado é **pendente**, não concluído.

## Validações comprovadas no ciclo 2026-09-08

Ao trabalhar neste repositório, considere as seguintes evidências operacionais já comprovadas:

- O workflow `.github/workflows/php83.yml` executa separadamente compatibilidade PHP 8.3 e PHPStan on PHP 8.3. Trate um resultado verde de um job como insuficiente até que todos os check-runs do commit estejam `completed` com `conclusion=success`.
- Após merge, consulte os check-runs do SHA real de `origin/master`; os checks do PR e os checks pós-merge são execuções distintas.
- Para verificação sem ambiguidade, use `gh api repos/leohmoraes/Prescia/commits/<sha>/check-runs` e confirme `status=completed` e `conclusion=success` para PHP 8.3 e PHPStan.
- Em consultas de PR, confirme `state=MERGED`, `mergedAt` preenchido, `mergeable=true` e `mergeable_state=clean` antes de considerar o lote concluído.
- Se uma branch de PR incluir commits que já entraram no `master`, faça `git fetch origin --prune`, rebase sobre `origin/master`, valide `git diff --check origin/master...HEAD` e publique com `git push --force-with-lease`. Não force-puxe uma branch sem confirmar o SHA remoto.
- Atualizações de testes que codificam SQL antigo devem acompanhar a implementação. Quando o CI falhar por uma expectativa obsoleta, corrija a regressão no mesmo PR, mantenha o teste orientado ao contrato seguro e aguarde nova execução completa.
- Para mudanças SQL, prefira `queryPrepared`, `queryPrepared` com tipos explícitos e `fetchPrepared` quando a API do driver oferecer a operação. Mantenha nomes de tabela e coluna derivados de metadados internos; nunca transforme entrada externa em identificador SQL.
- Para o fechamento administrativo, só encerre uma issue quando o PR relacionado estiver mergeado e os checks do commit mergeado estiverem verdes. Adicione comentário com a referência do PR, escopo efetivamente entregue e eventuais limitações antes de fechar.
- Registre a validação final no `docs/RELATORIO_CICLO_2026-09-08.md` e no `CHANGELOG.md` quando a alteração for pública. Não declare uma auditoria ampla concluída por causa de um lote parcial; mantenha issues de escopo maior abertas.

## Política de baseline

Mantenha `phpstan-baseline.neon` sem crescimento automático. Não adicione diagnósticos de regressão, segurança, compatibilidade PHP 8.3 ou arquivos recém-alterados. Remova entradas somente quando a correção for comprovada e documentada.

## Política de commit e CI

Faça commits pequenos e coerentes, por exemplo:

```text
phpstan: tipar contexto do forum bi_bb
```

Inclua `Refs #<numero>` no corpo quando houver issue. Publique somente depois de validar sintaxe e diff. Verifique os workflows separadamente: compatibilidade PHP 8.3 aprovada não significa que a análise global do PHPStan esteja verde.

## Critérios de conclusão

Considere o lote concluído somente quando:

- o contexto de execução estiver comprovado;
- o diagnóstico tratado desaparecer sem supressão indevida;
- a sintaxe PHP 8.3 passar;
- não houver regressão no diff ou nos testes aplicáveis;
- a documentação estiver atualizada;
- o commit estiver publicado e o estado do CI registrado;
- o próximo diagnóstico estiver identificado.

## Navegação no repositório Prescia

Use estes arquivos como fontes de contexto, sem duplicar seu conteúdo na skill:

- `docs/PLANO_PHPSTAN_PROXIMO_LOTE.md`: ordem dos lotes e critérios de aceite;
- `docs/RELATORIO_PHPSTAN_PROGRESSO.md`: métricas, histórico e estado do CI;
- `phpstan.neon.dist`: bootstrap, stubs, `scanFiles` e nível;
- `phpstan-baseline.neon`: baseline controlada;
- `CHANGELOG.md`: histórico de mudanças públicas;
- `.github/workflows/php83.yml`: compatibilidade PHP 8.3 e execução do PHPStan.

Para operações GitHub, prefira `gh` autenticado no terminal. Não declare sucesso de workflow enquanto `status` não for `completed` e `conclusion` não for `success`.

## Exemplo de decisão

Para um payload com `$core` indefinido e uma variável `$sql` possivelmente não atribuída:

1. confirme qual método inclui o payload;
2. documente `$core` como a classe concreta fornecida pelo carregador;
3. verifique quais modos atribuem `$sql`;
4. inicialize `$sql` somente se o contrato de fluxo justificar um valor neutro seguro;
5. valide o payload isoladamente;
6. registre se o arquivo foi concluído ou se há diagnósticos independentes restantes.

Nunca substitua essa investigação por `@phpstan-ignore`, `mixed`, criação de um `CPrescia` artificial ou expansão automática da baseline.
