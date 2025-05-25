### Tecnologias utilizadas:

    - PHP
    - Framework Laravel
    - Banco de dados MySQL

### Funcionalidades conforme requisitos:

    - Registro e Autenticação de usuários
    - Depositos
    - Transferências entre contas
    - Reversão de transações

### Algumas práticas de Segurança adotadas:

    - Autenticação por JWT
    - Token com tempo de expiração
    - Validação de Requisições com Form Requests
    - Prevenção de Repetição de Transações
    - Verificações de Regras de Negócio
    - Tratamento de Exceções com Rollback (Transações que caem em exceção tem seu saldo original restaurado)
    - Tratamento de exceções retornando apenas mensagens genéricas para não expor detalhes internos

### Pontos Bônus adicionados:

    - Testes de integração (Com Insomnia)
    - Testes de unidade
    - Documentação
    - Observabilidade: Registro de log de tudo o que entra nas requisições por rotas e transações(transactions_entry) e de todas as exceptions(transactions_errors)
    - Configuração do Docker
