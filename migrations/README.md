# Migrações Automáticas

Adicione seus arquivos SQL nesta pasta para serem executados automaticamente no deploy.

## Como funciona:
1. Crie um arquivo `.sql` numerado (ex: `002_adicionar_tabela_usuarios.sql`).
2. Adicione os comandos SQL.
3. No próximo deploy, o sistema executará este arquivo se ele ainda não tiver sido rodado.

## Exemplo de Arquivo SQL:
```sql
CREATE TABLE IF NOT EXISTS tabela_teste (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL
);
INSERT INTO tabela_teste (nome) VALUES ('Teste 1');
```

## Importante:
- O sistema usa SQLite (`api/.db.db`).
- Se houver erro, o deploy para.
- O arquivo `.db.db` NÃO é sobrescrito no deploy (está na lista de exclusão do arquivo `.github/workflows/deploy.yml`).
