# Deployment Guide

## Overview
This guide outlines deployment considerations for the legacy PHP system and the target Python stack.

## Legacy (PHP) Deployment
- Web server: Apache or Nginx with PHP-FPM
- Document roots:
  - panel/ and webpanel/ served under appropriate virtual hosts
  - api/ routed to PHP handlers
- Environment configuration: PHP ini settings, file permissions
- Refer to: `../DEPLOYMENT.md` for specific steps

## Target (Python) Deployment
- Containerized FastAPI service behind reverse proxy
- PostgreSQL and Redis as managed services or containers
- Environment via `.env`/secrets manager
- Health/readiness endpoints for orchestration

## CI/CD
- Build, lint, type-check, tests
- Image build and push
- Deploy via pipeline (e.g., GitHub Actions) _(To be generated)_

## Observability
- Structured logs
- Basic metrics and traces _(To be generated)_

## Security
- Secrets management
- TLS termination at proxy/load balancer
- Audit logging _(To be generated)_


