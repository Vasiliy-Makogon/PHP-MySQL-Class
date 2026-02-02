**Các ngôn ngữ khác:**
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
- [Türkçe Dokümantasyon](README_tr.md)

---

## Tải thư viện

Bạn có thể [tải xuống dưới dạng lưu trữ](https://github.com/Vasiliy-Makogon/Database/archive/master.zip), sao chép từ trang web này hoặc cài đặt qua composer ([liên kết đến packagist.org](https://packagist.org/packages/krugozor/database)):
```
composer require krugozor/database
```


## `krugozor/database` là gì?

`krugozor/database` là một thư viện lớp PHP >= 8.0 để làm việc đơn giản, tiện lợi, nhanh chóng và an toàn với cơ sở dữ liệu MySQL, sử dụng phần mở rộng PHP [mysqli](https://www.php.net/manual/vi/book.mysqli.php).


### Tại sao cần một lớp tùy chỉnh cho MySQL nếu PHP đã có trừu tượng PDO và phần mở rộng mysqli?

Những nhược điểm chính của tất cả các thư viện để làm việc với cơ sở dữ liệu MySQL trong PHP là:

* **Dài dòng**
  * Để ngăn chặn SQL injection, các nhà phát triển có hai cách:
    * Sử dụng [prepared statements](https://www.php.net/manual/vi/mysqli.quickstart.prepared-statements.php).
    * Thoát thủ công các tham số đi vào thân truy vấn SQL. Chạy các tham số chuỗi qua [mysqli_real_escape_string](https://www.php.net/manual/vi/mysqli.real-escape-string.php) và chuyển đổi các tham số số dự kiến sang các kiểu tương ứng — `int` và `float`.
  * Cả hai phương pháp đều có những nhược điểm to lớn:
    * Prepared statements [cực kỳ dài dòng](https://www.php.net/manual/vi/mysqli.prepare.php#refsect1-mysqli.prepare-examples). Sử dụng trừu tượng PDO hoặc phần mở rộng mysqli "ngay lập tức", mà không tổng hợp tất cả các phương thức để lấy dữ liệu từ DBMS là hoàn toàn không thể — để lấy giá trị từ bảng, cần phải viết tối thiểu 5 dòng mã! Và như vậy cho mỗi truy vấn!
    * Thoát thủ công các tham số đi vào thân truy vấn SQL — thậm chí không được thảo luận. Một lập trình viên giỏi là một lập trình viên lười biếng. Mọi thứ phải được tự động hóa tối đa.
* **Không thể lấy truy vấn SQL để gỡ lỗi**
  * Để hiểu tại sao truy vấn SQL không hoạt động trong chương trình, cần phải gỡ lỗi nó — tìm lỗi logic hoặc cú pháp. Để tìm lỗi, cần "nhìn thấy" chính truy vấn SQL, mà cơ sở dữ liệu "phàn nàn", với các tham số được thay thế trong thân của nó. Tức là, có SQL được hình thành đầy đủ.
Nếu nhà phát triển sử dụng PDO với prepared statements, điều này... KHÔNG THỂ! Không có cơ chế tiện lợi tối đa cho việc này [ĐƯỢC CUNG CẤP](https://qna.habr.com/q/22669) trong các thư viện gốc. Chỉ còn cách là vặn vẹo hoặc nhìn vào log cơ sở dữ liệu.


### Giải pháp: `krugozor/database` — lớp để làm việc với MySQL

1. Loại bỏ sự dài dòng — thay vì 3 dòng mã trở lên để thực hiện một truy vấn khi sử dụng thư viện "gốc", bạn chỉ viết một dòng.
2. Thoát tất cả các tham số đi vào thân truy vấn, theo loại placeholder được chỉ định — bảo vệ đáng tin cậy khỏi SQL injection.
3. Không thay thế chức năng của adapter mysqli "gốc", mà chỉ bổ sung cho nó.
4. Có thể mở rộng. Về cơ bản, thư viện chỉ cung cấp trình phân tích cú pháp và thực thi truy vấn SQL với bảo vệ được đảm bảo khỏi SQL injection. Bạn có thể kế thừa từ bất kỳ lớp nào của thư viện và sử dụng cả cơ chế của thư viện và cơ chế của `mysqli` và `mysqli_result` để tạo các phương thức cần thiết.

### Thư viện `krugozor/database` KHÔNG phải là gì?

Hầu hết các wrapper cho các driver cơ sở dữ liệu khác nhau là một đống mã vô dụng với kiến trúc tồi tệ. Các tác giả của chúng, không hiểu mục đích thực tế của wrapper của họ, biến chúng thành query builders, thư viện ActiveRecord và các giải pháp ORM khác.

Thư viện `krugozor/database` không phải là bất kỳ thứ gì trong số đó. Đây chỉ là một công cụ tiện lợi để làm việc với SQL thông thường trong khuôn khổ DBMS MySQL — và không hơn!


## placeholders (trình giữ chỗ) là gì?

**Placeholders** (trình giữ chỗ) là **các dấu hiệu đặc biệt được đánh kiểu được viết trong chuỗi truy vấn SQL *thay vì các giá trị rõ ràng (tham số truy vấn)***. Chính các giá trị được truyền "sau", làm đối số tiếp theo của phương thức chính thực thi truy vấn SQL:

```php
$result = $db->query(
    "SELECT * FROM `users` WHERE `name` = '?s' AND `age` = ?i",
    "D'Artagnan", 41
);
```

Các tham số truy vấn SQL đã đi qua hệ thống *placeholders* được xử lý bởi các cơ chế thoát đặc biệt, tùy thuộc vào loại placeholder. Tức là, bây giờ bạn không còn cần đặt các biến vào các hàm thoát như `mysqli_real_escape_string()` hoặc chuyển đổi chúng sang kiểu số, như đã làm trước đây:

```php
<?php
// Trước đây, trước mỗi truy vấn đến DBMS chúng tôi đã làm
// khoảng như thế này (và nhiều người vẫn không làm "điều này"):
$id = (int) $_POST['id'];
$value = mysqli_real_escape_string($mysql, $_POST['value']);
$result = mysqli_query($mysql, "SELECT * FROM `t` WHERE `f1` = '$value' AND `f2` = $id");
```

Bây giờ viết truy vấn trở nên dễ dàng, nhanh chóng, và quan trọng nhất, thư viện `krugozor/database` ngăn chặn hoàn toàn tất cả các SQL injection có thể xảy ra.


### Giới thiệu về hệ thống placeholder

Các loại placeholder và mục đích của chúng được mô tả dưới đây. Trước khi làm quen với các loại placeholder, cần hiểu cơ chế của thư viện hoạt động như thế nào.

#### Vấn đề của PHP

PHP là một ngôn ngữ kiểu yếu và trong quá trình phát triển thư viện này đã nảy sinh một thế lưỡng nan về ý thức hệ.
Hãy tưởng tượng chúng ta có một bảng với cấu trúc sau:

```sql
`name` varchar not null
`flag` tinyint not null
```
và thư viện PHẢI (vì lý do nào đó, có thể không phụ thuộc vào nhà phát triển) thực hiện truy vấn sau:

```php
$db->query(
    "INSERT INTO `t` SET `name` = '?s', `flag` = ?i",
    null, false
);
```
Trong ví dụ này, có một nỗ lực ghi giá trị `null` vào trường văn bản `not null` `name`,
và kiểu boolean `false` vào trường số `flag`. Phải làm gì trong tình huống này?

* Ai chịu trách nhiệm xác thực các tham số truy vấn - mã khách hàng hay thư viện?
* Có cần thiết trong trường hợp này phải ngắt việc thực thi chương trình hay có thể áp dụng một số thao tác để dữ liệu được ghi vào cơ sở dữ liệu?
* Chúng ta có thể diễn giải giá trị `false` cho cột `tinyint` là giá trị `0` và `null` là chuỗi rỗng cho cột `name` không?
* Làm thế nào chúng ta có thể đơn giản hóa hoặc chuẩn hóa vấn đề này trong mã của chúng ta?

Xem xét các câu hỏi được đặt ra, đã quyết định triển khai hai chế độ hoạt động trong thư viện này.

### Các chế độ hoạt động của thư viện

  * **Mysql::MODE_STRICT — chế độ khớp nghiêm ngặt giữa loại placeholder và loại đối số**.
    Trong chế độ `Mysql::MODE_STRICT` *loại đối số phải khớp với loại placeholder*. Ví dụ, nỗ lực truyền giá trị `55.5` hoặc `'55.5'` làm đối số cho placeholder kiểu số nguyên `?i` sẽ ném ra một ngoại lệ:

```php
// thiết lập chế độ nghiêm ngặt
$db->setTypeMode(Mysql::MODE_STRICT);
// biểu thức này sẽ không được thực thi, ngoại lệ sẽ được ném ra:
// nỗ lực chỉ định giá trị kiểu "double" cho placeholder kiểu "integer" trong mẫu truy vấn "SELECT ?i"
$db->query('SELECT ?i', 55.5);
```

* **Mysql::MODE_TRANSFORM — chế độ chuyển đổi đối số sang loại placeholder trong trường hợp không khớp loại placeholder và loại đối số.** Chế độ `Mysql::MODE_TRANSFORM` được thiết lập mặc định và là chế độ "khoan dung" — trong trường hợp không khớp loại placeholder và loại đối số không tạo ngoại lệ, mà *cố gắng chuyển đổi đối số sang loại placeholder cần thiết thông qua chính ngôn ngữ PHP*. Nhân tiện, tôi, với tư cách là tác giả của thư viện, luôn sử dụng chế độ này, chế độ nghiêm ngặt (`Mysql::MODE_STRICT`) tôi chưa bao giờ sử dụng trong công việc thực tế, nhưng có thể bạn sẽ cần nó.

**Các chuyển đổi sau được phép trong chế độ Mysql::MODE_TRANSFORM:**

* **Chuyển đổi sang kiểu `int` (placeholder `?i`)**
  * các số dấu phẩy động, được biểu diễn trong cả kiểu `string` và kiểu `double`
  * `bool` TRUE được chuyển đổi thành `int(1)`, FALSE được chuyển đổi thành `int(0)`
  * `null` được chuyển đổi thành `int(0)`
* **Chuyển đổi sang kiểu `double` (placeholder `?d`)**
  * các số nguyên, được biểu diễn trong cả kiểu `string` và kiểu `int`
  * `bool` TRUE được chuyển đổi thành `float(1)`, FALSE được chuyển đổi thành `float(0)`
  * `null` được chuyển đổi thành `float(0)`
* **Chuyển đổi sang kiểu `string` (placeholder `?s`)**
  * `bool` TRUE được chuyển đổi thành `string(1) "1"`, FALSE được chuyển đổi thành `string(1) "0"`. Hành vi này khác với chuyển đổi kiểu `bool` sang `int` trong PHP, vì thường xuyên, trong thực tế, kiểu boolean được ghi trong MySQL chính xác là dưới dạng số.
  * giá trị kiểu `numeric` được chuyển đổi thành chuỗi theo quy tắc chuyển đổi của PHP
  * `null` được chuyển đổi thành `string(0) ""`
* **Chuyển đổi sang kiểu `null` (placeholder `?n`)**
  * bất kỳ đối số nào.
* Không được phép chuyển đổi cho mảng, đối tượng và tài nguyên.


### Thư viện cung cấp những loại placeholder nào?


#### `?i` — placeholder cho số nguyên

```php
$db->query(
    'SELECT * FROM `users` WHERE `id` = ?i', 123
);
```
Truy vấn SQL sau khi chuyển đổi mẫu:
```sql
SELECT * FROM `users` WHERE `id` = 123
```

**CHÚ Ý!** Nếu bạn làm việc với các số vượt quá `PHP_INT_MAX`, thì:
* Chỉ sử dụng chúng dưới dạng chuỗi trong các chương trình của bạn.
* Không sử dụng placeholder này, sử dụng placeholder chuỗi `?s` (xem bên dưới). Vấn đề là các số vượt quá `PHP_INT_MAX`, PHP diễn giải chúng là số dấu phẩy động. Trình phân tích cú pháp của thư viện sẽ cố gắng chuyển đổi tham số sang kiểu `int`, kết quả là «*kết quả sẽ không xác định, vì float không có đủ độ chính xác để trả về kết quả chính xác. Trong trường hợp này sẽ không có cảnh báo hoặc thông báo nào được đưa ra!*» — [php.net](https://www.php.net/manual/vi/language.types.integer.php#language.types.integer.casting.from-float).

#### `?d` — placeholder cho số dấu phẩy động

```php
$db->query(
    'SELECT * FROM `prices` WHERE `cost` IN (?d, ?d)',
    12.56, '12.33'
);
```
Truy vấn SQL sau khi chuyển đổi mẫu:
```sql
SELECT * FROM `prices` WHERE `cost` IN (12.56, 12.33)
```

**CHÚ Ý!** Nếu bạn sử dụng thư viện để làm việc với kiểu dữ liệu `double`, hãy đặt locale phù hợp để dấu phân cách giữa phần nguyên và phần thập phân giống nhau ở cả cấp PHP và cấp DBMS.

#### `?s` — placeholder cho kiểu chuỗi

Giá trị đối số được thoát bằng phương thức `mysqli::real_escape_string()`:

```php
$db->query(
    'SELECT "?s"',
    "Tất cả các bạn đều ngu ngốc, và tôi là D'Artagnan!"
);
```
 Truy vấn SQL sau khi chuyển đổi mẫu:

```sql
SELECT "Tất cả các bạn đều ngu ngốc, và tôi là D\'Artagnan!"
```
#### `?S` — placeholder kiểu chuỗi để chèn vào toán tử SQL LIKE

Giá trị đối số được thoát bằng phương thức `mysqli::real_escape_string()` + thoát các ký tự đặc biệt được sử dụng trong toán tử LIKE (`%` và `_`):

```php
$db->query('SELECT "?S"', '% _');
```
 Truy vấn SQL sau khi chuyển đổi mẫu:
```sql
SELECT "\% \_"
```

 #### `?n` — placeholder cho kiểu `NULL`
Giá trị của bất kỳ đối số nào đều bị bỏ qua, các placeholder được thay thế bằng chuỗi `NULL` trong truy vấn SQL:

```php
$db->query('SELECT ?n', 123);
```
 Truy vấn SQL sau khi chuyển đổi mẫu:
```sql
SELECT NULL
```

 #### `?A*` — placeholder cho tập hợp liên kết từ mảng liên kết, tạo chuỗi các cặp dạng `khóa = giá trị`

trong đó ký hiệu `*` là một trong các placeholder:

 * `i` (placeholder cho số nguyên)
 * `d` (placeholder cho số dấu phẩy động)
 * `s` (placeholder cho kiểu chuỗi)

 các quy tắc chuyển đổi và thoát giống với các kiểu vô hướng đơn được mô tả ở trên. Ví dụ:

```php
$db->query(
    'INSERT INTO `test` SET ?Ai',
    ['first' => '123', 'second' => 456]
);
```
Truy vấn SQL sau khi chuyển đổi mẫu:
```sql
INSERT INTO `test` SET `first` = "123", `second` = "456"
```

#### `?a*` — placeholder cho tập hợp từ mảng đơn giản (hoặc cũng có thể liên kết), tạo chuỗi giá trị

 trong đó `*` là một trong các kiểu:
 * `i` (placeholder cho số nguyên)
 * `d` (placeholder cho số dấu phẩy động)
 * `s` (placeholder cho kiểu chuỗi)

 các quy tắc chuyển đổi và thoát giống với các kiểu vô hướng đơn được mô tả ở trên. Ví dụ:

```php
$db->query(
    'SELECT * FROM `test` WHERE `id` IN (?ai)',
    [123, 456]
);
```
Truy vấn SQL sau khi chuyển đổi mẫu:
```sql
SELECT * FROM `test` WHERE `id` IN ("123", "456")
```


#### `?A[?n, ?s, ?i, ...]` — placeholder cho tập hợp liên kết với chỉ định rõ ràng kiểu và số lượng đối số, tạo chuỗi các cặp `khóa = giá trị`

Ví dụ:
```php
$db->query(
    'INSERT INTO `users` SET ?A[?i, "?s"]',
    ['age' => 41, 'name' => "D'Artagnan"]
);
```
Truy vấn SQL sau khi chuyển đổi mẫu:
```sql
INSERT INTO `users` SET `age` = 41,`name` = "D\'Artagnan"
```

#### `?a[?n, ?s, ?i, ...]` — placeholder cho tập hợp với chỉ định rõ ràng kiểu và số lượng đối số, tạo chuỗi giá trị
Ví dụ:
```php
$db->query(
    'SELECT * FROM `users` WHERE `name` IN (?a["?s", "?s"])',
    ["hầu tước d\"Arquien", "D'Artagnan"]
);
```
Truy vấn SQL sau khi chuyển đổi mẫu:
```sql
SELECT * FROM `users` WHERE `name` IN ("hầu tước d\"Arquien", "D\'Artagnan")
```


#### `?f` — placeholder cho tên bảng hoặc trường

Placeholder này dành cho các trường hợp khi tên bảng hoặc trường được truyền trong truy vấn qua tham số. Tên trường và bảng được bao quanh bằng ký hiệu "backtick":

```php
$db->query(
    'SELECT ?f FROM ?f',
    'name',
    'database.table_name'
);
```
 Truy vấn SQL sau khi chuyển đổi mẫu:
```sql
SELECT `name` FROM `database`.`table_name`
```


### Dấu ngoặc kép giới hạn

**Thư viện yêu cầu lập trình viên tuân thủ cú pháp SQL.** Điều này có nghĩa là truy vấn sau sẽ không hoạt động:

```php
$db->query(
    'SELECT CONCAT("Hello, ", ?s, "!")',
    'world'
);
```
— placeholder `?s` phải được đặt trong dấu ngoặc đơn hoặc dấu ngoặc kép:
```php
$db->query(
    'SELECT concat("Hello, ", "?s", "!")',
    'world'
);
```
Truy vấn SQL sau khi chuyển đổi mẫu:
```sql
SELECT concat("Hello, ", "world", "!")
```

Đối với những người quen làm việc với PDO, điều này có thể có vẻ kỳ lạ, nhưng việc triển khai một cơ chế xác định liệu trong một trường hợp có cần phải đặt giá trị placeholder trong dấu ngoặc kép hay không là một nhiệm vụ rất không đơn giản đòi hỏi phải viết một trình phân tích cú pháp hoàn chỉnh.


## Ví dụ làm việc với thư viện

Xem trong tệp [../console/tests.php](../console/tests.php)
