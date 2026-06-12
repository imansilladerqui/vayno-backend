# Vayno Backend

Symfony REST API for the Vayno parking platform (`/api/v1`).

## Run

```bash
cp .env.example .env.local
docker compose up --build
```

API: `http://localhost:8000/api/v1`

Docs: `http://localhost:8000/api/doc` (set `ENABLE_API_DOCS=true`; disabled in prod by default)

## Tests

```bash
vendor/bin/phpunit
```

Deploy: [deploy/RAILWAY.md](deploy/RAILWAY.md)
