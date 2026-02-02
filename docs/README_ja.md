**他の言語:**
- [English documentation](../README.md)
- [Русская документация](README_ru.md)
- [Documentation française](README_fr.md)
- [Deutsche Dokumentation](README_de.md)
- [Documentazione italiana](README_it.md)
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

## ライブラリの入手

[アーカイブとしてダウンロード](https://github.com/Vasiliy-Makogon/Database/archive/master.zip)するか、このサイトからクローンするか、composer経由でインストールできます（[packagist.orgへのリンク](https://packagist.org/packages/krugozor/database)）：
```
composer require krugozor/database
```


## `krugozor/database`とは？

`krugozor/database`は、PHP拡張機能[mysqli](https://www.php.net/manual/ja/book.mysqli.php)を使用したMySQLデータベースでの簡単、便利、高速、安全な作業のためのPHP >= 8.0クラスライブラリです。


### PHPにはPDO抽象化とmysqli拡張機能があるのに、なぜMySQL用のカスタムクラスが必要なのか？

PHPでmysqlデータベースを扱うすべてのライブラリの主な欠点は：

* **冗長性**
  * SQLインジェクションを防ぐために、開発者には2つの方法があります：
    * [プリペアドステートメント](https://www.php.net/manual/ja/mysqli.quickstart.prepared-statements.php)を使用する。
    * SQLクエリ本体に入るパラメータを手動でエスケープする。文字列パラメータは[mysqli_real_escape_string](https://www.php.net/manual/ja/mysqli.real-escape-string.php)を通し、期待される数値パラメータは対応する型 — `int`と`float`に変換する。
  * 両方のアプローチには大きな欠点があります：
    * プリペアドステートメントは[ひどく冗長](https://www.php.net/manual/ja/mysqli.prepare.php#refsect1-mysqli.prepare-examples)です。PDO抽象化やmysqli拡張機能を「箱から出して」使用し、DBMSからデータを取得するすべてのメソッドを集約せずに使用するのは単純に不可能です — テーブルから値を取得するには最低5行のコードを書く必要があります！そして各クエリごとに！
    * SQLクエリ本体に入るパラメータを手動でエスケープする — これは議論の余地すらありません。優れたプログラマーは怠惰なプログラマーです。すべてが最大限自動化されるべきです。
* **デバッグ用のSQLクエリを取得することが不可能**
  * プログラムでSQLクエリが機能しない理由を理解するには、それをデバッグする必要があります — 論理的または構文エラーを見つける必要があります。エラーを見つけるには、データベースが「文句を言った」SQLクエリ自体を、その本体に代入されたパラメータとともに「見る」必要があります。つまり、完全に形成されたSQLを持つ必要があります。
開発者がプリペアドステートメントを使用したPDOを使用している場合、これは...不可能です！ネイティブライブラリにはこのための最大限便利なメカニズムが[提供されていません](https://qna.habr.com/q/22669)。無理をするか、データベースログを見るしかありません。


### 解決策：`krugozor/database` — MySQLを扱うクラス

1. 冗長性を排除 — 「ネイティブ」ライブラリを使用した場合の1つのクエリを実行するための3行以上のコードの代わりに、たった1行を書くだけです。
2. 指定されたプレースホルダータイプに従って、クエリ本体に入るすべてのパラメータをエスケープ — SQLインジェクションからの確実な保護。
3. 「ネイティブ」mysqliアダプターの機能を置き換えるのではなく、単にそれを補完します。
4. 拡張可能。本質的に、ライブラリはパーサーとSQLインジェクションからの保証された保護を備えたSQLクエリの実行のみを提供します。ライブラリの任意のクラスから継承し、ライブラリのメカニズムと`mysqli`および`mysqli_result`のメカニズムの両方を使用して、必要なメソッドを作成できます。

### `krugozor/database`ライブラリではないものは何か？

さまざまなデータベースドライバーのラッパーのほとんどは、ひどいアーキテクチャを持つ無駄なコードの山です。その作者は、自分のラッパーの実用的な目的を理解していないまま、それらをクエリビルダー、ActiveRecordライブラリ、その他のORMソリューションのようなものに変えてしまいます。

`krugozor/database`ライブラリはこれらのいずれでもありません。これは、MySQL DBMSの枠組み内で通常のSQLを扱うための便利なツールに過ぎません — それ以上ではありません！


## placeholders（プレースホルダー）とは？

**Placeholders**（プレースホルダー）は、**SQLクエリ文字列内で*明示的な値（クエリパラメータ）の代わりに*書かれる特殊な型付きマーカー**です。値自体は「後で」、SQLクエリを実行する主要メソッドの後続の引数として渡されます：

```php
$result = $db->query(
    "SELECT * FROM `users` WHERE `name` = '?s' AND `age` = ?i",
    "ダルタニャン", 41
);
```

*placeholders*システムを通過したSQLクエリパラメータは、プレースホルダータイプに応じて特殊なエスケープメカニズムによって処理されます。つまり、以前のように変数を`mysqli_real_escape_string()`のようなエスケープ関数に入れたり、数値型に変換したりする必要はもうありません：

```php
<?php
// 以前は、各DBMSへのクエリの前に
// だいたいこれを行っていました（そして多くの人は今でも「これ」をしていません）：
$id = (int) $_POST['id'];
$value = mysqli_real_escape_string($mysql, $_POST['value']);
$result = mysqli_query($mysql, "SELECT * FROM `t` WHERE `f1` = '$value' AND `f2` = $id");
```

今ではクエリを書くのが簡単で高速になり、何よりも`krugozor/database`ライブラリはすべての可能なSQLインジェクションを完全に防ぎます。


### プレースホルダーシステムの紹介

プレースホルダーのタイプとその目的については以下で説明します。プレースホルダーのタイプに慣れる前に、ライブラリのメカニズムがどのように機能するかを理解する必要があります。

#### PHPの問題

PHPは弱い型付けの言語であり、このライブラリの開発中にイデオロギー的なジレンマが生じました。
次の構造を持つテーブルがあるとします：

```sql
`name` varchar not null
`flag` tinyint not null
```
そしてライブラリは（おそらく開発者に依存しない何らかの理由で）次のクエリを実行しなければなりません：

```php
$db->query(
    "INSERT INTO `t` SET `name` = '?s', `flag` = ?i",
    null, false
);
```
この例では、テキスト`not null`フィールド`name`に`null`値を書き込もうとし、
数値フィールド`flag`にブール型`false`を書き込もうとしています。この状況でどうすべきでしょうか？

* クエリパラメータの検証の責任を誰が負うべきか - クライアントコードかライブラリか？
* この場合、プログラムの実行を中断すべきか、それとも何らかの操作を適用してデータをデータベースに書き込むべきか？
* `tinyint`カラムの`false`値を`0`値として解釈し、`name`カラムの`null`を空文字列として解釈できるか？
* コード内でこの問題をどのように簡素化または標準化できるか？

これらの質問を考慮して、このライブラリに2つの動作モードを実装することが決定されました。

### ライブラリの動作モード

  * **Mysql::MODE_STRICT — プレースホルダータイプと引数タイプの厳密な一致モード**。
    `Mysql::MODE_STRICT`モードでは、*引数タイプはプレースホルダータイプと一致する必要があります*。たとえば、整数型プレースホルダー`?i`に対して引数として`55.5`または`'55.5'`の値を渡そうとすると、例外がスローされます：

```php
// 厳密モードを設定
$db->setTypeMode(Mysql::MODE_STRICT);
// この式は実行されず、例外がスローされます：
// クエリテンプレート "SELECT ?i" で "integer" 型のプレースホルダーに "double" 型の値を指定しようとしました
$db->query('SELECT ?i', 55.5);
```

* **Mysql::MODE_TRANSFORM — プレースホルダータイプと引数タイプが一致しない場合に引数をプレースホルダータイプに変換するモード。** `Mysql::MODE_TRANSFORM`モードはデフォルトで設定されており、「寛容な」モードです — プレースホルダータイプと引数タイプが一致しない場合、例外を生成せず、*PHP言語自体を使用して引数を必要なプレースホルダータイプに変換しようとします*。ちなみに、私はライブラリの作者として、常にこのモードを使用しており、厳密モード（`Mysql::MODE_STRICT`）は実際の作業では使用したことがありませんが、あなたには必要かもしれません。

**Mysql::MODE_TRANSFORMモードでは次の変換が許可されています：**

* **`int`型（プレースホルダー`?i`）に変換されます**
  * `string`型と`double`型の両方で表される浮動小数点数
  * `bool` TRUEは`int(1)`に、FALSEは`int(0)`に変換されます
  * `null`は`int(0)`に変換されます
* **`double`型（プレースホルダー`?d`）に変換されます**
  * `string`型と`int`型の両方で表される整数
  * `bool` TRUEは`float(1)`に、FALSEは`float(0)`に変換されます
  * `null`は`float(0)`に変換されます
* **`string`型（プレースホルダー`?s`）に変換されます**
  * `bool` TRUEは`string(1) "1"`に、FALSEは`string(1) "0"`に変換されます。この動作はPHPでの`bool`から`int`への型変換とは異なります。これは実際には、ブール型がMySQLで数値として書き込まれることが多いためです。
  * `numeric`型の値はPHPの変換ルールに従って文字列に変換されます
  * `null`は`string(0) ""`に変換されます
* **`null`型（プレースホルダー`?n`）に変換されます**
  * 任意の引数。
* 配列、オブジェクト、リソースの変換は許可されていません。


### ライブラリで提供されているプレースホルダータイプは？


#### `?i` — 整数のプレースホルダー

```php
$db->query(
    'SELECT * FROM `users` WHERE `id` = ?i', 123
);
```
テンプレート変換後のSQLクエリ：
```sql
SELECT * FROM `users` WHERE `id` = 123
```

**注意！** `PHP_INT_MAX`を超える数値を操作する場合：
* プログラムでは文字列としてのみ操作してください。
* このプレースホルダーを使用せず、文字列プレースホルダー`?s`を使用してください（下記参照）。問題は、`PHP_INT_MAX`を超える数値をPHPが浮動小数点数として解釈することです。ライブラリのパーサーはパラメータを`int`型に変換しようとしますが、その結果「*結果は未定義になります。floatには正しい結果を返すのに十分な精度がないためです。この場合、警告も通知も出力されません！*」 — [php.net](https://www.php.net/manual/ja/language.types.integer.php#language.types.integer.casting.from-float)。

#### `?d` — 浮動小数点数のプレースホルダー

```php
$db->query(
    'SELECT * FROM `prices` WHERE `cost` IN (?d, ?d)',
    12.56, '12.33'
);
```
テンプレート変換後のSQLクエリ：
```sql
SELECT * FROM `prices` WHERE `cost` IN (12.56, 12.33)
```

**注意！** `double`データ型を扱うためにライブラリを使用する場合は、PHPレベルとDBMSレベルの両方で整数部と小数部の区切り文字が同じになるように適切なロケールを設定してください。

#### `?s` — 文字列型のプレースホルダー

引数の値は`mysqli::real_escape_string()`メソッドでエスケープされます：

```php
$db->query(
    'SELECT "?s"',
    "あなたたちは皆馬鹿だ、そして私はダルタニャンだ！"
);
```
 テンプレート変換後のSQLクエリ：

```sql
SELECT "あなたたちは皆馬鹿だ、そして私はダルタニャンだ！"
```
#### `?S` — SQL LIKE演算子で使用するための文字列型プレースホルダー

引数の値は`mysqli::real_escape_string()`メソッドでエスケープされ + LIKE演算子で使用される特殊文字（`%`と`_`）のエスケープが行われます：

```php
$db->query('SELECT "?S"', '% _');
```
 テンプレート変換後のSQLクエリ：
```sql
SELECT "\% \_"
```

 #### `?n` — `NULL`型のプレースホルダー
任意の引数の値は無視され、プレースホルダーはSQLクエリ内で文字列`NULL`に置き換えられます：

```php
$db->query('SELECT ?n', 123);
```
 テンプレート変換後のSQLクエリ：
```sql
SELECT NULL
```

 #### `?A*` — 連想配列から連想セットのプレースホルダー、`キー = 値`の形式のペアのシーケンスを生成

記号`*`は次のいずれかのプレースホルダーです：

 * `i`（整数のプレースホルダー）
 * `d`（浮動小数点数のプレースホルダー）
 * `s`（文字列型のプレースホルダー）

 変換とエスケープのルールは、上記で説明した単一のスカラー型と同じです。例：

```php
$db->query(
    'INSERT INTO `test` SET ?Ai',
    ['first' => '123', 'second' => 456]
);
```
テンプレート変換後のSQLクエリ：
```sql
INSERT INTO `test` SET `first` = "123", `second` = "456"
```

#### `?a*` — 単純な（または連想的な）配列からセットのプレースホルダー、値のシーケンスを生成

 `*`は次のタイプのいずれかです：
 * `i`（整数のプレースホルダー）
 * `d`（浮動小数点数のプレースホルダー）
 * `s`（文字列型のプレースホルダー）

 変換とエスケープのルールは、上記で説明した単一のスカラー型と同じです。例：

```php
$db->query(
    'SELECT * FROM `test` WHERE `id` IN (?ai)',
    [123, 456]
);
```
テンプレート変換後のSQLクエリ：
```sql
SELECT * FROM `test` WHERE `id` IN ("123", "456")
```


#### `?A[?n, ?s, ?i, ...]` — タイプと引数の数を明示的に指定した連想セットのプレースホルダー、`キー = 値`のペアのシーケンスを生成

例：
```php
$db->query(
    'INSERT INTO `users` SET ?A[?i, "?s"]',
    ['age' => 41, 'name' => "ダルタニャン"]
);
```
テンプレート変換後のSQLクエリ：
```sql
INSERT INTO `users` SET `age` = 41,`name` = "ダルタニャン"
```

#### `?a[?n, ?s, ?i, ...]` — タイプと引数の数を明示的に指定したセットのプレースホルダー、値のシーケンスを生成
例：
```php
$db->query(
    'SELECT * FROM `users` WHERE `name` IN (?a["?s", "?s"])',
    ["侯爵ダルキアン", "ダルタニャン"]
);
```
テンプレート変換後のSQLクエリ：
```sql
SELECT * FROM `users` WHERE `name` IN ("侯爵ダルキアン", "ダルタニャン")
```


#### `?f` — テーブル名またはフィールド名のプレースホルダー

このプレースホルダーは、クエリ内でテーブル名またはフィールド名がパラメータとして渡される場合を対象としています。フィールド名とテーブル名は「バッククォート」記号で囲まれます：

```php
$db->query(
    'SELECT ?f FROM ?f',
    'name',
    'database.table_name'
);
```
 テンプレート変換後のSQLクエリ：
```sql
SELECT `name` FROM `database`.`table_name`
```


### 区切り引用符

**ライブラリはプログラマーにSQL構文の遵守を要求します。** これは、次のクエリが機能しないことを意味します：

```php
$db->query(
    'SELECT CONCAT("Hello, ", ?s, "!")',
    'world'
);
```
— プレースホルダー`?s`は単一引用符または二重引用符で囲む必要があります：
```php
$db->query(
    'SELECT concat("Hello, ", "?s", "!")',
    'world'
);
```
テンプレート変換後のSQLクエリ：
```sql
SELECT concat("Hello, ", "world", "!")
```

PDOでの作業に慣れている人にとっては、これは奇妙に見えるかもしれませんが、プレースホルダーの値を引用符で囲む必要があるかどうかを決定するメカニズムを実装することは、完全なパーサーを書く必要がある非常に自明ではないタスクです。


## ライブラリの使用例

ファイル [../console/tests.php](../console/tests.php) を参照してください
