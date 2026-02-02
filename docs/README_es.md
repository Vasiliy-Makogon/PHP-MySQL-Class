**Otros idiomas:**
- [English documentation](../README.md)
- [Русская документация](README_ru.md)
- [Documentation française](README_fr.md)
- [Deutsche Dokumentation](README_de.md)
- [Documentazione italiana](README_it.md)
- [日本語ドキュメント](README_ja.md)
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

## Obtener la biblioteca

Puede [descargarla como archivo](https://github.com/Vasiliy-Makogon/Database/archive/master.zip), clonarla desde este sitio o instalarla mediante composer ([enlace a packagist.org](https://packagist.org/packages/krugozor/database)):
```
composer require krugozor/database
```


## ¿Qué es `krugozor/database`?

`krugozor/database` es una biblioteca de clases PHP >= 8.0 para trabajar de manera simple, cómoda, rápida y segura con bases de datos MySQL, utilizando la extensión PHP [mysqli](https://www.php.net/manual/es/book.mysqli.php).


### ¿Por qué se necesita una clase personalizada para MySQL si PHP ya tiene la abstracción PDO y la extensión mysqli?

Las principales desventajas de todas las bibliotecas para trabajar con bases de datos MySQL en PHP son:

* **Verbosidad**
  * Para prevenir inyecciones SQL, los desarrolladores tienen dos caminos:
    * Utilizar [sentencias preparadas](https://www.php.net/manual/es/mysqli.quickstart.prepared-statements.php).
    * Escapar manualmente los parámetros que van al cuerpo de la consulta SQL. Pasar los parámetros de cadena a través de [mysqli_real_escape_string](https://www.php.net/manual/es/mysqli.real-escape-string.php) y convertir los parámetros numéricos esperados a los tipos correspondientes — `int` y `float`.
  * Ambos enfoques tienen desventajas colosales:
    * Las sentencias preparadas son [terriblemente verbosas](https://www.php.net/manual/es/mysqli.prepare.php#refsect1-mysqli.prepare-examples). Usar "fuera de la caja" la abstracción PDO o la extensión mysqli, sin agregar todos los métodos para obtener datos del DBMS es simplemente imposible — ¡para obtener un valor de una tabla es necesario escribir al menos 5 líneas de código! ¡Y así para cada consulta!
    * El escape manual de parámetros que van al cuerpo de la consulta SQL — ni siquiera se discute. Un buen programador es un programador perezoso. Todo debe estar automatizado al máximo.
* **Imposibilidad de obtener la consulta SQL para depuración**
  * Para entender por qué una consulta SQL no funciona en el programa, es necesario depurarla — encontrar un error lógico o sintáctico. Para encontrar el error, es necesario "ver" la consulta SQL misma, sobre la cual la base de datos "se quejó", con los parámetros sustituidos en su cuerpo. Es decir, tener SQL completo y formado.
Si el desarrollador usa PDO con sentencias preparadas, esto es... ¡IMPOSIBLE! No hay mecanismos máximamente convenientes para esto [PREVISTOS](https://qna.habr.com/q/22669) en las bibliotecas nativas. Solo queda hacer contorsiones o mirar en el registro de la base de datos.


### Solución: `krugozor/database` — clase para trabajar con MySQL

1. Elimina la verbosidad — en lugar de 3 o más líneas de código para ejecutar una consulta al usar la biblioteca "nativa", escribes solo una.
2. Escapa todos los parámetros que van al cuerpo de la consulta, según el tipo de marcador de posición especificado — protección confiable contra inyecciones SQL.
3. No reemplaza la funcionalidad del adaptador mysqli "nativo", sino que simplemente lo complementa.
4. Extensible. En esencia, la biblioteca proporciona solo un analizador y la ejecución de consultas SQL con protección garantizada contra inyecciones SQL. Puede heredar de cualquier clase de la biblioteca y, utilizando tanto los mecanismos de la biblioteca como los mecanismos de `mysqli` y `mysqli_result`, crear los métodos necesarios para trabajar.

### ¿Qué NO es la biblioteca `krugozor/database`?

La mayoría de los wrappers para varios controladores de bases de datos son un amontonamiento de código inútil con una arquitectura horrible. Sus autores, sin comprender ellos mismos el propósito práctico de sus wrappers, los convierten en constructores de consultas (sql builder), bibliotecas ActiveRecord y otras soluciones ORM.

La biblioteca `krugozor/database` no es nada de lo mencionado. ¡Es solo una herramienta conveniente para trabajar con SQL normal en el marco del DBMS MySQL — y nada más!


## ¿Qué son los placeholders (marcadores de posición)?

Los **placeholders** (marcadores de posición) son **marcadores tipados especiales que se escriben en la cadena de consulta SQL *en lugar de valores explícitos (parámetros de consulta)***. Los valores mismos se pasan "después", como argumentos subsiguientes del método principal que ejecuta la consulta SQL:

```php
$result = $db->query(
    "SELECT * FROM `users` WHERE `name` = '?s' AND `age` = ?i",
    "D'Artagnan", 41
);
```

Los parámetros de consulta SQL que han pasado por el sistema de *placeholders* son procesados por mecanismos especiales de escape, dependiendo del tipo de marcador de posición. Es decir, ahora no es necesario encerrar las variables en funciones de escape como `mysqli_real_escape_string()` o convertirlas a tipo numérico, como se hacía antes:

```php
<?php
// Antes, antes de cada consulta al DBMS hacíamos
// aproximadamente esto (y muchos todavía no hacen "esto"):
$id = (int) $_POST['id'];
$value = mysqli_real_escape_string($mysql, $_POST['value']);
$result = mysqli_query($mysql, "SELECT * FROM `t` WHERE `f1` = '$value' AND `f2` = $id");
```

Ahora escribir consultas se ha vuelto fácil, rápido y, lo más importante, la biblioteca `krugozor/database` previene completamente todas las posibles inyecciones SQL.


### Introducción al sistema de marcadores de posición

Los tipos de marcadores de posición y su propósito se describen a continuación. Antes de familiarizarse con los tipos de marcadores de posición, es necesario entender cómo funciona el mecanismo de la biblioteca.

#### El problema de PHP

PHP es un lenguaje débilmente tipado y durante el desarrollo de esta biblioteca surgió un dilema ideológico.
Imaginemos que tenemos una tabla con la siguiente estructura:

```sql
`name` varchar not null
`flag` tinyint not null
```
y la biblioteca DEBE (por alguna razón, posiblemente no dependiente del desarrollador) ejecutar la siguiente consulta:

```php
$db->query(
    "INSERT INTO `t` SET `name` = '?s', `flag` = ?i",
    null, false
);
```
En este ejemplo, se intenta escribir el valor `null` en el campo de texto `not null` `name`,
y el tipo booleano `false` en el campo numérico `flag`. ¿Qué hacer en esta situación?

* ¿Quién es responsable de la validación de los parámetros de consulta - el código del cliente o la biblioteca?
* ¿Es necesario en este caso interrumpir la ejecución del programa o, tal vez, aplicar algunas manipulaciones para que los datos se escriban en la base de datos?
* ¿Podemos interpretar el valor `false` para la columna `tinyint` como valor `0` y `null` como cadena vacía para la columna `name`?
* ¿Cómo podemos simplificar o estandarizar en nuestro código esta problemática?

En vista de las preguntas planteadas, se decidió implementar en esta biblioteca dos modos de funcionamiento.

### Modos de funcionamiento de la biblioteca

  * **Mysql::MODE_STRICT — modo estricto de correspondencia entre tipo de marcador de posición y tipo de argumento**.
    En el modo `Mysql::MODE_STRICT` *el tipo de argumento debe corresponder al tipo de marcador de posición*. Por ejemplo, el intento de pasar como argumento el valor `55.5` o `'55.5'` para el marcador de posición de tipo entero `?i` generará una excepción:

```php
// establecemos el modo estricto
$db->setTypeMode(Mysql::MODE_STRICT);
// esta expresión no se ejecutará, se lanzará una excepción:
// intento de especificar para el marcador de posición de tipo "integer" un valor de tipo "double" en la plantilla de consulta "SELECT ?i"
$db->query('SELECT ?i', 55.5);
```

* **Mysql::MODE_TRANSFORM — modo de conversión del argumento al tipo de marcador de posición en caso de no correspondencia entre tipo de marcador de posición y tipo de argumento.** El modo `Mysql::MODE_TRANSFORM` está establecido por defecto y es un modo "tolerante" — en caso de no correspondencia entre tipo de marcador de posición y tipo de argumento no genera una excepción, sino que *intenta convertir el argumento al tipo de marcador de posición requerido mediante el propio lenguaje PHP*. Por cierto, yo, como autor de la biblioteca, siempre uso precisamente este modo, el modo estricto (`Mysql::MODE_STRICT`) nunca lo he usado en el trabajo real, pero quizás justo a usted le será necesario.

**Se permiten las siguientes conversiones en el modo Mysql::MODE_TRANSFORM:**

* **Al tipo `int` (marcador de posición `?i`) se convierten**
  * números de punto flotante, representados tanto en tipo `string` como en tipo `double`
  * `bool` TRUE se convierte en `int(1)`, FALSE se convierte en `int(0)`
  * `null` se convierte en `int(0)`
* **Al tipo `double` (marcador de posición `?d`) se convierten**
  * números enteros, representados tanto en tipo `string` como en tipo `int`
  * `bool` TRUE se convierte en `float(1)`, FALSE se convierte en `float(0)`
  * `null` se convierte en `float(0)`
* **Al tipo `string` (marcador de posición `?s`) se convierten**
  * `bool` TRUE se convierte en `string(1) "1"`, FALSE se convierte en `string(1) "0"`. Este comportamiento difiere de la conversión del tipo `bool` a `int` en PHP, ya que a menudo, en la práctica, el tipo booleano se escribe en MySQL precisamente como número.
  * valor de tipo `numeric` se convierte en cadena según las reglas de conversión de PHP
  * `null` se convierte en `string(0) ""`
* **Al tipo `null` (marcador de posición `?n`) se convierten**
  * cualquier argumento.
* Para arrays, objetos y recursos no se permiten conversiones.


### ¿Qué tipos de marcadores de posición ofrece la biblioteca?


#### `?i` — marcador de posición para números enteros

```php
$db->query(
    'SELECT * FROM `users` WHERE `id` = ?i', 123
);
```
Consulta SQL después de la conversión de la plantilla:
```sql
SELECT * FROM `users` WHERE `id` = 123
```

**¡ATENCIÓN!** Si opera con números que exceden `PHP_INT_MAX`, entonces:
* Opérelos exclusivamente como cadenas en sus programas.
* No use este marcador de posición, use el marcador de posición de cadena `?s` (ver abajo). El hecho es que los números que exceden `PHP_INT_MAX`, PHP los interpreta como números de punto flotante. El analizador de la biblioteca intentará convertir el parámetro al tipo `int`, como resultado «*el resultado será indefinido, ya que float no tiene suficiente precisión para devolver el resultado correcto. ¡En este caso no se emitirá ni advertencia ni aviso!*» — [php.net](https://www.php.net/manual/es/language.types.integer.php#language.types.integer.casting.from-float).

#### `?d` — marcador de posición para números de punto flotante

```php
$db->query(
    'SELECT * FROM `prices` WHERE `cost` IN (?d, ?d)',
    12.56, '12.33'
);
```
Consulta SQL después de la conversión de la plantilla:
```sql
SELECT * FROM `prices` WHERE `cost` IN (12.56, 12.33)
```

**¡ATENCIÓN!** Si utiliza la biblioteca para trabajar con el tipo de datos `double`, establezca la configuración regional apropiada para que el separador entre parte entera y fraccionaria sea igual tanto a nivel de PHP como a nivel de DBMS.

#### `?s` — marcador de posición para tipo cadena

Los valores de los argumentos se escapan con el método `mysqli::real_escape_string()`:

```php
$db->query(
    'SELECT "?s"',
    "¡Todos ustedes son tontos, y yo soy D'Artagnan!"
);
```
 Consulta SQL después de la conversión de la plantilla:

```sql
SELECT "¡Todos ustedes son tontos, y yo soy D\'Artagnan!"
```
#### `?S` — marcador de posición para tipo cadena para inserción en el operador SQL LIKE

Los valores de los argumentos se escapan con el método `mysqli::real_escape_string()` + escape de caracteres especiales utilizados en el operador LIKE (`%` y `_`):

```php
$db->query('SELECT "?S"', '% _');
```
 Consulta SQL después de la conversión de la plantilla:
```sql
SELECT "\% \_"
```

 #### `?n` — marcador de posición para tipo `NULL`
Los valores de cualquier argumento se ignoran, los marcadores de posición se reemplazan con la cadena `NULL` en la consulta SQL:

```php
$db->query('SELECT ?n', 123);
```
 Consulta SQL después de la conversión de la plantilla:
```sql
SELECT NULL
```

 #### `?A*` — marcador de posición para conjunto asociativo de array asociativo, genera una secuencia de pares de la forma `clave = valor`

donde el símbolo `*` es uno de los marcadores de posición:

 * `i` (marcador de posición para números enteros)
 * `d` (marcador de posición para números de punto flotante)
 * `s` (marcador de posición para tipo cadena)

 las reglas de conversión y escape son las mismas que para los tipos escalares individuales descritos arriba. Ejemplo:

```php
$db->query(
    'INSERT INTO `test` SET ?Ai',
    ['first' => '123', 'second' => 456]
);
```
Consulta SQL después de la conversión de la plantilla:
```sql
INSERT INTO `test` SET `first` = "123", `second` = "456"
```

#### `?a*` — marcador de posición para conjunto de array simple (o también asociativo), genera una secuencia de valores

 donde `*` es uno de los tipos:
 * `i` (marcador de posición para números enteros)
 * `d` (marcador de posición para números de punto flotante)
 * `s` (marcador de posición para tipo cadena)

 las reglas de conversión y escape son las mismas que para los tipos escalares individuales descritos arriba. Ejemplo:

```php
$db->query(
    'SELECT * FROM `test` WHERE `id` IN (?ai)',
    [123, 456]
);
```
Consulta SQL después de la conversión de la plantilla:
```sql
SELECT * FROM `test` WHERE `id` IN ("123", "456")
```


#### `?A[?n, ?s, ?i, ...]` — marcador de posición para conjunto asociativo con indicación explícita de tipo y número de argumentos, genera una secuencia de pares `clave = valor`

Ejemplo:
```php
$db->query(
    'INSERT INTO `users` SET ?A[?i, "?s"]',
    ['age' => 41, 'name' => "D'Artagnan"]
);
```
Consulta SQL después de la conversión de la plantilla:
```sql
INSERT INTO `users` SET `age` = 41,`name` = "D\'Artagnan"
```

#### `?a[?n, ?s, ?i, ...]` — marcador de posición para conjunto con indicación explícita de tipo y número de argumentos, genera una secuencia de valores
Ejemplo:
```php
$db->query(
    'SELECT * FROM `users` WHERE `name` IN (?a["?s", "?s"])',
    ["marqués d\"Arquién", "D'Artagnan"]
);
```
Consulta SQL después de la conversión de la plantilla:
```sql
SELECT * FROM `users` WHERE `name` IN ("marqués d\"Arquién", "D\'Artagnan")
```


#### `?f` — marcador de posición para nombre de tabla o campo

Este marcador de posición está destinado para casos en que el nombre de tabla o campo se pasa en la consulta a través de un parámetro. Los nombres de campos y tablas se encierran con el símbolo "comilla invertida":

```php
$db->query(
    'SELECT ?f FROM ?f',
    'name',
    'database.table_name'
);
```
 Consulta SQL después de la conversión de la plantilla:
```sql
SELECT `name` FROM `database`.`table_name`
```


### Comillas delimitadoras

**La biblioteca requiere que el programador respete la sintaxis SQL.** Esto significa que la siguiente consulta no funcionará:

```php
$db->query(
    'SELECT CONCAT("Hello, ", ?s, "!")',
    'world'
);
```
— el marcador de posición `?s` debe encerrarse entre comillas simples o dobles:
```php
$db->query(
    'SELECT concat("Hello, ", "?s", "!")',
    'world'
);
```
Consulta SQL después de la conversión de la plantilla:
```sql
SELECT concat("Hello, ", "world", "!")
```

Para quienes están acostumbrados a trabajar con PDO esto puede parecer extraño, pero implementar un mecanismo que determine si en un caso es necesario encerrar el valor del marcador de posición entre comillas o no es una tarea muy no trivial que requiere escribir un analizador completo.


## Ejemplos de trabajo con la biblioteca

Ver en el archivo [../console/tests.php](../console/tests.php)
