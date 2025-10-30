# MoonVPN Python Scaffold

## FastAPI
- Install: `poetry install`
- Run: `poetry run uvicorn app.main:app --reload`
- Health: GET /healthz

## Aiogram Bot
- Env: set `BOT_TOKEN` and `API_BASE_URL`
- Run: `poetry run python -m bot.main`

## Services
- `docker-compose up -d` (Postgres, Redis)

See ../docs/migration-roadmap.md for phases.


