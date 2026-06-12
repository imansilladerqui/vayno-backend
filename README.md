# Vayno Backend

Symfony REST API for the Vayno parking platform (`/api/v1`).

## Run

```bash
cp .env.example .env.local
docker compose up --build
```

API: `http://localhost:8000/api/v1`

Docs (dev only): `http://localhost:8000/api/doc`

## Tests

```bash
vendor/bin/phpunit
```

Deploy: [deploy/RAILWAY.md](deploy/RAILWAY.md)
