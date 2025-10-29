# API de Funcionários

Uma API RESTful para gerenciamento de funcionários construída com Laravel, que oferece autenticação JWT e operações CRUD completas.

## 📋 Índice

- [Sobre o Projeto](#sobre-o-projeto)
- [Tecnologias Utilizadas](#tecnologias-utilizadas)
- [Configuração](#configuração)
- [Autenticação](#autenticação)
- [Endpoints da API](#endpoints-da-api)
- [Modelos de Dados](#modelos-de-dados)
- [Códigos de Status](#códigos-de-status)

## 🚀 Sobre o Projeto

Esta API permite o gerenciamento completo de funcionários, incluindo:

- ✅ Autenticação de usuários com JWT
- ✅ CRUD completo de funcionários
- ✅ Importação de funcionários via CSV
- ✅ Validação de dados brasileiros (CPF, Estados)
- ✅ Controle de acesso baseado em políticas
- ✅ Soft delete para funcionários

## 🛠 Tecnologias Utilizadas

- **Laravel 12.x** - Framework PHP
- **JWT Authentication** - Autenticação por tokens
- **MySQL** - Banco de dados
- **Docker** - Containerização
- **PHPUnit** - Testes automatizados

## ⚙️ Configuração
Foi criado um arquivo `Makefile` para facilitar a execução de comandos na fase de desenvolvimento.
Abra o arquivo `Makefile` na raiz do projeto e veja os comandos disponíveis

### Usando Docker

```bash
# Clone o repositório
git clone https://github.com/ludmilla-carvalho/employees-api.git

# Entre no diretório
cd employees-api

# Configure o arquivo .env
cp .env.example .env

# Execute o ambiente
make up

# Gere a chave da aplicação
php artisan key:generate

# Execute as migrations
make migrate

# Execute os seeders (opcional)
make seed
```

O sistema estará acessível em http://localhost

O PHPMyAdmin está acessível em http://localhost:8080  - lá você pode ver todos os registros inseridos via migration e API

O MailHog está acessível em http://localhost:8025, alí é possível ver os emails enviados após o processamento da importação

Também há uma colection do postman disponível em localizado na pasta `.docs`

**LEIA O ARQUIVO README.MD** na pasta `.docs`, há um melhor deatlhamento descrito deste arquivo

## 🧑‍💻 Desenvolvimento
Acesse o container para rodar comandos artisan:
```bash
make bash
php artisan route:list
php artisan tinker
exit
```


## 🖌 Testes e Qualidade

### Rodar testes

```bash
make test
```
Todos os testes são rodados com cobertura, que está disponível na pasta `coverage`

### Formatação de Código - Laravel Pint
O projeto utiliza **Laravel Pint** para padronizar o código PHP.
```bash
make format
```
### Pré-commit Hook
Um hook Git foi configurado para rodar Pint e o PHPStan automaticamente antes de cada commit. Este hoock está localizado na pasta `extras` e deve ser copiado para a pasta `.git/hooks`
```bash
cp .extras/pre-commit .git/hooks/pre-commit
```
- Certifique-se que `.git/hooks/pre-commit` existe e é executável:
```bash
chmod +x .git/hooks/pre-commit
```
 - Ao tentar commitar código PHP:
   - Se houver problemas de formatação, o commit será bloqueado e será necessário corrigir.
   - Se tudo estiver correto, o commit será realizado normalmente.


## 🔐 Autenticação

A API utiliza autenticação JWT (JSON Web Token). Todos os endpoints, exceto o login, requerem um token válido.

### Headers Obrigatórios

```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### URL Base

```
http://localhost/api
```

## 📚 Endpoints da API

### 🔑 Autenticação

#### Login
```http
POST /api/auth/login
```

**Corpo da Requisição:**
```json
{
    "email": "usuario@example.com",
    "password": "secret"
}
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600,
        "user": {
            "id": 1,
            "name": "Nome do Usuário",
            "email": "usuario@example.com",
            "email_verified_at": null,
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        }
    }
}
```

#### Informações do Usuário Logado
```http
GET /api/auth/me
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "User information retrieved successfully",
    "data": {
        "id": 1,
        "name": "Nome do Usuário",
        "email": "usuario@example.com",
        "email_verified_at": null,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

#### Logout
```http
POST /api/auth/logout
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Logged out successfully",
    "data": null
}
```

#### Renovar Token
```http
POST /api/auth/refresh
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

### 👥 Funcionários

#### Listar Funcionários
```http
GET /api/employees
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Employees retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "João Silva",
            "email": "joao@example.com",
            "cpf": "12345678901",
            "city": "São Paulo",
            "state": "SP",
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

#### Visualizar Funcionário
```http
GET /api/employees/{id}
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Employee retrieved successfully",
    "data": {
        "id": 1,
        "name": "João Silva",
        "email": "joao@example.com",
        "cpf": "12345678901",
        "city": "São Paulo",
        "state": "SP",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

#### Criar Funcionário
```http
POST /api/employees
```

**Corpo da Requisição:**
```json
{
    "name": "Maria Santos",
    "email": "maria@example.com",
    "cpf": "98765432100",
    "city": "Rio de Janeiro",
    "state": "RJ"
}
```

**Resposta de Sucesso (201):**
```json
{
    "success": true,
    "message": "Employee created successfully",
    "data": {
        "id": 2,
        "name": "Maria Santos",
        "email": "maria@example.com",
        "cpf": "98765432100",
        "city": "Rio de Janeiro",
        "state": "RJ",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

#### Atualizar Funcionário
```http
PUT /api/employees/{id}
PATCH /api/employees/{id}
```

**Corpo da Requisição:**
```json
{
    "name": "Maria Santos Silva",
    "email": "maria.silva@example.com",
    "cpf": "98765432100",
    "city": "Rio de Janeiro",
    "state": "RJ"
}
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Employee updated successfully",
    "data": {
        "id": 2,
        "name": "Maria Santos Silva",
        "email": "maria.silva@example.com",
        "cpf": "98765432100",
        "city": "Rio de Janeiro",
        "state": "RJ",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T12:00:00.000000Z"
    }
}
```

#### Excluir Funcionário
```http
DELETE /api/employees/{id}
```

**Resposta de Sucesso (200):**
```json
{
    "success": true,
    "message": "Employee deleted successfully",
    "data": null
}
```

#### Importar Funcionários (CSV)
```http
POST /api/employees/import
```

**Corpo da Requisição (multipart/form-data):**
- `file`: Arquivo CSV contendo os dados dos funcionários

**Formato do CSV:**
```csv
name,email,cpf,city,state
João Silva,joao@example.com,12345678901,São Paulo,SP
Maria Santos,maria@example.com,98765432100,Rio de Janeiro,RJ
```

**Resposta de Sucesso (202):**
```json
{
    "success": true,
    "message": "The import of employee data will be processed shortly. You will be notified when it is complete.",
    "data": null
}
```

## 📊 Modelos de Dados

### Employee (Funcionário)
```json
{
    "id": "integer",
    "name": "string (max: 255)",
    "email": "string (email, max: 255, unique)",
    "cpf": "string (11 digits, unique)",
    "city": "string (max: 255)",
    "state": "string (Brazilian state code)",
    "user_id": "integer",
    "created_at": "datetime",
    "updated_at": "datetime",
    "deleted_at": "datetime|null"
}
```

### User (Usuário)
```json
{
    "id": "integer",
    "name": "string",
    "email": "string (email, unique)",
    "email_verified_at": "datetime|null",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

### Estados Brasileiros Válidos
```
AC, AL, AP, AM, BA, CE, DF, ES, GO, MA, MT, MS, MG, PA, PB, PR, PE, PI, RJ, RN, RS, RO, RR, SC, SP, SE, TO
```

## 📋 Regras de Validação

### Funcionário
- **name**: Obrigatório, string, máximo 255 caracteres
- **email**: Obrigatório, formato de email válido, máximo 255 caracteres, único
- **cpf**: Obrigatório, CPF válido, 11 dígitos, único
- **city**: Obrigatório, string, máximo 255 caracteres
- **state**: Obrigatório, deve ser um estado brasileiro válido (sigla ou nome completo)

### Login
- **email**: Obrigatório, formato de email válido
- **password**: Obrigatório, string, mínimo 6 caracteres

### Importação CSV
- **file**: Obrigatório, arquivo CSV ou TXT, máximo 2MB

## 🔢 Códigos de Status

- **200** - OK (Sucesso)
- **201** - Created (Criado com sucesso)
- **202** - Accepted (Aceito para processamento)
- **400** - Bad Request (Dados inválidos)
- **401** - Unauthorized (Não autorizado)
- **403** - Forbidden (Acesso negado)
- **404** - Not Found (Não encontrado)
- **422** - Unprocessable Entity (Erro de validação)
- **500** - Internal Server Error (Erro interno)

