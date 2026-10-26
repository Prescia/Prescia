# GitHub Copilot – Repository Instructions
# Projeto: PHP 8+ (Frameworkless / Vanilla PHP)

Estas instruções definem como o GitHub Copilot deve gerar código
neste repositório PHP moderno sem uso de frameworks como Laravel ou Symfony.

---

## 📌 Linguagem e Versão

- Todo código deve ser escrito em **PHP 8.1+**.
- Utilizar `declare(strict_types=1);` em todos os arquivos PHP.
- Usar **tipagem forte** em parâmetros, retornos e propriedades.
- Evitar funcionalidades obsoletas ou comportamento implícito do PHP.

---

## 🧱 Padrões e Convenções

- Seguir rigorosamente os padrões:
  - **PSR-1** (Basic Coding Standard)
  - **PSR-4** (Autoloading)
  - **PSR-12** (Extended Coding Style)
- Classes em **StudlyCase**
- Métodos e variáveis em **camelCase**
- Constantes em **UPPER_SNAKE_CASE**
- Um arquivo → uma classe/interface/enum.

---

## 📁 Organização de Código

Estrutura sugerida (ajustável conforme o projeto):

- `src/`
  - `Application/` → casos de uso
  - `Domain/` → regras de negócio
  - `Infrastructure/` → banco, APIs externas, IO
  - `Http/` → controllers, middlewares
- `tests/`
- `config/`
- `public/` (se aplicável)

Evitar lógica de negócio em:
- Controllers
- Scripts de bootstrap
- Camada de infraestrutura

---

## 🧠 Arquitetura e Design

- Favorecer **SOLID** e baixo acoplamento.
- Usar **injeção de dependência** via construtor.
- Programar para **interfaces**, não implementações.
- Evitar singletons e estado global.
- Separar claramente:
  - Orquestração
  - Regra de negócio
  - Infraestrutura

---

## 🔌 Interfaces e Contratos

- Toda dependência externa deve ser abstraída por **interfaces**.
- Interfaces devem representar **comportamento**, não tecnologia.
- Evitar vazamento de detalhes de infraestrutura para o domínio.

Exemplo:
- `UserRepositoryInterface`
- `PaymentGatewayInterface`

---

## 🗄️ Persistência e Banco de Dados

- Evitar SQL diretamente em Controllers ou Use Cases.
- Isolar acesso a dados em Repositories ou Gateways.
- Usar **prepared statements** sempre.
- Tratar erros de banco de forma explícita.
- Nunca misturar regras de negócio com queries SQL.

---

## 🔐 Segurança

- Nunca confiar em input externo.
- Validar e sanitizar entradas explicitamente.
- Evitar `eval`, `exec`, `shell_exec`.
- Nunca expor stack traces ou mensagens internas ao usuário final.
- Manter segredos fora do código-fonte (env/config).

---

## 🧪 Testes

- Utilizar **PHPUnit**.
- Testes devem ser:
  - Determinísticos
  - Independentes
  - Focados em comportamento
- Evitar dependências externas reais em testes (usar mocks/stubs).
- Priorizar testes de unidade para regras de negócio.
- Testes de integração devem ser explícitos e isolados.

---

## 📦 Erros e Exceções

- Usar **exceptions tipadas**.
- Não usar exceções para controle de fluxo.
- Capturar exceções somente quando for possível tratá-las.
- Mensagens de erro devem ser claras, técnicas e seguras.

---

## 📝 Comentários e Documentação

- Comentários devem explicar **intenções e decisões**, não o óbvio.
- Métodos públicos devem ter PHPDoc quando:
  - A lógica for complexa
  - O contrato não for óbvio
- Evitar comentários redundantes.

---

## 🚫 O que evitar

- Código sem tipagem.
- Funções globais.
- Classes anêmicas sem comportamento.
- Mistura de domínio com infraestrutura.
- Métodos longos ou com múltiplas responsabilidades.
- Dependência direta de variáveis globais ou `$_*`.

---

## 🎯 Objetivo Final

Gerar código PHP que seja:
- Moderno
- Tipado
- Testável
- Desacoplado
- Fácil de manter
- Independente de frameworks
