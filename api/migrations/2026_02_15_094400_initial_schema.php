<?php
/**
 * ============================================
 * MIGRATION EXEMPLO / TEMPLATE
 * ============================================
 * 
 * Para criar uma nova migration:
 * 1. Copie este arquivo
 * 2. Renomeie com o formato: YYYY_MM_DD_HHMMSS_descricao.php
 *    Exemplo: 2026_02_15_093000_add_user_preferences.php
 * 3. Preencha o SQL no 'up' (e opcionalmente no 'down')
 * 4. Faça push para o GitHub — será executada automaticamente!
 * 
 * FORMATOS ACEITOS:
 * - String única com SQL
 * - Array de strings SQL (para múltiplos statements)
 */

return [
    // SQL para aplicar a migration
    'up' => "
        -- Garante estrutura base das tabelas existentes
        CREATE TABLE IF NOT EXISTS resellers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            created_at DATETIME DEFAULT (datetime('now')),
            updated_at DATETIME DEFAULT (datetime('now'))
        );
    ",

    // SQL para reverter (rollback) - usado apenas manualmente
    'down' => "
        -- Não remove tabela resellers por segurança
        SELECT 1;
    ",

    // Descrição da migration (para log)
    'description' => 'Garante estrutura base da tabela resellers com timestamps'
];
