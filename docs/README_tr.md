**Diğer diller:**
- [English documentation](../README.md)
- [Русская документация](README_ru.md)
- [Documentation française](README_fr.md)
- [Deutsche Dokumentation](README_de.md)
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
- [Tài liệu tiếng Việt](README_vi.md)

---

## Kütüphaneyi İndirme

[Arşiv olarak indirebilir](https://github.com/Vasiliy-Makogon/Database/archive/master.zip), bu siteden klonlayabilir veya composer aracılığıyla kurabilirsiniz ([packagist.org bağlantısı](https://packagist.org/packages/krugozor/database)):
```
composer require krugozor/database
```


## `krugozor/database` nedir?

`krugozor/database`, PHP uzantısı [mysqli](https://www.php.net/manual/tr/book.mysqli.php) kullanarak MySQL veritabanı ile basit, rahat, hızlı ve güvenli çalışma için PHP >= 8.0 sınıf kütüphanesidir.


### PHP'de zaten PDO soyutlaması ve mysqli uzantısı varken neden MySQL için özel bir sınıfa ihtiyaç var?

PHP'de MySQL veritabanı ile çalışmak için tüm kütüphanelerin ana dezavantajları:

* **Ayrıntılılık**
  * SQL enjeksiyonlarını önlemek için geliştiricilerin iki yolu vardır:
    * [Hazırlanmış ifadeleri](https://www.php.net/manual/tr/mysqli.quickstart.prepared-statements.php) kullanmak.
    * SQL sorgu gövdesine giren parametreleri manuel olarak kaçırma. String parametreleri [mysqli_real_escape_string](https://www.php.net/manual/tr/mysqli.real-escape-string.php) üzerinden geçirmek ve beklenen sayısal parametreleri ilgili türlere — `int` ve `float` dönüştürmek.
  * Her iki yaklaşımın da büyük dezavantajları vardır:
    * Hazırlanmış ifadeler [korkunç derecede ayrıntılıdır](https://www.php.net/manual/tr/mysqli.prepare.php#refsect1-mysqli.prepare-examples). PDO soyutlamasını veya mysqli uzantısını "kutusundan çıkar çıkmaz" kullanmak, DBMS'den veri almak için tüm yöntemleri toplamadan tamamen imkansızdır — bir tablodan bir değer almak için en az 5 satır kod yazmak gerekir! Ve her sorgu için böyle!
    * SQL sorgu gövdesine giren parametreleri manuel olarak kaçırma — tartışılmaz bile. İyi bir programcı tembel bir programcıdır. Her şey maksimum düzeyde otomatik olmalıdır.
* **Hata ayıklama için SQL sorgusunu alma imkansızlığı**
  * Programda bir SQL sorgusunun neden çalışmadığını anlamak için onu hata ayıklamak gerekir — mantıksal veya sözdizimsel bir hata bulmak. Hatayı bulmak için, veritabanının "şikayet ettiği" SQL sorgusunun kendisini, gövdesinde yerine konan parametrelerle "görmek" gerekir. Yani, tam olarak oluşturulmuş SQL'e sahip olmak.
Geliştirici hazırlanmış ifadelerle PDO kullanıyorsa, bu... İMKANSIZ! Bunun için maksimum düzeyde uygun mekanizmalar yerel kütüphanelerde [SAĞLANMAMIŞTIR](https://qna.habr.com/q/22669). Sadece kıvranmak veya veritabanı günlüğüne bakmak kalır.


### Çözüm: `krugozor/database` — MySQL ile çalışmak için sınıf

1. Ayrıntılılığı ortadan kaldırır — "yerel" kütüphane kullanılırken bir sorguyu yürütmek için 3 veya daha fazla satır kod yerine, sadece bir tane yazarsınız.
2. Belirtilen yer tutucu türüne göre sorgu gövdesine giren tüm parametreleri kaçırır — SQL enjeksiyonlarına karşı güvenilir koruma.
3. "Yerel" mysqli adaptörünün işlevselliğini değiştirmez, sadece onu tamamlar.
4. Genişletilebilir. Özünde, kütüphane yalnızca bir ayrıştırıcı ve SQL enjeksiyonlarına karşı garantili koruma ile SQL sorgu yürütmesi sağlar. Kütüphanenin herhangi bir sınıfından miras alabilir ve hem kütüphanenin mekanizmalarını hem de `mysqli` ve `mysqli_result` mekanizmalarını kullanarak gerekli yöntemleri oluşturabilirsiniz.

### `krugozor/database` kütüphanesi ne DEĞİLDİR?

Çeşitli veritabanı sürücüleri için sarmalayıcıların çoğu, korkunç mimariye sahip işe yaramaz kod yığınlarıdır. Yazarları, sarmalayıcılarının pratik amacını kendileri anlamadan, onları sorgu oluşturucular, ActiveRecord kütüphaneleri ve diğer ORM çözümleri haline getirirler.

`krugozor/database` kütüphanesi bunlardan hiçbiri değildir. Bu, MySQL DBMS çerçevesinde normal SQL ile çalışmak için sadece kullanışlı bir araçtır — ve daha fazlası değil!


## placeholders (yer tutucular) nedir?

**Placeholders** (yer tutucular), **SQL sorgu dizesinde *açık değerler (sorgu parametreleri) yerine* yazılan özel türde işaretleyicilerdir**. Değerlerin kendisi "daha sonra", SQL sorgusunu yürüten ana yöntemin sonraki argümanları olarak iletilir:

```php
$result = $db->query(
    "SELECT * FROM `users` WHERE `name` = '?s' AND `age` = ?i",
    "D'Artagnan", 41
);
```

*placeholders* sisteminden geçen SQL sorgu parametreleri, yer tutucu türüne bağlı olarak özel kaçırma mekanizmaları tarafından işlenir. Yani, artık değişkenleri `mysqli_real_escape_string()` gibi kaçırma işlevlerine koymaya veya daha önce yapıldığı gibi sayısal türe dönüştürmeye gerek yoktur:

```php
<?php
// Daha önce, DBMS'ye her sorgudan önce
// kabaca bunu yapıyorduk (ve birçok kişi hala "bunu" yapmıyor):
$id = (int) $_POST['id'];
$value = mysqli_real_escape_string($mysql, $_POST['value']);
$result = mysqli_query($mysql, "SELECT * FROM `t` WHERE `f1` = '$value' AND `f2` = $id");
```

Artık sorgu yazmak kolay, hızlı hale geldi ve en önemlisi, `krugozor/database` kütüphanesi tüm olası SQL enjeksiyonlarını tamamen önler.


### Yer tutucu sistemine giriş

Yer tutucu türleri ve amaçları aşağıda açıklanmıştır. Yer tutucu türlerine aşina olmadan önce, kütüphanenin mekanizmasının nasıl çalıştığını anlamak gerekir.

#### PHP'nin problemi

PHP zayıf tipli bir dildir ve bu kütüphanenin geliştirilmesi sırasında ideolojik bir ikilem ortaya çıktı.
Aşağıdaki yapıya sahip bir tablomuz olduğunu hayal edin:

```sql
`name` varchar not null
`flag` tinyint not null
```
ve kütüphane (muhtemelen geliştiriciye bağlı olmayan bir nedenle) aşağıdaki sorguyu yürütmek ZORUNDA:

```php
$db->query(
    "INSERT INTO `t` SET `name` = '?s', `flag` = ?i",
    null, false
);
```
Bu örnekte, metin `not null` alanı `name`'e `null` değeri yazma girişimi var,
ve sayısal alan `flag`'e boolean tipi `false`. Bu durumda ne yapmalı?

* Sorgu parametrelerinin doğrulanmasından kim sorumlu - istemci kodu mu kütüphane mi?
* Bu durumda program yürütmesini kesmek mi gerekli, yoksa belki de verilerin veritabanına yazılması için bazı manipülasyonlar uygulanmalı mı?
* `tinyint` sütunu için `false` değerini `0` değeri olarak ve `name` sütunu için `null`'ı boş dize olarak yorumlayabilir miyiz?
* Kodumuzdaki bu sorunu nasıl basitleştirebilir veya standartlaştırabiliriz?

Ortaya atılan sorular göz önüne alındığında, bu kütüphanede iki çalışma modu uygulamaya karar verildi.

### Kütüphanenin çalışma modları

  * **Mysql::MODE_STRICT — yer tutucu türü ile argüman türü arasında katı eşleşme modu**.
    `Mysql::MODE_STRICT` modunda *argüman türü yer tutucu türüyle eşleşmelidir*. Örneğin, tamsayı türü yer tutucu `?i` için argüman olarak `55.5` veya `'55.5'` değerini iletme girişimi bir istisna atacaktır:

```php
// katı mod ayarla
$db->setTypeMode(Mysql::MODE_STRICT);
// bu ifade yürütülmeyecek, istisna atılacak:
// "SELECT ?i" sorgu şablonunda "integer" türündeki yer tutucu için "double" türünde değer belirtme girişimi
$db->query('SELECT ?i', 55.5);
```

* **Mysql::MODE_TRANSFORM — yer tutucu türü ile argüman türü eşleşmediğinde argümanı yer tutucu türüne dönüştürme modu.** `Mysql::MODE_TRANSFORM` modu varsayılan olarak ayarlanmıştır ve "hoşgörülü" bir moddur — yer tutucu türü ile argüman türü eşleşmediğinde istisna oluşturmaz, *PHP dilinin kendisi aracılığıyla argümanı gerekli yer tutucu türüne dönüştürmeye çalışır*. Bu arada, ben, kütüphanenin yazarı olarak, her zaman tam olarak bu modu kullanıyorum, katı modu (`Mysql::MODE_STRICT`) gerçek çalışmada hiç kullanmadım, ama belki sizin için gerekli olacaktır.

**Mysql::MODE_TRANSFORM modunda aşağıdaki dönüşümlere izin verilir:**

* **`int` türüne (yer tutucu `?i`) dönüştürülür**
  * hem `string` türünde hem de `double` türünde temsil edilen kayan noktalı sayılar
  * `bool` TRUE `int(1)`'e, FALSE `int(0)`'a dönüştürülür
  * `null` `int(0)`'a dönüştürülür
* **`double` türüne (yer tutucu `?d`) dönüştürülür**
  * hem `string` türünde hem de `int` türünde temsil edilen tamsayılar
  * `bool` TRUE `float(1)`'e, FALSE `float(0)`'a dönüştürülür
  * `null` `float(0)`'a dönüştürülür
* **`string` türüne (yer tutucu `?s`) dönüştürülür**
  * `bool` TRUE `string(1) "1"`'e, FALSE `string(1) "0"`'a dönüştürülür. Bu davranış, PHP'de `bool`'dan `int`'e tür dönüşümünden farklıdır, çünkü çoğu zaman, pratikte, boolean türü MySQL'de tam olarak sayı olarak yazılır.
  * `numeric` türünün değeri PHP dönüşüm kurallarına göre dizeye dönüştürülür
  * `null` `string(0) ""`'a dönüştürülür
* **`null` türüne (yer tutucu `?n`) dönüştürülür**
  * herhangi bir argüman.
* Diziler, nesneler ve kaynaklar için dönüşümlere izin verilmez.


### Kütüphane hangi yer tutucu türlerini sunar?


#### `?i` — tamsayılar için yer tutucu

```php
$db->query(
    'SELECT * FROM `users` WHERE `id` = ?i', 123
);
```
Şablon dönüşümünden sonra SQL sorgusu:
```sql
SELECT * FROM `users` WHERE `id` = 123
```

**DİKKAT!** `PHP_INT_MAX`'ı aşan sayılarla çalışıyorsanız:
* Programlarınızda bunları yalnızca dizeler olarak kullanın.
* Bu yer tutucuyu kullanmayın, dize yer tutucusu `?s`'yi kullanın (aşağıya bakın). Sorun şu ki, `PHP_INT_MAX`'ı aşan sayıları PHP kayan noktalı sayılar olarak yorumlar. Kütüphanenin ayrıştırıcısı parametreyi `int` türüne dönüştürmeye çalışacak, sonuç olarak «*sonuç tanımsız olacaktır, çünkü float doğru sonucu döndürmek için yeterli hassasiyete sahip değildir. Bu durumda ne uyarı ne de bildirim çıkarılmayacaktır!*» — [php.net](https://www.php.net/manual/tr/language.types.integer.php#language.types.integer.casting.from-float).

#### `?d` — kayan noktalı sayılar için yer tutucu

```php
$db->query(
    'SELECT * FROM `prices` WHERE `cost` IN (?d, ?d)',
    12.56, '12.33'
);
```
Şablon dönüşümünden sonra SQL sorgusu:
```sql
SELECT * FROM `prices` WHERE `cost` IN (12.56, 12.33)
```

**DİKKAT!** `double` veri türüyle çalışmak için kütüphaneyi kullanıyorsanız, hem PHP düzeyinde hem de DBMS düzeyinde tamsayı ve kesirli kısım arasındaki ayırıcının aynı olması için uygun yerel ayarı yapın.

#### `?s` — dize türü için yer tutucu

Argüman değerleri `mysqli::real_escape_string()` yöntemiyle kaçırılır:

```php
$db->query(
    'SELECT "?s"',
    "Hepiniz aptalsınız ve ben D'Artagnan'ım!"
);
```
 Şablon dönüşümünden sonra SQL sorgusu:

```sql
SELECT "Hepiniz aptalsınız ve ben D\'Artagnan\'ım!"
```
#### `?S` — SQL LIKE operatörüne yerleştirme için dize türü yer tutucu

Argüman değerleri `mysqli::real_escape_string()` yöntemiyle kaçırılır + LIKE operatöründe kullanılan özel karakterlerin (`%` ve `_`) kaçırılması:

```php
$db->query('SELECT "?S"', '% _');
```
 Şablon dönüşümünden sonra SQL sorgusu:
```sql
SELECT "\% \_"
```

 #### `?n` — `NULL` türü için yer tutucu
Herhangi bir argümanın değerleri yok sayılır, yer tutucular SQL sorgusunda `NULL` dizesiyle değiştirilir:

```php
$db->query('SELECT ?n', 123);
```
 Şablon dönüşümünden sonra SQL sorgusu:
```sql
SELECT NULL
```

 #### `?A*` — ilişkisel diziden ilişkisel küme için yer tutucu, `anahtar = değer` biçiminde çift dizisi üretir

burada `*` sembolü aşağıdaki yer tutuculardan biridir:

 * `i` (tamsayılar için yer tutucu)
 * `d` (kayan noktalı sayılar için yer tutucu)
 * `s` (dize türü için yer tutucu)

 dönüştürme ve kaçırma kuralları yukarıda açıklanan tekli skaler türlerle aynıdır. Örnek:

```php
$db->query(
    'INSERT INTO `test` SET ?Ai',
    ['first' => '123', 'second' => 456]
);
```
Şablon dönüşümünden sonra SQL sorgusu:
```sql
INSERT INTO `test` SET `first` = "123", `second` = "456"
```

#### `?a*` — basit (veya ilişkisel) diziden küme için yer tutucu, değer dizisi üretir

 burada `*` aşağıdaki türlerden biridir:
 * `i` (tamsayılar için yer tutucu)
 * `d` (kayan noktalı sayılar için yer tutucu)
 * `s` (dize türü için yer tutucu)

 dönüştürme ve kaçırma kuralları yukarıda açıklanan tekli skaler türlerle aynıdır. Örnek:

```php
$db->query(
    'SELECT * FROM `test` WHERE `id` IN (?ai)',
    [123, 456]
);
```
Şablon dönüşümünden sonra SQL sorgusu:
```sql
SELECT * FROM `test` WHERE `id` IN ("123", "456")
```


#### `?A[?n, ?s, ?i, ...]` — tür ve argüman sayısının açık gösterimi ile ilişkisel küme için yer tutucu, `anahtar = değer` çift dizisi üretir

Örnek:
```php
$db->query(
    'INSERT INTO `users` SET ?A[?i, "?s"]',
    ['age' => 41, 'name' => "D'Artagnan"]
);
```
Şablon dönüşümünden sonra SQL sorgusu:
```sql
INSERT INTO `users` SET `age` = 41,`name` = "D\'Artagnan"
```

#### `?a[?n, ?s, ?i, ...]` — tür ve argüman sayısının açık gösterimi ile küme için yer tutucu, değer dizisi üretir
Örnek:
```php
$db->query(
    'SELECT * FROM `users` WHERE `name` IN (?a["?s", "?s"])',
    ["markiz d\"Arquien", "D'Artagnan"]
);
```
Şablon dönüşümünden sonra SQL sorgusu:
```sql
SELECT * FROM `users` WHERE `name` IN ("markiz d\"Arquien", "D\'Artagnan")
```


#### `?f` — tablo veya alan adı için yer tutucu

Bu yer tutucu, tablo veya alan adının sorguda parametre aracılığıyla iletildiği durumlar içindir. Alan ve tablo adları "backtick" sembolü ile çerçevelenir:

```php
$db->query(
    'SELECT ?f FROM ?f',
    'name',
    'database.table_name'
);
```
 Şablon dönüşümünden sonra SQL sorgusu:
```sql
SELECT `name` FROM `database`.`table_name`
```


### Sınırlayıcı tırnak işaretleri

**Kütüphane, programcının SQL sözdizimini takip etmesini gerektirir.** Bu, aşağıdaki sorgunun çalışmayacağı anlamına gelir:

```php
$db->query(
    'SELECT CONCAT("Hello, ", ?s, "!")',
    'world'
);
```
— yer tutucu `?s` tek veya çift tırnak içine alınmalıdır:
```php
$db->query(
    'SELECT concat("Hello, ", "?s", "!")',
    'world'
);
```
Şablon dönüşümünden sonra SQL sorgusu:
```sql
SELECT concat("Hello, ", "world", "!")
```

PDO ile çalışmaya alışkın olanlar için bu garip görünebilir, ancak bir durumda yer tutucu değerinin tırnak içine alınıp alınmayacağını belirleyen bir mekanizma uygulamak, tam bir ayrıştırıcı yazmayı gerektiren çok önemsiz olmayan bir görevdir.


## Kütüphane ile çalışma örnekleri

Dosyaya bakın [../console/tests.php](../console/tests.php)
