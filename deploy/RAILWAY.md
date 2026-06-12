# Deploy en Railway (POC)

## Arquitectura

- **Servicio API**: build desde `Dockerfile` (o repo GitHub)
- **PostgreSQL**: plantilla managed de Railway
- **CI**: GitHub Actions valida tests; Railway despliega automáticamente al pushear a `main`

## Pasos (una sola vez)

### 1. Subir el repo a GitHub

```bash
git init
git add .
git commit -m "feat: Symfony Vayno API"
git remote add origin git@github.com:TU_USUARIO/vayno-backend.git
git push -u origin main
```

### 2. Crear proyecto en Railway

1. [railway.com](https://railway.com) → New Project
2. **Add PostgreSQL** (`+ New` → `Database` → `PostgreSQL`)
3. **Add GitHub Repo** → seleccionar `vayno-backend`
4. Railway detecta el `Dockerfile` y `railway.json`

### 3. Variables del servicio API

En el servicio API → Variables:

```env
DATABASE_URL=${{Postgres.DATABASE_URL}}
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=<genera-un-secreto-largo>
ACCESS_TOKEN_EXPIRE_MINUTES=15
REFRESH_TOKEN_EXPIRE_DAYS=7
RESERVATION_GRACE_MINUTES=15
CORS_ORIGINS=https://tu-crm.vercel.app
MESSENGER_TRANSPORT_DSN=sync://
```

Usa referencias internas `${{Postgres.DATABASE_URL}}`, no la URL pública.

### 4. Dominio público

Settings → Networking → Generate Domain.

Ejemplo: `https://vayno-api-production.up.railway.app`

### 5. Actualizar el CRM

En `vayno-crm`:

```env
NEXT_PUBLIC_API_URL=https://vayno-api-production.up.railway.app/api/v1
```

## Deploy automático

Cada `git push` a `main`:

1. Railway hace build del `Dockerfile`
2. Ejecuta migraciones (`railway.json` → `startCommand`)
3. Arranca el servidor PHP en `$PORT`

GitHub Actions corre tests en paralelo (no despliega).

## Créditos gratuitos

- Trial: ~$5 por 30 días (API + Postgres suele alcanzar)
- Plan Free: ~$1/mes (muy justo; considera Hobby $5/mes para POC continuo)

## Scheduler en Railway

Para expirar reservas, añade un segundo servicio (opcional) con:

```bash
php bin/console messenger:consume scheduler_default
```

O ejecuta manualmente `php bin/console app:expire-reservations` vía cron externo.
