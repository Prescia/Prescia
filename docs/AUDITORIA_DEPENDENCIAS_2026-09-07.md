# Auditoria de Dependências Composer e npm

> **Atualização de estado — 2026-09-14:** o `composer.json` atual declara PHP `>=8.3 <8.6`, portanto a frase histórica da linha 30 que dizia `>=8.3 <8.4` foi corrigida. A conclusão sobre o PHPUnit permanece uma recomendação de atualização major separada.

**Projeto:** Prescia
**Repositório:** `leohmoraes/Prescia`
**Branch:** `master`
**Data:** 7 de setembro de 2026
**Escopo:** dependências PHP/Composer e JavaScript/npm

## Resumo executivo

A auditoria não identificou vulnerabilidades conhecidas nas dependências bloqueadas do Composer. O comando `composer audit --locked --no-interaction` terminou com código zero e informou **nenhum advisory de segurança**.

O repositório não contém `package.json`, `package-lock.json`, `npm-shrinkwrap.json`, `yarn.lock` ou `pnpm-lock.yaml`. Portanto, não há dependências npm declaradas ou bloqueadas para auditar neste projeto.

Foi identificada uma atualização possível para a dependência de desenvolvimento PHPUnit. A versão instalada é `10.5.64` e a versão mais recente informada pelo Composer é `12.5.34`. Essa diferença representa uma atualização de major version, não uma vulnerabilidade confirmada. A atualização não foi aplicada porque pode exigir alterações de compatibilidade e não é necessária para corrigir um CVE conhecido.

## Resultados

| Ecossistema | Manifesto/lockfile encontrado | Auditoria de vulnerabilidades | Desatualizações relevantes |
|---|---|---|---|
| Composer | `composer.json` e `composer.lock` | **Nenhum advisory encontrado** | PHPUnit `10.5.64` → `12.5.34` disponível |
| npm | Nenhum arquivo de manifesto ou lockfile | Não aplicável | Não aplicável |

## Dependência desatualizada

| Pacote | Tipo | Instalado | Mais recente informado | Estado de segurança | Ação recomendada |
|---|---|---:|---:|---|---|
| `phpunit/phpunit` | Desenvolvimento | `10.5.64` | `12.5.34` | Sem advisory reportado pelo Composer | Planejar atualização major separada, após revisar compatibilidade da suíte e da versão mínima de PHP |

O projeto declara PHP `>=8.3 <8.6`. A atualização do PHPUnit deve ser tratada como uma mudança de compatibilidade de testes, não como uma correção automática de segurança. O lockfile não deve ser alterado sem uma execução dedicada de atualização, testes completos e revisão das mudanças transitivas.

## Comandos executados

```text
composer audit --locked --no-interaction
composer outdated --direct --format=json --no-interaction
find . -path './.git' -prune -o -path '*/node_modules' -prune -o \
  \( -name package.json -o -name package-lock.json -o \
     -name npm-shrinkwrap.json -o -name yarn.lock -o \
     -name pnpm-lock.yaml \) -print
```

Resultados relevantes:

```text
No security vulnerability advisories found.
COMPOSER_AUDIT_EXIT=0
```

O `composer outdated` listou somente `phpunit/phpunit` como dependência direta desatualizada. Nenhuma dependência de produção foi apontada como desatualizada nesse comando.

## Limitações

A ausência de advisories significa que as bases de advisories consultadas pelo Composer não associaram vulnerabilidades conhecidas às versões bloqueadas no momento da execução. Isso não constitui garantia de ausência de vulnerabilidades desconhecidas ou de falhas no código da aplicação.

Como o repositório não possui dependências npm declaradas, não foi executado `npm audit`. Arquivos JavaScript estáticos ou bibliotecas vendorizadas fora de um manifesto npm não foram tratados como dependências gerenciadas.

## Recomendações

A dependência PHPUnit deve permanecer na versão atual até que seja aberto um lote específico de compatibilidade para a versão 12. Esse lote deve atualizar o constraint, regenerar o lockfile, executar a suíte completa e revisar eventuais mudanças na API de extensões e configurações do PHPUnit.

A rotina semanal de segurança já configurada no GitHub Actions deve continuar executando `composer audit --locked`, PHPUnit, PHPStan, lint PHP e a varredura de código. Se o projeto passar a incorporar frontend gerenciado por npm, o novo manifesto e lockfile deverão ser incluídos no repositório e auditados com `npm audit` ou ferramenta equivalente.

## Referências

[1]: https://getcomposer.org/doc/03-cli.md#audit "Composer audit command"
[2]: https://getcomposer.org/doc/03-cli.md#outdated "Composer outdated command"
[3]: https://docs.npmjs.com/cli/v11/commands/npm-audit "npm audit command"
[4]: https://phpunit.de/ "PHPUnit project documentation"
