AbraFlexi WebHook Acceptor
==========================

Listen for AbraFlexi changes


![Logo](package-logo.svg?raw=true)

Savers
------

 * PdoSQL - store data in postgresql, mysql, mssql etc. 
 * Api    - push to some api (act as proxy) - not yet finished
 * Kafka  - store data in nosql database  - not yet finished

Installation
------------

```shell
sudo apt install lsb-release wget
echo "deb http://repo.vitexsoftware.com $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
sudo wget -O /etc/apt/trusted.gpg.d/vitexsoftware.gpg http://repo.vitexsoftware.cz/keyring.gpg
sudo apt update
sudo apt install multiflexi-DATABASE 
```

Please choose one of database type: **multiflexi-mysql**, **multiflexi-pgsql** a **multiflexi-sqlite** to install.

Target Web root to /usr/share/abraflexi-webhook-acceptor

(Apache can be configured by `a2enconf abraflexi-webhook-acceptor` command)

Then open this location in browser as URI reachable by AbraFlexi server:

![Done](installer-done.png?raw=true)

>> Note: You can safely ignore the "⚠ REMOTE_HOST is not set. Is HostnameLookups On ?" message.

Database configuration
----------------------

 *   DB_TYPE     - pgsql|mysql|sqlsrv|sqlite
 *   DB_HOST     - localhost is default
 *   DB_PORT     - database port
 *   DB_DATABASE - database schema name
 *   DB_USERNAME - database user login name
 *   DB_PASSWORD - database user password
 *   DB_SETUP    - database setup command (executed directly after connect)

