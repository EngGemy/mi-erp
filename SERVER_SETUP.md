# One-time server setup (shared hosting)

Use this checklist **once** before the GitHub Actions pipeline can deploy successfully.

## Requirements

| Item | Value |
|------|--------|
| PHP | **8.3+** (8.4 recommended; matches `composer.json` `^8.3`) |
| Extensions | `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `intl` (if used) |
| Composer | Available on server `PATH` |
| Git | SSH access to GitHub from the server (deploy key or HTTPS with token) |
| MySQL | Database created; credentials in server `.env` |
| Node | **Not required on the server** — Filament assets are published via Artisan |

Stack: Laravel 12, Filament 4, Livewire 3, cache/session/queue via **database** driver.

---

## 1. SSH deploy key (GitHub Actions → server)

On your **local machine** (or a secure admin machine):

```bash
ssh-keygen -t ed25519 -C "github-actions-crown-bom-deploy" -f crown-bom-deploy -N ""
```

- Add **`crown-bom-deploy.pub`** to the hosting account’s `~/.ssh/authorized_keys` (read/write access to the app directory only if the host allows scoped keys).
- Put the **private** key contents (`crown-bom-deploy`, entire file including `BEGIN`/`END` lines) into the GitHub repository secret **`SSH_KEY`**.

Never commit the private key to the repository.

---

## 2. GitHub repository secrets

In **GitHub → Repository → Settings → Secrets and variables → Actions → New repository secret**:

| Secret | Example (placeholder) | Description |
|--------|------------------------|-------------|
| `SSH_HOST` | `ssh.example-host.com` | SSH hostname |
| `SSH_PORT` | `22` | SSH port (omit only if your host uses 22 and you set `22` in the secret) |
| `SSH_USER` | `u123456789` | SSH username |
| `SSH_KEY` | `-----BEGIN OPENSSH PRIVATE KEY-----…` | Private deploy key (full PEM) |
| `DEPLOY_PATH` | `/var/www/abc123-def456/crown-bom_app` | Absolute path to the **git clone root** on the server |

All deploy configuration must come from secrets — nothing is hardcoded in the workflow.

---

## 3. Initial clone on the server

SSH in manually (your own key, not necessarily the deploy key):

```bash
cd /var/www/<your-uuid>
git clone git@github.com:YOUR_ORG/crown-bom.git crown-bom_app
cd crown-bom_app
```

Configure `.env` (copy from `.env.example`):

- `APP_ENV=production`, `APP_DEBUG=false`
- `APP_URL=https://your-domain.example`
- `DB_*` MySQL credentials
- `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`

```bash
composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate
php artisan migrate --force
```

Create real storage directories (do **not** rely on `php artisan storage:link` — many shared hosts return **403** for symlinks):

```bash
mkdir -p storage/app/public
mkdir -p public/storage
chmod -R 775 storage bootstrap/cache
```

Optional first sync:

```bash
cp -ru storage/app/public/. public/storage/ 2>/dev/null || true
```

Run once manually:

```bash
bash deploy.sh
```

Ensure `deploy.sh` is executable:

```bash
chmod +x deploy.sh
```

### Git authentication on the server

`deploy.sh` runs `git fetch origin main`. The server must authenticate to GitHub:

- **Deploy key** (read-only) added to the repo, with `git remote` using SSH, or  
- **HTTPS** remote with a fine-grained token in `~/.netrc` (host-dependent).

---

## 4. Storage disk decision (optional permanent fix)

Today `config/filesystems.php` uses:

```php
'public' => [
    'root' => storage_path('app/public'),
    // ...
],
```

Uploads are written under `storage/app/public`; `deploy.sh` **copies** them to `public/storage` after each deploy because symlinks are blocked.

**Alternative (one-time config change, not applied automatically):** point the `public` disk `root` to `public_path('storage')` so Laravel writes directly where the web server serves files. That reduces reliance on the copy step but requires a deliberate change and regression testing. Until then, keep the sync step in `deploy.sh`.

---

## 5. First automated deploy

1. Add all GitHub secrets from section 2.  
2. Push to `main` **or** run **Actions → Deploy to Production → Run workflow**.  
3. Open the workflow run log — each `deploy.sh` step prints `>>> …` markers.

### Verification checklist

| Check | Expected |
|-------|----------|
| `/` | App responds (welcome or redirect) |
| `/admin/login` | Filament login **styled** (CSS/JS from `filament:assets`) |
| Upload test | Upload logo/settings image → URL under `/storage/...` returns **200** |
| `storage/logs/laravel.log` | No new 500s after deploy |

If login is unstyled: SSH in, `cd $DEPLOY_PATH`, run `php artisan filament:assets` and confirm `public/js/filament` / `public/css/filament` exist.

If images 403/404: confirm `public/storage` is a **directory** (not a broken symlink) and contains files after `deploy.sh`.

---

## 6. Prerequisites before the first push to `main`

The workflow triggers on every push to `main`. **Add GitHub secrets before pushing** the workflow file, or the first run will fail at SSH/deploy.

After secrets are set, pushes to `main` auto-deploy via `.github/workflows/deploy.yml`.
