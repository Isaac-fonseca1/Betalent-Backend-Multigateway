# BeTalent - Multi-Gateway Payment API

Sistema gerenciador de pagamentos multi-gateway desenvolvido em **Laravel 11 + PHP 8.3**, com autenticação Sanctum, autorização por roles, failover automático entre gateways e TDD.

> **Nível de implementação:** Sênior (Nível 3)

---

## 📋 Requisitos

- Docker & Docker Compose
- Git

---

## 🚀 Como instalar e rodar

```bash
# 1. Clone o repositório
git clone <url-do-repositorio>
cd Betalent-Backend-Teste

# 2. Suba os containers
docker compose up -d

# 3. Rode as migrations e seeders
docker compose exec app php artisan migrate:fresh --seed

# 4. Rode os testes
docker compose exec app php artisan test
```

A API estará disponível em `http://localhost:8000/api`.

### Dados de teste (Seeder)

| Recurso | Dados |
|---|---|
| **Admin** | `admin@betalent.test` / `password` (role: ADMIN) |
| **Gateway 1** | driver: `gateway1`, priority: 1 |
| **Gateway 2** | driver: `gateway2`, priority: 2 |
| **Produtos** | Plano Basic (R$ 49,90), Plano Premium (R$ 149,90) |
| **Cliente teste** | João da Silva, CPF: 12345678909 |

---

## 🗄 Estrutura do Banco de Dados

| Tabela | Campos principais |
|---|---|
| `users` | name, email, password, role (ADMIN/MANAGER/FINANCE/USER), soft_deletes |
| `clients` | name, email, document (CPF/CNPJ), document_type, phone |
| `gateways` | name, driver, priority, is_active |
| `products` | name, description, amount, is_active, soft_deletes |
| `transactions` | client_id, gateway_id, external_id, status, amount, card_last_numbers, error_message |
| `transaction_products` | transaction_id, product_id, quantity, unit_price |

> Valores monetários armazenados em **centavos** (BigInt) para evitar problemas de ponto flutuante.

---

## 🛣 Rotas da API

### Rotas Públicas (sem autenticação)

| Método | Rota | Descrição |
|---|---|---|
| `POST` | `/api/auth/login` | Autenticação (retorna Bearer Token) |
| `POST` | `/api/checkout` | Realizar uma compra |

### Rotas Privadas (Bearer Token via `Authorization` header)

#### Usuários (ADMIN, MANAGER)
| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/users` | Listar usuários |
| `POST` | `/api/users` | Criar usuário |
| `GET` | `/api/users/{id}` | Detalhes do usuário |
| `PUT` | `/api/users/{id}` | Editar usuário |
| `DELETE` | `/api/users/{id}` | Deletar usuário (soft delete) |

#### Clientes (todos autenticados)
| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/clients` | Listar clientes |
| `POST` | `/api/clients` | Criar cliente |
| `GET` | `/api/clients/{id}` | Detalhes do cliente + todas suas compras |
| `PUT` | `/api/clients/{id}` | Editar cliente |
| `DELETE` | `/api/clients/{id}` | Deletar cliente |

#### Produtos (ADMIN, MANAGER, FINANCE)
| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/products` | Listar produtos |
| `POST` | `/api/products` | Criar produto |
| `GET` | `/api/products/{id}` | Detalhes do produto |
| `PUT` | `/api/products/{id}` | Editar produto |
| `DELETE` | `/api/products/{id}` | Deletar produto (soft delete) |

#### Gateways (ADMIN)
| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/gateways` | Listar gateways (ADMIN, MANAGER, FINANCE) |
| `POST` | `/api/gateways` | Criar gateway |
| `GET` | `/api/gateways/{id}` | Detalhes do gateway |
| `PUT` | `/api/gateways/{id}` | Editar gateway (ativar/desativar, alterar prioridade) |
| `DELETE` | `/api/gateways/{id}` | Deletar gateway |

