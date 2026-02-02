**Autres langues:**
- [English documentation](../README.md)
- [Русская документация](README_ru.md)
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
- [Tài liệu tiếng Việt](README_vi.md)

---

## Obtenir la bibliothèque

Vous pouvez [télécharger l'archive](https://github.com/Vasiliy-Makogon/Database/archive/master.zip), cloner depuis ce site, ou télécharger via composer ([lien vers packagist.org](https://packagist.org/packages/krugozor/database)):
```
composer require krugozor/database
```


## Qu'est-ce que `krugozor/database`?

`krugozor/database` est une bibliothèque de classes PHP >= 8.0 pour un travail simple, pratique, rapide et sécurisé avec la base de données MySQL, utilisant l'extension PHP [mysqli](https://www.php.net/manual/fr/book.mysqli.php).


### Pourquoi avons-nous besoin d'une classe personnalisée pour MySQL si PHP dispose de l'abstraction PDO et de l'extension mysqli?

Les principaux inconvénients de toutes les bibliothèques pour travailler avec la base de données MySQL en PHP sont:

* **Verbosité**
    * Pour prévenir les injections SQL, les développeurs ont deux options:
        * Utiliser les [requêtes préparées](https://www.php.net/manual/fr/mysqli.quickstart.prepared-statements.php).
        * Échapper manuellement les paramètres allant dans le corps de la requête SQL. Les paramètres de type chaîne passent par [mysqli_real_escape_string](https://www.php.net/manual/fr/mysqli.real-escape-string.php), et les paramètres numériques attendus sont convertis aux types appropriés - `int` et `float`.
    * Les deux approches présentent d'énormes inconvénients:
        * Les requêtes préparées sont [terriblement verbeuses](https://www.php.net/manual/fr/mysqli.prepare.php#refsect1-mysqli.prepare-examples). Utiliser l'abstraction PDO ou l'extension mysqli "telles quelles", sans agrégation de toutes les méthodes pour obtenir des données du SGBD est tout simplement impossible - pour obtenir une valeur d'une table, vous devez écrire au moins 5 lignes de code! Et ainsi de suite pour chaque requête!
        * L'échappement manuel des paramètres allant dans le corps d'une requête SQL n'est même pas discutable. Un bon programmeur est un programmeur paresseux. Tout doit être aussi automatisé que possible.
* **Impossibilité d'obtenir la requête SQL pour le débogage**
    * Pour comprendre pourquoi la requête SQL ne fonctionne pas dans le programme, vous devez la déboguer - trouver soit une erreur logique, soit une erreur de syntaxe. Pour trouver une erreur, vous devez "voir" la requête SQL elle-même, sur laquelle la base de données "râle", avec les paramètres substitués dans son corps. C'est-à-dire avoir un SQL complet formé. Si le développeur utilise PDO avec des requêtes préparées, alors c'est... IMPOSSIBLE! Aucun mécanisme pratique pour cela n'est [PRÉVU](https://qna.habr.com/q/22669) dans les bibliothèques natives. Il ne reste plus qu'à s'en sortir autrement ou à consulter le journal de la base de données.


### Solution: `krugozor/database` est une classe pour travailler avec MySQL

1. Élimine la verbosité - au lieu de 3 lignes de code ou plus pour exécuter une requête lors de l'utilisation de la bibliothèque "native", vous n'en écrivez qu'une seule.
2. Échappe tous les paramètres allant dans le corps de la requête selon le type de placeholder spécifié - protection fiable contre les injections SQL.
3. Ne remplace pas la fonctionnalité de l'adaptateur "natif" mysqli, mais le complète simplement.
4. Extensible. En fait, la bibliothèque ne fournit qu'un analyseur et l'exécution d'une requête SQL avec une protection garantie contre les injections SQL. Vous pouvez hériter de n'importe quelle classe de la bibliothèque et utiliser à la fois les mécanismes de la bibliothèque et les mécanismes `mysqli` et `mysqli_result` pour créer les méthodes dont vous avez besoin.


### Ce que la bibliothèque `krugozor/database` n'est PAS

La plupart des wrappers pour divers pilotes de bases de données sont un amas de code inutile avec une architecture déplorable. Leurs auteurs, ne comprenant pas eux-mêmes l'objectif pratique de leurs wrappers, les transforment en une sorte de constructeurs de requêtes (sql builder), bibliothèques ActiveRecord et autres solutions ORM.

La bibliothèque `krugozor/database` n'est rien de tout cela. C'est juste un outil pratique pour travailler avec du SQL ordinaire dans le cadre du SGBD MySQL - et rien de plus!


## Que sont les placeholders?

**Les placeholders** — **des *marqueurs typés* spéciaux qui sont écrits dans la chaîne de requête SQL *à la place des valeurs explicites (paramètres de requête)***. Et les valeurs elles-mêmes sont passées "plus tard", comme arguments suivants de la méthode principale qui exécute une requête SQL:

```php
$result = $db->query(
    "SELECT * FROM `users` WHERE `name` = '?s' AND `age` = ?i",
    "d'Artagnan", 41
);
```

Les paramètres de requête SQL passés par le système de *placeholders* sont traités par des mécanismes d'échappement spéciaux, selon le type de placeholders. C'est-à-dire que vous n'avez plus besoin d'envelopper les variables dans des fonctions d'échappement comme `mysqli_real_escape_string()` ou de les convertir en type numérique comme auparavant:

```php
<?php
// Auparavant, avant chaque requête au SGBD, nous faisions
// quelque chose comme ceci (et beaucoup ne le font toujours pas):
$id = (int) $_POST['id'];
$value = mysqli_real_escape_string($mysql, $_POST['value']);
$result = mysqli_query($mysql, "SELECT * FROM `t` WHERE `f1` = '$value' AND `f2` = $id");
```

Maintenant, il est devenu facile d'écrire des requêtes, rapidement, et surtout, la bibliothèque `krugozor/database` prévient complètement toute injection SQL possible.

### Introduction au système de placeholders

Les types de placeholders et leurs objectifs sont décrits ci-dessous. Avant de se familiariser avec les types de placeholders, il est nécessaire de comprendre comment fonctionne le mécanisme de la bibliothèque.

#### Le problème de PHP

PHP est un langage faiblement typé et un dilemme idéologique est apparu lors du développement de cette bibliothèque.
Imaginons que nous ayons une table avec la structure suivante:

```sql
`name` varchar not null
`flag` tinyint not null
```
et la bibliothèque DOIT (pour une raison quelconque, peut-être indépendante de la volonté du développeur) exécuter la requête suivante:

```php
$db->query(
    "INSERT INTO `t` SET `name` = '?s', `flag` = ?i",
    null, false
);
```
Dans cet exemple, une tentative est faite d'écrire une valeur `null` dans le champ texte `not null` `name`, et un type booléen `false` dans le champ numérique `flag`. Que devons-nous faire dans cette situation?

* Qui devrait être responsable de la validation des paramètres de requête - le code client ou la bibliothèque?
* Devons-nous interrompre l'exécution du programme dans ce cas, ou peut-être devrions-nous appliquer certaines manipulations pour que les données soient écrites dans la base de données?
* Pouvons-nous traiter la valeur `false` pour la colonne `tinyint` comme la valeur `0`, et `null` comme une chaîne vide pour la colonne `name`?
* Comment pouvons-nous simplifier ou standardiser de tels problèmes dans notre code?

Compte tenu des questions soulevées, il a été décidé d'implémenter deux modes de fonctionnement dans cette bibliothèque.

### Modes de fonctionnement de la bibliothèque

* **Mysql::MODE_STRICT - mode de correspondance stricte pour le type de placeholder et le type d'argument**.
  En mode `Mysql::MODE_STRICT`, *le type d'argument doit correspondre au type de placeholder*. Par exemple, une tentative de passer la valeur `55.5` ou `'55.5'` comme argument pour un placeholder entier `?i` entraînera une exception:

```php
// définir le mode strict
$db->setTypeMode(Mysql::MODE_STRICT);
// cette expression ne sera pas exécutée, une exception sera levée:
// tentative de spécifier une valeur de type "double" pour un placeholder de type "integer" dans le modèle de requête "SELECT ?i"
$db->query('SELECT ?i', 55.5);
```

* **Mysql::MODE_TRANSFORM — mode de conversion d'argument vers le type de placeholder lorsque le type de placeholder et le type d'argument ne correspondent pas.** Le mode `Mysql::MODE_TRANSFORM` est défini par défaut et est un mode "tolérant" - si le type de placeholder et le type d'argument ne correspondent pas, il ne lève pas d'exception, mais *essaie de convertir l'argument vers le type de placeholder souhaité en utilisant le langage PHP lui-même*. D'ailleurs, moi, en tant qu'auteur de la bibliothèque, j'utilise toujours ce mode particulier, je n'ai jamais utilisé le mode strict (`Mysql::MODE_STRICT`) dans un travail réel, mais peut-être en aurez-vous besoin spécifiquement.

**Les transformations suivantes sont autorisées en mode `Mysql::MODE_TRANSFORM`:**

* **Conversion vers le type `int` (placeholder `?i`)**
  * nombres à virgule flottante représentés en types `string` et `double`
  * `bool` TRUE est converti en `int(1)`, FALSE est converti en `int(0)`
  * `null` est converti en `int(0)`
* **Conversion vers le type `double` (placeholder `?d`)**
  * entiers représentés en types `string` et `int`
  * `bool` TRUE devient `float(1)`, FALSE devient `float(0)`
  * `null` est converti en `float(0)`
* **Conversion vers le type `string` (placeholder `?s`)**
  * `bool` TRUE est converti en `string(1) "1"`, FALSE est converti en `string(1) "0"`. Ce comportement est différent de la conversion de `bool` en `int` en PHP, car souvent, en pratique, le type booléen est écrit dans MySQL comme un nombre.
  * une valeur `numeric` est convertie en chaîne selon les règles de conversion de PHP
  * `null` est converti en `string(0) ""`
* **Conversion vers le type `null` (placeholder `?n`)**
  * tous les arguments.
* Pour les tableaux, objets et ressources, les conversions ne sont pas autorisées.


### Quels types de placeholders sont fournis dans la bibliothèque `krugozor/database`?


#### `?i` — placeholder entier

```php
$db->query(
    'SELECT * FROM `users` WHERE `id` = ?i', 123
);
```
Requête SQL après conversion du modèle:
```sql
SELECT * FROM `users` WHERE `id` = 123
```

**ATTENTION!** Si vous travaillez avec des nombres qui dépassent les limites de `PHP_INT_MAX`, alors:

* Travaillez avec eux exclusivement comme des chaînes dans vos programmes.
* N'utilisez pas ce placeholder, utilisez le placeholder de chaîne `?s` (voir ci-dessous). Le fait est que les nombres au-delà des limites de `PHP_INT_MAX`, PHP les interprète comme des nombres à virgule flottante. L'analyseur de la bibliothèque essaiera de convertir le paramètre en type `int`, par conséquent "*le résultat sera indéfini, car le float n'a pas une précision suffisante pour retourner le résultat correct. Dans ce cas, ni un avertissement ni même une remarque ne seront affichés!*" — [php.net](https://www.php.net/manual/fr/language.types.integer.php#language.types.integer.casting.from-float).

#### `?d` — placeholder à virgule flottante

```php
$db->query(
    'SELECT * FROM `prices` WHERE `cost` IN (?d, ?d)',
    12.56, '12.33'
);
```
Requête SQL après conversion du modèle:
```sql
SELECT * FROM `prices` WHERE `cost` IN (12.56, 12.33)
```

**ATTENTION!** Si vous utilisez une bibliothèque pour travailler avec le type de données `double`, définissez la locale appropriée afin que le séparateur des parties entière et décimale soit le même au niveau PHP et au niveau SGBD.

#### `?s` — placeholder de type chaîne

Les valeurs des arguments sont échappées à l'aide de la méthode `mysqli::real_escape_string()`:

```php
$db->query(
    'SELECT "?s"',
    "Vous êtes tous des imbéciles, et moi je suis d'Artagnan!"
);
```

Requête SQL après conversion du modèle:

```sql
SELECT "Vous êtes tous des imbéciles, et moi je suis d\'Artagnan!"
```

#### `?S` — placeholder de type chaîne pour substitution dans l'opérateur SQL LIKE

Les valeurs des arguments sont échappées à l'aide de la méthode `mysqli::real_escape_string()` + échappement des caractères spéciaux utilisés dans l'opérateur LIKE (`%` et `_`):

```php
$db->query('SELECT "?S"', '% _');
```
Requête SQL après conversion du modèle:
```sql
SELECT "\% \_"
```

#### `?n` — placeholder de type `NULL`

La valeur de tous les arguments est ignorée, les placeholders sont remplacés par la chaîne `NULL` dans la requête SQL:

```php
$db->query('SELECT ?n', 123);
```
Requête SQL après conversion du modèle:
```sql
SELECT NULL
```

#### `?A*` — placeholder d'ensemble associatif à partir d'un tableau associatif, générant une séquence de paires de la forme `clé = valeur`

où le caractère `*` est l'un des placeholders:

* `i` (placeholder entier)
* `d` (placeholder flottant)
* `s` (placeholder de type chaîne)

les règles de conversion et d'échappement sont les mêmes que pour les types scalaires simples décrits ci-dessus. Exemple:

```php
$db->query(
    'INSERT INTO `test` SET ?Ai',
    ['first' => '123', 'second' => 456]
);
```
Requête SQL après conversion du modèle:
```sql
INSERT INTO `test` SET `first` = "123", `second` = "456"
```

#### `?a*` - placeholder d'ensemble à partir d'un tableau simple (ou également associatif), générant une séquence de valeurs

où `*` est l'un des types:
* `i` (placeholder entier)
* `d` (placeholder flottant)
* `s` (placeholder de type chaîne)

les règles de conversion et d'échappement sont les mêmes que pour les types scalaires simples décrits ci-dessus. Exemple:

```php
$db->query(
    'SELECT * FROM `test` WHERE `id` IN (?ai)',
    [123, 456]
);
```
Requête SQL après conversion du modèle:
```sql
SELECT * FROM `test` WHERE `id` IN ("123", "456")
```


#### `?A[?n, ?s, ?i, ...]` — placeholder d'ensemble associatif avec indication explicite du type et du nombre d'arguments, générant une séquence de paires `clé = valeur`

Exemple:
```php
$db->query(
    'INSERT INTO `users` SET ?A[?i, "?s"]',
    ['age' => 41, 'name' => "d'Artagnan"]
);
```
Requête SQL après conversion du modèle:
```sql
INSERT INTO `users` SET `age` = 41,`name` = "d\'Artagnan"
```

#### `?a[?n, ?s, ?i, ...]` — placeholder d'ensemble avec indication explicite du type et du nombre d'arguments, générant une séquence de valeurs

Exemple:

```php
$db->query(
    'SELECT * FROM `users` WHERE `name` IN (?a["?s", "?s"])',
    ["Marquis d\"Arcy", "d'Artagnan"]
);
```
Requête SQL après conversion du modèle:
```sql
SELECT * FROM `users` WHERE `name` IN ("Marquis d\"Arcy", "d\'Artagnan")
```


#### `?f` — placeholder de nom de table ou de champ

Ce placeholder est destiné aux cas où le nom d'une table ou d'un champ est passé dans la requête en tant que paramètre. Les noms de champs et de tables sont encadrés par des backticks:

```php
$db->query(
    'SELECT ?f FROM ?f',
    'name',
    'database.table_name'
);
```
Requête SQL après conversion du modèle:
```sql
SELECT `name` FROM `database`.`table_name`
```


### Guillemets délimiteurs

**La bibliothèque exige du programmeur qu'il respecte la syntaxe SQL.** Cela signifie que la requête suivante ne fonctionnera pas:

```php
$db->query(
    'SELECT CONCAT("Hello, ", ?s, "!")',
    'world'
);
```
— le placeholder `?s` doit être entouré de guillemets simples ou doubles:
```php
$db->query(
    'SELECT concat("Hello, ", "?s", "!")',
    'world'
);
```
Requête SQL après conversion du modèle:
```sql
SELECT concat("Hello, ", "world", "!")
```

Pour ceux qui ont l'habitude de travailler avec PDO, cela semblera étrange, mais implémenter un mécanisme qui détermine s'il est nécessaire d'entourer la valeur du placeholder de guillemets dans un cas ou non est une tâche très non triviale qui nécessite l'écriture d'un analyseur complet.


## Exemples de travail avec la bibliothèque

Voir dans le fichier [./console/tests.php](../console/tests.php)
