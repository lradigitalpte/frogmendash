# Local development with Docker

Replaces XAMPP for local work. Runs the app exactly like Railway (same Dockerfile,
nginx + php-fpm) plus a stable MySQL 8, so you can test migrations locally before deploying.

| Service | URL / address |
|---|---|
| App | http://localhost:8000 |
| Adminer (DB browser) | http://localhost:8081 |
| MySQL (from Workbench/host) | host `127.0.0.1`, port `3307`, user `root`, password `secret` |

> MySQL is on host port **3307** (not 3306) so it won't clash with XAMPP if it's still installed.

---

## First-time setup

```bash
# 1. Start the database only
docker compose up -d db

# 2. (Recommended) Load your real data so migrations are tested against it.
#    Imports the prod backup into the local 'frogmendb'.
docker compose exec -T db mysql -u root -psecret frogmendb < "C:/Users/Admin/Desktop/frogmen-full-backup-2026-06-25.sql"

# 3. Build + start the app. This also runs `php artisan migrate` automatically
#    (via the same start.sh as prod), applying the tenancy migrations.
docker compose up app --build
```

Open http://localhost:8000 and log in with your normal credentials (they came in with the imported data).

> If you skip step 2, the app sees a fresh DB on first boot and runs the ERP installer
> to create a clean instance instead.

---

## Testing the tenancy work locally

After `docker compose up app` (which runs the migrations):

1. **Bank Accounts** — you should see only your company's accounts; create one and confirm it saves.
2. **Reports** — same; and open a client share-link to confirm public reports still work.
3. **Phase 0 scope** — log in and confirm you still see all your data (you're super-admin → pass-through).

Roll a migration back if needed:
```bash
docker compose exec app php artisan migrate:rollback --step=2
```

---

## Everyday commands

```bash
docker compose up -d                 # start everything in the background
docker compose logs -f app           # watch app logs
docker compose exec app php artisan <cmd>    # run artisan (tinker, migrate, etc.)
docker compose exec app php artisan optimize:clear   # clear cached config after .env edits
docker compose exec app npm run build        # rebuild CSS/JS assets after frontend edits
docker compose down                  # stop (data is kept in the db_data volume)
docker compose down -v               # stop AND wipe the database volume (fresh start)
```

PHP code edits are picked up live (the project is mounted into the container).
Composer or npm dependency changes need a rebuild: `docker compose up app --build`.

---

## Backing up prod from here (Hobby plan has no Railway backups)

The MySQL 8 client in Docker can dump prod directly (unlike XAMPP's MariaDB client):

```bash
docker run --rm mysql:8 mysqldump -h caboose.proxy.rlwy.net --port 49972 --protocol=TCP \
  -u root -p'<prod-password>' --single-transaction --no-tablespaces --skip-lock-tables \
  railway > "C:/Users/Admin/Desktop/frogmen-backup-$(date +%Y-%m-%d).sql"
```

(The planned in-app "Backup to S3" button will automate this server-side.)

---

## Notes
- First `--build` is slow (installs composer + npm deps inside the image); later starts are fast.
- The `.dockerignore` excludes the separate `frontend/` and `backend/` apps and rebuilt
  folders, keeping the image small.
- `db_data` is a named volume — your local DB survives `docker compose down` (use `-v` to wipe).
