# Python Migration Roadmap

## Goals
- Maintain functional parity while improving reliability, security, and developer experience
- Incremental migration with minimal downtime and measurable checkpoints

## Guiding Principles
- Feature parity first, modernization second
- Strangler pattern: route traffic progressively to new Python services
- Idempotent migrations with rollback paths
- Automate tests and checks before switching traffic

## Target Stack
- API: FastAPI, Pydantic v2, Uvicorn, SQLAlchemy 2.x, Alembic
- Bot: Aiogram 3.x (async)
- DB: PostgreSQL 15+
- Cache/Queue: Redis 7+
- Tooling: Poetry, Ruff, Mypy (strict), Pytest, Pre-commit, Docker/Compose

## Phase 0 – Discovery & Parity Baseline
1. Enumerate API endpoints (paths, methods, params, responses) _(To be generated)_
2. Extract data schema (tables, relations, constraints) _(To be generated)_
3. Map critical flows: auth, payments, provisioning, bot commands
4. Define acceptance tests per flow (golden paths + key edge cases)

## Phase 1 – Foundation
1. Create mono-repo `python/` with Poetry + tooling scaffold
2. Define shared `core` package: settings, logging, errors, security utils
3. Provision PostgreSQL + Redis locally via Docker Compose
4. Implement base FastAPI app with health, readiness, and version endpoints

## Phase 2 – API Parity (Backend First)
1. Design SQLAlchemy models mirroring legacy schema
2. Implement endpoint groups in priority order:
   - Auth/session, Users, Products, Payments, Panels/Provisioning
3. Add integration tests against PostgreSQL (pytest + testcontainers if needed)
4. Populate Alembic migrations and seed data scripts
5. Observability: structured logs, request IDs, basic metrics

## Phase 3 – Bot Migration
1. Port bot flows to Aiogram 3.x
2. Replace direct PHP calls with Python API calls
3. Add rate limits, retries, and error handling patterns (circuit breaker)
4. Bot integration tests with mocked API

## Phase 4 – UI Transition Strategy
- Option A: Keep PHP panels temporarily; re-point to Python API
- Option B: Rebuild UI (e.g., Next.js) later; out of scope for initial cut

## Phase 5 – Cutover & Hardening
1. Reverse proxy routes to Python API gradually (path-by-path)
2. Shadow traffic for comparison; alert on diffs
3. Load/perf tests; tune DB indexes and caching
4. Security review; secrets management; audit logging

## Deliverables & Checkpoints
- docs/api-contracts.md _(To be generated)_
- docs/data-models.md _(To be generated)_
- Python service skeleton in `python/`
- CI pipeline for lint, type-check, test
- Canary release plan and rollback docs

## Risks and Mitigations
- Data parity drift → migration tests, read-after-write checks
- Hidden business rules → capture via acceptance tests and code comments
- Payment provider nuances → sandbox validation, replay harness

## Acceptance Criteria
- All golden-path tests pass against Python API
- Bot functions fully via Python API
- Zero net capability loss; performance not worse than baseline
