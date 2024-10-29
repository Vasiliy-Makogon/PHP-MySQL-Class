<?php

use Krugozor\Database\Mysql;
use Krugozor\Database\Statement;
use Krugozor\Database\MySqlException;

// This is a file to understand the essence of the library. You can run it many times from the console.

foreach (glob(dirname(dirname(__FILE__)) . "/src/*.php") as $filename) {
    require_once $filename;
}

// Your data for connecting to the MYSQL server
const MYSQL_SERVER = 'localhost';
const MYSQL_USER = 'root';
const MYSQL_PASSWORD = '';
const MYSQL_PORT = 3306;
// The test will create a database.
// Make sure your mysql user has the rights to create databases and tables (CREATE privilege)
// https://dev.mysql.com/doc/refman/8.4/en/privileges-provided.html#priv_create
const TEMPORARY_DATABASE_NAME = 'krugozor_database_test';

// In the same case as with \mysqli, you can not specify the connection parameters in the constructor,
// but rely on the mysqli configuration (https://www.php.net/manual/ru/mysqli.configuration.php), i.e.:
// ini_set('mysqli.default_host', 'localhost');
// ini_set('mysqli.default_user', 'root');
// ini_set('mysqli.default_pw', '');
// ini_set('mysqli.default_port', 3306);
// ini_set('mysqli.default_socket', null);

try {
    $db = Mysql::create(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_PORT);

    $db
        // Error output language
        ->setErrorMessagesLang('ru')
        // Setting the encoding
        ->setCharset("utf8")
        // Enable storage of executed queries for reporting/debugging/statistics
        ->setStoreQueries(true)
        // Create testing database...
        ->query('CREATE DATABASE IF NOT EXISTS ?f', TEMPORARY_DATABASE_NAME);
    // ...use this database
    $db->setDatabaseName(TEMPORARY_DATABASE_NAME);


    // A clear example of two modes of library operation:

    // 1. Mysql::MODE_TRANSFORM mode
    $db->setTypeMode(Mysql::MODE_TRANSFORM);

    // Result: 8 - simple addition of two integers
    $result = $db->query('SELECT ?i + ?i', 3, 5);
    echo "{$db->getQueryString()} ({$result->getOne()})" . PHP_EOL;

    // Result: also 8, because the value 3.5 was cast to type int,
    // because the Mysql::MODE_TRANSFORM mode is activated by default
    // and the value 3.5 was cast to type int by the PHP language itself
    $result = $db->query('SELECT ?i + ?i', 3.5, 5);
    echo "{$db->getQueryString()} ({$result->getOne()})" . PHP_EOL;

    // Result: 1, because null is cast to 0, true is cast to 1
    $result = $db->query('SELECT ?i + ?i', null, true);
    echo "{$db->getQueryString()} ({$result->getOne()})" . PHP_EOL;

    // Result: SELECT "0", "", "0.001"
    $result = $db->query('SELECT "?s", "?s", "?s"', false, null, 0.001);
    echo "{$db->getQueryString()}" . PHP_EOL;
    echo PHP_EOL;


    // 2. Mysql::MODE_STRICT mode
    $db->setTypeMode(Mysql::MODE_STRICT);

    // Result: 8.5
    $result = $db->query('SELECT ?d + ?i', 3.5, 5);
    echo "{$db->getQueryString()} ({$result->getOne()})" . PHP_EOL;

    // Result: 8.5, despite the Mysql::MODE_STRICT mode, strings and numbers in the arguments
    // are interpreted correctly by the library, since they are floating-point numbers
    $result = $db->query('SELECT ?d + ?i', '3.5', 5);
    echo "{$db->getQueryString()} ({$result->getOne()})" . PHP_EOL;
    echo PHP_EOL;

    // In Mysql::MODE_STRICT mode such tricks will not work, an exception with the error will be thrown
    try {
        $db->query('SELECT "?i", "?i", "?s"', '33.5', 12.1, false);
    } catch (MysqlException $e) {
        echo $e->getMessage() . PHP_EOL . PHP_EOL;
    }


    // Examples with insertion and selection of data:

    // Creating a table
    $db->query('
        CREATE TABLE IF NOT EXISTS test (
            id int unsigned not null primary key auto_increment,
            name varchar(50) not null,
            age int not null
        );
    ');
    // Let's clear it, because you might run this script more than once.
    $db->query('TRUNCATE TABLE `test`');
    // Let's return the operating mode Mysql::MODE_TRANSFORM
    $db->setTypeMode(Mysql::MODE_TRANSFORM);


    // Inserting data using different methods:

    // Easy data insertion through different types of placeholders:
    $db->query("INSERT INTO `test` VALUES (null, '?s', ?i)", 'Ivan the Terrible', '54');
    echo "{$db->getQueryString()} (inserted rows: {$db->getAffectedRows()})" . PHP_EOL;

    // Inserting values via an associative set placeholder of type 'string':
    $data = ['name' => "D'Artagnan", 'age' => '19'];
    $db->query('INSERT INTO `test` SET ?As', $data);
    echo "{$db->getQueryString()} (inserted rows: {$db->getAffectedRows()})" . PHP_EOL;

    // Inserting values via an associative set placeholder with explicitly
    // specifying the type and number of arguments:
    $data = ['name' => "%%% Joseph Stalin %%%", 'age' => '56'];
    $db->query('INSERT INTO `test` SET ?A["?s", ?i]', $data);
    echo "{$db->getQueryString()} (inserted rows: {$db->getAffectedRows()})" . PHP_EOL;
    echo PHP_EOL;


    // Data selection:

    // Simple data selection:
    $result = $db->query('SELECT `name` FROM `test` WHERE `name` = "?s"', "D'Artagnan");
    echo "{$db->getQueryString()} ({$result->getOne()})" . PHP_EOL;

    // A normal selection, but the name of the fields and table is also passed through the placeholder:
    $result = $db->query(
        'SELECT ?f FROM ?f WHERE ?f = "?s"',
        'name', TEMPORARY_DATABASE_NAME . '.test', 'id', 1
    );
    echo "{$db->getQueryString()} ({$result->getOne()})" . PHP_EOL;

    // LIKE search. Please note that the special character % is intentionally present in the name
    // of Joseph Stalin - it will be correctly escaped:
    $result = $db->query('SELECT `name` FROM `test` WHERE `name` LIKE "%?S%"', "%");
    echo "{$db->getQueryString()} ({$result->getOne()})" . PHP_EOL;
    echo PHP_EOL;

    // The null-type placeholder ignores the argument:
    $result = $db->query('SELECT ?n', 123);
    echo "{$db->getQueryString()} ({$result->getOne()})" . PHP_EOL;
    echo PHP_EOL;

    // Using the queryArguments() method - arguments are passed as an array.
    // This is the second method for querying the database after the query() method:
    $sql = 'SELECT * FROM `test` WHERE `name` like "%?s%" OR `name` = "?s"';
    $arguments[] = "Stalin";
    $arguments[] = "D'Artagnan";
    $result = $db->queryArguments($sql, $arguments);
    echo "{$db->getQueryString()} (inserted rows: {$result->getNumRows()}):" . PHP_EOL;
    foreach ($result->fetchAssocArray() as $data) {
        print_r($data);
    }
    echo PHP_EOL;

    // Let's get all executed requests of the current connection:
    print_r($db->getQueries());
    echo PHP_EOL;

    // Let's delete the test database:
    $db->query('DROP DATABASE ?f', TEMPORARY_DATABASE_NAME);

    echo $db->query('SELECT "Good by!"')->getOne();
    echo PHP_EOL;
} catch (MySqlException $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}