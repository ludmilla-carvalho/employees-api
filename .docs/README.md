# Documentação da API - Guia de Uso

Este diretório contém toda a documentação da API de Funcionários. Aqui você encontrará diferentes formatos e ferramentas para auxiliar no desenvolvimento e teste da API.

## 📁 Arquivos Disponíveis

### 📖 Documentação Principal
- **`../README.md`** - Documentação completa da API com exemplos detalhados

### 🔧 Especificação OpenAPI
- **`api-documentation.yaml`** - Especificação OpenAPI 3.0 da API
  - Pode ser usado com Swagger UI, ReDoc ou outras ferramentas
  - Contém todos os endpoints, schemas e exemplos

### 🚀 Postman Collection
- **`Employees-API.postman_collection.json`** - Coleção do Postman com todos os endpoints
- **`Employees-API.postman_environment.json`** - Variáveis de ambiente para o Postman

### 📊 Arquivo de Exemplo
- **`exemplo-importacao-funcionarios.csv`** - Exemplo de arquivo CSV para teste de importação

## 🛠 Como Usar

### Postman
1. Importe a collection: `Employees-API.postman_collection.json`
2. Importe o environment: `Employees-API.postman_environment.json`
3. Configure as variáveis de ambiente conforme necessário
4. Execute o endpoint de login primeiro para obter o token
5. O token será automaticamente configurado para os próximos requests

### Swagger UI
1. Para visualizar a documentação interativa usando Swagger UI:
2. Usar o editor online: https://editor.swagger.io/ 
3. Cole o conteúdo do arquivo api-documentation.yaml 

### Insomnia
1. Importe a collection do Postman (Insomnia suporta formato Postman)
2. Configure as variáveis de ambiente
3. Execute os requests

## 🔑 Configuração de Autenticação

### Variáveis de Ambiente Importantes
- **`base_url`**: URL base da API (padrão: `http://localhost/api`)
- **`jwt_token`**: Token JWT (preenchido automaticamente após login)
- **`user_email`**: Email do usuário para login
- **`user_password`**: Senha do usuário para login

### Fluxo de Autenticação
1. Execute o request de **Login** primeiro
2. O token JWT será extraído automaticamente da resposta
3. Todos os outros requests usarão este token automaticamente
4. Se o token expirar, execute **Renovar Token** ou **Login** novamente

## 📝 Exemplos de Teste

### 1. Fluxo Completo de Teste
```
1. Login → Obter token
2. Listar Funcionários → Ver lista atual
3. Criar Funcionário → Adicionar novo
4. Visualizar Funcionário → Ver detalhes
5. Atualizar Funcionário → Modificar dados
6. Excluir Funcionário → Remover
```

### 2. Teste de Importação CSV
```
1. Login → Obter token
2. Usar arquivo exemplo-importacao-funcionarios.csv
3. Executar Import → Funcionários serão processados em background
4. Listar Funcionários → Verificar se foram importados
```

## 🐛 Troubleshooting

### Token Expirado
- **Problema**: Responses com status 401
- **Solução**: Execute Login ou Refresh Token

### Arquivo CSV Inválido
- **Problema**: Erro 422 na importação
- **Solução**: Verifique o formato do CSV usando o arquivo de exemplo

### Dados Inválidos
- **Problema**: Erro 422 na criação/atualização
- **Solução**: Verifique:
  - CPF deve ser válido e único
  - Email deve ser único
  - Estado deve ser sigla brasileira válida ou nome do estado válido

### CORS Issues
- **Problema**: Erro de CORS no browser
- **Solução**: Configure CORS no backend ou use Postman/Insomnia

## 📊 Códigos de Status Comuns
- **200**: Sucesso
- **201**: Criado com sucesso
- **202**: Aceito para processamento (importação)
- **401**: Token inválido/expirado
- **403**: Sem permissão para acessar recurso
- **404**: Recurso não encontrado
- **422**: Dados de validação inválidos

## 🔄 Atualizações
Esta documentação é atualizada conforme mudanças na API. Sempre verifique a versão mais recente no repositório.