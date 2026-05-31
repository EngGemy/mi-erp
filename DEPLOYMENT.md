# Deployment runbook

## How deploys work

1. Developer pushes to **`main`** (or triggers **workflow_dispatch** manually).
2. GitHub Actions (`.github/workflows/deploy.yml`):
   - Checks out the repo
   - Validates `deploy.sh` syntax
   - SSHs to the server and runs `bash deploy.sh` in `DEPLOY_PATH`
3. On the server, `deploy.sh`:
   - `git fetch` + `git reset --hard origin/main`
   - `composer install --no-dev`
   - `php artisan migrate --force`
   - `php artisan optimize:clear` (**before** rebuild — prevents stale route/view 500s)
   - `php artisan filament:assets` (admin UI styling)
   - Sync `storage/app/public` → `public/storage` (real dir; **never deletes** extra files in `public/storage`)
   - Fix permissions on `storage`, `bootstrap/cache`, `public/storage`
   - Rebuild: `config:cache`, `route:cache`, `view:cache`, `filament:cache-components`

**Asset strategy:** no `npm` on the server. Filament uses prebuilt assets from `filament:assets`. Custom Crown CSS is in `public/css/` and deploys via git. CI does **not** run `npm run build` (Vite is only used for `welcome.blade.php`).

**Concurrency:** one deploy group per repository; overlapping pushes queue (in-progress deploy is not cancelled).

---

## Operator checklist (each release)

- [ ] Secrets still valid (`SSH_*`, `DEPLOY_PATH`)
- [ ] Workflow run green in GitHub Actions
- [ ] `/admin/login` styled
- [ ] Sample `/storage/...` image returns 200
- [ ] Critical flows smoke-tested (login, one Filament resource)
- [ ] If migration failed in log, fix DB and re-run deploy — do not leave app half-migrated

---

## Rollback

SSH to the server:

```bash
cd "$DEPLOY_PATH"   # same path as GitHub secret DEPLOY_PATH
git fetch origin main
git log --oneline -5   # pick previous good SHA
git reset --hard <PREVIOUS_SHA>
bash deploy.sh
```

`deploy.sh` is idempotent: safe to re-run after rollback. Database migrations are **not** auto-reversed — if the bad deploy ran new migrations, restore DB from backup or run manual down migrations before re-deploying an older commit.

---

## Troubleshooting

| Symptom | Likely cause | Fix |
|---------|----------------|-----|
| 500 after deploy | Stale config/route/view cache | `php artisan optimize:clear` then rebuild (already in `deploy.sh`; re-run script) |
| Unstyled `/admin/login` | Missing Filament assets | `php artisan filament:assets` |
| Images 403/404 | Symlink blocked or empty `public/storage` | Ensure `public/storage` is a directory; re-run deploy sync step |
| `git fetch` fails on server | No GitHub auth on server | Configure deploy key / token for server git remote |
| Permission errors | `storage` not writable | `chmod -R 775 storage bootstrap/cache` (in `deploy.sh`) |

---

## Related docs

- **First-time setup:** [SERVER_SETUP.md](SERVER_SETUP.md)
- **Architecture:** [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
