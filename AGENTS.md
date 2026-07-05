# AGENTS.md — abraflexi-webhook-acceptor

## What this project does

Receives HTTP POST webhooks from AbraFlexi (Changes API `winstrom.changes[]`
format), persists them to a configurable backend (SQL / MongoDB / Redis / Kafka)
and optionally triggers post-hooks.

Production entry point: `src/webhook.php`  
Core class: `AbraFlexi\Acceptor\HookReciever` (extends `AbraFlexi\Changes`)  
Savers: `src/AbraFlexi/Acceptor/Saver/{PdoSQL,MongoDB,Redis,Kafka,Api}.php`

## AbraFlexi webhook payload format

```json
{
  "winstrom": {
    "@globalVersion": "3656477",
    "changes": [{
      "@evidence": "adresar",
      "@in-version": "3656477",
      "@operation": "update",
      "@timestamp": "2021-03-12 23:59:55.586215",
      "id": "4435",
      "external-ids": ["code:TEST5"]
    }],
    "next": "none"
  }
}
```

Sample payloads: `tests/hooks/*.json`  
Common evidences: `adresar`, `faktura-vydana`, `banka`, `pohledavka`  
Operations: `create`, `update`, `delete`

## Compatibility with abraflexi-reminder

This project shares an ecosystem with **abraflexi-reminder**
(`VitexSoftware/abraflexi-reminder`). Maintain compatibility on the following
points.

### Customer labels (adresar.stitky)

abraflexi-reminder owns this label state machine. Do not overwrite or delete
these labels — they are set and cleared by the reminder pipeline as part of its
state management.

| Label | Set by | Cleared by |
|-------|--------|------------|
| `UPOMINKA1` | Reminder RT53 — 1st notice | Clear Labels RT55 (after payment) |
| `UPOMINKA2` | Reminder RT53 — 2nd notice | Clear Labels RT55 (after payment) |
| `UPOMINKA3` | Reminder RT53 — 3rd notice | Clear Labels RT55 (after payment) |
| `NEPLATIC`  | Reminder RT53 — score ≥ 3 | Clear Labels RT55 (after payment) |
| `ODPOJENO`  | ByServiceToggle notifier RT57 | Clear Labels RT55 (after payment) |

### Evidences relevant for testing
- `faktura-vydana` — issued invoices (reminded by abraflexi-reminder)
- `banka` — bank transactions (matched to invoices by abraflexi-matcher)
- `adresar` — customers (labels for reminders and disconnection)
- `pohledavka` — receivables (see warning below)

### pohledavka — typDoklK pitfall
Records in the `pohledavka` evidence can have null `typDoklK`. `AbraFlexi\Relation`
throws a `TypeError` if you try to construct a Relation from null. Do not call
`getColumnsFromAbraFlexi` with `includes=/pohledavka/typDokl` or with
`typDokl(typDoklK,kod)` in colsToGet for this evidence.

## Environment variables

| Variable | Description |
|----------|-------------|
| `DB_CONNECTION` | DB type: `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite` |
| `DB_HOST` | Database hostname |
| `DB_PORT` | Database port |
| `DB_DATABASE` | Database name |
| `DB_USERNAME` | Database user |
| `DB_PASSWORD` | Database password |
| `WHA_SAVER` | Pipe-separated list of Saver classes, e.g. `PdoSQL\|MongoDB` |
| `ABRAFLEXI_URL` | AbraFlexi server URL |
| `ABRAFLEXI_LOGIN` | AbraFlexi login |
| `ABRAFLEXI_PASSWORD` | AbraFlexi password |
| `ABRAFLEXI_COMPANY` | Company code in AbraFlexi |

## Test workflow

```bash
# Replay all sample hooks through all configured backends
php tests/test_all_backends.php

# Replay hooks into SQL backend
php tests/loadhooks.php

# Interactive browser-based replay
php -S localhost:8080 tests/posthooks.php
```

Sample hooks: `tests/hooks/*.json` — add new payloads here for each new test
scenario, name them `webhook-<unix-timestamp>.json`.

## Adding a new Saver

1. Create `src/AbraFlexi/Acceptor/Saver/MyStorage.php` implementing
   `AbraFlexi\Acceptor\saver`
2. Interface requires: `save(array $changes): bool`
3. Set `WHA_SAVER=MyStorage` in `.env`

## Conventions

- PHP 8.1+, `declare(strict_types=1)` in all files
- Namespace: `AbraFlexi\Acceptor`
- Package name: `vitexsoftware/abraflexi-webhook-acceptor`
- Build: `dpkg-buildpackage -b -uc`
- CI blocks when `RebulidDEBRepoByAnsible` is running
