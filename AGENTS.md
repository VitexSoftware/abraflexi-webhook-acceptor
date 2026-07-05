# AGENTS.md — abraflexi-webhook-acceptor

## Co projekt dělá

Přijímá HTTP POST webhooky z AbraFlexi (Changes API formát `winstrom.changes[]`),
ukládá je do konfigurovatelného backendu (SQL/MongoDB/Redis/Kafka) a volitelně
spouští posthooks.

Produkční vstupní bod: `src/webhook.php`  
Hlavní třída: `AbraFlexi\Acceptor\HookReciever` (extends `AbraFlexi\Changes`)  
Savers: `src/AbraFlexi/Acceptor/Saver/{PdoSQL,MongoDB,Redis,Kafka,Api}.php`

## Formát webhooků z AbraFlexi

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

Vzorové payloady jsou v `tests/hooks/*.json`.  
Evidence jsou: `adresar`, `faktura-vydana`, `banka`, `pohledavka` aj.  
Operace jsou: `create`, `update`, `delete`.

## Kompatibilita s abraflexi-reminder

Tento projekt sdílí ekosystém s **abraflexi-reminder** (`VitexSoftware/abraflexi-reminder`).
Udržuj kompatibilitu v těchto bodech:

### Štítky zákazníků (adresar.stitky)
Abraflexi-reminder nastavuje/čte tyto štítky na záznamu `adresar`:

| Štítek | Nastavuje | Maže |
|--------|-----------|------|
| `UPOMINKA1` | abraflexi-reminder (RT53) | abraflexi-reminder-clean-labels (RT55) |
| `UPOMINKA2` | abraflexi-reminder (RT53) | abraflexi-reminder-clean-labels (RT55) |
| `UPOMINKA3` | abraflexi-reminder (RT53) | abraflexi-reminder-clean-labels (RT55) |
| `NEPLATIC`  | abraflexi-reminder (RT53) | abraflexi-reminder-clean-labels (RT55) |
| `ODPOJENO`  | ByServiceToggle notifier (RT57) | abraflexi-reminder-clean-labels (RT55) |

Pokud webhook acceptor reaguje na změny `adresar`, nesmí sám mazat ani
přepisovat tyto štítky — jsou součástí stavového stroje reminder pipeline.

### Evidence relevantní pro testování
- `faktura-vydana` — vydané faktury (abraflexi-reminder je upomíná)
- `banka` — bankovní pohyby (abraflexi-matcher je páruje s fakturami)
- `adresar` — zákazníci (štítky pro reminder a odpojení)
- `pohledavka` — pohledávky (mohou mít null `typDoklK` — nezlomit parsování)

### Pozor na pohledavka
Záznamy evidence `pohledavka` mohou mít null `typDoklK`. `AbraFlexi\Relation`
hodí `TypeError` pokud se pokusíš konstruovat Relation z null. Nevolej
`getColumnsFromAbraFlexi` s `includes=/pohledavka/typDokl` nebo s
`typDokl(typDoklK,kod)` v colsToGet.

## Env proměnné

| Proměnná | Popis |
|----------|-------|
| `DB_CONNECTION` | Typ DB: `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite` |
| `DB_HOST` | Hostname databáze |
| `DB_PORT` | Port databáze |
| `DB_DATABASE` | Název databáze |
| `DB_USERNAME` | Uživatel DB |
| `DB_PASSWORD` | Heslo DB |
| `WHA_SAVER` | Pipe-separated seznam Saver tříd, např. `PdoSQL\|MongoDB` |
| `ABRAFLEXI_URL` | URL AbraFlexi serveru |
| `ABRAFLEXI_LOGIN` | Login do AbraFlexi |
| `ABRAFLEXI_PASSWORD` | Heslo do AbraFlexi |
| `ABRAFLEXI_COMPANY` | Kód firmy v AbraFlexi |

## Testovací workflow

```bash
# Replay všech vzorových hooků přes všechny backendy
php tests/test_all_backends.php

# Replay hooků do SQL backendu (loadhooks.php)
php tests/loadhooks.php

# Interaktivní replay přes browser
php -S localhost:8080 tests/posthooks.php
```

Vzorové hooky: `tests/hooks/*.json` — přidávej sem nové payloady při každém
novém testovacím scénáři, pojmenuj je `webhook-<unix-timestamp>.json`.

## Architektura — jak přidat Saver

1. Vytvoř `src/AbraFlexi/Acceptor/Saver/MojeUloziste.php` implementující interface `AbraFlexi\Acceptor\saver`
2. Interface vyžaduje: `save(array $changes): bool`
3. Nastav `WHA_SAVER=MojeUloziste` v `.env`

## Konvence

- PHP 8.1+, `declare(strict_types=1)` ve všech souborech
- Namespace: `AbraFlexi\Acceptor`
- Balíček se jmenuje `vitexsoftware/abraflexi-webhook-acceptor`
- Debian packaging: `dpkg-buildpackage -b -uc`
- CI blokuje pokud běží `RebulidDEBRepoByAnsible`
