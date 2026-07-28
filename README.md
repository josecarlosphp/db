# db

PHP classes to interact with databases.
Works with MySQL, MySQLi, and PDO.

---

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D5.6%20%7C%207.x%20%7C%208.x-777BB4.svg)](https://www.php.net/)

A flexible abstraction library in PHP for database interaction. It offers seamless switching between database drivers (`PDO`, `MySQLi`, `MySQL`), built-in transaction management, dynamic query building, automatic error recovery, and result set abstraction.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Quick Start & Usage](#quick-start--usage)
  - [1. Connecting to the Database](#1-connecting-to-the-database)
  - [2. Basic CRUD Operations](#2-basic-crud-operations)
  - [3. Transaction Management](#3-transaction-management)
  - [4. Working with Result Sets](#4-working-with-result-sets)
  - [5. Building Dynamic Queries (SqlSelectQuery)](#5-building-dynamic-queries-sqlselectquery)
  - [6. Active Record Pattern (DbReg)](#6-active-record-pattern-dbreg)
- [Class Architecture](#class-architecture)
- [License & Copyright](#license--copyright)

## Features

- **Multi-Driver Abstraction:** Works transparently with `PDO`, `MySQLi`, and legacy `MySQL`.
- **Automatic Driver Detection:** `DbConnection::Factory()` automatically chooses the best available driver based on PHP extensions loaded in your environment.
- **Transaction Management:** Integrated support for starting, committing, rolling back, and checking active transactions (`beginTransaction()`, `commit()`, `rollback()`, `inTransaction()`).
- **Resilient Query Execution:**
  - Automatic retry on MySQL deadlocks.
  - Automatic reconnect handling when connection fails (`Server has gone away`).
  - Automatic SQL mode adjustments for legacy queries.
- **SQL Select Query Builder:** Programmatically build complex `SELECT` queries with filters, joins, groupings, and pagination using `SqlSelectQuery`.
- **Active Record / Table Abstraction (`DbReg`):** Easily map tables to object instances for CRUD operations.

## Installation

Install via Composer:

```bash
composer require josecarlosphp/db
```

Or configure your `composer.json`:

```json
{
    "require": {
        "josecarlosphp/db": "^1.0"
    }
}
```

Or include using PSR-4 autoloading:

```php
require_once __DIR__ . '/vendor/autoload.php';

use josecarlosphp\db\DbConnection;
```

## Quick Start & Usage

### 1. Connecting to the Database

Use the factory method to automatically pick the best driver (`PDO`, `MySQLi`, or `MySQL`):

```php
use josecarlosphp\db\DbConnection;

// Factory auto-selects available driver
$db = DbConnection::Factory(
    $ip = 'localhost',
    $dbport = 3306,
    $dbname = 'my_database',
    $dbuser = 'root',
    $dbpass = 'secret',
    $connect = true,
    $charset = 'utf8mb4'
);

// Or explicitly specify a driver ('PDO', 'MySQLi', 'MySQL'):
$dbPdo = DbConnection::Factory('localhost', 3306, 'my_database', 'root', 'secret', true, 'utf8mb4', false, 'PDO');
```

### 2. Basic CRUD Operations

```php
// SELECT query
$res = $db->Select("SELECT * FROM users WHERE status = 'active'");

// INSERT query
$id = $db->Insert("INSERT INTO users (name, email) VALUES ('Juan', 'juan@example.com')");
$lastInsertId = $db->Insert_id();

// UPDATE query
$db->Update("UPDATE users SET status = 'inactive' WHERE last_login < '2023-01-01'");
$affectedRows = $db->AffectedRows();

// Check if records exist
$exists = $db->Exists('juan@example.com', 'email', 'users');
```

### 3. Transaction Management

Easily wrap multiple database operations in ACID transactions:

```php
try {
    $db->beginTransaction();

    $queries = [
        "UPDATE accounts SET balance = balance - 100 WHERE id = 1",
        "UPDATE accounts SET balance = balance + 100 WHERE id = 2",
    ];
    foreach ($queries as $query) {
        if (!$db->Execute($query)) {
            throw new Exception($db->Error());
        }
    }

    if ($db->inTransaction()) {
        $db->commit();
        echo "Transaction committed successfully!";
    }
} catch (\Exception $e) {
    if ($db->inTransaction()) {
        $db->rollback();
    }
    echo "Transaction failed: " . $e->getMessage();
}
```

### 4. Working with Result Sets

Abstract result sets provide clean iteration methods:

```php
$rs = $db->Select("SELECT id, name, email FROM users");

while ($row = $rs->FetchAssoc()) {
    echo $row['name'] . ' - ' . $row['email'] . "\n";
}

// Or fetch all rows at once
$allUsers = $rs->FetchAllAssoc();
```

### 5. Building Dynamic Queries (SqlSelectQuery)

Construct structured queries programmatically:

```php
use josecarlosphp\db\SqlSelectQuery;

$query = new SqlSelectQuery();
$query->SetSelect('u.id, u.name, count(o.id) as total_orders');
$query->SetFrom('users u');
$query->SetJoin('LEFT JOIN orders o ON u.id = o.user_id');
$query->SetWhere("u.status = 'active'");
$query->SetGroupBy('u.id');
$query->SetOrderBy('total_orders DESC');
$query->SetLimit('0, 10');

$sql = $query->GetQuery();
$res = $db->Select($sql);
```

### 6. Active Record Pattern (DbReg)

Interact with database tables as objects:

```php
use josecarlosphp\db\DbReg;

$user = new DbReg($db, 'users');
$user->Load(1); // Load record with ID 1

if ($user->SetValue('name', 'Carlos') && $user->Save()) {
    echo "User updated successfully!";
}else{
    echo "User update failed: " . $user->Error();
}
```

## Class Architecture

| Class / Component | Description |
|---|---|
| `DbConnection` | Abstract base class defining common interface & resilient query engine. |
| `DbConnection_PDO` | Driver implementation using PHP Data Objects (PDO). |
| `DbConnection_MySQLi` | Driver implementation using MySQLi extension functions. |
| `DbConnection_MySQL` | Driver implementation using legacy MySQL extension functions. |
| `DbResultSet` | Abstract result set wrapper with `FetchAssoc()`, `FetchRow()`, `FetchAll()`. |
| `SqlSelectQuery` | Helper class to build and manipulate SQL `SELECT` queries dynamically. |
| `DbReg` | Active record / Table ORM helper for loading, saving, and deleting records. |

## License & Copyright

Copyright (C) 2019  José Carlos Cruz Parra <https://github.com/josecarlosphp/>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
