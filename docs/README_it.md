**Altre lingue:**
- [English documentation](../README.md)
- [Русская документация](README_ru.md)
- [Documentation française](README_fr.md)
- [Deutsche Dokumentation](README_de.md)
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

## Ottenere la libreria

Potete [scaricarla come archivio](https://github.com/Vasiliy-Makogon/Database/archive/master.zip), clonarla da questo sito o installarla tramite composer ([link a packagist.org](https://packagist.org/packages/krugozor/database)):
```
composer require krugozor/database
```


## Cos'è `krugozor/database`?

`krugozor/database` è una libreria di classi PHP >= 8.0 per un lavoro semplice, comodo, veloce e sicuro con il database MySQL, utilizzando l'estensione PHP [mysqli](https://www.php.net/manual/it/book.mysqli.php).


### Perché serve una classe personalizzata per MySQL se PHP ha già l'astrazione PDO e l'estensione mysqli?

I principali svantaggi di tutte le librerie per lavorare con database MySQL in PHP sono:

* **Verbosità**
  * Per prevenire le SQL injection, gli sviluppatori hanno due vie:
    * Utilizzare [prepared statements](https://www.php.net/manual/it/mysqli.quickstart.prepared-statements.php).
    * Eseguire manualmente l'escape dei parametri che vanno nel corpo della query SQL. Far passare i parametri stringa attraverso [mysqli_real_escape_string](https://www.php.net/manual/it/mysqli.real-escape-string.php) e convertire i parametri numerici attesi nei tipi corrispondenti — `int` e `float`.
  * Entrambi gli approcci hanno svantaggi colossali:
    * I prepared statements sono [terribilmente verbosi](https://www.php.net/manual/it/mysqli.prepare.php#refsect1-mysqli.prepare-examples). Usare "out of the box" l'astrazione PDO o l'estensione mysqli, senza aggregare tutti i metodi per ottenere dati dal DBMS è semplicemente impossibile — per ottenere un valore da una tabella è necessario scrivere almeno 5 righe di codice! E così per ogni query!
    * L'escape manuale dei parametri che vanno nel corpo della query SQL — non è nemmeno discusso. Un buon programmatore è un programmatore pigro. Tutto dovrebbe essere automatizzato al massimo.
* **Impossibilità di ottenere la query SQL per il debug**
  * Per capire perché una query SQL non funziona nel programma, è necessario fare il debug — trovare un errore logico o sintattico. Per trovare l'errore, è necessario "vedere" la query SQL stessa, su cui il database "si è lamentato", con i parametri sostituiti nel suo corpo. Cioè, avere SQL completo e formato.
Se lo sviluppatore usa PDO con prepared statements, questo è... IMPOSSIBILE! Nessun meccanismo massimamente comodo per questo è [PREVISTO](https://qna.habr.com/q/22669) nelle librerie native. Rimane solo fare contorsioni o guardare nel log del database.


### Soluzione: `krugozor/database` — classe per lavorare con MySQL

1. Elimina la verbosità — invece di 3 o più righe di codice per eseguire una query quando si utilizza la libreria "nativa", ne scrivi solo una.
2. Esegue l'escape di tutti i parametri che vanno nel corpo della query, secondo il tipo di placeholder specificato — protezione affidabile dalle SQL injection.
3. Non sostituisce la funzionalità dell'adattatore mysqli "nativo", ma semplicemente la integra.
4. Estensibile. In sostanza, la libreria fornisce solo un parser e l'esecuzione di query SQL con protezione garantita dalle SQL injection. Potete ereditare da qualsiasi classe della libreria e, utilizzando sia i meccanismi della libreria che i meccanismi di `mysqli` e `mysqli_result`, creare i metodi necessari per lavorare.

### Cosa NON è la libreria `krugozor/database`?

La maggior parte dei wrapper per vari driver di database sono un ammasso di codice inutile con un'architettura orribile. I loro autori, non comprendendo essi stessi lo scopo pratico dei loro wrapper, li trasformano in query builder, librerie ActiveRecord e altre soluzioni ORM.

La libreria `krugozor/database` non è niente di tutto questo. È solo uno strumento comodo per lavorare con SQL normale nel contesto del DBMS MySQL — e niente di più!


## Cosa sono i placeholders (segnaposto)?

I **placeholders** (segnaposto) sono **speciali marcatori tipizzati che si scrivono nella stringa della query SQL *al posto di valori espliciti (parametri della query)***. I valori stessi vengono passati "dopo", come argomenti successivi del metodo principale che esegue la query SQL:

```php
$result = $db->query(
    "SELECT * FROM `users` WHERE `name` = '?s' AND `age` = ?i",
    "D'Artagnan", 41
);
```

I parametri della query SQL che hanno attraversato il sistema di *placeholders* vengono elaborati da speciali meccanismi di escape, a seconda del tipo di placeholder. Cioè, ora non è più necessario racchiudere le variabili in funzioni di escape come `mysqli_real_escape_string()` o convertirle in tipo numerico, come si faceva prima:

```php
<?php
// Prima, prima di ogni query al DBMS facevamo
// circa questo (e molti ancora non fanno "questo"):
$id = (int) $_POST['id'];
$value = mysqli_real_escape_string($mysql, $_POST['value']);
$result = mysqli_query($mysql, "SELECT * FROM `t` WHERE `f1` = '$value' AND `f2` = $id");
```

Ora scrivere query è diventato facile, veloce e, soprattutto, la libreria `krugozor/database` previene completamente tutte le possibili SQL injection.


### Introduzione al sistema di placeholders

I tipi di placeholders e il loro scopo sono descritti di seguito. Prima di familiarizzare con i tipi di placeholders, è necessario comprendere come funziona il meccanismo della libreria.

#### Il problema di PHP

PHP è un linguaggio debolmente tipizzato e durante lo sviluppo di questa libreria è sorto un dilemma ideologico.
Immaginiamo di avere una tabella con la seguente struttura:

```sql
`name` varchar not null
`flag` tinyint not null
```
e la libreria DEVE (per qualche motivo, forse non dipendente dallo sviluppatore) eseguire la seguente query:

```php
$db->query(
    "INSERT INTO `t` SET `name` = '?s', `flag` = ?i",
    null, false
);
```
In questo esempio, si tenta di scrivere il valore `null` nel campo testuale `not null` `name`,
e il tipo booleano `false` nel campo numerico `flag`. Come comportarsi in questa situazione?

* Chi è responsabile della validazione dei parametri della query - il codice client o la libreria?
* È necessario in questo caso interrompere l'esecuzione del programma o, forse, applicare qualche manipolazione affinché i dati vengano scritti nel database?
* Possiamo interpretare il valore `false` per la colonna `tinyint` come valore `0` e `null` come stringa vuota per la colonna `name`?
* Come possiamo semplificare o standardizzare nel nostro codice questa problematica?

In vista delle questioni sollevate, si è deciso di implementare in questa libreria due modalità di funzionamento.

### Modalità di funzionamento della libreria

  * **Mysql::MODE_STRICT — modalità rigorosa di corrispondenza tra tipo di placeholder e tipo di argomento**.
    Nella modalità `Mysql::MODE_STRICT` *il tipo di argomento deve corrispondere al tipo di placeholder*. Ad esempio, il tentativo di passare come argomento il valore `55.5` o `'55.5'` per il placeholder di tipo intero `?i` genererà un'eccezione:

```php
// impostiamo la modalità rigorosa
$db->setTypeMode(Mysql::MODE_STRICT);
// questa espressione non verrà eseguita, verrà generata un'eccezione:
// tentativo di specificare per il placeholder di tipo "integer" un valore di tipo "double" nel template della query "SELECT ?i"
$db->query('SELECT ?i', 55.5);
```

* **Mysql::MODE_TRANSFORM — modalità di conversione dell'argomento al tipo di placeholder in caso di mancata corrispondenza tra tipo di placeholder e tipo di argomento.** La modalità `Mysql::MODE_TRANSFORM` è impostata di default ed è una modalità "tollerante" — in caso di mancata corrispondenza tra tipo di placeholder e tipo di argomento non genera un'eccezione, ma *tenta di convertire l'argomento nel tipo di placeholder richiesto tramite il linguaggio PHP stesso*. A proposito, io, come autore della libreria, uso sempre proprio questa modalità, la modalità rigorosa (`Mysql::MODE_STRICT`) non l'ho mai usata nel lavoro reale, ma forse proprio a voi servirà.

**Sono ammesse le seguenti conversioni nella modalità Mysql::MODE_TRANSFORM:**

* **Al tipo `int` (placeholder `?i`) vengono convertiti**
  * numeri a virgola mobile, rappresentati sia nel tipo `string` che nel tipo `double`
  * `bool` TRUE viene convertito in `int(1)`, FALSE viene convertito in `int(0)`
  * `null` viene convertito in `int(0)`
* **Al tipo `double` (placeholder `?d`) vengono convertiti**
  * numeri interi, rappresentati sia nel tipo `string` che nel tipo `int`
  * `bool` TRUE viene convertito in `float(1)`, FALSE viene convertito in `float(0)`
  * `null` viene convertito in `float(0)`
* **Al tipo `string` (placeholder `?s`) vengono convertiti**
  * `bool` TRUE viene convertito in `string(1) "1"`, FALSE viene convertito in `string(1) "0"`. Questo comportamento differisce dalla conversione del tipo `bool` in `int` in PHP, poiché spesso, nella pratica, il tipo booleano viene scritto in MySQL proprio come numero.
  * valore di tipo `numeric` viene convertito in stringa secondo le regole di conversione di PHP
  * `null` viene convertito in `string(0) ""`
* **Al tipo `null` (placeholder `?n`) vengono convertiti**
  * qualsiasi argomento.
* Per array, oggetti e risorse non sono ammesse conversioni.


### Quali tipi di placeholders offre la libreria?


#### `?i` — placeholder per numeri interi

```php
$db->query(
    'SELECT * FROM `users` WHERE `id` = ?i', 123
);
```
Query SQL dopo la conversione del template:
```sql
SELECT * FROM `users` WHERE `id` = 123
```

**ATTENZIONE!** Se lavorate con numeri che superano `PHP_INT_MAX`, allora:
* Operateli esclusivamente come stringhe nei vostri programmi.
* Non usate questo placeholder, usate il placeholder stringa `?s` (vedi sotto). Il fatto è che i numeri che superano `PHP_INT_MAX`, PHP li interpreta come numeri a virgola mobile. Il parser della libreria tenterà di convertire il parametro al tipo `int`, di conseguenza «*il risultato sarà indefinito, poiché float non ha sufficiente precisione per restituire il risultato corretto. In questo caso non verrà emesso né un avviso né un notice!*» — [php.net](https://www.php.net/manual/it/language.types.integer.php#language.types.integer.casting.from-float).

#### `?d` — placeholder per numeri a virgola mobile

```php
$db->query(
    'SELECT * FROM `prices` WHERE `cost` IN (?d, ?d)',
    12.56, '12.33'
);
```
Query SQL dopo la conversione del template:
```sql
SELECT * FROM `prices` WHERE `cost` IN (12.56, 12.33)
```

**ATTENZIONE!** Se utilizzate la libreria per lavorare con il tipo di dati `double`, impostate la locale appropriata affinché il separatore tra parte intera e frazionaria sia uguale sia a livello PHP che a livello DBMS.

#### `?s` — placeholder per tipo stringa

I valori degli argomenti vengono sottoposti a escape con il metodo `mysqli::real_escape_string()`:

```php
$db->query(
    'SELECT "?s"',
    "Siete tutti sciocchi, e io sono D'Artagnan!"
);
```
 Query SQL dopo la conversione del template:

```sql
SELECT "Siete tutti sciocchi, e io sono D\'Artagnan!"
```
#### `?S` — placeholder per tipo stringa per l'inserimento nell'operatore SQL LIKE

I valori degli argomenti vengono sottoposti a escape con il metodo `mysqli::real_escape_string()` + escape dei caratteri speciali utilizzati nell'operatore LIKE (`%` e `_`):

```php
$db->query('SELECT "?S"', '% _');
```
 Query SQL dopo la conversione del template:
```sql
SELECT "\% \_"
```

 #### `?n` — placeholder per tipo `NULL`
I valori di qualsiasi argomento vengono ignorati, i placeholders vengono sostituiti con la stringa `NULL` nella query SQL:

```php
$db->query('SELECT ?n', 123);
```
 Query SQL dopo la conversione del template:
```sql
SELECT NULL
```

 #### `?A*` — placeholder per insieme associativo da array associativo, genera una sequenza di coppie nella forma `chiave = valore`

dove il simbolo `*` è uno dei placeholders:

 * `i` (placeholder per numeri interi)
 * `d` (placeholder per numeri a virgola mobile)
 * `s` (placeholder per tipo stringa)

 le regole di conversione ed escape sono le stesse dei tipi scalari singoli descritti sopra. Esempio:

```php
$db->query(
    'INSERT INTO `test` SET ?Ai',
    ['first' => '123', 'second' => 456]
);
```
Query SQL dopo la conversione del template:
```sql
INSERT INTO `test` SET `first` = "123", `second` = "456"
```

#### `?a*` — placeholder per insieme da array semplice (o anche associativo), genera una sequenza di valori

 dove `*` è uno dei tipi:
 * `i` (placeholder per numeri interi)
 * `d` (placeholder per numeri a virgola mobile)
 * `s` (placeholder per tipo stringa)

 le regole di conversione ed escape sono le stesse dei tipi scalari singoli descritti sopra. Esempio:

```php
$db->query(
    'SELECT * FROM `test` WHERE `id` IN (?ai)',
    [123, 456]
);
```
Query SQL dopo la conversione del template:
```sql
SELECT * FROM `test` WHERE `id` IN ("123", "456")
```


#### `?A[?n, ?s, ?i, ...]` — placeholder per insieme associativo con indicazione esplicita di tipo e numero di argomenti, genera una sequenza di coppie `chiave = valore`

Esempio:
```php
$db->query(
    'INSERT INTO `users` SET ?A[?i, "?s"]',
    ['age' => 41, 'name' => "D'Artagnan"]
);
```
Query SQL dopo la conversione del template:
```sql
INSERT INTO `users` SET `age` = 41,`name` = "D\'Artagnan"
```

#### `?a[?n, ?s, ?i, ...]` — placeholder per insieme con indicazione esplicita di tipo e numero di argomenti, genera una sequenza di valori
Esempio:
```php
$db->query(
    'SELECT * FROM `users` WHERE `name` IN (?a["?s", "?s"])',
    ["marchese d\"Arquien", "D'Artagnan"]
);
```
Query SQL dopo la conversione del template:
```sql
SELECT * FROM `users` WHERE `name` IN ("marchese d\"Arquien", "D\'Artagnan")
```


#### `?f` — placeholder per nome di tabella o campo

Questo placeholder è destinato ai casi in cui il nome della tabella o del campo viene passato nella query tramite parametro. I nomi dei campi e delle tabelle vengono racchiusi tra il simbolo "backtick":

```php
$db->query(
    'SELECT ?f FROM ?f',
    'name',
    'database.table_name'
);
```
 Query SQL dopo la conversione del template:
```sql
SELECT `name` FROM `database`.`table_name`
```


### Virgolette delimitatrici

**La libreria richiede al programmatore il rispetto della sintassi SQL.** Questo significa che la seguente query non funzionerà:

```php
$db->query(
    'SELECT CONCAT("Hello, ", ?s, "!")',
    'world'
);
```
— il placeholder `?s` deve essere racchiuso tra virgolette singole o doppie:
```php
$db->query(
    'SELECT concat("Hello, ", "?s", "!")',
    'world'
);
```
Query SQL dopo la conversione del template:
```sql
SELECT concat("Hello, ", "world", "!")
```

Per chi è abituato a lavorare con PDO questo potrà sembrare strano, ma implementare un meccanismo che determini se in un caso sia necessario racchiudere il valore del placeholder tra virgolette o no è un compito molto non banale che richiede la scrittura di un parser completo.


## Esempi di lavoro con la libreria

Vedere nel file [../console/tests.php](../console/tests.php)
