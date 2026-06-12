# Deploy en Railway (POC)

## Arquitectura

```
GitHub (push main) → Railway build (Dockerfile) → API + Postgres
CRM (Vercel/local) → HTTPS → Railway API (/api/v1)
```

- **API**: build desde `Dockerfile`, `railway.json` define el start command
- **PostgreSQL**: servicio managed de Railway
- **Secrets**: solo en Railway Variables (nunca en el repo)

---

## Variables por entorno

| Dónde | Archivo / sitio | Qué guardar |
|-------|-----------------|-------------|
| **Local** | `.env.local` (gitignored) | Todo: secretos, DB, CORS local |
| **Repo** | `.env.example` | Plantilla sin secretos reales |
| **Railway** | Service → Variables | Secretos y config de producción |

No uses `.env` ni `.env.dist` en el repo. Symfony necesita un `.env` mínimo para arrancar; Docker lo crea solo (`APP_ENV=prod` en la imagen, `APP_ENV=dev` en compose).

### Local

```bash
cp .env.example .env.local
# Edita .env.local con tu APP_SECRET, CORS_ORIGINS, etc.
docker compose up --build
```

### Railway (producción)

En el servicio **API** → **Variables**:

```env
DATABASE_URL=${{Postgres.DATABASE_URL}}
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=<openssl rand -base64 32>
ACCESS_TOKEN_EXPIRE_MINUTES=15
REFRESH_TOKEN_EXPIRE_DAYS=7
RESERVATION_GRACE_MINUTES=15
CORS_ORIGINS=https://tu-crm.vercel.app
ALLOW_OWNER_REGISTRATION=false
ENABLE_API_DOCS=true
```

`ALLOW_OWNER_REGISTRATION=false` en producción (default). Pon `true` solo si necesitas registrar owners desde la API en el POC.

`ENABLE_API_DOCS=true` expone Swagger en `/api/doc`. Déjalo en `false` cuando la API sea pública de verdad.

`${{Postgres.DATABASE_URL}}` es una referencia interna al servicio Postgres del mismo proyecto.

**Importante:** genera un `APP_SECRET` distinto al de local. Nunca reutilices el mismo secreto entre entornos.

---

## Troubleshooting: `could not find driver`

Ese error casi siempre significa que `DATABASE_URL` **no apunta a PostgreSQL**.

| Síntoma | Causa |
|---------|--------|
| `ExceptionConverter.php` (MySQL) | `DATABASE_URL` usa `mysql://` |
| `could not find driver` | La imagen solo tiene `pdo_pgsql`, no `pdo_mysql` |
| `Invalid platform version ""` | `DATABASE_URL` sin versión; la app usa `server_version: 16` en Doctrine |

**Fix en Railway (servicio API → Variables):**

1. Borra cualquier `DATABASE_URL` manual con `mysql://` o `127.0.0.1`
2. Click **Add Reference** → elige tu servicio **PostgreSQL** (no MySQL)
3. Variable: `DATABASE_URL`
4. El nombre del servicio en la referencia debe coincidir, ej. `${{Postgres.DATABASE_URL}}` si el servicio se llama "Postgres"

La URL correcta empieza por `postgresql://` (Railway la genera sola al usar la referencia).

Railway no incluye `serverVersion` en la URL; la app lo fija en `doctrine.yaml` (`server_version: '16'`). No hace falta añadirlo a mano en Variables.

Tras guardar, redeploy. En logs deberías ver migraciones OK, no el error de driver.

---

## CORS: local vs producción

La API lee `CORS_ORIGINS` (lista separada por comas). El navegador solo permite peticiones desde esos orígenes.

| Entorno | `CORS_ORIGINS` | Quién llama |
|---------|----------------|-------------|
| Local | `http://localhost:3000` | CRM en `npm run dev` |
| Producción | `https://tu-crm.vercel.app` | CRM deployado en Vercel |

Varios orígenes (ej. local + preview de Vercel):

```env
CORS_ORIGINS=http://localhost:3000,https://vayno-crm-git-main.vercel.app
```

**Reglas:**

- Sin `*` — hay que listar cada dominio explícitamente
- Debe coincidir el protocolo (`http` vs `https`) y el puerto
- Tras cambiar `CORS_ORIGINS` en Railway, redeploy o restart del servicio

En el CRM:

```env
# Local
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1

# Producción
NEXT_PUBLIC_API_URL=https://vayno-api-production.up.railway.app/api/v1
```

---

## Guía paso a paso (desde cero)

Tu dominio actual: **https://api-vayno.up.railway.app**

### Paso 1 — Proyecto en Railway

