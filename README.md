**Other languages:**
- [Русская документация](docs/README_ru.md)
- [Documentation française](docs/README_fr.md)

---

## Getting the Library

You can [download it as an archive](https://github.com/Vasiliy-Makogon/Database/archive/master.zip), clone it from this site, or install via composer ([packagist.org link](https://packagist.org/packages/krugozor/database)):
```
composer require krugozor/database
```


## What is `krugozor/database`?

`krugozor/database` is a PHP >= 8.0 class library for simple, convenient, fast, and secure work with MySQL databases, using the PHP extension [mysqli](https://www.php.net/manual/en/book.mysqli.php).


### Why do you need a custom class for MySQL when PHP already has PDO abstraction and the mysqli extension?

The main drawbacks of all libraries for working with MySQL in PHP are:

* **Verbosity**
  * To prevent SQL injections, developers have two options:
    * Use [prepared statements](https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php).
    * Manually escape parameters going into the SQL query body. Pass string parameters through [mysqli_real_escape_string](https://www.php.net/manual/en/mysqli.real-escape-string.php), and cast expected numeric parameters to the appropriate types — `int` and `float`.
  * Both approaches have significant drawbacks:
    * Prepared statements are [terribly verbose](https://www.php.net/manual/en/mysqli.prepare.php#refsect1-mysqli.prepare-examples). Using PDO abstraction or the mysqli extension "out of the box", without aggregating all methods for retrieving data from the DBMS, is simply impossible — to get a value from a table you need to write at least 5 lines of code! And that's for every single query!
    * Manual escaping of parameters going into the SQL query body is not even worth discussing. A good programmer is a lazy programmer. Everything should be automated as much as possible.
* **Inability to get the SQL query for debugging**
  * To understand why an SQL query doesn't work in your program, you need to debug it — find either a logical or syntactic error. To find the error, you need to "see" the actual SQL query that the database complained about, with parameters substituted into its body. That is, to have a fully formed SQL statement. If a developer uses PDO with prepared statements, this is... IMPOSSIBLE! No convenient mechanisms for this are [PROVIDED](https://qna.habr.com/q/22669) in the native libraries. You're left with either workarounds or digging through the database log.


### Solution: `krugozor/database` — a class for working with MySQL

1. Eliminates verbosity — instead of 3 or more lines of code to execute a single query when using the "native" library, you write just one.
2. Escapes all parameters going into the query body according to the specified placeholder type — reliable protection against SQL injections.
3. Does not replace the functionality of the "native" mysqli adapter, but simply complements it.
4. Extensible. Essentially, the library provides only a parser and SQL query execution with guaranteed protection against SQL injections. You can inherit from any library class and, using both library mechanisms and `mysqli` and `mysqli_result` mechanisms, create the methods you need.

### What the `krugozor/database` library is NOT

Most wrappers for various database drivers are a pile of useless code with terrible architecture. Their authors, not understanding the practical purpose of their wrappers themselves, turn them into something like query builders (sql builder), ActiveRecord libraries, and other ORM solutions.

The `krugozor/database` library is none of these. It's just a convenient tool for working with regular SQL within the MySQL DBMS — and nothing more!


## What are placeholders?

**Placeholders** — **special typed markers that are written in the SQL query string *instead of explicit values (query parameters)***. The values themselves are passed "later", as subsequent arguments to the main method that executes the SQL query:

```php
$result = $db->query(
    "SELECT * FROM `users` WHERE `name` = '?s' AND `age` = ?i",
    "d'Artagnan", 41
);
```

SQL query parameters passed through the *placeholder* system are processed by special escaping mechanisms, depending on the placeholder type. This means you no longer need to wrap variables in escaping functions like `mysqli_real_escape_string()` or cast them to numeric types, as was done before:

```php
<?php
// Previously, before each query to the DBMS, we did
// something like this (and many still don't do it):
$id = (int) $_POST['id'];
$value = mysqli_real_escape_string($mysql, $_POST['value']);
$result = mysqli_query($mysql, "SELECT * FROM `t` WHERE `f1` = '$value' AND `f2` = $id");
```

Now writing queries has become easy, fast, and most importantly, the `krugozor/database` library completely prevents any possible SQL injections.


### Introduction to the Placeholder System

The types of placeholders and their purposes are described below. Before getting acquainted with placeholder types, you need to understand how the library mechanism works.

#### The PHP Problem

PHP is a weakly typed language, and an ideological dilemma arose during the development of this library.
Imagine we have a table with the following structure:

```sql
`name` varchar not null
`flag` tinyint not null
```
and the library MUST (for some reason, possibly beyond the developer's control) execute the following query:

```php
$db->query(
    "INSERT INTO `t` SET `name` = '?s', `flag` = ?i",
    null, false
);
```
In this example, there's an attempt to write a `null` value to the `not null` text field `name`, and a boolean `false` to the numeric field `flag`. What should we do in this situation?

* Who should be responsible for validating query parameters — the client code or the library?
* Should we interrupt program execution in this case, or perhaps apply some manipulations so that the data gets written to the database?
* Can we treat the `false` value for the `tinyint` column as `0`, and `null` as an empty string for the `name` column?
* How can we simplify or standardize such issues in our code?

Given these questions, it was decided to implement two operating modes in this library.

### Library Operating Modes

  * **Mysql::MODE_STRICT — strict mode for matching placeholder type and argument type**.
    In `Mysql::MODE_STRICT` mode, *the argument type must match the placeholder type*. For example, attempting to pass the value `55.5` or `'55.5'` as an argument for an integer placeholder `?i` will result in an exception being thrown:

```php
// set strict operating mode
$db->setTypeMode(Mysql::MODE_STRICT);
// this expression will not be executed, an exception will be thrown:
// attempt to specify a value of type "double" for placeholder of type "integer" in query template "SELECT ?i"
$db->query('SELECT ?i', 55.5);
```

* **Mysql::MODE_TRANSFORM — mode for converting the argument to the placeholder type when the placeholder type and argument type don't match.** The `Mysql::MODE_TRANSFORM` mode is set by default and is a "tolerant" mode — when the placeholder type and argument type don't match, it doesn't throw an exception, but *tries to convert the argument to the required placeholder type using PHP itself*. By the way, I, as the library author, always use this particular mode; I've never used strict mode (`Mysql::MODE_STRICT`) in real work, but perhaps you specifically will need it.

**The following conversions are allowed in Mysql::MODE_TRANSFORM mode:**

* **Converted to type `int` (placeholder `?i`)**
  * floating-point numbers represented as either `string` or `double` type
  * `bool` TRUE is converted to `int(1)`, FALSE is converted to `int(0)`
  * `null` is converted to `int(0)`
* **Converted to type `double` (placeholder `?d`)**
  * integers represented as either `string` or `int` type
  * `bool` TRUE is converted to `float(1)`, FALSE is converted to `float(0)`
  * `null` is converted to `float(0)`
* **Converted to type `string` (placeholder `?s`)**
  * `bool` TRUE is converted to `string(1) "1"`, FALSE is converted to `string(1) "0"`. This behavior differs from casting `bool` to `int` in PHP, since often, in practice, boolean types are written to MySQL as numbers.
  * `numeric` type values are converted to string according to PHP conversion rules
  * `null` is converted to `string(0) ""`
* **Converted to type `null` (placeholder `?n`)**
  * any arguments.
* For arrays, objects, and resources, conversions are not allowed.


### What placeholder types are available in the library?


#### `?i` — integer placeholder

```php
$db->query(
    'SELECT * FROM `users` WHERE `id` = ?i', 123
);
```
SQL query after template conversion:
```sql
SELECT * FROM `users` WHERE `id` = 123
```

**WARNING!** If you're working with numbers that exceed `PHP_INT_MAX`, then:
* Work with them exclusively as strings in your programs.
* Don't use this placeholder; use the string placeholder `?s` (see below). The thing is, numbers exceeding `PHP_INT_MAX` are interpreted by PHP as floating-point numbers. The library parser will try to convert the parameter to `int` type, and as a result "*the result will be undefined, since float doesn't have sufficient precision to return the correct result. In this case, neither a warning nor even a notice will be displayed!*" — [php.net](https://www.php.net/manual/en/language.types.integer.php#language.types.integer.casting.from-float).

#### `?d` — floating-point number placeholder

```php
$db->query(
    'SELECT * FROM `prices` WHERE `cost` IN (?d, ?d)',
    12.56, '12.33'
);
```
SQL query after template conversion:
```sql
SELECT * FROM `prices` WHERE `cost` IN (12.56, 12.33)
```

**WARNING!** If you're using the library to work with the `double` data type, set the appropriate locale so that the decimal separator is the same at both the PHP level and the DBMS level.

#### `?s` — string type placeholder

Argument values are escaped using the `mysqli::real_escape_string()` method:

```php
$db->query(
    'SELECT "?s"',
    "You're all fools, and I'm d'Artagnan!"
);
```
SQL query after template conversion:

```sql
SELECT "You\'re all fools, and I\'m d\'Artagnan!"
```
#### `?S` — string type placeholder for substitution in the SQL LIKE operator

Argument values are escaped using the `mysqli::real_escape_string()` method + escaping of special characters used in the LIKE operator (`%` and `_`):

```php
$db->query('SELECT "?S"', '% _');
```
SQL query after template conversion:
```sql
SELECT "\% \_"
```

#### `?n` — `NULL` type placeholder
The value of any arguments is ignored; placeholders are replaced with the string `NULL` in the SQL query:

```php
$db->query('SELECT ?n', 123);
```
SQL query after template conversion:
```sql
SELECT NULL
```

#### `?A*` — associative set placeholder from an associative array, generating a sequence of `key = value` pairs

where the `*` character is one of the placeholders:

 * `i` (integer placeholder)
 * `d` (floating-point number placeholder)
 * `s` (string type placeholder)

the conversion and escaping rules are the same as for the single scalar types described above. Example:

```php
$db->query(
    'INSERT INTO `test` SET ?Ai',
    ['first' => '123', 'second' => 456]
);
```
SQL query after template conversion:
```sql
INSERT INTO `test` SET `first` = "123", `second` = "456"
```

#### `?a*` — set placeholder from a simple (or also associative) array, generating a sequence of values

where `*` is one of the types:
 * `i` (integer placeholder)
 * `d` (floating-point number placeholder)
 * `s` (string type placeholder)

the conversion and escaping rules are the same as for the single scalar types described above. Example:

```php
$db->query(
    'SELECT * FROM `test` WHERE `id` IN (?ai)',
    [123, 456]
);
```
SQL query after template conversion:
```sql
SELECT * FROM `test` WHERE `id` IN ("123", "456")
```


#### `?A[?n, ?s, ?i, ...]` — associative set placeholder with explicit type and argument count specification, generating a sequence of `key = value` pairs

Example:
```php
$db->query(
    'INSERT INTO `users` SET ?A[?i, "?s"]',
    ['age' => 41, 'name' => "d'Artagnan"]
);
```
SQL query after template conversion:
```sql
INSERT INTO `users` SET `age` = 41,`name` = "d\'Artagnan"
```

#### `?a[?n, ?s, ?i, ...]` — set placeholder with explicit type and argument count specification, generating a sequence of values
Example:
```php
$db->query(
    'SELECT * FROM `users` WHERE `name` IN (?a["?s", "?s"])',
    ["Marquis d\"Arcy", "d'Artagnan"]
);
```
SQL query after template conversion:
```sql
SELECT * FROM `users` WHERE `name` IN ("Marquis d\"Arcy", "d\'Artagnan")
```


#### `?f` — table or field name placeholder

This placeholder is intended for cases when the table or field name is passed in the query as a parameter. Field and table names are enclosed in backticks:

```php
$db->query(
    'SELECT ?f FROM ?f',
    'name',
    'database.table_name'
);
```
SQL query after template conversion:
```sql
SELECT `name` FROM `database`.`table_name`
```


### Delimiting Quotes

**The library requires the programmer to follow SQL syntax.** This means the following query will not work:

```php
$db->query(
    'SELECT CONCAT("Hello, ", ?s, "!")',
    'world'
);
```
— the `?s` placeholder must be enclosed in single or double quotes:
```php
$db->query(
    'SELECT concat("Hello, ", "?s", "!")',
    'world'
);
```
SQL query after template conversion:
```sql
SELECT concat("Hello, ", "world", "!")
```

For those accustomed to working with PDO, this will seem strange, but implementing a mechanism that determines whether to enclose a placeholder value in quotes or not is a very non-trivial task requiring writing an entire parser.


## Examples of Working with the Library

See the file [./console/tests.php](./console/tests.php)
