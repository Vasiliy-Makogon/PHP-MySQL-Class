**Outros idiomas:**
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
- [हिंदी दस्तावेज़](README_hi.md)
- [التوثيق بالعربية](README_ar.md)
- [Türkçe Dokümantasyon](README_tr.md)
- [Tài liệu tiếng Việt](README_vi.md)

---

## Obtendo a biblioteca

Você pode [baixá-la como arquivo](https://github.com/Vasiliy-Makogon/Database/archive/master.zip), cloná-la deste site ou instalá-la via composer ([link para packagist.org](https://packagist.org/packages/krugozor/database)):
```
composer require krugozor/database
```


## O que é `krugozor/database`?

`krugozor/database` é uma biblioteca de classes PHP >= 8.0 para trabalho simples, conveniente, rápido e seguro com banco de dados MySQL, usando a extensão PHP [mysqli](https://www.php.net/manual/pt_BR/book.mysqli.php).


### Por que é necessária uma classe personalizada para MySQL se o PHP já tem a abstração PDO e a extensão mysqli?

As principais desvantagens de todas as bibliotecas para trabalhar com banco de dados MySQL em PHP são:

* **Verbosidade**
  * Para prevenir injeções SQL, os desenvolvedores têm dois caminhos:
    * Usar [declarações preparadas](https://www.php.net/manual/pt_BR/mysqli.quickstart.prepared-statements.php).
    * Escapar manualmente os parâmetros que vão para o corpo da consulta SQL. Parâmetros de string são passados por [mysqli_real_escape_string](https://www.php.net/manual/pt_BR/mysqli.real-escape-string.php), e parâmetros numéricos esperados são convertidos para os tipos correspondentes — `int` e `float`.
  * Ambas as abordagens têm desvantagens colossais:
    * Declarações preparadas são [terrivelmente verbosas](https://www.php.net/manual/pt_BR/mysqli.prepare.php#refsect1-mysqli.prepare-examples). Usar "fora da caixa" a abstração PDO ou a extensão mysqli, sem agregar todos os métodos para obter dados do DBMS é simplesmente impossível — para obter um valor de uma tabela é necessário escrever no mínimo 5 linhas de código! E assim para cada consulta!
    * Escapar manualmente os parâmetros que vão para o corpo da consulta SQL — nem mesmo é discutido. Um bom programador é um programador preguiçoso. Tudo deve ser maximamente automatizado.
* **Impossibilidade de obter a consulta SQL para depuração**
  * Para entender por que uma consulta SQL não funciona no programa, é necessário depurá-la — encontrar um erro lógico ou sintático. Para encontrar o erro, é necessário "ver" a consulta SQL em si, sobre a qual o banco de dados "reclamou", com os parâmetros substituídos em seu corpo. Ou seja, ter SQL completo e formado.
Se o desenvolvedor usa PDO com declarações preparadas, isso é... IMPOSSÍVEL! Não há mecanismos maximamente convenientes para isso [PREVISTOS](https://qna.habr.com/q/22669) nas bibliotecas nativas. Resta apenas fazer contorcionismos ou olhar no log do banco de dados.


### Solução: `krugozor/database` — classe para trabalhar com MySQL

1. Elimina a verbosidade — em vez de 3 ou mais linhas de código para executar uma consulta ao usar a biblioteca "nativa", você escreve apenas uma.
2. Escapa todos os parâmetros que vão para o corpo da consulta, de acordo com o tipo de placeholder especificado — proteção confiável contra injeções SQL.
3. Não substitui a funcionalidade do adaptador mysqli "nativo", mas simplesmente o complementa.
4. Extensível. Em essência, a biblioteca fornece apenas um parser e a execução de consultas SQL com proteção garantida contra injeções SQL. Você pode herdar de qualquer classe da biblioteca e, usando tanto os mecanismos da biblioteca quanto os mecanismos de `mysqli` e `mysqli_result`, criar os métodos necessários para trabalhar.

### O que a biblioteca `krugozor/database` NÃO é?

A maioria dos wrappers para vários drivers de banco de dados são um amontoado de código inútil com arquitetura horrível. Seus autores, não compreendendo eles mesmos o propósito prático de seus wrappers, os transformam em construtores de consultas (sql builder), bibliotecas ActiveRecord e outras soluções ORM.

A biblioteca `krugozor/database` não é nada disso. É apenas uma ferramenta conveniente para trabalhar com SQL normal no contexto do DBMS MySQL — e nada mais!


## O que são placeholders (marcadores)?

**Placeholders** (marcadores) são **marcadores tipados especiais que são escritos na string da consulta SQL *no lugar de valores explícitos (parâmetros da consulta)***. Os valores em si são passados "depois", como argumentos subsequentes do método principal que executa a consulta SQL:

```php
$result = $db->query(
    "SELECT * FROM `users` WHERE `name` = '?s' AND `age` = ?i",
    "D'Artagnan", 41
);
```

Os parâmetros da consulta SQL que passaram pelo sistema de *placeholders* são processados por mecanismos especiais de escape, dependendo do tipo de placeholder. Ou seja, agora você não precisa mais colocar variáveis em funções de escape como `mysqli_real_escape_string()` ou convertê-las para tipo numérico, como era feito antes:

```php
<?php
// Antes, antes de cada consulta ao DBMS fazíamos
// aproximadamente isso (e muitos ainda não fazem "isso"):
$id = (int) $_POST['id'];
$value = mysqli_real_escape_string($mysql, $_POST['value']);
$result = mysqli_query($mysql, "SELECT * FROM `t` WHERE `f1` = '$value' AND `f2` = $id");
```

Agora escrever consultas ficou fácil, rápido e, o mais importante, a biblioteca `krugozor/database` previne completamente todas as possíveis injeções SQL.


### Introdução ao sistema de placeholders

Os tipos de placeholders e seus propósitos são descritos abaixo. Antes de se familiarizar com os tipos de placeholders, é necessário entender como funciona o mecanismo da biblioteca.

#### O problema do PHP

PHP é uma linguagem fracamente tipada e durante o desenvolvimento desta biblioteca surgiu um dilema ideológico.
Imagine que temos uma tabela com a seguinte estrutura:

```sql
`name` varchar not null
`flag` tinyint not null
```
e a biblioteca DEVE (por algum motivo, possivelmente não dependente do desenvolvedor) executar a seguinte consulta:

```php
$db->query(
    "INSERT INTO `t` SET `name` = '?s', `flag` = ?i",
    null, false
);
```
Neste exemplo, há uma tentativa de escrever o valor `null` no campo de texto `not null` `name`,
e o tipo booleano `false` no campo numérico `flag`. O que fazer nesta situação?

* Quem é responsável pela validação dos parâmetros da consulta - o código cliente ou a biblioteca?
* É necessário neste caso interromper a execução do programa ou, talvez, aplicar algumas manipulações para que os dados sejam escritos no banco de dados?
* Podemos interpretar o valor `false` para a coluna `tinyint` como valor `0` e `null` como string vazia para a coluna `name`?
* Como podemos simplificar ou padronizar esta problemática em nosso código?

Em vista das questões levantadas, foi decidido implementar nesta biblioteca dois modos de operação.

### Modos de operação da biblioteca

  * **Mysql::MODE_STRICT — modo estrito de correspondência entre tipo de placeholder e tipo de argumento**.
    No modo `Mysql::MODE_STRICT` *o tipo de argumento deve corresponder ao tipo de placeholder*. Por exemplo, a tentativa de passar como argumento o valor `55.5` ou `'55.5'` para o placeholder de tipo inteiro `?i` gerará uma exceção:

```php
// estabelecemos o modo estrito
$db->setTypeMode(Mysql::MODE_STRICT);
// esta expressão não será executada, uma exceção será lançada:
// tentativa de especificar para o placeholder de tipo "integer" um valor de tipo "double" no template da consulta "SELECT ?i"
$db->query('SELECT ?i', 55.5);
```

* **Mysql::MODE_TRANSFORM — modo de conversão do argumento para o tipo de placeholder em caso de não correspondência entre tipo de placeholder e tipo de argumento.** O modo `Mysql::MODE_TRANSFORM` é definido por padrão e é um modo "tolerante" — em caso de não correspondência entre tipo de placeholder e tipo de argumento não gera uma exceção, mas *tenta converter o argumento para o tipo de placeholder necessário através da própria linguagem PHP*. A propósito, eu, como autor da biblioteca, sempre uso exatamente este modo, o modo estrito (`Mysql::MODE_STRICT`) nunca usei no trabalho real, mas talvez você precise dele.

**São permitidas as seguintes conversões no modo Mysql::MODE_TRANSFORM:**

* **Para o tipo `int` (placeholder `?i`) são convertidos**
  * números de ponto flutuante, representados tanto no tipo `string` quanto no tipo `double`
  * `bool` TRUE é convertido em `int(1)`, FALSE é convertido em `int(0)`
  * `null` é convertido em `int(0)`
* **Para o tipo `double` (placeholder `?d`) são convertidos**
  * números inteiros, representados tanto no tipo `string` quanto no tipo `int`
  * `bool` TRUE é convertido em `float(1)`, FALSE é convertido em `float(0)`
  * `null` é convertido em `float(0)`
* **Para o tipo `string` (placeholder `?s`) são convertidos**
  * `bool` TRUE é convertido em `string(1) "1"`, FALSE é convertido em `string(1) "0"`. Este comportamento difere da conversão do tipo `bool` para `int` em PHP, pois frequentemente, na prática, o tipo booleano é escrito em MySQL precisamente como número.
  * valor do tipo `numeric` é convertido em string de acordo com as regras de conversão do PHP
  * `null` é convertido em `string(0) ""`
* **Para o tipo `null` (placeholder `?n`) são convertidos**
  * quaisquer argumentos.
* Para arrays, objetos e recursos não são permitidas conversões.


### Quais tipos de placeholders a biblioteca oferece?


#### `?i` — placeholder para números inteiros

```php
$db->query(
    'SELECT * FROM `users` WHERE `id` = ?i', 123
);
```
Consulta SQL após conversão do template:
```sql
SELECT * FROM `users` WHERE `id` = 123
```

**ATENÇÃO!** Se você opera com números que excedem `PHP_INT_MAX`, então:
* Opere-os exclusivamente como strings em seus programas.
* Não use este placeholder, use o placeholder de string `?s` (veja abaixo). O fato é que números que excedem `PHP_INT_MAX`, o PHP os interpreta como números de ponto flutuante. O parser da biblioteca tentará converter o parâmetro para o tipo `int`, resultando em «*o resultado será indefinido, pois float não tem precisão suficiente para retornar o resultado correto. Neste caso não será emitido nem aviso nem notice!*» — [php.net](https://www.php.net/manual/pt_BR/language.types.integer.php#language.types.integer.casting.from-float).

#### `?d` — placeholder para números de ponto flutuante

```php
$db->query(
    'SELECT * FROM `prices` WHERE `cost` IN (?d, ?d)',
    12.56, '12.33'
);
```
Consulta SQL após conversão do template:
```sql
SELECT * FROM `prices` WHERE `cost` IN (12.56, 12.33)
```

**ATENÇÃO!** Se você usa a biblioteca para trabalhar com o tipo de dados `double`, defina a localidade apropriada para que o separador entre a parte inteira e fracionária seja igual tanto no nível PHP quanto no nível DBMS.

#### `?s` — placeholder para tipo string

Os valores dos argumentos são escapados com o método `mysqli::real_escape_string()`:

```php
$db->query(
    'SELECT "?s"',
    "Vocês todos são tolos, e eu sou D'Artagnan!"
);
```
 Consulta SQL após conversão do template:

```sql
SELECT "Vocês todos são tolos, e eu sou D\'Artagnan!"
```
#### `?S` — placeholder para tipo string para inserção no operador SQL LIKE

Os valores dos argumentos são escapados com o método `mysqli::real_escape_string()` + escape de caracteres especiais usados no operador LIKE (`%` e `_`):

```php
$db->query('SELECT "?S"', '% _');
```
 Consulta SQL após conversão do template:
```sql
SELECT "\% \_"
```

 #### `?n` — placeholder para tipo `NULL`
Os valores de quaisquer argumentos são ignorados, os placeholders são substituídos pela string `NULL` na consulta SQL:

```php
$db->query('SELECT ?n', 123);
```
 Consulta SQL após conversão do template:
```sql
SELECT NULL
```

 #### `?A*` — placeholder para conjunto associativo de array associativo, gera uma sequência de pares na forma `chave = valor`

onde o símbolo `*` é um dos placeholders:

 * `i` (placeholder para números inteiros)
 * `d` (placeholder para números de ponto flutuante)
 * `s` (placeholder para tipo string)

 as regras de conversão e escape são as mesmas dos tipos escalares únicos descritos acima. Exemplo:

```php
$db->query(
    'INSERT INTO `test` SET ?Ai',
    ['first' => '123', 'second' => 456]
);
```
Consulta SQL após conversão do template:
```sql
INSERT INTO `test` SET `first` = "123", `second` = "456"
```

#### `?a*` — placeholder para conjunto de array simples (ou também associativo), gera uma sequência de valores

 onde `*` é um dos tipos:
 * `i` (placeholder para números inteiros)
 * `d` (placeholder para números de ponto flutuante)
 * `s` (placeholder para tipo string)

 as regras de conversão e escape são as mesmas dos tipos escalares únicos descritos acima. Exemplo:

```php
$db->query(
    'SELECT * FROM `test` WHERE `id` IN (?ai)',
    [123, 456]
);
```
Consulta SQL após conversão do template:
```sql
SELECT * FROM `test` WHERE `id` IN ("123", "456")
```


#### `?A[?n, ?s, ?i, ...]` — placeholder para conjunto associativo com indicação explícita de tipo e número de argumentos, gera uma sequência de pares `chave = valor`

Exemplo:
```php
$db->query(
    'INSERT INTO `users` SET ?A[?i, "?s"]',
    ['age' => 41, 'name' => "D'Artagnan"]
);
```
Consulta SQL após conversão do template:
```sql
INSERT INTO `users` SET `age` = 41,`name` = "D\'Artagnan"
```

#### `?a[?n, ?s, ?i, ...]` — placeholder para conjunto com indicação explícita de tipo e número de argumentos, gera uma sequência de valores
Exemplo:
```php
$db->query(
    'SELECT * FROM `users` WHERE `name` IN (?a["?s", "?s"])',
    ["marquês d\"Arquien", "D'Artagnan"]
);
```
Consulta SQL após conversão do template:
```sql
SELECT * FROM `users` WHERE `name` IN ("marquês d\"Arquien", "D\'Artagnan")
```


#### `?f` — placeholder para nome de tabela ou campo

Este placeholder é destinado para casos em que o nome da tabela ou campo é passado na consulta através de um parâmetro. Nomes de campos e tabelas são enquadrados com o símbolo "crase":

```php
$db->query(
    'SELECT ?f FROM ?f',
    'name',
    'database.table_name'
);
```
 Consulta SQL após conversão do template:
```sql
SELECT `name` FROM `database`.`table_name`
```


### Aspas delimitadoras

**A biblioteca requer que o programador respeite a sintaxe SQL.** Isso significa que a seguinte consulta não funcionará:

```php
$db->query(
    'SELECT CONCAT("Hello, ", ?s, "!")',
    'world'
);
```
— o placeholder `?s` deve ser colocado entre aspas simples ou duplas:
```php
$db->query(
    'SELECT concat("Hello, ", "?s", "!")',
    'world'
);
```
Consulta SQL após conversão do template:
```sql
SELECT concat("Hello, ", "world", "!")
```

Para quem está acostumado a trabalhar com PDO isso pode parecer estranho, mas implementar um mecanismo que determine se em um caso é necessário colocar o valor do placeholder entre aspas ou não é uma tarefa muito não trivial que requer escrever um parser completo.


## Exemplos de trabalho com a biblioteca

Veja no arquivo [../console/tests.php](../console/tests.php)
