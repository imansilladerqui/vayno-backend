# Vayno Backend (Symfony)

API REST para la plataforma de reservas de estacionamiento **Vayno**. Compatible con `vayno-crm` y la app móvil vía `/api/v1`.

## Stack

- PHP 8.3 + Symfony 7.2
- PostgreSQL 16
- JWT (access + refresh)
- Doctrine ORM
- Docker Compose (desarrollo)
- Railway (POC / deploy automático desde GitHub)

## Inicio rápido (Docker)

```bash
cp .env.example .env
docker compose up --build
```

API disponible en: `http://localhost:8000/api/v1`

## Desarrollo local (sin Docker)

Requisitos: PHP 8.3+, Composer, PostgreSQL 16.

```bash
cp .env.example .env
composer install
php bin/console doctrine:migrations:migrate
php -S 0.0.0.0:8000 -t public
```

## Variables de entorno

| Variable | Descripción |
|----------|-------------|
| `DATABASE_URL` | Conexión PostgreSQL |
| `APP_SECRET` | Secreto JWT (HS256) y Symfony |
| `ACCESS_TOKEN_EXPIRE_MINUTES` | TTL access token (default 15) |
| `REFRESH_TOKEN_EXPIRE_DAYS` | TTL refresh token (default 7) |
| `RESERVATION_GRACE_MINUTES` | Grace period check-in / expiración |
| `CORS_ORIGINS` | Orígenes CRM separados por coma |

## API

Base: `/api/v1`

| Módulo | Endpoints |
|--------|-----------|
| Auth | `POST /auth/register`, `/register-owner`, `/login`, `/refresh` |
| Users | `GET/PATCH /users/me` |
| Lots | CRUD `/lots` |
| Slots | CRUD `/lots/{id}/slots`, `/slots/{id}` |
| Availability | `/slots/{id}/availability`, `DELETE /availability/{id}` |
| Reservations | `GET /slots/available`, CRUD `/reservations` |
| Check-in/out | `POST /reservations/{id}/checkin`, `/checkout` |
| Owner | `GET /owner/reservations` |

Errores en formato FastAPI: `{"detail": "mensaje"}`.

## Scheduler

Expira reservas `PENDING`/`CONFIRMED` cada minuto:

```bash
php bin/console messenger:consume scheduler_default
```

En Docker Compose el servicio `scheduler` lo ejecuta automáticamente.

## Tests

```bash
vendor/bin/phpunit
```

## Deploy en Railway

Ver [deploy/RAILWAY.md](deploy/RAILWAY.md).

## Referencia

La especificación funcional original está en `vayno-backend-old` (Python/FastAPI).
