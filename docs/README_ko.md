**다른 언어:**
- [English documentation](../README.md)
- [Русская документация](README_ru.md)
- [Documentation française](README_fr.md)
- [Deutsche Dokumentation](README_de.md)
- [Documentazione italiana](README_it.md)
- [日本語ドキュメント](README_ja.md)
- [Documentación en español](README_es.md)
- [简体中文文档](README_zh-CN.md)
- [繁體中文文件](README_zh-TW.md)
- [Dokumentasi Bahasa Indonesia](README_id.md)
- [Documentação em Português (BR)](README_pt-BR.md)
- [हिंदी दस्तावेज़](README_hi.md)
- [التوثيق بالعربية](README_ar.md)
- [Türkçe Dokümantasyon](README_tr.md)
- [Tài liệu tiếng Việt](README_vi.md)

---

## 라이브러리 다운로드

[아카이브로 다운로드](https://github.com/Vasiliy-Makogon/Database/archive/master.zip)하거나, 이 사이트에서 클론하거나, composer를 통해 설치할 수 있습니다 ([packagist.org 링크](https://packagist.org/packages/krugozor/database)):
```
composer require krugozor/database
```


## `krugozor/database`란 무엇인가요?

`krugozor/database`는 PHP 확장 [mysqli](https://www.php.net/manual/ko/book.mysqli.php)를 사용하여 MySQL 데이터베이스와 간단하고, 편리하며, 빠르고, 안전하게 작업하기 위한 PHP >= 8.0 클래스 라이브러리입니다.


### PHP에 이미 PDO 추상화와 mysqli 확장이 있는데 왜 MySQL용 사용자 정의 클래스가 필요한가요?

PHP에서 MySQL 데이터베이스 작업을 위한 모든 라이브러리의 주요 단점은:

* **장황함**
  * SQL 인젝션을 방지하기 위해 개발자는 두 가지 방법이 있습니다:
    * [준비된 문](https://www.php.net/manual/ko/mysqli.quickstart.prepared-statements.php) 사용.
    * SQL 쿼리 본문에 들어가는 매개변수를 수동으로 이스케이프. 문자열 매개변수는 [mysqli_real_escape_string](https://www.php.net/manual/ko/mysqli.real-escape-string.php)을 통과시키고 예상되는 숫자 매개변수는 해당 유형 — `int`와 `float`로 변환.
  * 두 접근 방식 모두 엄청난 단점이 있습니다:
    * 준비된 문은 [끔찍하게 장황합니다](https://www.php.net/manual/ko/mysqli.prepare.php#refsect1-mysqli.prepare-examples). PDO 추상화나 mysqli 확장을 "박스에서 꺼내" 사용하고, DBMS에서 데이터를 가져오는 모든 메서드를 집계하지 않고 사용하는 것은 단순히 불가능합니다 — 테이블에서 값을 가져오려면 최소 5줄의 코드를 작성해야 합니다! 그리고 각 쿼리마다!
    * SQL 쿼리 본문에 들어가는 매개변수를 수동으로 이스케이프하는 것 — 논의조차 되지 않습니다. 좋은 프로그래머는 게으른 프로그래머입니다. 모든 것이 최대한 자동화되어야 합니다.
* **디버깅을 위한 SQL 쿼리를 얻을 수 없음**
  * 프로그램에서 SQL 쿼리가 작동하지 않는 이유를 이해하려면 디버그해야 합니다 — 논리적 오류나 구문 오류를 찾아야 합니다. 오류를 찾으려면 데이터베이스가 "불평한" SQL 쿼리 자체를 본문에 대체된 매개변수와 함께 "볼" 필요가 있습니다. 즉, 완전히 형성된 SQL을 가져야 합니다.
개발자가 준비된 문과 함께 PDO를 사용하는 경우, 이것은... 불가능합니다! 네이티브 라이브러리에는 이를 위한 최대한 편리한 메커니즘이 [제공되지 않습니다](https://qna.habr.com/q/22669). 무리하게 작업하거나 데이터베이스 로그를 살펴보는 것만 남습니다.


### 해결책: `krugozor/database` — MySQL 작업을 위한 클래스

1. 장황함 제거 — "네이티브" 라이브러리를 사용할 때 하나의 쿼리를 실행하기 위한 3줄 이상의 코드 대신 단 하나만 작성합니다.
2. 지정된 플레이스홀더 유형에 따라 쿼리 본문에 들어가는 모든 매개변수를 이스케이프 — SQL 인젝션으로부터의 신뢰할 수 있는 보호.
3. "네이티브" mysqli 어댑터의 기능을 대체하지 않고 단순히 보완합니다.
4. 확장 가능. 본질적으로 라이브러리는 파서와 SQL 인젝션으로부터 보장된 보호를 갖춘 SQL 쿼리 실행만 제공합니다. 라이브러리의 모든 클래스에서 상속하고 라이브러리의 메커니즘과 `mysqli` 및 `mysqli_result`의 메커니즘을 모두 사용하여 필요한 메서드를 만들 수 있습니다.

### `krugozor/database` 라이브러리가 아닌 것은 무엇인가요?

다양한 데이터베이스 드라이버를 위한 대부분의 래퍼는 끔찍한 아키텍처를 가진 쓸모없는 코드의 집합입니다. 그들의 작성자는 래퍼의 실용적인 목적을 스스로 이해하지 못한 채 쿼리 빌더, ActiveRecord 라이브러리 및 기타 ORM 솔루션과 같은 것으로 변환합니다.

`krugozor/database` 라이브러리는 이들 중 어느 것도 아닙니다. 이것은 MySQL DBMS 프레임워크 내에서 일반 SQL로 작업하기 위한 편리한 도구일 뿐입니다 — 그 이상도 이하도 아닙니다!


## placeholders(플레이스홀더)란 무엇인가요?

**Placeholders**(플레이스홀더)는 **SQL 쿼리 문자열에서 *명시적 값(쿼리 매개변수) 대신* 작성되는 특수 타입화된 마커**입니다. 값 자체는 "나중에" SQL 쿼리를 실행하는 주요 메서드의 후속 인수로 전달됩니다:

```php
$result = $db->query(
    "SELECT * FROM `users` WHERE `name` = '?s' AND `age` = ?i",
    "다르타냥", 41
);
```

*placeholders* 시스템을 통과한 SQL 쿼리 매개변수는 플레이스홀더 유형에 따라 특수 이스케이프 메커니즘에 의해 처리됩니다. 즉, 이제 이전처럼 변수를 `mysqli_real_escape_string()`과 같은 이스케이프 함수에 넣거나 숫자 유형으로 변환할 필요가 없습니다:

```php
<?php
// 이전에는 각 DBMS 쿼리 전에
// 대략 이렇게 했습니다(그리고 많은 사람들이 여전히 "이것"을 하지 않습니다):
$id = (int) $_POST['id'];
$value = mysqli_real_escape_string($mysql, $_POST['value']);
$result = mysqli_query($mysql, "SELECT * FROM `t` WHERE `f1` = '$value' AND `f2` = $id");
```

이제 쿼리 작성이 쉽고 빠르며, 가장 중요한 것은 `krugozor/database` 라이브러리가 모든 가능한 SQL 인젝션을 완전히 방지한다는 것입니다.


### 플레이스홀더 시스템 소개

플레이스홀더의 유형과 그 목적은 아래에 설명되어 있습니다. 플레이스홀더 유형에 익숙해지기 전에 라이브러리의 메커니즘이 어떻게 작동하는지 이해해야 합니다.

#### PHP의 문제

PHP는 약한 타입 언어이며 이 라이브러리 개발 중에 이데올로기적 딜레마가 발생했습니다.
다음 구조를 가진 테이블이 있다고 상상해 봅시다:

```sql
`name` varchar not null
`flag` tinyint not null
```
그리고 라이브러리는 (아마도 개발자와 무관한 어떤 이유로) 다음 쿼리를 실행해야 합니다:

```php
$db->query(
    "INSERT INTO `t` SET `name` = '?s', `flag` = ?i",
    null, false
);
```
이 예에서는 텍스트 `not null` 필드 `name`에 `null` 값을 쓰려고 시도하고,
숫자 필드 `flag`에 불린 타입 `false`를 쓰려고 시도합니다. 이 상황에서 어떻게 해야 할까요?

* 쿼리 매개변수의 유효성 검사 책임은 누구에게 있는가 - 클라이언트 코드인가 라이브러리인가?
* 이 경우 프로그램 실행을 중단해야 하는가, 아니면 데이터가 데이터베이스에 기록되도록 일부 조작을 적용해야 하는가?
* `tinyint` 컬럼에 대한 `false` 값을 `0` 값으로 해석하고 `name` 컬럼에 대한 `null`을 빈 문자열로 해석할 수 있는가?
* 코드에서 이러한 문제를 어떻게 단순화하거나 표준화할 수 있는가?

제기된 질문을 고려하여 이 라이브러리에 두 가지 작동 모드를 구현하기로 결정했습니다.

### 라이브러리의 작동 모드

  * **Mysql::MODE_STRICT — 플레이스홀더 유형과 인수 유형의 엄격한 일치 모드**.
    `Mysql::MODE_STRICT` 모드에서는 *인수 유형이 플레이스홀더 유형과 일치해야 합니다*. 예를 들어, 정수 유형 플레이스홀더 `?i`에 대한 인수로 `55.5` 또는 `'55.5'` 값을 전달하려고 하면 예외가 발생합니다:

```php
// 엄격 모드 설정
$db->setTypeMode(Mysql::MODE_STRICT);
// 이 표현식은 실행되지 않으며 예외가 발생합니다:
// 쿼리 템플릿 "SELECT ?i"에서 "integer" 유형의 플레이스홀더에 대해 "double" 유형의 값을 지정하려는 시도
$db->query('SELECT ?i', 55.5);
```

* **Mysql::MODE_TRANSFORM — 플레이스홀더 유형과 인수 유형이 일치하지 않을 때 인수를 플레이스홀더 유형으로 변환하는 모드.** `Mysql::MODE_TRANSFORM` 모드는 기본적으로 설정되어 있으며 "관대한" 모드입니다 — 플레이스홀더 유형과 인수 유형이 일치하지 않을 때 예외를 생성하지 않고 *PHP 언어 자체를 사용하여 인수를 필요한 플레이스홀더 유형으로 변환하려고 시도합니다*. 참고로, 저는 라이브러리의 작성자로서 항상 정확히 이 모드를 사용하며, 엄격 모드(`Mysql::MODE_STRICT`)는 실제 작업에서 사용한 적이 없지만, 아마도 귀하에게는 필요할 수 있습니다.

**Mysql::MODE_TRANSFORM 모드에서 허용되는 변환:**

* **`int` 유형(플레이스홀더 `?i`)으로 변환됩니다**
  * `string` 유형과 `double` 유형 모두로 표현된 부동 소수점 숫자
  * `bool` TRUE는 `int(1)`로, FALSE는 `int(0)`로 변환됩니다
  * `null`은 `int(0)`로 변환됩니다
* **`double` 유형(플레이스홀더 `?d`)으로 변환됩니다**
  * `string` 유형과 `int` 유형 모두로 표현된 정수
  * `bool` TRUE는 `float(1)`로, FALSE는 `float(0)`로 변환됩니다
  * `null`은 `float(0)`로 변환됩니다
* **`string` 유형(플레이스홀더 `?s`)으로 변환됩니다**
  * `bool` TRUE는 `string(1) "1"`로, FALSE는 `string(1) "0"`로 변환됩니다. 이 동작은 PHP에서 `bool`에서 `int`로의 유형 변환과 다릅니다. 실제로 불린 유형이 MySQL에 정확히 숫자로 기록되는 경우가 많기 때문입니다.
  * `numeric` 유형의 값은 PHP 변환 규칙에 따라 문자열로 변환됩니다
  * `null`은 `string(0) ""`로 변환됩니다
* **`null` 유형(플레이스홀더 `?n`)으로 변환됩니다**
  * 모든 인수.
* 배열, 객체 및 리소스에 대한 변환은 허용되지 않습니다.


### 라이브러리에서 제공하는 플레이스홀더 유형은?


#### `?i` — 정수용 플레이스홀더

```php
$db->query(
    'SELECT * FROM `users` WHERE `id` = ?i', 123
);
```
템플릿 변환 후 SQL 쿼리:
```sql
SELECT * FROM `users` WHERE `id` = 123
```

**주의!** `PHP_INT_MAX`를 초과하는 숫자로 작업하는 경우:
* 프로그램에서 문자열로만 작업하십시오.
* 이 플레이스홀더를 사용하지 말고 문자열 플레이스홀더 `?s`를 사용하십시오(아래 참조). 문제는 `PHP_INT_MAX`를 초과하는 숫자를 PHP가 부동 소수점 숫자로 해석한다는 것입니다. 라이브러리의 파서는 매개변수를 `int` 유형으로 변환하려고 시도하며, 그 결과 «*결과가 정의되지 않습니다. float에 올바른 결과를 반환할 충분한 정밀도가 없기 때문입니다. 이 경우 경고나 알림도 출력되지 않습니다!*» — [php.net](https://www.php.net/manual/ko/language.types.integer.php#language.types.integer.casting.from-float).

#### `?d` — 부동 소수점 숫자용 플레이스홀더

```php
$db->query(
    'SELECT * FROM `prices` WHERE `cost` IN (?d, ?d)',
    12.56, '12.33'
);
```
템플릿 변환 후 SQL 쿼리:
```sql
SELECT * FROM `prices` WHERE `cost` IN (12.56, 12.33)
```

**주의!** `double` 데이터 유형으로 작업하기 위해 라이브러리를 사용하는 경우, PHP 레벨과 DBMS 레벨 모두에서 정수 부분과 소수 부분 사이의 구분 기호가 동일하도록 적절한 로케일을 설정하십시오.

#### `?s` — 문자열 유형용 플레이스홀더

인수 값은 `mysqli::real_escape_string()` 메서드로 이스케이프됩니다:

```php
$db->query(
    'SELECT "?s"',
    "너희들은 모두 바보야, 그리고 나는 다르타냥이다!"
);
```
 템플릿 변환 후 SQL 쿼리:

```sql
SELECT "너희들은 모두 바보야, 그리고 나는 다르타냥이다!"
```
#### `?S` — SQL LIKE 연산자에 삽입하기 위한 문자열 유형 플레이스홀더

인수 값은 `mysqli::real_escape_string()` 메서드로 이스케이프되고 + LIKE 연산자에서 사용되는 특수 문자(`%` 및 `_`)가 이스케이프됩니다:

```php
$db->query('SELECT "?S"', '% _');
```
 템플릿 변환 후 SQL 쿼리:
```sql
SELECT "\% \_"
```

 #### `?n` — `NULL` 유형용 플레이스홀더
모든 인수의 값이 무시되고, 플레이스홀더는 SQL 쿼리에서 문자열 `NULL`로 대체됩니다:

```php
$db->query('SELECT ?n', 123);
```
 템플릿 변환 후 SQL 쿼리:
```sql
SELECT NULL
```

 #### `?A*` — 연관 배열에서 연관 집합용 플레이스홀더, `키 = 값` 형태의 쌍 시퀀스 생성

여기서 기호 `*`는 다음 플레이스홀더 중 하나입니다:

 * `i`(정수용 플레이스홀더)
 * `d`(부동 소수점 숫자용 플레이스홀더)
 * `s`(문자열 유형용 플레이스홀더)

 변환 및 이스케이프 규칙은 위에서 설명한 단일 스칼라 유형과 동일합니다. 예:

```php
$db->query(
    'INSERT INTO `test` SET ?Ai',
    ['first' => '123', 'second' => 456]
);
```
템플릿 변환 후 SQL 쿼리:
```sql
INSERT INTO `test` SET `first` = "123", `second` = "456"
```

#### `?a*` — 단순(또는 연관) 배열에서 집합용 플레이스홀더, 값의 시퀀스 생성

 여기서 `*`는 다음 유형 중 하나입니다:
 * `i`(정수용 플레이스홀더)
 * `d`(부동 소수점 숫자용 플레이스홀더)
 * `s`(문자열 유형용 플레이스홀더)

 변환 및 이스케이프 규칙은 위에서 설명한 단일 스칼라 유형과 동일합니다. 예:

```php
$db->query(
    'SELECT * FROM `test` WHERE `id` IN (?ai)',
    [123, 456]
);
```
템플릿 변환 후 SQL 쿼리:
```sql
SELECT * FROM `test` WHERE `id` IN ("123", "456")
```


#### `?A[?n, ?s, ?i, ...]` — 유형과 인수 수를 명시적으로 지정한 연관 집합용 플레이스홀더, `키 = 값` 쌍의 시퀀스 생성

예:
```php
$db->query(
    'INSERT INTO `users` SET ?A[?i, "?s"]',
    ['age' => 41, 'name' => "다르타냥"]
);
```
템플릿 변환 후 SQL 쿼리:
```sql
INSERT INTO `users` SET `age` = 41,`name` = "다르타냥"
```

#### `?a[?n, ?s, ?i, ...]` — 유형과 인수 수를 명시적으로 지정한 집합용 플레이스홀더, 값의 시퀀스 생성
예:
```php
$db->query(
    'SELECT * FROM `users` WHERE `name` IN (?a["?s", "?s"])',
    ["후작 다르키앙", "다르타냥"]
);
```
템플릿 변환 후 SQL 쿼리:
```sql
SELECT * FROM `users` WHERE `name` IN ("후작 다르키앙", "다르타냥")
```


#### `?f` — 테이블 또는 필드 이름용 플레이스홀더

이 플레이스홀더는 쿼리에서 테이블 또는 필드 이름이 매개변수를 통해 전달되는 경우를 위한 것입니다. 필드 및 테이블 이름은 "백틱" 기호로 둘러싸입니다:

```php
$db->query(
    'SELECT ?f FROM ?f',
    'name',
    'database.table_name'
);
```
 템플릿 변환 후 SQL 쿼리:
```sql
SELECT `name` FROM `database`.`table_name`
```


### 구분 따옴표

**라이브러리는 프로그래머에게 SQL 구문 준수를 요구합니다.** 이것은 다음 쿼리가 작동하지 않음을 의미합니다:

```php
$db->query(
    'SELECT CONCAT("Hello, ", ?s, "!")',
    'world'
);
```
— 플레이스홀더 `?s`는 작은따옴표 또는 큰따옴표로 묶어야 합니다:
```php
$db->query(
    'SELECT concat("Hello, ", "?s", "!")',
    'world'
);
```
템플릿 변환 후 SQL 쿼리:
```sql
SELECT concat("Hello, ", "world", "!")
```

PDO로 작업하는 데 익숙한 사람들에게는 이것이 이상하게 보일 수 있지만, 한 경우에 플레이스홀더 값을 따옴표로 묶어야 하는지 여부를 결정하는 메커니즘을 구현하는 것은 완전한 파서를 작성해야 하는 매우 간단하지 않은 작업입니다.


## 라이브러리 사용 예제

파일 [../console/tests.php](../console/tests.php) 참조
