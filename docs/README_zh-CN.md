**其他语言:**
- [English documentation](../README.md)
- [Русская документация](README_ru.md)
- [Documentation française](README_fr.md)
- [Deutsche Dokumentation](README_de.md)
- [Documentazione italiana](README_it.md)
- [日本語ドキュメント](README_ja.md)
- [Documentación en español](README_es.md)
- [한국어 문서](README_ko.md)
- [繁體中文文件](README_zh-TW.md)
- [Dokumentasi Bahasa Indonesia](README_id.md)
- [Documentação em Português (BR)](README_pt-BR.md)
- [हिंदी दस्तावेज़](README_hi.md)
- [التوثيق بالعربية](README_ar.md)
- [Türkçe Dokümantasyon](README_tr.md)
- [Tài liệu tiếng Việt](README_vi.md)

---

## 获取库

您可以[下载存档](https://github.com/Vasiliy-Makogon/Database/archive/master.zip)，从此站点克隆或通过composer安装（[packagist.org链接](https://packagist.org/packages/krugozor/database)）：
```
composer require krugozor/database
```


## 什么是`krugozor/database`？

`krugozor/database`是一个PHP >= 8.0类库，用于使用PHP扩展[mysqli](https://www.php.net/manual/zh/book.mysqli.php)简单、方便、快速和安全地处理MySQL数据库。


### 既然PHP已经有了PDO抽象和mysqli扩展，为什么还需要MySQL的自定义类？

PHP中所有MySQL数据库工作库的主要缺点是：

* **冗长性**
  * 为了防止SQL注入，开发人员有两种方法：
    * 使用[预处理语句](https://www.php.net/manual/zh/mysqli.quickstart.prepared-statements.php)。
    * 手动转义进入SQL查询主体的参数。字符串参数通过[mysqli_real_escape_string](https://www.php.net/manual/zh/mysqli.real-escape-string.php)，并将预期的数字参数转换为相应的类型 — `int`和`float`。
  * 两种方法都有巨大的缺点：
    * 预处理语句[非常冗长](https://www.php.net/manual/zh/mysqli.prepare.php#refsect1-mysqli.prepare-examples)。使用"开箱即用"的PDO抽象或mysqli扩展，而不聚合从DBMS获取数据的所有方法是根本不可能的 — 要从表中获取值，必须至少编写5行代码！每个查询都是如此！
    * 手动转义进入SQL查询主体的参数 — 甚至不讨论。好的程序员是懒惰的程序员。一切都应该最大程度地自动化。
* **无法获取SQL查询进行调试**
  * 要了解为什么程序中的SQL查询不工作，需要调试它 — 找到逻辑或语法错误。要找到错误，需要"看到"数据库"抱怨"的SQL查询本身，其主体中替换了参数。即，拥有完全形成的SQL。
如果开发人员使用带有预处理语句的PDO，这是...不可能的！本机库中没有[提供](https://qna.habr.com/q/22669)最大方便的机制。只能扭曲或查看数据库日志。


### 解决方案：`krugozor/database` — 用于处理MySQL的类

1. 消除冗长 — 使用"本机"库执行一个查询需要3行或更多代码，而您只需编写一行。
2. 根据指定的占位符类型转义进入查询主体的所有参数 — 可靠地防止SQL注入。
3. 不替换"本机"mysqli适配器的功能，而只是补充它。
4. 可扩展。本质上，该库仅提供解析器和具有保证防止SQL注入的SQL查询执行。您可以从库的任何类继承，并使用库的机制和`mysqli`和`mysqli_result`的机制创建所需的方法。

### `krugozor/database`库不是什么？

大多数用于各种数据库驱动程序的包装器都是具有可怕架构的无用代码堆积。它们的作者自己不了解包装器的实际目的，将它们转变为查询构建器、ActiveRecord库和其他ORM解决方案。

`krugozor/database`库不是这些中的任何一个。这只是一个在MySQL DBMS框架内处理普通SQL的便捷工具 — 仅此而已！


## 什么是placeholders（占位符）？

**Placeholders**（占位符）是**在SQL查询字符串中*代替显式值（查询参数）*编写的特殊类型化标记**。值本身"稍后"作为执行SQL查询的主要方法的后续参数传递：

```php
$result = $db->query(
    "SELECT * FROM `users` WHERE `name` = '?s' AND `age` = ?i",
    "达尔塔尼昂", 41
);
```

通过*placeholders*系统的SQL查询参数由特殊的转义机制处理，具体取决于占位符类型。也就是说，现在不再需要像以前那样将变量包含在`mysqli_real_escape_string()`等转义函数中或将其转换为数字类型：

```php
<?php
// 以前，在每次查询DBMS之前，我们都做
// 大约这样（许多人仍然不做"这个"）：
$id = (int) $_POST['id'];
$value = mysqli_real_escape_string($mysql, $_POST['value']);
$result = mysqli_query($mysql, "SELECT * FROM `t` WHERE `f1` = '$value' AND `f2` = $id");
```

现在编写查询变得简单、快速，最重要的是`krugozor/database`库完全防止所有可能的SQL注入。


### 占位符系统介绍

占位符的类型及其用途如下所述。在熟悉占位符类型之前，需要了解库的机制如何工作。

#### PHP的问题

PHP是一种弱类型语言，在开发此库期间出现了意识形态困境。
想象一下，我们有一个具有以下结构的表：

```sql
`name` varchar not null
`flag` tinyint not null
```
并且库必须（由于某种原因，可能不依赖于开发人员）执行以下查询：

```php
$db->query(
    "INSERT INTO `t` SET `name` = '?s', `flag` = ?i",
    null, false
);
```
在这个例子中，尝试将`null`值写入文本`not null`字段`name`，
并将布尔类型`false`写入数字字段`flag`。在这种情况下该怎么办？

* 谁负责验证查询参数 - 客户端代码还是库？
* 在这种情况下是否需要中断程序执行，或者可能应该应用一些操作以便将数据写入数据库？
* 我们可以将`tinyint`列的`false`值解释为`0`值，将`name`列的`null`解释为空字符串吗？
* 我们如何在代码中简化或标准化这个问题？

考虑到提出的问题，决定在此库中实现两种操作模式。

### 库的操作模式

  * **Mysql::MODE_STRICT — 占位符类型和参数类型的严格匹配模式**。
    在`Mysql::MODE_STRICT`模式下，*参数类型必须与占位符类型匹配*。例如，尝试为整数类型占位符`?i`传递`55.5`或`'55.5'`值作为参数将引发异常：

```php
// 设置严格模式
$db->setTypeMode(Mysql::MODE_STRICT);
// 此表达式不会执行，将引发异常：
// 尝试在查询模板"SELECT ?i"中为"integer"类型的占位符指定"double"类型的值
$db->query('SELECT ?i', 55.5);
```

* **Mysql::MODE_TRANSFORM — 当占位符类型和参数类型不匹配时将参数转换为占位符类型的模式。** `Mysql::MODE_TRANSFORM`模式默认设置，是"宽容"模式 — 当占位符类型和参数类型不匹配时不生成异常，而是*尝试使用PHP语言本身将参数转换为所需的占位符类型*。顺便说一句，我作为库的作者，总是使用这种模式，严格模式（`Mysql::MODE_STRICT`）在实际工作中从未使用过，但也许您需要它。

**Mysql::MODE_TRANSFORM模式允许以下转换：**

* **转换为`int`类型（占位符`?i`）**
  * 以`string`类型和`double`类型表示的浮点数
  * `bool` TRUE转换为`int(1)`，FALSE转换为`int(0)`
  * `null`转换为`int(0)`
* **转换为`double`类型（占位符`?d`）**
  * 以`string`类型和`int`类型表示的整数
  * `bool` TRUE转换为`float(1)`，FALSE转换为`float(0)`
  * `null`转换为`float(0)`
* **转换为`string`类型（占位符`?s`）**
  * `bool` TRUE转换为`string(1) "1"`，FALSE转换为`string(1) "0"`。这种行为与PHP中`bool`到`int`的类型转换不同，因为在实践中，布尔类型通常在MySQL中恰好作为数字写入。
  * `numeric`类型的值根据PHP转换规则转换为字符串
  * `null`转换为`string(0) ""`
* **转换为`null`类型（占位符`?n`）**
  * 任何参数。
* 不允许对数组、对象和资源进行转换。


### 库提供哪些占位符类型？


#### `?i` — 整数占位符

```php
$db->query(
    'SELECT * FROM `users` WHERE `id` = ?i', 123
);
```
模板转换后的SQL查询：
```sql
SELECT * FROM `users` WHERE `id` = 123
```

**注意！** 如果您使用超过`PHP_INT_MAX`的数字，那么：
* 在程序中仅将它们作为字符串使用。
* 不要使用此占位符，使用字符串占位符`?s`（见下文）。问题是，超过`PHP_INT_MAX`的数字，PHP会将其解释为浮点数。库的解析器将尝试将参数转换为`int`类型，结果«*结果将是未定义的，因为float没有足够的精度来返回正确的结果。在这种情况下，不会输出警告，甚至不会输出通知！*» — [php.net](https://www.php.net/manual/zh/language.types.integer.php#language.types.integer.casting.from-float)。

#### `?d` — 浮点数占位符

```php
$db->query(
    'SELECT * FROM `prices` WHERE `cost` IN (?d, ?d)',
    12.56, '12.33'
);
```
模板转换后的SQL查询：
```sql
SELECT * FROM `prices` WHERE `cost` IN (12.56, 12.33)
```

**注意！** 如果您使用库处理`double`数据类型，请设置适当的区域设置，以便PHP级别和DBMS级别的整数和小数部分之间的分隔符相同。

#### `?s` — 字符串类型占位符

参数值使用`mysqli::real_escape_string()`方法转义：

```php
$db->query(
    'SELECT "?s"',
    "你们都是傻瓜，而我是达尔塔尼昂！"
);
```
 模板转换后的SQL查询：

```sql
SELECT "你们都是傻瓜，而我是达尔塔尼昂！"
```
#### `?S` — 用于SQL LIKE运算符插入的字符串类型占位符

参数值使用`mysqli::real_escape_string()`方法转义 + 转义LIKE运算符中使用的特殊字符（`%`和`_`）：

```php
$db->query('SELECT "?S"', '% _');
```
 模板转换后的SQL查询：
```sql
SELECT "\% \_"
```

 #### `?n` — `NULL`类型占位符
任何参数的值都被忽略，占位符在SQL查询中被替换为字符串`NULL`：

```php
$db->query('SELECT ?n', 123);
```
 模板转换后的SQL查询：
```sql
SELECT NULL
```

 #### `?A*` — 从关联数组生成关联集合的占位符，生成`键 = 值`形式的对序列

其中符号`*`是以下占位符之一：

 * `i`（整数占位符）
 * `d`（浮点数占位符）
 * `s`（字符串类型占位符）

 转换和转义规则与上述单个标量类型相同。示例：

```php
$db->query(
    'INSERT INTO `test` SET ?Ai',
    ['first' => '123', 'second' => 456]
);
```
模板转换后的SQL查询：
```sql
INSERT INTO `test` SET `first` = "123", `second` = "456"
```

#### `?a*` — 从简单（或关联）数组生成集合的占位符，生成值序列

 其中`*`是以下类型之一：
 * `i`（整数占位符）
 * `d`（浮点数占位符）
 * `s`（字符串类型占位符）

 转换和转义规则与上述单个标量类型相同。示例：

```php
$db->query(
    'SELECT * FROM `test` WHERE `id` IN (?ai)',
    [123, 456]
);
```
模板转换后的SQL查询：
```sql
SELECT * FROM `test` WHERE `id` IN ("123", "456")
```


#### `?A[?n, ?s, ?i, ...]` — 明确指定类型和参数数量的关联集合占位符，生成`键 = 值`对序列

示例：
```php
$db->query(
    'INSERT INTO `users` SET ?A[?i, "?s"]',
    ['age' => 41, 'name' => "达尔塔尼昂"]
);
```
模板转换后的SQL查询：
```sql
INSERT INTO `users` SET `age` = 41,`name` = "达尔塔尼昂"
```

#### `?a[?n, ?s, ?i, ...]` — 明确指定类型和参数数量的集合占位符，生成值序列
示例：
```php
$db->query(
    'SELECT * FROM `users` WHERE `name` IN (?a["?s", "?s"])',
    ["侯爵达尔基安", "达尔塔尼昂"]
);
```
模板转换后的SQL查询：
```sql
SELECT * FROM `users` WHERE `name` IN ("侯爵达尔基安", "达尔塔尼昂")
```


#### `?f` — 表名或字段名占位符

此占位符用于在查询中通过参数传递表名或字段名的情况。字段和表名用"反引号"符号括起来：

```php
$db->query(
    'SELECT ?f FROM ?f',
    'name',
    'database.table_name'
);
```
 模板转换后的SQL查询：
```sql
SELECT `name` FROM `database`.`table_name`
```


### 限定引号

**库要求程序员遵守SQL语法。** 这意味着以下查询将不起作用：

```php
$db->query(
    'SELECT CONCAT("Hello, ", ?s, "!")',
    'world'
);
```
— 占位符`?s`必须用单引号或双引号括起来：
```php
$db->query(
    'SELECT concat("Hello, ", "?s", "!")',
    'world'
);
```
模板转换后的SQL查询：
```sql
SELECT concat("Hello, ", "world", "!")
```

对于习惯使用PDO的人来说，这可能看起来很奇怪，但实现一个机制来确定在一种情况下是否需要用引号括起占位符值是一项非常不平凡的任务，需要编写完整的解析器。


## 使用库的示例

请参阅文件[../console/tests.php](../console/tests.php)