#### Transações (todos autenticados)
| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/transactions` | Listar todas as compras |
| `GET` | `/api/transactions/{id}` | Detalhes de uma compra |
| `POST` | `/api/transactions/{id}/refund` | Reembolsar compra (ADMIN, FINANCE) |

---

## 🔐 Permissões por Role

| Ação | ADMIN | MANAGER | FINANCE | USER |
|---|---|---|---|---|
| CRUD Usuários | ✅ | ✅ | ❌ | ❌ |
| CRUD Produtos | ✅ | ✅ | ✅ | Somente leitura |
| Gerenciar Gateways | ✅ | ❌ | ❌ | ❌ |
| Visualizar Gateways | ✅ | ✅ | ✅ | ❌ |
| Listar Clientes | ✅ | ✅ | ✅ | ✅ |
| Realizar Compra | ✅ | ✅ | ✅ | ✅ |
| Realizar Reembolso | ✅ | ❌ | ✅ | ❌ |

---

## 🔌 Integração com Gateways

A arquitetura utiliza o **Strategy Pattern** com uma `PaymentGatewayContract` (interface) e drivers concretos:

- **GatewayOneDriver** (porta 3001) — Autenticação via Bearer Token (`POST /login`)
- **GatewayTwoDriver** (porta 3002) — Autenticação via Headers (`Gateway-Auth-Token` + `Gateway-Auth-Secret`)

### Failover automático

O `CheckoutProcessor` tenta cobrar no gateway de maior prioridade. Se falhar, automaticamente tenta o próximo gateway ativo. Se todos falharem, a transação é registrada como `FAILED`.

### Adicionando novos gateways

1. Crie uma nova classe em `app/Services/Gateways/Drivers/` implementando `PaymentGatewayContract`
2. Registre o driver no `GatewayFactory`
3. Crie o registro do gateway no banco de dados

---

## 🧪 Testes (TDD)

```bash
docker compose exec app php artisan test
```

| Suite | Testes | Descrição |
|---|---|---|
| `AuthTest` | 5 | Login, credenciais inválidas, rotas protegidas |
| `RoleAuthorizationTest` | 12 | Permissões por role para CRUD e refund |
| `CheckoutIntegrationTest` | 6 | Multi-produto, failover, validação, falha total |

**Total: 30 testes, 56 assertions**

---

## 🏗 Decisões Técnicas

- **Nível 3 (Sênior)**: Implementação completa com foco em robustez, failover e segurança.
- **Cálculo de Checkout no Backend**: O valor total da compra é calculado exclusivamente no servidor (`CheckoutProcessor`), somando `unit_price * quantity` de múltiplos produtos, garantindo integridade financeira.
- **Gateways Autenticados**: Suporte a diferentes métodos de autenticação (Bearer Token e Headers customizados) simulando integração real com gateways externos.
- **Roles Granulares (RBAC)**:
  - `ADMIN`: Acesso total ao sistema.
  - `MANAGER`: Gestão de usuários e produtos.
  - `FINANCE`: Gestão de produtos e execução de reembolsos.
  - `USER`: Acesso a funcionalidades básicas (checkout e visualização).
- **Arquitetura TDD**: Desenvolvimento guiado por testes com cobertura de fluxos críticos (auth, failover, permissões).
- **UUIDs** como primary keys para segurança (não expõe volume de registros).
- **Valores em centavos** (BigInt) para precisão financeira absoluta.
- **Soft Deletes** em users e products para preservar o histórico de transações.
- **`unit_price`** em `transaction_products` preserva o preço exato no momento da compra.
- **Cache de Autenticação**: Otimização do Gateway 1 com cache do token Bearer para reduzir latência.
- **Custom Exceptions**: Erros financeiros semânticos com `error_code` padronizado.

---

## 📁 Estrutura do Projeto

```
app/
├── Http/
│   ├── Controllers/          # Lógica de controle (AuthController, UserController, etc.)
│   ├── Requests/             # FormRequests (Validação centralizada: CheckoutRequest, etc.)
│   └── Resources/            # API Resources (Transformação e padronização JSON)
├── Models/                   # Eloquent Models (User, Client, Product, Transaction, etc.)
├── Enums/                    # PHP 8.1+ Enums (UserRole, TransactionStatus, DocumentType)
├── Policies/                 # Authorization (Granularidade por role e propriedade)
├── Exceptions/               # Custom Business Exceptions (CheckoutFailed, RefundFailed)
└── Services/                 # Camada de Negócio
    ├── Checkout/            
    │   └── CheckoutProcessor.php # Orquestração do fluxo de pagamentos e failover
    └── Gateways/
        ├── Contracts/        # Interface PaymentGatewayContract (Strategy Pattern)
        ├── Drivers/          # Implementações específicas (GatewayOne, GatewayTwo)
        └── GatewayFactory.php # Fábrica dinâmica de gateways

database/
├── factories/                # Model Factories para TDD e Seeds profissionais
├── migrations/               # Schema evolutivo (Schema::create + SoftDeletes)
└── seeders/                  # Populadores (Admin, Gateways, Produtos, Clientes)

tests/
├── Feature/                  # Testes de Integração (Auth, Roles, Checkout/Failover)
└── Unit/                     # Testes Unitários de lógica isolada
```

---

## 📝 Exemplos de uso

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@betalent.test", "password": "password"}'
```

### Realizar compra (rota pública)
```bash
curl -X POST http://localhost:8000/api/checkout \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "<uuid-do-cliente>",
    "credit_card": "5569000000006063",
    "cvv": "010",
    "products": [
      {"id": "<uuid-produto-1>", "quantity": 2},
      {"id": "<uuid-produto-2>", "quantity": 1}
    ]
  }'
```

### Listar produtos (rota privada)
```bash
curl http://localhost:8000/api/products \
  -H "Authorization: Bearer <seu-token>"
```

---

Desenvolvido para o processo seletivo **BeTalent Tech** 🚀
