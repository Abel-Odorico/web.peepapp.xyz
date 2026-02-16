# 🚀 WEB PLAYER - Auto Deploy

## Como Funciona o Deploy Automático

### Fluxo
```
git push → GitHub Actions → FTP Sync → Auto Migrations → ✅ Online!
```

### Arquitetura
1. **Push na branch `main`** → dispara o workflow automaticamente
2. **GitHub Actions** sincroniza os arquivos via FTP usando a action `SamKirkland/FTP-Deploy-Action`
3. **Auto Migrations** são executadas via HTTP POST no endpoint `api/migrate.php`
4. **Resultado** é exibido no resumo do GitHub Actions

---

## 📋 Configuração Inicial (Já Feito)

### GitHub Secrets Necessários
| Secret | Descrição | Status |
|--------|-----------|--------|
| `FTP_SERVER` | Servidor FTP | ✅ Configurado |
| `FTP_USERNAME` | Usuário FTP | ✅ Configurado |
| `FTP_PASSWORD` | Senha FTP | ⚠️ Precisa configurar |
| `MIGRATION_SECRET` | Chave para auto-migrations | ✅ Configurado |

### Como Configurar a Senha do FTP
1. Acesse: https://github.com/Abel-Odorico/web.peepapp.xyz/settings/secrets/actions
2. Clique em **"New repository secret"**
3. Nome: `FTP_PASSWORD`
4. Valor: sua senha do FTP
5. Clique em **"Add secret"**

---

## 🗃️ Sistema de Migrations

### Como Criar uma Nova Migration

1. Crie um arquivo PHP em `api/migrations/` com o formato:
   ```
   YYYY_MM_DD_HHMMSS_descricao.php
   ```
   Exemplo: `2026_02_15_120000_add_favorites_table.php`

2. O arquivo deve retornar um array:
   ```php
   <?php
   return [
       'up' => "
           CREATE TABLE IF NOT EXISTS favorites (
               id INTEGER PRIMARY KEY AUTOINCREMENT,
               user_id INTEGER,
               channel_id TEXT,
               created_at DATETIME DEFAULT (datetime('now'))
           );
       ",
       'down' => "
           DROP TABLE IF EXISTS favorites;
       ",
       'description' => 'Cria tabela de favoritos'
   ];
   ```

3. Faça `git push` — a migration será executada automaticamente!

### Regras das Migrations
- ✅ Migrations são executadas em **ordem cronológica** (pelo nome do arquivo)
- ✅ Cada migration é executada **apenas uma vez** (rastreada na tabela `_migrations`)
- ✅ Executadas dentro de **transações** (rollback automático em caso de erro)
- ✅ **Log de auditoria** em `api/migrations/migration.log`
- ❌ **Nunca renomeie** um arquivo de migration após o push

---

## 🔒 Segurança

- Credenciais armazenadas em **GitHub Secrets** (encriptadas)
- Endpoint de migration protegido por **chave secreta**
- Arquivos sensíveis protegidos no `.htaccess`
- Banco de dados SQLite **não é versionado** no Git
- Chave de migration **não é versionada** no Git

---

## 📁 Estrutura do Projeto
```
web.peepapp.xyz/
├── .github/workflows/
│   └── deploy.yml          ← Workflow de deploy automático
├── api/
│   ├── migrate.php          ← Sistema de auto-migration
│   ├── migrations/          ← Arquivos de migration
│   │   └── 2026_02_15_*.php ← Migration inicial
│   ├── .migration_key       ← Chave secreta (NÃO versionada)
│   ├── .db.db              ← Banco SQLite (NÃO versionado)
│   ├── db.php              ← Lógica de login
│   ├── dns.php             ← Painel DNS
│   └── proxy.php           ← Proxy de conteúdo
├── includes/               ← Componentes PHP
├── css/                    ← Estilos
├── js/                     ← Scripts
├── images/ & img/          ← Assets
├── .gitignore              ← Proteição de arquivos sensíveis
└── htaccess                ← Configurações Apache
```

---

## 🛠️ Comandos Úteis

```bash
# Deploy manual (push)
git add -A && git commit -m "descrição" && git push

# Verificar status do último deploy
# Acesse: https://github.com/Abel-Odorico/web.peepapp.xyz/actions

# Executar migration manualmente
curl -X POST -H "X-Migration-Key: SUA_CHAVE" https://web.peepapp.xyz/api/migrate.php
```
