# Docker Setup for Prescia

This guide explains how to run Prescia using Docker.

## Prerequisites

- Docker installed on your system
- Docker Compose (usually comes with Docker Desktop)

## Quick Start

### Using Docker Compose (Recommended)

1. **Start the application:**
   ```bash
   docker-compose up -d
   ```

2. **Access the application:**
   - Open your browser and navigate to: http://localhost:8080

3. **Stop the application:**
   ```bash
   docker-compose down
   ```

### Using Docker only

1. **Build the image:**
   ```bash
   docker build -t prescia .
   ```

2. **Run the container:**
   ```bash
   docker run -d -p 8080:80 --name prescia-app prescia
   ```

3. **Stop the container:**
   ```bash
   docker stop prescia-app
   docker rm prescia-app
   ```

## Configuration

### Database Configuration

When using `docker-compose`, a MySQL 8.0 database is automatically provisioned with the following credentials:

- **Host:** `db` (container name)
- **Database:** `prescia`
- **User:** `prescia_user`
- **Password:** `prescia_pass`
- **Root Password:** `prescia_root`

To configure Prescia to use this database, edit `config/settings.php`:

```php
define("CONS_OVERRIDE_DB", "prescia");
define("CONS_OVERRIDE_DBUSER", "prescia_user");
define("CONS_OVERRIDE_DBPASS", "prescia_pass");
```

And ensure your database connection uses the hostname `db` instead of `localhost`.

### Master Configuration

Before running, you should configure:

1. **Master Password** - Edit `config/settings.php`:
   ```php
   define("CONS_MASTERPASS", "your-secure-password");
   ```

2. **Master Email** - Edit `config/settings.php`:
   ```php
   define("CONS_MASTERMAIL", "your-email@example.com");
   ```

## Volumes

The docker-compose setup includes:

- **Application files:** Mounted from current directory to `/var/www/html`
- **Config files:** Mounted from `./config` to `/var/www/html/config`
- **Database data:** Persistent volume `db_data` for MySQL data

## PHP Configuration

The Docker image includes:

- PHP 8.3 with Apache
- Short open tags are not required; application PHP files use `<?php` or `<?=`
- Timezone set to America/Sao_Paulo
- MySQLi and PDO extensions
- Apache mod_rewrite enabled

## Troubleshooting

### Permission Issues

If you encounter permission issues with the `_temp` directory:

```bash
docker-compose exec web chown -R www-data:www-data /var/www/html/_temp
docker-compose exec web chmod -R 775 /var/www/html/_temp
```

### View Logs

```bash
# Application logs
docker-compose logs web

# Database logs
docker-compose logs db

# Follow logs in real-time
docker-compose logs -f
```

### Access Container Shell

```bash
docker-compose exec web bash
```

## Environment Customization

To customize the setup, you can:

1. Modify the `Dockerfile` to add additional PHP extensions
2. Edit `docker-compose.yml` to change ports or environment variables
3. Create a custom PHP configuration file in the Dockerfile

## Production Considerations

For production use:

1. Change all default passwords in `docker-compose.yml`
2. Use environment variables instead of hardcoded credentials
3. Set up proper SSL/TLS certificates
4. Configure appropriate PHP memory limits and timeouts
5. Set `CONS_DEVELOPER` to `false` in `config/settings.php`
6. Enable caching and performance optimizations
