AbraFlexi WebHook Acceptor
==========================

Listen for AbraFlexi changes


![Logo](package-logo.svg?raw=true)

Savers
------

 * Api    - push to some api (act as proxy) - not yet finished
 * Kafka  - store data in nosql database  - not yet finished
 * PdoSQL - store data in postgresql, mysql, mssql etc. 


Installation
------------

```shell
sudo apt install lsb-release wget
echo "deb http://repo.vitexsoftware.com $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
sudo wget -O /etc/apt/trusted.gpg.d/vitexsoftware.gpg http://repo.vitexsoftware.cz/keyring.gpg
sudo apt update
sudo apt install multiflexi-DATABASE 
```

Please choose your database adapter: **multiflexi-mysql**, **multiflexi-pgsql** a **multiflexi-sqlite**


Database configuration
----------------------

 *   DB_TYPE     - pgsql|mysql|sqlsrv|sqlite
 *   DB_HOST     - localhost is default
 *   DB_PORT     - database port
 *   DB_DATABASE - database schema name
 *   DB_USERNAME - database user login name
 *   DB_PASSWORD - database user password
 *   DB_SETUP    - database setup command (executed directly after connect)

