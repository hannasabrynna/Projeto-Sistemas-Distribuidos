#!/bin/sh

# Saia imediatamente se um comando falhar
set -e

# Pega o host e a porta do banco de dados das variáveis de ambiente
host="$DB_HOST"
port="$DB_PORT"
cmd="$@"

# Loop até que a porta do banco de dados esteja acessível
until nc -z -v "$host" "$port"; do
  >&2 echo "MySQL is unavailable - sleeping"
  sleep 1
done

>&2 echo "MySQL is up - executing command"

# Após o banco de dados estar pronto, execute as otimizações do Laravel
# Estas são ideais para um ambiente de produção
php artisan config:cache
php artisan route:cache
php artisan view:cache
# Opcional: rode as migrations automaticamente ao iniciar o container
php artisan migrate --force

# Execute o comando original do container (CMD)
exec $cmd