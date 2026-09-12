# Installation

## Requirements

- Icinga Web 2 >= 2.12 (for the database migration hook)
- PHP >= 8.2
- MariaDB >= 10.3 or PostgreSQL >= 12

## Install the module

Unpack the module into your Icinga Web 2 module path. The directory **must** be named
`hcloud`:

```
/usr/share/icingaweb2/modules/hcloud
```

Then enable it:

```
icingacli module enable hcloud
```

## Create the database

MariaDB:

```sql
CREATE DATABASE hcloud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hcloud'@'localhost' IDENTIFIED BY 'some-password';
GRANT ALL ON hcloud.* TO 'hcloud'@'localhost';
```

PostgreSQL:

```sql
CREATE USER hcloud WITH PASSWORD 'some-password';
CREATE DATABASE hcloud OWNER hcloud;
```

Apply the baseline for your engine. A fresh install needs the baseline only, never the
upgrade scripts:

```
mysql hcloud < schema/mysql.sql
psql -U hcloud hcloud < schema/pgsql.sql
```

Create a matching Icinga Web 2 resource under *Configuration -> Resources*, then select it
under *Configuration -> Modules -> hcloud*.

## Upgrades

Pending schema upgrades appear under *Configuration -> Migrations* in the web interface.
From the command line:

```
icingacli hcloud migrate list
icingacli hcloud migrate run
```

## After deploying

Restart your web server after deploying PHP or translation changes. Opcache holds compiled
PHP, and gettext caches `.mo` files per worker process. Skipping the restart produces the
classic symptoms: one locale updates while another lags, or literal `%s` placeholders show
up in the interface.

Translations additionally need the target system locale (`de_DE.UTF-8` and so on) to be
generated on the host. Shipping the `.mo` file is not enough.
