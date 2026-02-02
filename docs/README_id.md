**Bahasa lain:**
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
- [Documentação em Português (BR)](README_pt-BR.md)
- [हिंदी दस्तावेज़](README_hi.md)
- [التوثيق بالعربية](README_ar.md)
- [Türkçe Dokümantasyon](README_tr.md)
- [Tài liệu tiếng Việt](README_vi.md)

---

## Mendapatkan Pustaka

Anda dapat [mengunduhnya sebagai arsip](https://github.com/Vasiliy-Makogon/Database/archive/master.zip), mengkloningnya dari situs ini, atau menginstalnya melalui composer ([tautan ke packagist.org](https://packagist.org/packages/krugozor/database)):
```
composer require krugozor/database
```


## Apa itu `krugozor/database`?

`krugozor/database` adalah pustaka kelas PHP >= 8.0 untuk bekerja dengan database MySQL secara sederhana, nyaman, cepat, dan aman, menggunakan ekstensi PHP [mysqli](https://www.php.net/manual/id/book.mysqli.php).


### Mengapa memerlukan kelas kustom untuk MySQL jika PHP sudah memiliki abstraksi PDO dan ekstensi mysqli?

Kekurangan utama dari semua pustaka untuk bekerja dengan database MySQL di PHP adalah:

* **Verbositas**
  * Untuk mencegah injeksi SQL, pengembang memiliki dua cara:
    * Menggunakan [prepared statements](https://www.php.net/manual/id/mysqli.quickstart.prepared-statements.php).
    * Secara manual meng-escape parameter yang masuk ke dalam body query SQL. Parameter string dijalankan melalui [mysqli_real_escape_string](https://www.php.net/manual/id/mysqli.real-escape-string.php), dan parameter numerik yang diharapkan dikonversi ke tipe yang sesuai — `int` dan `float`.
  * Kedua pendekatan memiliki kekurangan yang sangat besar:
    * Prepared statements [sangat bertele-tele](https://www.php.net/manual/id/mysqli.prepare.php#refsect1-mysqli.prepare-examples). Menggunakan abstraksi PDO atau ekstensi mysqli "out of the box", tanpa mengagregasi semua metode untuk mendapatkan data dari DBMS sama sekali tidak mungkin — untuk mendapatkan nilai dari tabel, Anda harus menulis minimal 5 baris kode! Dan begitu untuk setiap query!
    * Escape manual parameter yang masuk ke body query SQL — bahkan tidak dibahas. Programmer yang baik adalah programmer yang malas. Semuanya harus diotomatisasi semaksimal mungkin.
* **Ketidakmungkinan mendapatkan query SQL untuk debugging**
  * Untuk memahami mengapa query SQL tidak bekerja dalam program, perlu men-debug-nya — menemukan kesalahan logis atau sintaksis. Untuk menemukan kesalahan, perlu "melihat" query SQL itu sendiri, yang "dikeluhkan" oleh database, dengan parameter yang disubstitusi dalam body-nya. Yaitu, memiliki SQL yang terbentuk lengkap.
Jika pengembang menggunakan PDO dengan prepared statements, ini... TIDAK MUNGKIN! Tidak ada mekanisme yang sangat nyaman untuk ini yang [DISEDIAKAN](https://qna.habr.com/q/22669) dalam pustaka native. Hanya tersisa untuk melakukan akrobat atau melihat ke dalam log database.


### Solusi: `krugozor/database` — kelas untuk bekerja dengan MySQL

1. Menghilangkan verbositas — alih-alih 3 atau lebih baris kode untuk mengeksekusi satu query saat menggunakan pustaka "native", Anda hanya menulis satu baris.
2. Meng-escape semua parameter yang masuk ke body query, sesuai dengan tipe placeholder yang ditentukan — perlindungan yang andal dari injeksi SQL.
3. Tidak menggantikan fungsionalitas adaptor mysqli "native", tetapi hanya melengkapinya.
4. Dapat diperluas. Pada dasarnya, pustaka hanya menyediakan parser dan eksekusi query SQL dengan perlindungan terjamin dari injeksi SQL. Anda dapat mewarisi dari kelas mana pun dari pustaka dan menggunakan baik mekanisme pustaka maupun mekanisme `mysqli` dan `mysqli_result` untuk membuat metode yang Anda butuhkan.

### Apa yang BUKAN pustaka `krugozor/database`?

Sebagian besar wrapper untuk berbagai driver database adalah timbunan kode yang tidak berguna dengan arsitektur yang mengerikan. Penulis mereka, tidak memahami tujuan praktis dari wrapper mereka sendiri, mengubahnya menjadi query builder, pustaka ActiveRecord, dan solusi ORM lainnya.

Pustaka `krugozor/database` bukan salah satu dari yang disebutkan. Ini hanya alat yang nyaman untuk bekerja dengan SQL biasa dalam kerangka DBMS MySQL — dan tidak lebih!


## Apa itu placeholders (penampung)?

**Placeholders** (penampung) adalah **penanda bertipe khusus yang ditulis dalam string query SQL *alih-alih nilai eksplisit (parameter query)***. Nilai itu sendiri diteruskan "nanti", sebagai argumen berikutnya dari metode utama yang mengeksekusi query SQL:

```php
$result = $db->query(
    "SELECT * FROM `users` WHERE `name` = '?s' AND `age` = ?i",
    "D'Artagnan", 41
);
```

Parameter query SQL yang telah melalui sistem *placeholders* diproses oleh mekanisme escape khusus, tergantung pada tipe placeholder. Yaitu, sekarang Anda tidak perlu lagi menempatkan variabel dalam fungsi escape seperti `mysqli_real_escape_string()` atau mengkonversinya ke tipe numerik, seperti yang dilakukan sebelumnya:

```php
<?php
// Sebelumnya, sebelum setiap query ke DBMS kita melakukan
// kira-kira ini (dan banyak yang masih tidak melakukan "ini"):
$id = (int) $_POST['id'];
$value = mysqli_real_escape_string($mysql, $_POST['value']);
$result = mysqli_query($mysql, "SELECT * FROM `t` WHERE `f1` = '$value' AND `f2` = $id");
```

Sekarang menulis query menjadi mudah, cepat, dan yang paling penting, pustaka `krugozor/database` sepenuhnya mencegah semua injeksi SQL yang mungkin terjadi.


### Pengenalan sistem placeholder

Tipe placeholder dan tujuannya dijelaskan di bawah ini. Sebelum mengenal tipe placeholder, perlu memahami bagaimana mekanisme pustaka bekerja.

#### Masalah PHP

PHP adalah bahasa yang bertype lemah dan selama pengembangan pustaka ini muncul dilema ideologis.
Bayangkan kita memiliki tabel dengan struktur berikut:

```sql
`name` varchar not null
`flag` tinyint not null
```
dan pustaka HARUS (karena alasan tertentu, mungkin tidak tergantung pada pengembang) mengeksekusi query berikut:

```php
$db->query(
    "INSERT INTO `t` SET `name` = '?s', `flag` = ?i",
    null, false
);
```
Dalam contoh ini, ada upaya untuk menulis nilai `null` ke dalam field teks `not null` `name`,
dan tipe boolean `false` ke dalam field numerik `flag`. Apa yang harus dilakukan dalam situasi ini?

* Siapa yang bertanggung jawab atas validasi parameter query - kode klien atau pustaka?
* Apakah perlu dalam kasus ini menghentikan eksekusi program atau, mungkin, menerapkan beberapa manipulasi agar data ditulis ke database?
* Apakah kita dapat menafsirkan nilai `false` untuk kolom `tinyint` sebagai nilai `0`, dan `null` sebagai string kosong untuk kolom `name`?
* Bagaimana kita dapat menyederhanakan atau menstandardisasi masalah ini dalam kode kita?

Mengingat pertanyaan-pertanyaan yang diajukan, diputuskan untuk mengimplementasikan dua mode operasi dalam pustaka ini.

### Mode operasi pustaka

  * **Mysql::MODE_STRICT — mode ketat kesesuaian antara tipe placeholder dan tipe argumen**.
    Dalam mode `Mysql::MODE_STRICT` *tipe argumen harus sesuai dengan tipe placeholder*. Misalnya, upaya untuk meneruskan nilai `55.5` atau `'55.5'` sebagai argumen untuk placeholder tipe integer `?i` akan memunculkan exception:

```php
// menetapkan mode ketat
$db->setTypeMode(Mysql::MODE_STRICT);
// ekspresi ini tidak akan dieksekusi, exception akan dimunculkan:
// upaya untuk menentukan nilai tipe "double" untuk placeholder tipe "integer" dalam template query "SELECT ?i"
$db->query('SELECT ?i', 55.5);
```

* **Mysql::MODE_TRANSFORM — mode konversi argumen ke tipe placeholder jika tipe placeholder dan tipe argumen tidak sesuai.** Mode `Mysql::MODE_TRANSFORM` diatur secara default dan merupakan mode "toleran" — jika tipe placeholder dan tipe argumen tidak sesuai, tidak menghasilkan exception, tetapi *mencoba mengkonversi argumen ke tipe placeholder yang diperlukan melalui bahasa PHP itu sendiri*. Omong-omong, saya, sebagai penulis pustaka, selalu menggunakan mode ini, mode ketat (`Mysql::MODE_STRICT`) tidak pernah saya gunakan dalam pekerjaan nyata, tetapi mungkin Anda membutuhkannya.

**Konversi berikut diperbolehkan dalam mode Mysql::MODE_TRANSFORM:**

* **Ke tipe `int` (placeholder `?i`) dikonversi**
  * angka floating point, baik yang direpresentasikan dalam tipe `string` maupun dalam tipe `double`
  * `bool` TRUE dikonversi menjadi `int(1)`, FALSE dikonversi menjadi `int(0)`
  * `null` dikonversi menjadi `int(0)`
* **Ke tipe `double` (placeholder `?d`) dikonversi**
  * bilangan bulat, baik yang direpresentasikan dalam tipe `string` maupun dalam tipe `int`
  * `bool` TRUE dikonversi menjadi `float(1)`, FALSE dikonversi menjadi `float(0)`
  * `null` dikonversi menjadi `float(0)`
* **Ke tipe `string` (placeholder `?s`) dikonversi**
  * `bool` TRUE dikonversi menjadi `string(1) "1"`, FALSE dikonversi menjadi `string(1) "0"`. Perilaku ini berbeda dari konversi tipe `bool` ke `int` dalam PHP, karena seringkali, dalam praktik, tipe boolean ditulis di MySQL justru sebagai angka.
  * nilai tipe `numeric` dikonversi menjadi string sesuai aturan konversi PHP
  * `null` dikonversi menjadi `string(0) ""`
* **Ke tipe `null` (placeholder `?n`) dikonversi**
  * argumen apa pun.
* Untuk array, objek, dan resource konversi tidak diperbolehkan.


### Tipe placeholder apa yang disediakan pustaka?


#### `?i` — placeholder untuk bilangan bulat

```php
$db->query(
    'SELECT * FROM `users` WHERE `id` = ?i', 123
);
```
Query SQL setelah konversi template:
```sql
SELECT * FROM `users` WHERE `id` = 123
```

**PERHATIAN!** Jika Anda bekerja dengan angka yang melebihi `PHP_INT_MAX`, maka:
* Operasikan mereka secara eksklusif sebagai string dalam program Anda.
* Jangan gunakan placeholder ini, gunakan placeholder string `?s` (lihat di bawah). Masalahnya adalah bahwa angka yang melebihi `PHP_INT_MAX`, PHP menginterpretasikannya sebagai angka floating point. Parser pustaka akan mencoba mengkonversi parameter ke tipe `int`, akibatnya «*hasilnya akan tidak terdefinisi, karena float tidak memiliki presisi yang cukup untuk mengembalikan hasil yang benar. Dalam kasus ini tidak akan ada peringatan atau bahkan notice yang dikeluarkan!*» — [php.net](https://www.php.net/manual/id/language.types.integer.php#language.types.integer.casting.from-float).

#### `?d` — placeholder untuk angka floating point

```php
$db->query(
    'SELECT * FROM `prices` WHERE `cost` IN (?d, ?d)',
    12.56, '12.33'
);
```
Query SQL setelah konversi template:
```sql
SELECT * FROM `prices` WHERE `cost` IN (12.56, 12.33)
```

**PERHATIAN!** Jika Anda menggunakan pustaka untuk bekerja dengan tipe data `double`, tetapkan locale yang sesuai agar pemisah antara bagian integer dan fractional sama baik di level PHP maupun di level DBMS.

#### `?s` — placeholder untuk tipe string

Nilai argumen di-escape dengan metode `mysqli::real_escape_string()`:

```php
$db->query(
    'SELECT "?s"',
    "Kalian semua bodoh, dan saya adalah D'Artagnan!"
);
```
 Query SQL setelah konversi template:

```sql
SELECT "Kalian semua bodoh, dan saya adalah D\'Artagnan!"
```
#### `?S` — placeholder untuk tipe string untuk penyisipan dalam operator SQL LIKE

Nilai argumen di-escape dengan metode `mysqli::real_escape_string()` + escape karakter khusus yang digunakan dalam operator LIKE (`%` dan `_`):

```php
$db->query('SELECT "?S"', '% _');
```
 Query SQL setelah konversi template:
```sql
SELECT "\% \_"
```

 #### `?n` — placeholder untuk tipe `NULL`
Nilai argumen apa pun diabaikan, placeholder diganti dengan string `NULL` dalam query SQL:

```php
$db->query('SELECT ?n', 123);
```
 Query SQL setelah konversi template:
```sql
SELECT NULL
```

 #### `?A*` — placeholder untuk himpunan asosiatif dari array asosiatif, menghasilkan urutan pasangan dalam bentuk `kunci = nilai`

di mana simbol `*` adalah salah satu placeholder:

 * `i` (placeholder untuk bilangan bulat)
 * `d` (placeholder untuk angka floating point)
 * `s` (placeholder untuk tipe string)

 aturan konversi dan escape sama dengan tipe skalar tunggal yang dijelaskan di atas. Contoh:

```php
$db->query(
    'INSERT INTO `test` SET ?Ai',
    ['first' => '123', 'second' => 456]
);
```
Query SQL setelah konversi template:
```sql
INSERT INTO `test` SET `first` = "123", `second` = "456"
```

#### `?a*` — placeholder untuk himpunan dari array sederhana (atau juga asosiatif), menghasilkan urutan nilai

 di mana `*` adalah salah satu tipe:
 * `i` (placeholder untuk bilangan bulat)
 * `d` (placeholder untuk angka floating point)
 * `s` (placeholder untuk tipe string)

 aturan konversi dan escape sama dengan tipe skalar tunggal yang dijelaskan di atas. Contoh:

```php
$db->query(
    'SELECT * FROM `test` WHERE `id` IN (?ai)',
    [123, 456]
);
```
Query SQL setelah konversi template:
```sql
SELECT * FROM `test` WHERE `id` IN ("123", "456")
```


#### `?A[?n, ?s, ?i, ...]` — placeholder untuk himpunan asosiatif dengan penentuan eksplisit tipe dan jumlah argumen, menghasilkan urutan pasangan `kunci = nilai`

Contoh:
```php
$db->query(
    'INSERT INTO `users` SET ?A[?i, "?s"]',
    ['age' => 41, 'name' => "D'Artagnan"]
);
```
Query SQL setelah konversi template:
```sql
INSERT INTO `users` SET `age` = 41,`name` = "D\'Artagnan"
```

#### `?a[?n, ?s, ?i, ...]` — placeholder untuk himpunan dengan penentuan eksplisit tipe dan jumlah argumen, menghasilkan urutan nilai
Contoh:
```php
$db->query(
    'SELECT * FROM `users` WHERE `name` IN (?a["?s", "?s"])',
    ["marquis d\"Arquien", "D'Artagnan"]
);
```
Query SQL setelah konversi template:
```sql
SELECT * FROM `users` WHERE `name` IN ("marquis d\"Arquien", "D\'Artagnan")
```


#### `?f` — placeholder untuk nama tabel atau field

Placeholder ini ditujukan untuk kasus ketika nama tabel atau field diteruskan dalam query melalui parameter. Nama field dan tabel dibingkai dengan simbol "backtick":

```php
$db->query(
    'SELECT ?f FROM ?f',
    'name',
    'database.table_name'
);
```
 Query SQL setelah konversi template:
```sql
SELECT `name` FROM `database`.`table_name`
```


### Tanda kutip pembatas

**Pustaka memerlukan programmer untuk mematuhi sintaks SQL.** Ini berarti query berikut tidak akan bekerja:

```php
$db->query(
    'SELECT CONCAT("Hello, ", ?s, "!")',
    'world'
);
```
— placeholder `?s` harus diberi tanda kutip tunggal atau ganda:
```php
$db->query(
    'SELECT concat("Hello, ", "?s", "!")',
    'world'
);
```
Query SQL setelah konversi template:
```sql
SELECT concat("Hello, ", "world", "!")
```

Bagi mereka yang terbiasa bekerja dengan PDO, ini mungkin tampak aneh, tetapi mengimplementasikan mekanisme yang menentukan apakah dalam satu kasus nilai placeholder perlu diberi tanda kutip atau tidak adalah tugas yang sangat tidak trivial yang memerlukan penulisan parser lengkap.


## Contoh bekerja dengan pustaka

Lihat dalam file [../console/tests.php](../console/tests.php)
