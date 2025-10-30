# Development Guide

## Prerequisites
- PHP 8.x (legacy services) — until migration completes
- Docker (optional for DB/Redis)
- Composer (if modifying legacy PHP vendor)

## Recommended (Target Python Stack)
- Python 3.11+
- Poetry
- PostgreSQL 15+, Redis 7+

## Environment Setup
1. Clone the repository
2. Create `.env` files as needed (API keys, DB, Redis) _(To be generated)_
3. Start services (DB/Redis) via Docker Compose _(To be generated)_

## Running (Legacy)
- Panels: serve via PHP’s built-in server or Apache/Nginx
- API: serve `api/` endpoints through your web server
- Bot: run PHP entry in `vpnbot/` _(To be generated)_

## Running (Target Python)
1. Create `python/` workspace with Poetry _(See migration-roadmap.md)_
2. `poetry install`
3. `poetry run uvicorn app.main:app --reload`

## Testing
- Legacy: manual flows guided by existing docs
- Target: add pytest suite for API/bot _(To be generated)_

## Code Style
- Target Python: ruff + mypy (strict) _(To be generated)_

## Common Tasks
- Update dependencies
- Run formatters/linters
- Seed local DB _(To be generated)_

## Troubleshooting
- Check PHP error logs under project root or module dirs
- Validate file permissions for web server


