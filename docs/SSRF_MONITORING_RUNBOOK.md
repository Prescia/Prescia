# Runbook de monitoramento de SSRF

## Conclusão

O `loadURL()` emite eventos estruturados e redigidos para cada rejeição SSRF. Este runbook define como transformar esses eventos em métricas, alertas, investigação e retenção sem registrar credenciais, query strings ou corpos remotos.

A decisão de segurança permanece independente do monitoramento. Uma falha no logger não libera a conexão, e qualquer URL que falhe na validação continua sendo rejeitada.

## Contrato de evento

O evento possui o schema `prescia.security.v1` e o identificador fixo `ssrf_blocked`. O campo `reason` é uma categoria estável. O campo `host` contém apenas o hostname normalizado, sanitizado e truncado. O evento não contém a URL completa, a query string, credenciais, cookies, headers de autenticação ou conteúdo remoto.

| Campo | Política | Uso operacional |
| --- | --- | --- |
| `schema` | Valor fixo `prescia.security.v1` | Versionamento do parser |
| `event` | Valor fixo `ssrf_blocked` | Seleção do evento |
| `reason` | Categoria de baixa cardinalidade | Métrica e alerta |
| `scheme` | `http` ou `https` quando disponível | Diagnóstico |
| `host` | Host normalizado, filtrado e limitado a 253 caracteres | Agrupamento com cardinalidade controlada |
| `port` | Porta numérica válida ou `null` | Diagnóstico de política |
| `method` | Método normalizado | Métrica auxiliar |
| `timestamp` | ISO 8601 em UTC | Correlação temporal |

## Taxonomia de motivos

Os consumidores devem agrupar somente pelo campo `reason`. Não se deve criar label por URL completa, IP arbitrário, query string ou usuário.

| Motivo | Interpretação | Severidade inicial |
| --- | --- | --- |
| `invalid_url` | URL vazia, com quebra de linha ou NUL | Média |
| `invalid_request` | Esquema, host, credencial ou método inválido | Alta |
| `invalid_port` | Porta fora da política HTTP/HTTPS | Alta |
| `private_or_unresolved_address` | DNS vazio, endereço privado, reservado ou inconsistente | Alta |
| `connection_failed` | Nenhum IP público validado aceitou conexão | Média |
| `write_failed` | Falha ao enviar a requisição | Média |
| `response_too_large` | Resposta excedeu o limite de leitura | Média |
| `body_too_large` | Corpo excedeu o limite permitido | Média |

## Métricas

O agregador de logs deve produzir contadores por janela de cinco minutos, por ambiente e por componente chamador quando essa informação estiver disponível no contexto externo. O hostname deve ser usado apenas em uma dimensão amostrada ou em uma lista de investigação limitada; ele não deve ser uma label livre de alta cardinalidade.

| Métrica | Agrupamento | Objetivo |
| --- | --- | --- |
| `prescia_ssrf_blocked_total` | `environment`, `reason`, `method` | Volume geral de bloqueios |
| `prescia_ssrf_private_address_total` | `environment` | Tentativas contra redes internas |
| `prescia_ssrf_dns_failure_total` | `environment` | Falhas ou respostas DNS não confiáveis |
| `prescia_ssrf_resource_limit_total` | `environment`, `reason` | Exaustão por registros ou resposta |
| `prescia_ssrf_connection_failure_total` | `environment` | Indisponibilidade ou abuso de destinos externos |

O dashboard mínimo deve mostrar a taxa de bloqueios por minuto, a distribuição por `reason`, os cinco ambientes com maior volume e a tendência de vinte e quatro horas. Nenhum painel deve exibir query strings, credenciais, cookies ou corpos remotos.

## Alertas e resposta

Os limiares abaixo são valores iniciais. A equipe responsável deve calibrá-los com a linha de base de produção durante a primeira semana.

| Alerta | Condição inicial | Ação |
| --- | --- | --- |
| Aumento de SSRF | Mais de 20 bloqueios por minuto por 5 minutos no mesmo ambiente | Abrir incidente de segurança e identificar endpoint chamador |
| Rede interna | Qualquer sequência de 5 bloqueios `private_or_unresolved_address` em 10 minutos | Escalar para segurança e revisar origem autenticada |
| Metadata endpoint | Qualquer tentativa contra endereço de metadata ou loopback | Escalação imediata e preservação dos eventos redigidos |
| Varredura | Mais de 10 hosts distintos em 10 minutos para a mesma origem, quando a origem estiver disponível fora do evento | Aplicar rate limit e revisar a sessão |
| Falha DNS | Mais de 50% dos eventos de uma janela com falha DNS | Verificar resolver, egress gateway e possível abuso |
| Limite de recurso | Mais de 10 eventos de resposta ou DNS acima do limite em 10 minutos | Verificar DoS e limites do destino externo |

A investigação deve começar pelo ambiente, janela de tempo, motivo e endpoint chamador. O analista não deve solicitar ou registrar a URL completa para reproduzir o evento. A reprodução deve usar um hostname de teste controlado e os mesmos limites de DNS, conexão, leitura e tamanho.

## Retenção e acesso

Os eventos devem ser enviados ao logger estruturado existente e ao agregador de logs da aplicação. O ambiente de produção deve restringir a leitura desses eventos ao time de segurança, à operação da aplicação e ao responsável pelo incidente. Recomenda-se retenção quente de trinta dias e retenção fria de noventa dias, sujeita à política legal e operacional do ambiente. A exportação deve manter a redação aplicada na origem.

O logger deve tratar caracteres de controle e falhas de serialização sem interromper o fluxo de segurança. O teste de falha do logger deve confirmar que `loadURL()` continua retornando falha e nunca abre o socket quando a validação não é satisfeita.

## Verificação em CI e homologação

A CI executa os testes determinísticos de sintaxe, PHPUnit e PHPStan. O teste de rebinding usa um conector injetado e confirma que a conexão recebe o IP validado, não o hostname. Os testes de DNS controlado devem ser executados em homologação com um resolver autoritativo que produza respostas A, AAAA e CNAME mistas. Redirects não são seguidos pelo cliente atual; qualquer implementação futura deverá repetir toda a validação para cada destino.

A homologação deve verificar os seguintes casos: loopback IPv4 e IPv6, redes privadas, link-local, ULA, metadata endpoint, hosts numéricos alternativos, hostnames sem ponto, respostas DNS vazias, excesso de registros, corpo acima do limite e falha do logger. O resultado deve registrar o ambiente, a versão do commit e os nomes dos testes, sem armazenar dados sensíveis.

## Referências

[1]: https://github.com/leohmoraes/Prescia/issues/66 "Issue 66 — monitoramento de tentativas de SSRF"

[2]: https://github.com/leohmoraes/Prescia/blob/master/prescia/lib/loadURL.php "Implementação de loadURL"

[3]: https://github.com/leohmoraes/Prescia/blob/master/tests/SecurityRegressionTest.php "Regressões de segurança do Prescia"

[4]: https://github.com/leohmoraes/Prescia/issues/65 "Issue 65 — DNS rebinding em loadURL"

**Autor:** Manus AI

**Data:** 2026-09-10

**Issue relacionada:** [#66][1]

**Implementação relacionada:** [loadURL.php][2]

**Testes relacionados:** [SecurityRegressionTest.php][3]

**Plano de DNS rebinding:** [#65][4]

---

Este documento define o procedimento operacional. A configuração efetiva de limiares, retenção e permissões deve ser aplicada no agregador de logs e no sistema de alertas do ambiente de produção.

