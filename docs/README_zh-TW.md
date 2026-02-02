**其他語言:**
- [English documentation](../README.md)
- [Русская документация](README_ru.md)
- [Documentation française](README_fr.md)
- [Deutsche Dokumentation](README_de.md)
- [Documentazione italiana](README_it.md)
- [日本語ドキュメント](README_ja.md)
- [Documentación en español](README_es.md)
- [한국어 문서](README_ko.md)
- [简体中文文档](README_zh-CN.md)
- [Dokumentasi Bahasa Indonesia](README_id.md)
- [Documentação em Português (BR)](README_pt-BR.md)
- [हिंदी दस्तावेज़](README_hi.md)
- [التوثيق بالعربية](README_ar.md)
- [Türkçe Dokümantasyon](README_tr.md)
- [Tài liệu tiếng Việt](README_vi.md)

---

## 獲取函式庫

您可以[下載壓縮檔](https://github.com/Vasiliy-Makogon/Database/archive/master.zip)，從此站點克隆或透過composer安裝（[packagist.org連結](https://packagist.org/packages/krugozor/database)）：
```
composer require krugozor/database
```


## 什麼是`krugozor/database`？

`krugozor/database`是一個PHP >= 8.0類別函式庫，用於使用PHP擴展[mysqli](https://www.php.net/manual/zh/book.mysqli.php)簡單、方便、快速和安全地處理MySQL資料庫。


### 既然PHP已經有了PDO抽象和mysqli擴展，為什麼還需要MySQL的自訂類別？

PHP中所有MySQL資料庫工作函式庫的主要缺點是：

* **冗長性**
  * 為了防止SQL注入，開發人員有兩種方法：
    * 使用[預處理語句](https://www.php.net/manual/zh/mysqli.quickstart.prepared-statements.php)。
    * 手動轉義進入SQL查詢主體的參數。字串參數透過[mysqli_real_escape_string](https://www.php.net/manual/zh/mysqli.real-escape-string.php)，並將預期的數字參數轉換為相應的類型 — `int`和`float`。
  * 兩種方法都有巨大的缺點：
    * 預處理語句[非常冗長](https://www.php.net/manual/zh/mysqli.prepare.php#refsect1-mysqli.prepare-examples)。使用「開箱即用」的PDO抽象或mysqli擴展，而不聚合從DBMS獲取資料的所有方法是根本不可能的 — 要從表中獲取值，必須至少編寫5行程式碼！每個查詢都是如此！
    * 手動轉義進入SQL查詢主體的參數 — 甚至不討論。好的程式設計師是懶惰的程式設計師。一切都應該最大程度地自動化。
* **無法獲取SQL查詢進行除錯**
  * 要了解為什麼程式中的SQL查詢不工作，需要除錯它 — 找到邏輯或語法錯誤。要找到錯誤，需要「看到」資料庫「抱怨」的SQL查詢本身，其主體中替換了參數。即，擁有完全形成的SQL。
如果開發人員使用帶有預處理語句的PDO，這是...不可能的！本機函式庫中沒有[提供](https://qna.habr.com/q/22669)最大方便的機制。只能扭曲或查看資料庫日誌。


### 解決方案：`krugozor/database` — 用於處理MySQL的類別

1. 消除冗長 — 使用「本機」函式庫執行一個查詢需要3行或更多程式碼，而您只需編寫一行。
2. 根據指定的佔位符類型轉義進入查詢主體的所有參數 — 可靠地防止SQL注入。
3. 不替換「本機」mysqli適配器的功能，而只是補充它。
4. 可擴展。本質上，該函式庫僅提供解析器和具有保證防止SQL注入的SQL查詢執行。您可以從函式庫的任何類別繼承，並使用函式庫的機制和`mysqli`和`mysqli_result`的機制建立所需的方法。

### `krugozor/database`函式庫不是什麼？

大多數用於各種資料庫驅動程式的包裝器都是具有可怕架構的無用程式碼堆積。它們的作者自己不了解包裝器的實際目的，將它們轉變為查詢建構器、ActiveRecord函式庫和其他ORM解決方案。

`krugozor/database`函式庫不是這些中的任何一個。這只是一個在MySQL DBMS框架內處理普通SQL的便捷工具 — 僅此而已！


## 什麼是placeholders（佔位符）？

**Placeholders**（佔位符）是**在SQL查詢字串中*代替顯式值（查詢參數）*編寫的特殊類型化標記**。值本身「稍後」作為執行SQL查詢的主要方法的後續參數傳遞：

```php
$result = $db->query(
    "SELECT * FROM `users` WHERE `name` = '?s' AND `age` = ?i",
    "達太安", 41
);
```

通過*placeholders*系統的SQL查詢參數由特殊的轉義機制處理，具體取決於佔位符類型。也就是說，現在不再需要像以前那樣將變數包含在`mysqli_real_escape_string()`等轉義函式中或將其轉換為數字類型：

```php
<?php
// 以前，在每次查詢DBMS之前，我們都做
// 大約這樣（許多人仍然不做「這個」）：
$id = (int) $_POST['id'];
$value = mysqli_real_escape_string($mysql, $_POST['value']);
$result = mysqli_query($mysql, "SELECT * FROM `t` WHERE `f1` = '$value' AND `f2` = $id");
```

現在編寫查詢變得簡單、快速，最重要的是`krugozor/database`函式庫完全防止所有可能的SQL注入。


### 佔位符系統介紹

佔位符的類型及其用途如下所述。在熟悉佔位符類型之前，需要了解函式庫的機制如何工作。

#### PHP的問題

PHP是一種弱類型語言，在開發此函式庫期間出現了意識形態困境。
想像一下，我們有一個具有以下結構的表：

```sql
`name` varchar not null
`flag` tinyint not null
```
並且函式庫必須（由於某種原因，可能不依賴於開發人員）執行以下查詢：

```php
$db->query(
    "INSERT INTO `t` SET `name` = '?s', `flag` = ?i",
    null, false
);
```
在這個例子中，嘗試將`null`值寫入文字`not null`欄位`name`，
並將布林類型`false`寫入數字欄位`flag`。在這種情況下該怎麼辦？

* 誰負責驗證查詢參數 - 客戶端程式碼還是函式庫？
* 在這種情況下是否需要中斷程式執行，或者可能應該應用一些操作以便將資料寫入資料庫？
* 我們可以將`tinyint`欄的`false`值解釋為`0`值，將`name`欄的`null`解釋為空字串嗎？
* 我們如何在程式碼中簡化或標準化這個問題？

考慮到提出的問題，決定在此函式庫中實現兩種操作模式。

### 函式庫的操作模式

  * **Mysql::MODE_STRICT — 佔位符類型和參數類型的嚴格匹配模式**。
    在`Mysql::MODE_STRICT`模式下，*參數類型必須與佔位符類型匹配*。例如，嘗試為整數類型佔位符`?i`傳遞`55.5`或`'55.5'`值作為參數將引發異常：

```php
// 設定嚴格模式
$db->setTypeMode(Mysql::MODE_STRICT);
// 此表達式不會執行，將引發異常：
// 嘗試在查詢範本「SELECT ?i」中為「integer」類型的佔位符指定「double」類型的值
$db->query('SELECT ?i', 55.5);
```

* **Mysql::MODE_TRANSFORM — 當佔位符類型和參數類型不匹配時將參數轉換為佔位符類型的模式。** `Mysql::MODE_TRANSFORM`模式預設設定，是「寬容」模式 — 當佔位符類型和參數類型不匹配時不產生異常，而是*嘗試使用PHP語言本身將參數轉換為所需的佔位符類型*。順便說一句，我作為函式庫的作者，總是使用這種模式，嚴格模式（`Mysql::MODE_STRICT`）在實際工作中從未使用過，但也許您需要它。

**Mysql::MODE_TRANSFORM模式允許以下轉換：**

* **轉換為`int`類型（佔位符`?i`）**
  * 以`string`類型和`double`類型表示的浮點數
  * `bool` TRUE轉換為`int(1)`，FALSE轉換為`int(0)`
  * `null`轉換為`int(0)`
* **轉換為`double`類型（佔位符`?d`）**
  * 以`string`類型和`int`類型表示的整數
  * `bool` TRUE轉換為`float(1)`，FALSE轉換為`float(0)`
  * `null`轉換為`float(0)`
* **轉換為`string`類型（佔位符`?s`）**
  * `bool` TRUE轉換為`string(1) "1"`，FALSE轉換為`string(1) "0"`。這種行為與PHP中`bool`到`int`的類型轉換不同，因為在實踐中，布林類型通常在MySQL中恰好作為數字寫入。
  * `numeric`類型的值根據PHP轉換規則轉換為字串
  * `null`轉換為`string(0) ""`
* **轉換為`null`類型（佔位符`?n`）**
  * 任何參數。
* 不允許對陣列、物件和資源進行轉換。


### 函式庫提供哪些佔位符類型？


#### `?i` — 整數佔位符

```php
$db->query(
    'SELECT * FROM `users` WHERE `id` = ?i', 123
);
```
範本轉換後的SQL查詢：
```sql
SELECT * FROM `users` WHERE `id` = 123
```

**注意！** 如果您使用超過`PHP_INT_MAX`的數字，那麼：
* 在程式中僅將它們作為字串使用。
* 不要使用此佔位符，使用字串佔位符`?s`（見下文）。問題是，超過`PHP_INT_MAX`的數字，PHP會將其解釋為浮點數。函式庫的解析器將嘗試將參數轉換為`int`類型，結果«*結果將是未定義的，因為float沒有足夠的精度來返回正確的結果。在這種情況下，不會輸出警告，甚至不會輸出通知！*» — [php.net](https://www.php.net/manual/zh/language.types.integer.php#language.types.integer.casting.from-float)。

#### `?d` — 浮點數佔位符

```php
$db->query(
    'SELECT * FROM `prices` WHERE `cost` IN (?d, ?d)',
    12.56, '12.33'
);
```
範本轉換後的SQL查詢：
```sql
SELECT * FROM `prices` WHERE `cost` IN (12.56, 12.33)
```

**注意！** 如果您使用函式庫處理`double`資料類型，請設定適當的區域設定，以便PHP級別和DBMS級別的整數和小數部分之間的分隔符相同。

#### `?s` — 字串類型佔位符

參數值使用`mysqli::real_escape_string()`方法轉義：

```php
$db->query(
    'SELECT "?s"',
    "你們都是傻瓜，而我是達太安！"
);
```
 範本轉換後的SQL查詢：

```sql
SELECT "你們都是傻瓜，而我是達太安！"
```
#### `?S` — 用於SQL LIKE運算子插入的字串類型佔位符

參數值使用`mysqli::real_escape_string()`方法轉義 + 轉義LIKE運算子中使用的特殊字元（`%`和`_`）：

```php
$db->query('SELECT "?S"', '% _');
```
 範本轉換後的SQL查詢：
```sql
SELECT "\% \_"
```

 #### `?n` — `NULL`類型佔位符
任何參數的值都被忽略，佔位符在SQL查詢中被替換為字串`NULL`：

```php
$db->query('SELECT ?n', 123);
```
 範本轉換後的SQL查詢：
```sql
SELECT NULL
```

 #### `?A*` — 從關聯陣列產生關聯集合的佔位符，產生`鍵 = 值`形式的對序列

其中符號`*`是以下佔位符之一：

 * `i`（整數佔位符）
 * `d`（浮點數佔位符）
 * `s`（字串類型佔位符）

 轉換和轉義規則與上述單個標量類型相同。範例：

```php
$db->query(
    'INSERT INTO `test` SET ?Ai',
    ['first' => '123', 'second' => 456]
);
```
範本轉換後的SQL查詢：
```sql
INSERT INTO `test` SET `first` = "123", `second` = "456"
```

#### `?a*` — 從簡單（或關聯）陣列產生集合的佔位符，產生值序列

 其中`*`是以下類型之一：
 * `i`（整數佔位符）
 * `d`（浮點數佔位符）
 * `s`（字串類型佔位符）

 轉換和轉義規則與上述單個標量類型相同。範例：

```php
$db->query(
    'SELECT * FROM `test` WHERE `id` IN (?ai)',
    [123, 456]
);
```
範本轉換後的SQL查詢：
```sql
SELECT * FROM `test` WHERE `id` IN ("123", "456")
```


#### `?A[?n, ?s, ?i, ...]` — 明確指定類型和參數數量的關聯集合佔位符，產生`鍵 = 值`對序列

範例：
```php
$db->query(
    'INSERT INTO `users` SET ?A[?i, "?s"]',
    ['age' => 41, 'name' => "達太安"]
);
```
範本轉換後的SQL查詢：
```sql
INSERT INTO `users` SET `age` = 41,`name` = "達太安"
```

#### `?a[?n, ?s, ?i, ...]` — 明確指定類型和參數數量的集合佔位符，產生值序列
範例：
```php
$db->query(
    'SELECT * FROM `users` WHERE `name` IN (?a["?s", "?s"])',
    ["侯爵達爾基安", "達太安"]
);
```
範本轉換後的SQL查詢：
```sql
SELECT * FROM `users` WHERE `name` IN ("侯爵達爾基安", "達太安")
```


#### `?f` — 表名或欄位名佔位符

此佔位符用於在查詢中透過參數傳遞表名或欄位名的情況。欄位和表名用「反引號」符號括起來：

```php
$db->query(
    'SELECT ?f FROM ?f',
    'name',
    'database.table_name'
);
```
 範本轉換後的SQL查詢：
```sql
SELECT `name` FROM `database`.`table_name`
```


### 限定引號

**函式庫要求程式設計師遵守SQL語法。** 這意味著以下查詢將不起作用：

```php
$db->query(
    'SELECT CONCAT("Hello, ", ?s, "!")',
    'world'
);
```
— 佔位符`?s`必須用單引號或雙引號括起來：
```php
$db->query(
    'SELECT concat("Hello, ", "?s", "!")',
    'world'
);
```
範本轉換後的SQL查詢：
```sql
SELECT concat("Hello, ", "world", "!")
```

對於習慣使用PDO的人來說，這可能看起來很奇怪，但實現一個機制來確定在一種情況下是否需要用引號括起佔位符值是一項非常不平凡的任務，需要編寫完整的解析器。


## 使用函式庫的範例

請參閱檔案[../console/tests.php](../console/tests.php)
