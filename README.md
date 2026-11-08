# Sinterklaas

Sinterklaas is a Dutch gift-exchange application for managing wish lists, reservations, lots, and “foppers” (jokes or surprises). It provides an authenticated Filament panel where participants can maintain their own list and browse the lists of the people they drew.

## Features

- Create and manage gifts with a title, description, price, shop or location, and images.
- Mark gifts as available, reserved, or purchased.
- Control gift visibility: everyone, everyone except the recipient, or only the person who created it.
- View and manage gifts reserved for you or your partner.
- Record which participant each person drew and open that participant’s gift list.
- Add foppers, including images, for another participant.
- Provide a shared rules page.
- Import gift-exchange data from the administrator page using the legacy JSON export format.
- Run administrative checks for missing or duplicate lots, reset gift statuses, and remove purchased gifts.

The application is currently in Dutch and uses the Dutch locale for prices and number formatting.

## Technology

- PHP 8.5
- Laravel 13
- Filament 5 and Livewire 4
- MariaDB/MySQL, SQLite, Redis, and database-backed queues
- Vite, Tailwind CSS, and Embla Carousel
- Spatie Media Library for gift and fopper images
- Docker and Docker Compose for local development and release images

## Local development with Docker

### Requirements

- Docker with Docker Compose
- Git
- [Just](https://github.com/casey/just) is optional and is used for local image-building helpers

### Setup

1. Copy the example environment file:

   ```sh
   cp .env.example .env
   ```

2. Configure the database values in `.env` for the services in `docker-compose.yml`:

   ```dotenv
   APP_NAME=Sinterklaas
   APP_URL=http://localhost:8000
   APP_PORT=8000

   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=sinterklaas
   DB_USERNAME=sinterklaas
   DB_PASSWORD=change-me
   ```

   `APP_PORT=8000` avoids the port conflict with the bundled phpMyAdmin service, which uses port `8080`.

3. Build and start the application and its supporting services:

   ```sh
   docker compose up -d --build
   ```

4. Generate the application key and run migrations:

   ```sh
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate
   ```

5. Open the application at [http://localhost:8000](http://localhost:8000).

The default Compose stack also includes:

- phpMyAdmin at [http://localhost:8080](http://localhost:8080)
- Mailpit at [http://localhost:8025](http://localhost:8025)
- Meilisearch at `http://localhost:7700`
- Redis at `localhost:6379`

### Seed data

The `DatabaseSeeder` contains the participant data used by the current Sinterklaas group. On a new local database, seed it with:

```sh
docker compose exec app php artisan db:seed
```

Review and update `database/seeders/DatabaseSeeder.php` before using this against a new group or any shared environment. Do not run the seeder in production unless its participant data is intentionally configured for that environment.

### Frontend assets

Install JavaScript dependencies and build the Vite assets when working outside the release-image build:

```sh
npm ci
npm run build
```

For an interactive Vite development server, use:

```sh
npm run dev
```

## Useful commands

Run the test suite inside the application container:

```sh
docker compose exec app php artisan test
```

Common Laravel commands can be run the same way, for example:

```sh
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan cache:clear
docker compose logs -f app
```

The development and production images start a Laravel Horizon worker for queued jobs. If you run the application directly on the host, start a worker separately when using an asynchronous queue:

```sh
php artisan horizon
```

## Application workflow

After signing in to the Filament panel:

1. Set or verify your lot on **Mijn lootje**.
2. Open the drawn participant’s list under **Lijstjes**.
3. Add gifts to your own list, and reserve or mark gifts as purchased on other lists.
4. Add foppers for the drawn participant under **Foppers**.
5. Use **Mijn reserveringen** to review gifts reserved for you or your partner.
6. Read the group rules under **Regels**.

The administrator page is only registered for the users with IDs `1` and `7` in the current implementation. It provides the lot checks, gift reset/removal actions, and legacy JSON import.

## Deployment

Deployment is image-based and is driven by GitHub Actions.

### Build and publish

Push changes to the `master` branch, or push a `v*` tag. The [release-image workflow](.github/workflows/build-release-image.yml) then:

1. Builds `Dockerfile.release` for `linux/amd64`.
2. Builds the frontend assets and installs production Composer dependencies.
3. Publishes the image to GitHub Container Registry.

The default branch publishes the `latest` tag. Commits also receive a SHA tag, while version tags receive version-based tags. The current production image is:

```text
ghcr.io/pascalmasson/sinterklaas:latest
```

### Update the remote server

After GitHub Actions finishes successfully, connect to the remote server and manually pull and restart the application service using the remote Compose file:

```sh
docker compose -f docker-compose.remote.yaml pull app
docker compose -f docker-compose.remote.yaml up -d app
```

Run database migrations explicitly when a release contains migration changes:

```sh
docker compose -f docker-compose.remote.yaml exec app php artisan migrate --force
```

The remote Compose configuration keeps uploaded media in a host-mounted `storage` directory and connects the application to the existing MySQL socket. Mount the server-only `.env` file at `/run/secrets/sinterklaas.env`; the release image copies it into `/var/www/html/.env` during startup, sets it readable by the PHP worker, and refuses to start if `APP_KEY` is missing. Keep production credentials outside the repository and provide them through the server’s deployment configuration.

The relevant remote Compose volume is:

```yaml
volumes:
  - '/path/on/server/.env:/run/secrets/sinterklaas.env:ro'
```

### Local image helpers

The `justfile` and `bin/docker-build.sh` are useful for manually building or inspecting images, but they are not part of the normal deployment path:

```sh
just build
just image-reference
just inspect
```

## Project layout

```text
app/                 Application models, Filament pages, resources, and enums
database/            Migrations, factories, and seeders
docker/              Nginx, PHP, and Horizon container configuration
resources/           Blade views, JavaScript, and CSS source files
public/              Public entry point and compiled/static assets
tests/               PHPUnit unit and feature tests
Dockerfile           Development-oriented application image
Dockerfile.release   Multi-stage production image
docker-compose.yml   Local development stack
docker-compose.remote.yaml
                     Remote production service definition
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
