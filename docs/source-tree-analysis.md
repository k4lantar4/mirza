# Source Tree Analysis

```text
/root/mirza/
├── api/                  # PHP API endpoints (services, routes)
├── panel/                # PHP web panel (UI, admin/user flows)
├── webpanel/             # PHP admin web (includes installation guide)
├── vpnbot/               # Bot integration (PHP entry: index.php)
├── docs/                 # Generated docs and stories
├── database/             # Database-related assets/scripts (legacy)
├── installer/            # Installation scripts
├── payment/              # Payment handlers/integration
├── vendor/               # PHP dependencies (Composer)
├── *.php                 # Legacy entry points and helpers at repo root
└── README.md             # Project readme
```

## Critical Folders
- api/: Backend endpoints and service logic
- panel/: User/admin panel UI
- webpanel/: Admin UI; includes setup and guides
- vpnbot/: Messaging bot integration
- payment/: Payment-related integrations
- installer/: Setup/installation scripts

## Entry Points (Detected by pattern)
- index.php files within root, panel/, webpanel/, vpnbot/

## Integration Notes
- Panels likely call API endpoints directly
- Bot likely invokes a subset of API operations
- Payment flows integrate with panel and API

## Follow-ups
- Map API route files and HTTP contracts _(To be generated)_
- Enumerate database schema and migrations _(To be generated)_
- Extract deployment topology and CI/CD _(To be generated)_
