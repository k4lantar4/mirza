# Architecture

## Executive Summary
This repository is a multi-part legacy PHP system composed of two web panels, a backend API, and a bot. The panels and bot interact with the API and shared configuration/data. The target state is a Python-based architecture that preserves behavior while modernizing reliability, observability, and security.

## Repository Structure and Parts
- panel (PHP, Web UI)
- webpanel (PHP, Admin UI)
- api (PHP, Backend endpoints)
- vpnbot (PHP, Bot entry)

## High-Level Architecture Pattern
- Layered architecture: UI (panel/webpanel) → API (api) → Data/Infra
- Integration via HTTP calls from UI and bot to API
- Shared helpers and configs at repo root

## Technology Stack (Current)
- Language: PHP
- Packaging: Composer (vendor/)
- UI: PHP templates
- Bot: PHP entry (`vpnbot/index.php`)
- Config/Secrets: PHP config files and shell scripts

## Technology Stack (Target)
- API: Python 3.11+, FastAPI, Pydantic, Uvicorn
- Bot: Aiogram 3.x (async)
- DB: PostgreSQL (SQLAlchemy + Alembic)
- Cache/Queue: Redis
- Tooling: Poetry, Ruff, Mypy (strict), Pytest, Docker

## Component Responsibilities
- panel/webpanel: User/admin flows, payment initiation, management operations
- api: Core business logic and endpoints
- vpnbot: Automations and user self-service

## Data Architecture
_(To be generated)_

## API Design
_(To be generated)_

## Integration Architecture
_(To be generated)_

## Deployment & Operations
_(To be generated)_

## Testing Strategy
_(To be generated)_
