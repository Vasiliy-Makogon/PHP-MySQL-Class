**Andere Sprachen:**
- [English documentation](../README.md)
- [Русская документация](README_ru.md)
- [Documentation française](README_fr.md)
- [Documentazione italiana](README_it.md)
- [日本語ドキュメント](README_ja.md)
- [Documentación en español](README_es.md)
- [한국어 문서](README_ko.md)
- [简体中文文档](README_zh-CN.md)
- [繁體中文文件](README_zh-TW.md)
- [Dokumentasi Bahasa Indonesia](README_id.md)
- [Documentação em Português (BR)](README_pt-BR.md)
- [हिंदी दस्तावेज़](README_hi.md)
- [التوثيق بالعربية](README_ar.md)
- [Türkçe Dokümantasyon](README_tr.md)
- [Tài liệu tiếng Việt](README_vi.md)

---

## Bibliothek herunterladen

Sie können die Bibliothek [als Archiv herunterladen](https://github.com/Vasiliy-Makogon/Database/archive/master.zip), von dieser Seite klonen oder über Composer installieren ([Link zu packagist.org](https://packagist.org/packages/krugozor/database)):
```
composer require krugozor/database
```


## Was ist `krugozor/database`?

`krugozor/database` ist eine PHP >= 8.0-Klassenbibliothek für einfache, bequeme, schnelle und sichere Arbeit mit MySQL-Datenbanken unter Verwendung der PHP-Erweiterung [mysqli](https://www.php.net/manual/de/book.mysqli.php).


### Warum braucht man eine eigene Klasse für MySQL, wenn PHP bereits die PDO-Abstraktion und die mysqli-Erweiterung bietet?

Die Hauptnachteile aller Bibliotheken für die Arbeit mit MySQL-Datenbanken in PHP sind:

* **Wortreichtum**
  * Um SQL-Injektionen zu verhindern, haben Entwickler zwei Möglichkeiten:
    * Verwendung von [Prepared Statements](https://www.php.net/manual/de/mysqli.quickstart.prepared-statements.php).
    * Manuelle Maskierung von Parametern, die in den SQL-Query-Body gelangen. String-Parameter durch [mysqli_real_escape_string](https://www.php.net/manual/de/mysqli.real-escape-string.php) laufen lassen und erwartete numerische Parameter in entsprechende Typen umwandeln — `int` und `float`.
  * Beide Ansätze haben erhebliche Nachteile:
    * Prepared Statements sind [schrecklich wortreich](https://www.php.net/manual/de/mysqli.prepare.php#refsect1-mysqli.prepare-examples). Die "Out-of-the-Box"-Verwendung der PDO-Abstraktion oder der mysqli-Erweiterung ohne Aggregierung aller Methoden zum Abrufen von Daten aus dem DBMS ist einfach unmöglich — um einen Wert aus einer Tabelle zu erhalten, müssen mindestens 5 Zeilen Code geschrieben werden! Und das für jede Abfrage!
    * Manuelle Maskierung von Parametern, die in den SQL-Query-Body gelangen — wird nicht einmal diskutiert. Ein guter Programmierer ist ein fauler Programmierer. Alles sollte maximal automatisiert sein.
* **Unmöglichkeit, die SQL-Abfrage zum Debuggen zu erhalten**
  * Um zu verstehen, warum eine SQL-Abfrage im Programm nicht funktioniert, muss sie debuggt werden — entweder ein logischer oder ein Syntaxfehler muss gefunden werden. Um den Fehler zu finden, muss man die SQL-Abfrage selbst "sehen", über die die Datenbank "gemeckert" hat, mit den darin eingesetzten Parametern. Das heißt, man muss vollständiges SQL haben.
Wenn der Entwickler PDO mit Prepared Statements verwendet, ist dies... UNMÖGLICH! Es sind keine maximal bequemen Mechanismen dafür in den nativen Bibliotheken [VORGESEHEN](https://qna.habr.com/q/22669). Es bleibt nur übrig, sich zu verrenken oder ins Datenbanklog zu schauen.


### Lösung: `krugozor/database` — Klasse für die Arbeit mit MySQL

1. Beseitigt Wortreichtum — anstatt 3 oder mehr Zeilen Code für die Ausführung einer Abfrage bei Verwendung der "nativen" Bibliothek zu schreiben, schreiben Sie nur eine.
2. Maskiert alle Parameter, die in den Query-Body gelangen, entsprechend dem angegebenen Platzhaltertyp — zuverlässiger Schutz vor SQL-Injektionen.
3. Ersetzt nicht die Funktionalität des "nativen" mysqli-Adapters, sondern ergänzt ihn nur.
4. Erweiterbar. Im Wesentlichen bietet die Bibliothek nur einen Parser und die Ausführung von SQL-Abfragen mit garantiertem Schutz vor SQL-Injektionen. Sie können von jeder Klasse der Bibliothek erben und sowohl die Mechanismen der Bibliothek als auch die Mechanismen von `mysqli` und `mysqli_result` verwenden, um die benötigten Methoden zu erstellen.

### Was ist die Bibliothek `krugozor/database` NICHT?

Die meisten Wrapper für verschiedene Datenbanktreiber sind eine Ansammlung nutzlosen Codes mit abscheulicher Architektur. Ihre Autoren, die den praktischen Zweck ihrer Wrapper selbst nicht verstehen, verwandeln sie in Query-Builder, ActiveRecord-Bibliotheken und andere ORM-Lösungen.

Die Bibliothek `krugozor/database` ist nichts davon. Dies ist nur ein praktisches Werkzeug für die Arbeit mit normalem SQL im Rahmen des MySQL-DBMS — und nicht mehr!


## Was sind Placeholders (Platzhalter)?

**Placeholders** (Platzhalter) sind **spezielle typisierte Marker, die in der SQL-Abfragezeichenfolge *anstelle von expliziten Werten (Abfrageparametern)* geschrieben werden**. Die Werte selbst werden "später" als nachfolgende Argumente der Hauptmethode übergeben, die die SQL-Abfrage ausführt:

```php
$result = $db->query(
    "SELECT * FROM `users` WHERE `name` = '?s' AND `age` = ?i",
    "D'Artagnan", 41
);
```

SQL-Abfrageparameter, die das *Placeholders*-System durchlaufen haben, werden durch spezielle Maskierungsmechanismen verarbeitet, abhängig vom Platzhaltertyp. Das heißt, Sie müssen Variablen jetzt nicht mehr in Maskierungsfunktionen wie `mysqli_real_escape_string()` einschließen oder sie in numerische Typen umwandeln, wie es früher der Fall war:

```php
<?php
// Früher haben wir vor jeder Abfrage an das DBMS
// ungefähr das gemacht (und viele machen "das" immer noch nicht):
$id = (int) $_POST['id'];
$value = mysqli_real_escape_string($mysql, $_POST['value']);
$result = mysqli_query($mysql, "SELECT * FROM `t` WHERE `f1` = '$value' AND `f2` = $id");
```

Jetzt ist das Schreiben von Abfragen einfach, schnell geworden, und vor allem verhindert die Bibliothek `krugozor/database` vollständig alle möglichen SQL-Injektionen.


### Einführung in das Platzhaltersystem

Die Typen von Platzhaltern und ihre Zwecke werden unten beschrieben. Bevor Sie sich mit den Platzhaltertypen vertraut machen, müssen Sie verstehen, wie der Mechanismus der Bibliothek funktioniert.

#### Das PHP-Problem

PHP ist eine schwach typisierte Sprache, und bei der Entwicklung dieser Bibliothek entstand ein ideologisches Dilemma.
Stellen Sie sich vor, wir haben eine Tabelle mit folgender Struktur:

```sql
`name` varchar not null
`flag` tinyint not null
```
und die Bibliothek MUSS (aus irgendeinem Grund, möglicherweise unabhängig vom Entwickler) die folgende Abfrage ausführen:

```php
$db->query(
    "INSERT INTO `t` SET `name` = '?s', `flag` = ?i",
    null, false
);
```
In diesem Beispiel wird versucht, den Wert `null` in das Text-`not null`-Feld `name` zu schreiben,
und den booleschen Typ `false` in das numerische Feld `flag`. Was soll man in dieser Situation tun?

* Wer ist für die Validierung der Abfrageparameter verantwortlich - der Client-Code oder die Bibliothek?
* Muss in diesem Fall die Programmausführung unterbrochen werden, oder sollten vielleicht einige Manipulationen angewendet werden, damit die Daten in die Datenbank geschrieben werden?
* Können wir den Wert `false` für die `tinyint`-Spalte als Wert `0` interpretieren und `null` als leere Zeichenfolge für die `name`-Spalte?
* Wie können wir diese Problematik in unserem Code vereinfachen oder standardisieren?

Angesichts der aufgeworfenen Fragen wurde beschlossen, zwei Betriebsmodi in dieser Bibliothek zu implementieren.

### Betriebsmodi der Bibliothek

  * **Mysql::MODE_STRICT — strikter Modus der Übereinstimmung zwischen Platzhaltertyp und Argumenttyp**.
    Im Modus `Mysql::MODE_STRICT` *muss der Argumenttyp dem Platzhaltertyp entsprechen*. Zum Beispiel führt der Versuch, den Wert `55.5` oder `'55.5'` als Argument für den Integer-Platzhalter `?i` zu übergeben, zu einer Ausnahme:

```php
// strikten Modus einstellen
$db->setTypeMode(Mysql::MODE_STRICT);
// dieser Ausdruck wird nicht ausgeführt, eine Ausnahme wird ausgelöst:
// Versuch, für den Platzhalter vom Typ "integer" einen Wert vom Typ "double" im Abfrage-Template "SELECT ?i" anzugeben
$db->query('SELECT ?i', 55.5);
```

* **Mysql::MODE_TRANSFORM — Modus der Umwandlung des Arguments in den Platzhaltertyp bei Nichtübereinstimmung von Platzhaltertyp und Argumenttyp.** Der Modus `Mysql::MODE_TRANSFORM` ist standardmäßig eingestellt und ist ein "toleranter" Modus — bei Nichtübereinstimmung von Platzhaltertyp und Argumenttyp wird keine Ausnahme generiert, sondern *versucht, das Argument in den erforderlichen Platzhaltertyp mittels der PHP-Sprache selbst umzuwandeln*. Nebenbei gesagt, verwende ich als Autor der Bibliothek immer genau diesen Modus, den strikten Modus (`Mysql::MODE_STRICT`) habe ich in der realen Arbeit nie verwendet, aber vielleicht werden Sie ihn brauchen.

**Folgende Umwandlungen sind im Modus Mysql::MODE_TRANSFORM erlaubt:**

* **Zum Typ `int` (Platzhalter `?i`) werden umgewandelt**
  * Fließkommazahlen, sowohl im Typ `string` als auch im Typ `double` dargestellt
  * `bool` TRUE wird zu `int(1)`, FALSE wird zu `int(0)`
  * `null` wird zu `int(0)`
* **Zum Typ `double` (Platzhalter `?d`) werden umgewandelt**
  * Ganzzahlen, sowohl im Typ `string` als auch im Typ `int` dargestellt
  * `bool` TRUE wird zu `float(1)`, FALSE wird zu `float(0)`
  * `null` wird zu `float(0)`
* **Zum Typ `string` (Platzhalter `?s`) werden umgewandelt**
  * `bool` TRUE wird zu `string(1) "1"`, FALSE wird zu `string(1) "0"`. Dieses Verhalten unterscheidet sich von der Umwandlung des Typs `bool` in `int` in PHP, da in der Praxis oft der boolesche Typ in MySQL genau als Zahl geschrieben wird.
  * Wert vom Typ `numeric` wird in eine Zeichenfolge gemäß den PHP-Umwandlungsregeln umgewandelt
  * `null` wird zu `string(0) ""`
* **Zum Typ `null` (Platzhalter `?n`) werden umgewandelt**
  * beliebige Argumente.
* Für Arrays, Objekte und Ressourcen sind Umwandlungen nicht erlaubt.


### Welche Platzhaltertypen bietet die Bibliothek?


#### `?i` — Platzhalter für Ganzzahlen

```php
$db->query(
    'SELECT * FROM `users` WHERE `id` = ?i', 123
);
```
SQL-Abfrage nach Umwandlung des Templates:
```sql
SELECT * FROM `users` WHERE `id` = 123
```

**ACHTUNG!** Wenn Sie mit Zahlen arbeiten, die über `PHP_INT_MAX` hinausgehen, dann:
* Verwenden Sie sie ausschließlich als Zeichenfolgen in Ihren Programmen.
* Verwenden Sie diesen Platzhalter nicht, verwenden Sie den String-Platzhalter `?s` (siehe unten). Die Sache ist, dass PHP Zahlen, die über `PHP_INT_MAX` hinausgehen, als Fließkommazahlen interpretiert. Der Parser der Bibliothek wird versuchen, den Parameter in den Typ `int` umzuwandeln, was dazu führt, dass «*das Ergebnis undefiniert sein wird, da float nicht genug Präzision hat, um das korrekte Ergebnis zurückzugeben. In diesem Fall wird weder eine Warnung noch ein Hinweis ausgegeben!*» — [php.net](https://www.php.net/manual/de/language.types.integer.php#language.types.integer.casting.from-float).

#### `?d` — Platzhalter für Fließkommazahlen

```php
$db->query(
    'SELECT * FROM `prices` WHERE `cost` IN (?d, ?d)',
    12.56, '12.33'
);
```
SQL-Abfrage nach Umwandlung des Templates:
```sql
SELECT * FROM `prices` WHERE `cost` IN (12.56, 12.33)
```

**ACHTUNG!** Wenn Sie die Bibliothek für die Arbeit mit dem Datentyp `double` verwenden, setzen Sie die entsprechende Locale, damit das Trennzeichen zwischen Ganzzahl- und Bruchteil sowohl auf PHP- als auch auf DBMS-Ebene gleich ist.

#### `?s` — Platzhalter für String-Typ

Argumentwerte werden mit der Methode `mysqli::real_escape_string()` maskiert:

```php
$db->query(
    'SELECT "?s"',
    "Ihr seid alle Narren, und ich bin D'Artagnan!"
);
```
 SQL-Abfrage nach Umwandlung des Templates:

```sql
SELECT "Ihr seid alle Narren, und ich bin D\'Artagnan!"
```
#### `?S` — Platzhalter für String-Typ zur Verwendung im SQL-Operator LIKE

Argumentwerte werden mit der Methode `mysqli::real_escape_string()` maskiert + Maskierung von Sonderzeichen, die im LIKE-Operator verwendet werden (`%` und `_`):

```php
$db->query('SELECT "?S"', '% _');
```
 SQL-Abfrage nach Umwandlung des Templates:
```sql
SELECT "\% \_"
```

 #### `?n` — Platzhalter für `NULL`-Typ
Die Werte beliebiger Argumente werden ignoriert, Platzhalter werden durch die Zeichenfolge `NULL` in der SQL-Abfrage ersetzt:

```php
$db->query('SELECT ?n', 123);
```
 SQL-Abfrage nach Umwandlung des Templates:
```sql
SELECT NULL
```

 #### `?A*` — Platzhalter für assoziative Menge aus assoziativem Array, generiert eine Sequenz von Paaren der Form `Schlüssel = Wert`

wobei das Symbol `*` einer der Platzhalter ist:

 * `i` (Platzhalter für Ganzzahlen)
 * `d` (Platzhalter für Fließkommazahlen)
 * `s` (Platzhalter für String-Typ)

 Die Umwandlungs- und Maskierungsregeln sind dieselben wie für die einzelnen skalaren Typen, die oben beschrieben wurden. Beispiel:

```php
$db->query(
    'INSERT INTO `test` SET ?Ai',
    ['first' => '123', 'second' => 456]
);
```
SQL-Abfrage nach Umwandlung des Templates:
```sql
INSERT INTO `test` SET `first` = "123", `second` = "456"
```

#### `?a*` — Platzhalter für Menge aus einfachem (oder auch assoziativem) Array, generiert eine Sequenz von Werten

 wobei `*` einer der Typen ist:
 * `i` (Platzhalter für Ganzzahlen)
 * `d` (Platzhalter für Fließkommazahlen)
 * `s` (Platzhalter für String-Typ)

 Die Umwandlungs- und Maskierungsregeln sind dieselben wie für die einzelnen skalaren Typen, die oben beschrieben wurden. Beispiel:

```php
$db->query(
    'SELECT * FROM `test` WHERE `id` IN (?ai)',
    [123, 456]
);
```
SQL-Abfrage nach Umwandlung des Templates:
```sql
SELECT * FROM `test` WHERE `id` IN ("123", "456")
```


#### `?A[?n, ?s, ?i, ...]` — Platzhalter für assoziative Menge mit expliziter Angabe von Typ und Anzahl der Argumente, generiert eine Sequenz von Paaren `Schlüssel = Wert`

Beispiel:
```php
$db->query(
    'INSERT INTO `users` SET ?A[?i, "?s"]',
    ['age' => 41, 'name' => "D'Artagnan"]
);
```
SQL-Abfrage nach Umwandlung des Templates:
```sql
INSERT INTO `users` SET `age` = 41,`name` = "D\'Artagnan"
```

#### `?a[?n, ?s, ?i, ...]` — Platzhalter für Menge mit expliziter Angabe von Typ und Anzahl der Argumente, generiert eine Sequenz von Werten
Beispiel:
```php
$db->query(
    'SELECT * FROM `users` WHERE `name` IN (?a["?s", "?s"])',
    ["Marquis d\"Arquien", "D'Artagnan"]
);
```
SQL-Abfrage nach Umwandlung des Templates:
```sql
SELECT * FROM `users` WHERE `name` IN ("Marquis d\"Arquien", "D\'Artagnan")
```


#### `?f` — Platzhalter für Tabellen- oder Feldnamen

Dieser Platzhalter ist für Fälle gedacht, in denen der Tabellen- oder Feldname in der Abfrage über einen Parameter übergeben wird. Feld- und Tabellennamen werden mit dem "Backtick"-Symbol umrahmt:

```php
$db->query(
    'SELECT ?f FROM ?f',
    'name',
    'database.table_name'
);
```
 SQL-Abfrage nach Umwandlung des Templates:
```sql
SELECT `name` FROM `database`.`table_name`
```


### Begrenzende Anführungszeichen

**Die Bibliothek erfordert vom Programmierer die Einhaltung der SQL-Syntax.** Das bedeutet, dass die folgende Abfrage nicht funktionieren wird:

```php
$db->query(
    'SELECT CONCAT("Hello, ", ?s, "!")',
    'world'
);
```
— der Platzhalter `?s` muss in einfache oder doppelte Anführungszeichen gesetzt werden:
```php
$db->query(
    'SELECT concat("Hello, ", "?s", "!")',
    'world'
);
```
SQL-Abfrage nach Umwandlung des Templates:
```sql
SELECT concat("Hello, ", "world", "!")
```

Für diejenigen, die an die Arbeit mit PDO gewöhnt sind, mag dies seltsam erscheinen, aber die Implementierung eines Mechanismus, der bestimmt, ob in einem Fall der Platzhalterwert in Anführungszeichen gesetzt werden muss oder nicht, ist eine sehr nicht triviale Aufgabe, die das Schreiben eines vollständigen Parsers erfordert.


## Beispiele für die Arbeit mit der Bibliothek

Siehe in der Datei [../console/tests.php](../console/tests.php)
