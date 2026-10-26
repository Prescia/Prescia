# Auditoria OWASP Top 10

**Projeto:** Prescia
**Data:** 7 de setembro de 2026
**Status:** auditoria inicial com primeiro achado corrigido

## Resultado inicial

A revisão cruzou as categorias OWASP Top 10 com as rotas PHP, mecanismos de autenticação, consultas SQL, uploads, redirects, saídas HTML e configuração do projeto.

Foi confirmado e corrigido um caso de **A03: Injection / A07: Identification and Authentication Failures e XSS refletido em saída de navegação** no fluxo administrativo de edição de thumbnails. A rota `prescia/plugins/bi_adm/payload/actions/edit_thumbnails.php` montava a query string do redirect concatenando valores de requisição diretamente:

```php
$qs[] = $key . "=" . $_REQUEST[$key];
$qs = "module=".$module->name."&field=".$_REQUEST['field']."&".implode("&",$qs);
```

A implementação foi substituída por um array controlado e `http_build_query(..., PHP_QUERY_RFC3986)`. Isso impede que caracteres de controle de query, aspas e markup alterem o contexto do redirect. Uma regressão estática foi adicionada em `tests/SecurityRegressionTest.php`.

## Validação do primeiro sublote

- PHPUnit: **34 testes e 2791 asserções aprovados**.
- PHPStan: **0 erros**.
- Lint PHP 8.3: aprovado nos arquivos tratados e nos arquivos PHP rastreados.
- `git diff --check`: aprovado.

## Matriz preliminar

| Categoria | Situação preliminar |
|---|---|
| A01 Broken Access Control | Parcialmente coberta; RBAC foi reforçado nas rotas AJAX e administrativas, mas há issues abertas para permissões do container e autorização residual. |
| A02 Cryptographic Failures | CSRF, cookies de sessão e hashing de senha já foram revisados; requer revisão adicional de segredos e transporte em deployment. |
| A03 Injection | Vários lotes de SQL foram parametrizados; sinks legados ainda existem e permanecem em issues abertas. O redirect de thumbnails foi corrigido neste lote. |
| A04 Insecure Design | Rate limiting de login continua pendente na issue #26. |
| A05 Security Misconfiguration | O workflow semanal usa `contents: read`; ainda é necessário revisar headers HTTP e configurações de produção. |
| A06 Vulnerable and Outdated Components | `composer audit` não encontrou advisories; não há manifesto npm. PHPUnit 10.5.64 tem atualização major disponível. |
| A07 Identification and Authentication Failures | Fluxos de sessão e CSRF foram reforçados; rate limiting ainda é pendência. |
| A08 Software and Data Integrity Failures | Composer lockfile e auditoria semanal estão presentes; revisão de pipeline e scripts de instalação permanece recomendada. |
| A09 Security Logging and Monitoring Failures | Há logging interno, mas a cobertura e o tratamento de eventos de segurança ainda precisam de revisão específica. |
| A10 SSRF | Não foi confirmado um SSRF neste primeiro mapeamento; `loadURL.php` e chamadas de URL devem receber revisão dedicada. |

## Pendências prioritárias

As próximas análises devem priorizar: rate limiting de login, escaping contextual nas saídas HTML, revisão de `loadURL.php` para SSRF, headers de segurança, permissões do container e sinks SQL legados restantes.

Este documento registra uma triagem inicial, não uma certificação de segurança. A ausência de um achado confirmado em uma categoria não prova ausência de vulnerabilidades.
