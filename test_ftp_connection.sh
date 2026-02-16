#!/bin/bash
# Script de Teste de Conexão FTP Local
# Execute este script no seu terminal para verificar se as credenciais estão corretas.
# Uso: bash test_ftp_connection.sh

HOST="103.204.193.146"
USER="webstreaming@web.peepapp.xyz"
PASS="PeepTV10203040*"

echo "🔍 Testando conexão com $HOST..."
echo "👤 Usuário: $USER"

# Tenta conectar usando curl (que geralmente já vem instalado)
curl -v -u "$USER:$PASS" "ftp://$HOST/" 

if [ $? -eq 0 ]; then
    echo "✅ CONEXÃO BEM SUCEDIDA! (Localmente funciona)"
    echo "Isso significa que o Github Actions provavelmente teve o IP bloqueado pelo firewall do servidor."
else
    echo "❌ FALHA NA CONEXÃO LOCAL"
    echo "Se falhou aqui também, a senha ou usuário estão INCORRETOS."
fi
