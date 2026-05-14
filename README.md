AbraFlexi WebHook Acceptor
==========================

Listen for AbraFlexi changes


![Logo](package-logo.svg?raw=true)

Savers
------

 * PdoSQL  - store data in postgresql, mysql, mssql etc.
 * Kafka   - produce messages to Apache Kafka topics (one topic per evidence type)
 * Redis   - store data in Redis Streams (one stream per evidence type)
 * MongoDB - store data in MongoDB collections (one collection per evidence type)
 * Api     - push to some api (act as proxy) - not yet finished

Installation
------------

```shell
sudo apt install lsb-release wget
echo "deb http://repo.vitexsoftware.com $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
sudo wget -O /etc/apt/trusted.gpg.d/vitexsoftware.gpg http://repo.vitexsoftware.cz/keyring.gpg
sudo apt update
sudo apt install abraflexi-webhook-acceptor-DATABASE 
```

Please choose one of database type: **abraflexi-webhook-acceptor-mysql**, **abraflexi-webhook-acceptor-pgsql** or **abraflexi-webhook-acceptor-sqlite** to install.

For additional storage backends, install one or more of:
 * **abraflexi-webhook-acceptor-kafka** - Apache Kafka backend (requires php-rdkafka)
 * **abraflexi-webhook-acceptor-redis** - Redis Streams backend (requires php-redis)
 * **abraflexi-webhook-acceptor-mongodb** - MongoDB backend (requires php-mongodb)

These can be installed alongside the SQL packages (no conflicts).

Target Web root to /usr/share/abraflexi-webhook-acceptor

(Apache can be configured by `a2enconf abraflexi-webhook-acceptor` command)

Then open this location in browser as URI reachable by AbraFlexi server:

![Done](installer-done.png?raw=true)

>> Note: You can safely ignore the "⚠ REMOTE_HOST is not set. Is HostnameLookups On ?" message.

Configuration
----------------------

 *   APP_DEBUG   - true|false - show debug messages
 *   EASE_LOGGER - syslog|console|file|mail|...
 *   WHA_SAVER   - PdoSQL|Kafka|Redis|MongoDB|Api - storage backend (pipe-delimited for multiple)
 *   DB_CONNECTION - pgsql|mysql|sqlsrv|sqlite
 *   DB_HOST     - localhost is default
 *   DB_PORT     - database port
 *   DB_DATABASE - database schema name
 *   DB_USERNAME - database user login name
 *   DB_PASSWORD - database user password
 *   DB_SETUP    - database setup command (executed directly after connect)
 *   KAFKA_BROKERS - Kafka broker addresses (default: localhost:9092)
 *   KAFKA_TOPIC_PREFIX - Kafka topic name prefix (default: abraflexi)
 *   REDIS_HOST  - Redis host (default: localhost)
 *   REDIS_PORT  - Redis port (default: 6379)
 *   REDIS_PASSWORD - Redis password (optional)
 *   REDIS_KEY_PREFIX - Redis key prefix (default: abraflexi)
 *   MONGODB_URI - MongoDB connection URI (default: mongodb://localhost:27017)
 *   MONGODB_DATABASE - MongoDB database name (default: abraflexi_webhook)

Testing
-------

### Unit Tests (PHPUnit)

```shell
composer test
```

Runs PHPUnit test suites under `tests/src/`. Backend-specific tests use `@requires extension` annotations and are automatically skipped when the PHP extension is not loaded:

 * `tests/src/AbraFlexi/Acceptor/Saver/PdoSQLTest.php` - SQLite/PDO storage
 * `tests/src/AbraFlexi/Acceptor/Saver/KafkaTest.php` - requires `ext-rdkafka`
 * `tests/src/AbraFlexi/Acceptor/Saver/RedisTest.php` - requires `ext-redis`
 * `tests/src/AbraFlexi/Acceptor/Saver/MongoDBTest.php` - requires `ext-mongodb`

### Integration Test — All Backends

Loads all 42 JSON webhook fixtures from `tests/hooks/` directly into each storage backend:

```shell
php tests/test_all_backends.php
```

**Prerequisites:** A configured `.env` file in the project root with connection details for each backend (see Configuration section above). Run database migrations first for PdoSQL:

```shell
cd src && phinx migrate -c ../phinx-adapter.php
```

**Output format:**

```
=== AbraFlexi WebHook Backend Test ===
Hook files: 42
Backends to test: PdoSQL, Kafka, Redis, MongoDB

------------------------------------------------------------
Testing backend: PdoSQL
------------------------------------------------------------
  Files loaded: 42 | Errors: 0 | Total changes: 331
  Last processed version: 3656931
...
============================================================
SUMMARY
============================================================
Backend      Status   Files    Errors   Changes
--------------------------------------------------
PdoSQL       OK       42       0        331
Kafka        OK       42       0        331
Redis        OK       42       0        331
MongoDB      OK       42       0        331

=== Verification ===
Redis streams/keys: 17
MongoDB collections: 17
Kafka topics (via shell): abraflexi-adresar, abraflexi-banka, ...
```

**Reading results:**

 * **Status OK** — all hook files were stored successfully
 * **Status PARTIAL** — some files failed (check `Errors` column)
 * **Status FAIL** — backend could not be initialized (missing extension, connection refused, etc.)
 * **Files** — number of JSON fixture files processed
 * **Changes** — total webhook change records stored (each file can contain multiple changes)
 * **Last processed version** — version tracking value after processing; `null (stateless)` for Kafka (fire-and-forget)
 * **Verification section** — confirms data actually landed in each backend by counting keys/collections/topics

**PdoSQL UNIQUE constraint warnings** are expected on repeated runs — the SQLite database already contains the data from a previous run. Delete `db/test_webhooks.sqlite` and re-run migrations to start fresh.

### Legacy Hook Loader

```shell
php tests/loadhooks.php
```

Uses the full `HookReciever` pipeline (including version-based deduplication via `onlyFreshHooks()`). Reads `WHA_SAVER` from `.env` to select the backend. Useful for testing the complete webhook processing flow including deduplication logic.

### Static Analysis

```shell
vendor/bin/phpstan analyse
```

Uses `phpstan.neon` configuration with baseline for known issues.

Test Infrastructure (Ansible)
-----------------------------

Ansible playbooks for provisioning test backends on Debian/Ubuntu:

```shell
# Install all backends
ansible-playbook ansible/site.yml -i ansible/inventory/hosts.yml

# Install only Redis
ansible-playbook ansible/site.yml -i ansible/inventory/hosts.yml --tags redis

# Install only Kafka
ansible-playbook ansible/site.yml -i ansible/inventory/hosts.yml --tags kafka

# Install only MongoDB
ansible-playbook ansible/site.yml -i ansible/inventory/hosts.yml --tags mongodb
```

After provisioning, verify services are running:

```shell
redis-cli PING                                              # expect: PONG
mongosh --eval "db.runCommand({ping:1})"                    # expect: ok: 1
/opt/kafka/bin/kafka-topics.sh --list --bootstrap-server localhost:9092
```