1. Entra en [railway.com](https://railway.com) → **New Project**
2. Nombre sugerido: `vayno`

### Paso 2 — PostgreSQL

1. En el proyecto → **+ New** → **Database** → **Add PostgreSQL**
2. Espera a que el servicio esté **Active** (icono verde)
3. Anota el **nombre del servicio** (ej. `Postgres`, `PostgreSQL`) — lo necesitas para la referencia de `DATABASE_URL`

### Paso 3 — API desde GitHub

1. **+ New** → **GitHub Repo** → elige `imansilladerqui/vayno-backend`
2. Railway crea un servicio (ej. `vayno-backend`)
3. En ese servicio → **Settings**:
   - **Branch**: `main`
   - **Root Directory**: `/` (vacío)
   - **Builder**: Dockerfile (detectado automáticamente vía `railway.json`)

### Paso 4 — Variables del servicio API

Click en el servicio **API** (no Postgres) → pestaña **Variables**.

**Primero** añade `DATABASE_URL` con referencia (no la escribas a mano):

1. **+ New Variable** → **Add Reference**
2. Service: tu PostgreSQL (ej. `Postgres`)
3. Variable: `DATABASE_URL`
4. Guardar → debe verse `${{Postgres.DATABASE_URL}}` (ajusta el nombre si tu servicio se llama distinto)

**Después** añade el resto (Raw Editor o una a una):

```env
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=<pega aquí: openssl rand -hex 32>
CORS_ORIGINS=http://localhost:3000
ENABLE_API_DOCS=true
ALLOW_OWNER_REGISTRATION=false
```

Opcionales (ya tienen default en prod si no los pones):

```env
ACCESS_TOKEN_EXPIRE_MINUTES=15
REFRESH_TOKEN_EXPIRE_DAYS=7
RESERVATION_GRACE_MINUTES=15
```

| Variable | Obligatoria | Notas |
|----------|-------------|-------|
| `DATABASE_URL` | Sí | **Solo** vía Add Reference → PostgreSQL |
| `APP_SECRET` | Sí | ≥ 32 caracteres, único, no el de local |
| `APP_ENV` | Sí | `prod` |
| `APP_DEBUG` | Sí | `0` |
| `CORS_ORIGINS` | Sí | Dominio(s) del CRM, separados por coma |
| `ENABLE_API_DOCS` | No | `true` para Swagger en `/api/doc` |

**No pongas** `mysql://`, `127.0.0.1` ni copies `DATABASE_URL` de `.env.local`.

### Paso 5 — Dominio público

1. Servicio **API** → **Settings** → **Networking**
2. **Generate Domain** (si no lo tienes ya)
3. Debería quedar: `api-vayno.up.railway.app`

### Paso 6 — Deploy

1. Servicio API → **Deployments** → **Deploy** (o push a `main` en GitHub)
2. Abre el deploy activo → **View logs**
3. Busca estas líneas (significa que todo OK):

```
=== Vayno API startup ===
DATABASE_URL scheme: OK (postgresql)
Running migrations...
Migrations OK. Starting PHP server on 0.0.0.0:XXXX...
```

Si ves `ERROR:` en logs, el contenedor muere → Railway muestra **502 Application failed to respond**.

### Paso 7 — Probar

```bash
# Swagger (con ENABLE_API_DOCS=true)
curl -I https://api-vayno.up.railway.app/api/doc

# API viva (login devuelve 401 o error de validación, no 502)
curl -X POST https://api-vayno.up.railway.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"test@test.com","password":"password12345"}'
```

Respuesta buena del login (aunque el usuario no exista): JSON con error de credenciales o validación, **no** HTML de Railway 502.

### Paso 8 — CRM

En tu frontend (Vercel/local):

```env
NEXT_PUBLIC_API_URL=https://api-vayno.up.railway.app/api/v1
```

Y en Railway `CORS_ORIGINS` incluye el dominio del CRM (ej. `https://tu-app.vercel.app`).

---

## Uso de Railway (referencia rápida)

### 1. Proyecto y servicios

1. [railway.com](https://railway.com) → **New Project**
2. **Add PostgreSQL** (Database → PostgreSQL)
3. **Deploy from GitHub** → repo `vayno-backend`
4. Railway detecta `Dockerfile` + `railway.json`

### 2. Variables

1. Click en el servicio **API** (no Postgres)
2. **Variables** → pegar las de arriba
3. Para `DATABASE_URL`, usa el botón **Add reference** → Postgres → `DATABASE_URL`

### 3. Dominio público

1. Servicio API → **Settings** → **Networking**
2. **Generate Domain** → ej. `vayno-api-production.up.railway.app`
3. Prueba: `curl https://TU-DOMINIO.up.railway.app/api/v1`

### 4. Deploy automático

Cada `git push` a `main`:

1. Build de la imagen Docker
2. Migraciones (`railway.json` → `startCommand`)
3. Servidor PHP en `$PORT`

Logs en tiempo real: servicio API → **Deployments** → click en el deploy activo.

### 5. Comandos en producción

Railway CLI (opcional):

```bash
npm i -g @railway/cli
railway login
railway link
railway run php bin/console app:expire-reservations
```

---

## Checklist post-deploy

- [ ] `curl https://TU-DOMINIO/api/v1` → `{"status":"ok","api":"v1"}`
- [ ] `CORS_ORIGINS` apunta al dominio real del CRM
- [ ] `APP_SECRET` es único y ≥ 32 caracteres
- [ ] CRM tiene `NEXT_PUBLIC_API_URL` con el dominio de Railway
- [ ] `ENABLE_API_DOCS=true` si quieres Swagger en `https://TU-DOMINIO/api/doc`

## Créditos

- Trial ~$5 / 30 días
- Plan Free ~$1/mes (ajustado para POC)

## Expirar reservas (opcional)

```bash
railway run php bin/console app:expire-reservations
```

O cron externo (cron-job.org) en el POC.
