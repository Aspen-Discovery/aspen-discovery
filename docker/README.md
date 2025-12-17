# Aspen Discovery - Docker

## Quick Start

```bash
git clone https://github.com/Aspen-Discovery/aspen-discovery.git
cd aspen-discovery/docker
cp files/env/default.env .env
docker compose up -d
```

Wait for initialization to complete:
```bash
docker compose logs -f backend
# Look for: "Starting PHP-FPM in foreground mode..."
```

Access Aspen at http://localhost:85 (default credentials: `aspen_admin` / `secretPass123`)

## Environment Variables

### Site Settings

| Variable | Default | Description |
|----------|---------|-------------|
| `SITE_NAME` | `aspen` | Site identifier |
| `URL` | `http://localhost:85` | Public URL |
| `TITLE` | `Aspen Discovery` | Library title |
| `LIBRARY` | `Test Library` | Library name |
| `TIMEZONE` | `America/Argentina/Cordoba` | PHP timezone |
| `ASPEN_ADMIN_PASSWORD` | `secretPass123` | Admin password |
| `SUPPORTING_COMPANY` | `ByWater Solutions` | Support company name |

### Database

| Variable | Default | Description |
|----------|---------|-------------|
| `DATABASE_HOST` | `db` | MariaDB hostname |
| `DATABASE_PORT` | `3306` | MariaDB port |
| `DATABASE_NAME` | `aspen` | Database name |
| `DATABASE_USER` | `aspenusr` | Database user |
| `DATABASE_PASSWORD` | `aspenpasswd` | Database password |
| `DATABASE_ROOT_PASSWORD` | `password` | MariaDB root password |

### Services

| Variable | Default | Description |
|----------|---------|-------------|
| `SOLR_HOST` | `solr` | Solr hostname |
| `SOLR_PORT` | `8983` | Solr port |
| `PHP_FPM_HOST` | `backend` | PHP-FPM hostname |
| `PHP_FPM_PORT` | `9000` | PHP-FPM port |

### Docker Images

| Variable | Default | Description |
|----------|---------|-------------|
| `BACKEND_IMAGE_TAG` | `aspendiscovery/aspen:latest` | Backend image |
| `SOLR_IMAGE_TAG` | `aspendiscovery/solr:latest` | Solr image |
| `MARIADB_IMAGE` | `mariadb:10.5` | MariaDB image |

## Updating Configuration

Environment variables are synced to configuration files on every container start. To apply changes:

1. Edit your `.env` file
2. Recreate containers:
   ```bash
   docker compose up -d --force-recreate
   ```

**Supported variables for runtime updates:**
- `DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWORD`
- `TIMEZONE`, `URL`, `TITLE`, `LIBRARY`
- `SOLR_HOST`, `SOLR_PORT`

**Note:** Changing database credentials requires the MariaDB user to already exist. MariaDB only creates users on first initialization.

## Services & Ports

| Service | Port | Description |
|---------|------|-------------|
| apache | 85 | Web interface |
| solr | 8983 | Search engine admin |
| backend | - | PHP-FPM (internal) |
| cron | - | Background tasks (internal) |
| db | 3306 | MariaDB (internal) |

## Common Commands

```bash
# View logs
docker compose logs -f backend

# Shell access
docker compose exec backend bash

# Stop services
docker compose down

# Apply configuration changes
docker compose up -d --force-recreate

# Full reset (delete all data)
docker compose down -v
rm -rf data/
```
