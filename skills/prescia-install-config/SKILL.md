---
name: prescia-install-config
description: Instalação, configuração e validação do framework Prescia em ambientes Docker ou Apache/PHP/MySQL. Use quando for necessário levantar domínios, e-mails, credenciais administrativas, dados do banco, permissões de pastas e depois aplicar e testar a configuração completa.
---

# Instalar e configurar o Prescia

Executar o fluxo em etapas, sem registrar segredos em logs, commits, issues ou mensagens. Usar o repositório e os arquivos reais do projeto antes de assumir nomes de constantes ou caminhos.

## Fluxo obrigatório

1. Inspecionar o repositório, o `README.md`, `Dockerfile`, arquivos Compose e os templates `config/domains.original` e `config/settings.php.original`.
2. Identificar o modo de instalação: Docker Compose ou Apache/Nginx manual; distinguir desenvolvimento, staging e produção.
3. Fazer o levantamento interativo abaixo. Perguntar em blocos curtos; nunca pedir senha em texto público ou incluí-la em arquivos versionados.
4. Mostrar um resumo dos valores não secretos, valores mascarados e ações planejadas. Pedir confirmação antes de aplicar mudanças externas, criar usuário administrativo, alterar permissões amplas ou reiniciar serviços.
5. Criar backups timestampados dos arquivos de configuração existentes antes de alterá-los.
6. Criar/configurar os arquivos de domínio e settings, preparar o banco e aplicar permissões mínimas.
7. Validar sintaxe, configuração, conectividade, permissões, roteamento e login administrativo sem imprimir senhas.
8. Executar `composer validate --strict`, `composer test` e `composer analyse`.
9. Entregar relatório sem segredos, caminhos dos backups, URLs, testes e pendências.

## Levantamento obrigatório

### Ambiente e implantação

Perguntar:

- O alvo é Docker Compose, Apache/Nginx manual, staging ou produção?
- Qual é o diretório absoluto do projeto e qual usuário/grupo executa o PHP/Apache (`www-data`, outro ou container)?
- Qual porta/URL local ou pública deve ser usada?
- Deve habilitar HTTPS atrás de proxy? Qual terminador TLS e quais headers são confiáveis?
- Pode criar/alterar arquivos fora do repositório, reiniciar containers/serviços e ajustar ownership/permissões?

### Domínios

Perguntar:

- Liste todos os domínios e subdomínios, um por linha.
- Qual domínio é o principal/canônico?
- Qual código de site deve ser associado a cada domínio? Reutilizar o formato exigido por `config/domains.original`.
- A instalação terá múltiplos sites/domínios ou apenas um?
- Deve habilitar o seletor local de domínio (`CONS_SITESELECTOR`) em desenvolvimento?

### E-mail

Perguntar:

- Qual é o e-mail mestre/administrativo (`CONS_MASTERMAIL`)?
- Qual remetente e nome do sistema devem ser usados?
- Existe SMTP externo? Host, porta, TLS/SSL, usuário e senha devem ser fornecidos por canal secreto; nunca gravar o segredo no Git.
- Qual timezone e endereço para erros/notificações?

### Administrador

Perguntar:

- Qual login do administrador inicial?
- Qual nome/e-mail do administrador?
- Qual senha forte será usada? Solicitar via entrada secreta ou mecanismo seguro; nunca ecoar nem salvar em relatório.
- O administrador deve ter nível 100/superadministrador?
- Existe usuário administrativo prévio que deve ser preservado ou atualizado?

### Banco de dados

Perguntar:

- Host, porta, nome do banco, usuário e senha.
- O banco já existe? É permitido criar banco, usuário e privilégios?
- Usar TLS no MySQL? Informar CA/certificados se obrigatório.
- O banco deve ser inicializado vazio, migrado de backup ou apenas testado?
- Em Docker, os valores devem substituir os defaults de desenvolvimento de `docker-compose.yml`?

### Pastas e permissões

Perguntar:

- Quais são os caminhos de `_temp`, logs, cache e backups?
- Quais pastas devem ser graváveis pelo processo web?
- Qual usuário/grupo executa o serviço?

Aplicar, salvo requisito justificado diferente: código/arquivos `0644`, diretórios `0755`, áreas graváveis somente para o serviço web, preferencialmente `0770` com grupo dedicado; nunca usar `0777` sem justificativa explícita. Proteger `config/`, `prescia/`, `tests/`, `tools/`, `docs/`, `.env`, backups e credenciais contra acesso HTTP.

## Aplicação

- Se os destinos não existirem, copiar `config/domains.original` para `config/domains` e `config/settings.php.original` para `config/settings.php`.
- Se já existirem, preservar backup e editar somente valores solicitados; nunca substituir cegamente configuração personalizada.
- Validar `CONS_MASTERPASS`, `CONS_MASTERMAIL`, banco e timezone.
- Preferir variáveis de ambiente/secret mounts para senhas quando suportado. Se o framework exigir constantes em `settings.php`, proteger o arquivo por permissões e negar acesso no Apache.
- Criar as pastas exigidas por `CPrescia::checkinstall()` e pelo runtime, incluindo `_temp/_logs`, `_temp/_cache` e `_temp/_backups` quando aplicável.
- Garantir `DocumentRoot` na superfície pública correta e habilitar `mod_rewrite`, `headers` e `expires`.
- Nunca executar `chmod -R 777` nem alterar ownership de todo o repositório para o usuário web.

## Validação

Executar, conforme o ambiente:

```bash
composer validate --strict
composer test
composer analyse
php -l config/settings.php
```

Também:

- testar cada domínio, a página inicial e uma rota inexistente/404;
- testar leitura/escrita de cache, log e backup;
- testar conexão MySQL;
- testar criação/login/logout do administrador;
- confirmar que `config`, `prescia`, `tests`, `tools` e `docs` não são servidos por HTTP;
- confirmar que `.env`, logs, dumps SQL e backups não são públicos;
- executar `git diff --check` e verificar que nenhum segredo aparece em status, diff, logs ou relatório.

Em Docker, executar `docker compose config`, `docker compose up -d --build`, `docker compose ps`, logs sem credenciais e smoke tests HTTP. Encerrar containers somente quando forem temporários ou quando solicitado.

## Segurança

- Tratar senhas, tokens, chaves SMTP, credenciais MySQL e certificados privados como dados secretos.
- Não colocar segredos em comandos visíveis, histórico, GitHub, issues ou commits.
- Mascarar valores em resumos.
- Não declarar a instalação segura apenas porque o login funciona; verificar isolamento HTTP e permissões.
- Se faltar uma decisão material, parar e perguntar; não adivinhar domínio canônico, credenciais, usuário do serviço ou política de backup.
- Se um teste falhar, corrigir e repetir a validação antes de declarar concluído.

## Relatório final

Gerar Markdown fora do controle de versão ou em caminho aprovado, contendo modo de instalação, domínios, caminhos, permissões efetivas, versões PHP/MySQL, testes, backups, URLs e pendências. Omitir senhas, tokens, chaves privadas e strings de conexão completas.
