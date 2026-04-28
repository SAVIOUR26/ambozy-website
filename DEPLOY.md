# GitHub Secrets Setup Guide
## For ambozygraphics.shop deployment

Go to your GitHub repo → **Settings → Secrets and variables → Actions → New repository secret**

Add these secrets:

| Secret Name      | Value | Where to find |
|-----------------|-------|---------------|
| `FTP_HOST`       | e.g. `ftp.ambozygraphics.shop` | Your hosting control panel |
| `FTP_USER`       | FTP username | Hosting control panel |
| `FTP_PASS`       | FTP password | Hosting control panel |
| `FTP_SERVER_DIR` | e.g. `/public_html/` | Root web folder on host |
| `DB_HOST`        | `localhost` | Usually localhost on shared hosting |
| `DB_NAME`        | `ambozy_db` | As created in phpMyAdmin |
| `DB_USER`        | Your DB username | phpMyAdmin |
| `DB_PASS`        | Your DB password | phpMyAdmin |
| `ADMIN_USER`     | `admin` | Your choice |
| `ADMIN_HASH`     | Run: `php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT);"` | Terminal |

## First Deploy Steps

1. Push repo to GitHub
2. Add all secrets above
3. In phpMyAdmin, create database `ambozy_db` and run `schema.sql`
4. Push any commit to `main`/`master` to trigger deploy
5. Visit `https://ambozygraphics.shop`
6. Visit `https://ambozygraphics.shop/admin` → login with your admin credentials

## Generating ADMIN_HASH locally
```bash
php -r "echo password_hash('YourChosenPassword', PASSWORD_DEFAULT);"
```
Copy the output (starts with `$2y$`) and paste as the `ADMIN_HASH` secret.
