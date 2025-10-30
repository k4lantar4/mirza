# Project Overview

## Project Name
Mirza

## Purpose
Legacy PHP-based VPN management system including web panels, API endpoints, and a bot interface. The goal is to reverse engineer and progressively reimplement with Python and modern tooling while preserving business logic and capabilities.

## Repository Structure
- Repository Type: Multi-part
- Parts:
  - panel (Web Panel, PHP)
  - webpanel (Admin Web, PHP)
  - api (Backend API, PHP)
  - vpnbot (Bot integration, PHP)

## Technology Stack (Detected)
- Primary Language(s): PHP
- Web/UI: Custom PHP templates
- Backend/API: PHP endpoints
- Bot: PHP entry points
- Storage/Config: PHP config files and scripts (further analysis required)

## High-Level Architecture
- Web Panels (panel, webpanel) provide UI for administration and user operations
- API (api) exposes endpoints consumed by panels and bot
- Bot (vpnbot) integrates with messaging platform to automate operations

## Modernization Objective
- Reverse engineer current PHP system
- Reimplement in Python 3.11+ with async patterns
- Introduce PostgreSQL, Redis, and modern frameworks where appropriate
- Maintain feature parity, improve reliability, testability, and security

## Key Documents (Existing)
- README.md, README2.md
- DEPLOYMENT.md
- webpanel/README.md, webpanel/INSTALLATION_GUIDE.md
- WEBPANEL_COMPLETE_GUIDE.md, WEBPANEL_BOT_INTEGRATION_GUIDE.md
- WARP.md, BUGFIXES.md

## Next Steps
- Generate detailed architecture documents per part _(To be generated)_
- Inventory API contracts _(To be generated)_
- Document data models _(To be generated)_
- Produce development and deployment guides _(To be generated)_
