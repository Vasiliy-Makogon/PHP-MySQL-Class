<?php

namespace Krugozor\Database;

use mysqli;
use mysqli_result;
use mysqli_sql_exception;

/**
 * @author Vasiliy Makogon
 * @link https://github.com/Vasiliy-Makogon/Database/
 */
class Mysql
{
    /** @var int Strict operating mode */
    public const MODE_STRICT = 1;

    /** @var int Tolerant operating mode */
    public const MODE_TRANSFORM = 2;

    /** @var int Current operating mode */
    protected int $type_mode = self::MODE_TRANSFORM;

    /** @var string|null */
    protected ?string $server;

    /** @var string|null */
    protected ?string $user;

    /** @var string|null */
    protected ?string $password;

    /** @var int|string|null */
    protected int|string|null $port;

    /** @var int|string|null */
    protected int|string|null $socket;

    /** @var string */
    protected string $database_name;

    /** @var mysqli|null */
    protected ?mysqli $mysqli = null;

    /** @var string|null Last SQL query string BEFORE conversion */
    private ?string $original_query = null;

    /** @var string|null Last SQL query string AFTER conversion */
    private ?string $query = null;

    /**
     * An array with all queries that have been executed by the object.
     * Keys are SQL after transformation, values are SQL before transformation.
     *
     * @var array
     */
    private array $queries = [];

    /** @var bool Whether to accumulate SQL queries in self::$queries storage */
    private bool $store_queries = false;

    /** @var string Error message language */
    private string $lang = 'en';

    /**
     * Error messages in different languages.
     *
     * @var array|array[]
     */
    protected array $i18n_error_messages = [
        'en' => [
            0 => '%s: error setting character encoding: %s',
            1 => '%s: database name not specified',
            2 => '%s: database selection error: %s',
            3 => '%s: unknown mode specified for library "%s", use allowed modes: "%s"',
            4 => '%s: no SQL query passed',
            5 => '%s: SQL query execution error: %s; SQL: %s',
            6 => 'attempt to specify a value of type "%s" for placeholder of type "%s" in query template "%s"',
            7 => '%s: number of placeholders in query "%s" does not match number of arguments passed',
            8 => 'Mismatch in the number of arguments and placeholders in the array, query: "%s"',
            9 => 'Attempting to use an array placeholder without specifying the data type of its elements',
            10 => 'Two consecutive `.` characters in a column or table name',
            11 => '%s: database connection error: %s',
        ],
        'ru' => [
            0 => '%s: ошибка установки кодировки: %s',
            1 => '%s: не указано имя базы данных',
            2 => '%s: ошибка выбора базы данных: %s',
            3 => '%s: указан неизвестный режим работы библиотеки "%s", используйте допустимые режимы: "%s"',
            4 => '%s: не передан SQL запрос',
            5 => '%s: ошибка выполнения SQL запроса: %s; SQL: "%s"',
            6 => 'попытка указать для заполнителя типа "%s" значение типа "%s" в шаблоне запроса "%s"',
            7 => '%s: количество заполнителей в запросе "%s" не соответствует переданному количеству аргументов',
            8 => 'Несовпадение количества аргументов и заполнителей в массиве, запрос: "%s", переданые аргументы: "%s"',
            9 => 'Попытка воспользоваться заполнителем массива без указания типа данных его элементов',
            10 => 'Два символа `.` идущие подряд в имени столбца или таблицы',
            11 => '%s: ошибка подключения к базе данных: %s',
        ],
        'fr' => [
            0 => '%s: erreur de définition de l\'encodage des caractères: %s',
            1 => '%s: nom de base de données non spécifié',
            2 => '%s: erreur de sélection de la base de données: %s',
            3 => '%s: mode inconnu spécifié pour la bibliothèque "%s", utilisez les modes autorisés: "%s"',
            4 => '%s: aucune requête SQL transmise',
            5 => '%s: erreur d\'exécution de la requête SQL: %s; SQL: %s',
            6 => 'tentative de spécifier une valeur de type "%s" pour un placeholder de type "%s" dans le modèle de requête "%s"',
            7 => '%s: le nombre de placeholders dans la requête "%s" ne correspond pas au nombre d\'arguments transmis',
            8 => 'Incompatibilité du nombre d\'arguments et de placeholders dans le tableau, requête: "%s"',
            9 => 'Tentative d\'utiliser un placeholder de tableau sans spécifier le type de données de ses éléments',
            10 => 'Deux caractères `.` consécutifs dans un nom de colonne ou de table',
            11 => '%s: erreur de connexion à la base de données: %s',
        ],
        'de' => [
            0 => '%s: Fehler beim Setzen der Zeichenkodierung: %s',
            1 => '%s: Datenbankname nicht angegeben',
            2 => '%s: Fehler bei der Datenbankauswahl: %s',
            3 => '%s: unbekannter Modus für die Bibliothek "%s" angegeben, verwenden Sie zulässige Modi: "%s"',
            4 => '%s: keine SQL-Abfrage übergeben',
            5 => '%s: Fehler bei der Ausführung der SQL-Abfrage: %s; SQL: %s',
            6 => 'Versuch, einen Wert vom Typ "%s" für Platzhalter vom Typ "%s" in der Abfragevorlage "%s" anzugeben',
            7 => '%s: Anzahl der Platzhalter in der Abfrage "%s" stimmt nicht mit der Anzahl der übergebenen Argumente überein',
            8 => 'Nichtübereinstimmung der Anzahl von Argumenten und Platzhaltern im Array, Abfrage: "%s"',
            9 => 'Versuch, einen Array-Platzhalter zu verwenden, ohne den Datentyp seiner Elemente anzugeben',
            10 => 'Zwei aufeinanderfolgende `.`-Zeichen in einem Spalten- oder Tabellennamen',
            11 => '%s: Fehler bei der Verbindung zur Datenbank: %s',
        ],
        'it' => [
            0 => '%s: errore nell\'impostazione della codifica dei caratteri: %s',
            1 => '%s: nome del database non specificato',
            2 => '%s: errore di selezione del database: %s',
            3 => '%s: modalità sconosciuta specificata per la libreria "%s", utilizzare le modalità consentite: "%s"',
            4 => '%s: nessuna query SQL passata',
            5 => '%s: errore di esecuzione della query SQL: %s; SQL: %s',
            6 => 'tentativo di specificare un valore di tipo "%s" per un placeholder di tipo "%s" nel template della query "%s"',
            7 => '%s: il numero di placeholder nella query "%s" non corrisponde al numero di argomenti passati',
            8 => 'Mancata corrispondenza del numero di argomenti e placeholder nell\'array, query: "%s"',
            9 => 'Tentativo di utilizzare un placeholder array senza specificare il tipo di dati dei suoi elementi',
            10 => 'Due caratteri `.` consecutivi in un nome di colonna o tabella',
            11 => '%s: errore di connessione al database: %s',
        ],
        'ja' => [
            0 => '%s: 文字エンコーディングの設定エラー: %s',
            1 => '%s: データベース名が指定されていません',
            2 => '%s: データベース選択エラー: %s',
            3 => '%s: ライブラリ "%s" に不明なモードが指定されました。許可されたモードを使用してください: "%s"',
            4 => '%s: SQLクエリが渡されていません',
            5 => '%s: SQLクエリ実行エラー: %s; SQL: %s',
            6 => 'クエリテンプレート "%3$s" で "%2$s" 型のプレースホルダーに "%1$s" 型の値を指定しようとしました',
            7 => '%s: クエリ "%s" 内のプレースホルダーの数が渡された引数の数と一致しません',
            8 => '配列内の引数とプレースホルダーの数が一致しません、クエリ: "%s"',
            9 => '要素のデータ型を指定せずに配列プレースホルダーを使用しようとしています',
            10 => 'カラム名またはテーブル名に連続した `.` 文字があります',
            11 => '%s: データベース接続エラー: %s',
        ],
        'es' => [
            0 => '%s: error al establecer la codificación de caracteres: %s',
            1 => '%s: nombre de base de datos no especificado',
            2 => '%s: error de selección de base de datos: %s',
            3 => '%s: modo desconocido especificado para la biblioteca "%s", use los modos permitidos: "%s"',
            4 => '%s: no se pasó ninguna consulta SQL',
            5 => '%s: error de ejecución de consulta SQL: %s; SQL: %s',
            6 => 'intento de especificar un valor de tipo "%s" para un placeholder de tipo "%s" en la plantilla de consulta "%s"',
            7 => '%s: el número de placeholders en la consulta "%s" no coincide con el número de argumentos pasados',
            8 => 'Discrepancia en el número de argumentos y placeholders en el array, consulta: "%s"',
            9 => 'Intento de usar un placeholder de array sin especificar el tipo de datos de sus elementos',
            10 => 'Dos caracteres `.` consecutivos en un nombre de columna o tabla',
            11 => '%s: error de conexión a la base de datos: %s',
        ],
        'ko' => [
            0 => '%s: 문자 인코딩 설정 오류: %s',
            1 => '%s: 데이터베이스 이름이 지정되지 않음',
            2 => '%s: 데이터베이스 선택 오류: %s',
            3 => '%s: 라이브러리 "%s"에 알 수 없는 모드가 지정되었습니다. 허용된 모드를 사용하세요: "%s"',
            4 => '%s: SQL 쿼리가 전달되지 않음',
            5 => '%s: SQL 쿼리 실행 오류: %s; SQL: %s',
            6 => '쿼리 템플릿 "%3$s"에서 "%2$s" 유형의 플레이스홀더에 "%1$s" 유형의 값을 지정하려는 시도',
            7 => '%s: 쿼리 "%s"의 플레이스홀더 수가 전달된 인수 수와 일치하지 않습니다',
            8 => '배열의 인수와 플레이스홀더 수가 일치하지 않습니다, 쿼리: "%s"',
            9 => '요소의 데이터 유형을 지정하지 않고 배열 플레이스홀더를 사용하려는 시도',
            10 => '열 또는 테이블 이름에 연속된 `.` 문자',
            11 => '%s: 데이터베이스 연결 오류: %s',
        ],
        'zh-CN' => [
            0 => '%s: 设置字符编码错误: %s',
            1 => '%s: 未指定数据库名称',
            2 => '%s: 数据库选择错误: %s',
            3 => '%s: 为库 "%s" 指定了未知模式，请使用允许的模式: "%s"',
            4 => '%s: 未传递SQL查询',
            5 => '%s: SQL查询执行错误: %s; SQL: %s',
            6 => '尝试在查询模板 "%3$s" 中为 "%2$s" 类型的占位符指定 "%1$s" 类型的值',
            7 => '%s: 查询 "%s" 中的占位符数量与传递的参数数量不匹配',
            8 => '数组中的参数和占位符数量不匹配，查询: "%s"',
            9 => '尝试使用数组占位符而未指定其元素的数据类型',
            10 => '列名或表名中有两个连续的 `.` 字符',
            11 => '%s: 数据库连接错误: %s',
        ],
        'zh-TW' => [
            0 => '%s: 設定字元編碼錯誤: %s',
            1 => '%s: 未指定資料庫名稱',
            2 => '%s: 資料庫選擇錯誤: %s',
            3 => '%s: 為函式庫 "%s" 指定了未知模式，請使用允許的模式: "%s"',
            4 => '%s: 未傳遞SQL查詢',
            5 => '%s: SQL查詢執行錯誤: %s; SQL: %s',
            6 => '嘗試在查詢範本 "%3$s" 中為 "%2$s" 類型的佔位符指定 "%1$s" 類型的值',
            7 => '%s: 查詢 "%s" 中的佔位符數量與傳遞的參數數量不符',
            8 => '陣列中的參數和佔位符數量不符，查詢: "%s"',
            9 => '嘗試使用陣列佔位符而未指定其元素的資料類型',
            10 => '欄名或表名中有兩個連續的 `.` 字元',
            11 => '%s: 資料庫連接錯誤: %s',
        ],
        'id' => [
            0 => '%s: kesalahan pengaturan encoding karakter: %s',
            1 => '%s: nama database tidak ditentukan',
            2 => '%s: kesalahan pemilihan database: %s',
            3 => '%s: mode tidak dikenal ditentukan untuk pustaka "%s", gunakan mode yang diizinkan: "%s"',
            4 => '%s: tidak ada query SQL yang diteruskan',
            5 => '%s: kesalahan eksekusi query SQL: %s; SQL: %s',
            6 => 'percobaan untuk menentukan nilai tipe "%s" untuk placeholder tipe "%s" dalam template query "%s"',
            7 => '%s: jumlah placeholder dalam query "%s" tidak sesuai dengan jumlah argumen yang diteruskan',
            8 => 'Ketidakcocokan jumlah argumen dan placeholder dalam array, query: "%s"',
            9 => 'Percobaan untuk menggunakan placeholder array tanpa menentukan tipe data elemennya',
            10 => 'Dua karakter `.` berturut-turut dalam nama kolom atau tabel',
            11 => '%s: kesalahan koneksi ke database: %s',
        ],
        'pt-BR' => [
            0 => '%s: erro ao definir codificação de caracteres: %s',
            1 => '%s: nome do banco de dados não especificado',
            2 => '%s: erro de seleção do banco de dados: %s',
            3 => '%s: modo desconhecido especificado para a biblioteca "%s", use os modos permitidos: "%s"',
            4 => '%s: nenhuma consulta SQL passada',
            5 => '%s: erro de execução da consulta SQL: %s; SQL: %s',
            6 => 'tentativa de especificar um valor do tipo "%s" para um placeholder do tipo "%s" no template da consulta "%s"',
            7 => '%s: o número de placeholders na consulta "%s" não corresponde ao número de argumentos passados',
            8 => 'Discrepância no número de argumentos e placeholders no array, consulta: "%s"',
            9 => 'Tentativa de usar um placeholder de array sem especificar o tipo de dados de seus elementos',
            10 => 'Dois caracteres `.` consecutivos em um nome de coluna ou tabela',
            11 => '%s: erro de conexão ao banco de dados: %s',
        ],
        'hi' => [
            0 => '%s: वर्ण एन्कोडिंग सेट करने में त्रुटि: %s',
            1 => '%s: डेटाबेस का नाम निर्दिष्ट नहीं है',
            2 => '%s: डेटाबेस चयन त्रुटि: %s',
            3 => '%s: लाइब्रेरी "%s" के लिए अज्ञात मोड निर्दिष्ट किया गया है, अनुमत मोड का उपयोग करें: "%s"',
            4 => '%s: कोई SQL क्वेरी पास नहीं की गई',
            5 => '%s: SQL क्वेरी निष्पादन त्रुटि: %s; SQL: %s',
            6 => 'क्वेरी टेम्पलेट "%3$s" में "%2$s" प्रकार के प्लेसहोल्डर के लिए "%1$s" प्रकार का मान निर्दिष्ट करने का प्रयास',
            7 => '%s: क्वेरी "%s" में प्लेसहोल्डर्स की संख्या पास किए गए तर्कों की संख्या से मेल नहीं खाती',
            8 => 'ऐरे में तर्कों और प्लेसहोल्डर्स की संख्या में बेमेल, क्वेरी: "%s"',
            9 => 'इसके तत्वों का डेटा प्रकार निर्दिष्ट किए बिना ऐरे प्लेसहोल्डर का उपयोग करने का प्रयास',
            10 => 'कॉलम या टेबल नाम में दो लगातार `.` वर्ण',
            11 => '%s: डेटाबेस कनेक्शन त्रुटि: %s',
        ],
        'ar' => [
            0 => '%s: خطأ في تعيين ترميز الأحرف: %s',
            1 => '%s: لم يتم تحديد اسم قاعدة البيانات',
            2 => '%s: خطأ في اختيار قاعدة البيانات: %s',
            3 => '%s: تم تحديد وضع غير معروف للمكتبة "%s"، استخدم الأوضاع المسموح بها: "%s"',
            4 => '%s: لم يتم تمرير استعلام SQL',
            5 => '%s: خطأ في تنفيذ استعلام SQL: %s; SQL: %s',
            6 => 'محاولة تحديد قيمة من النوع "%s" للعنصر النائب من النوع "%s" في قالب الاستعلام "%s"',
            7 => '%s: عدد العناصر النائبة في الاستعلام "%s" لا يتطابق مع عدد الوسيطات الممررة',
            8 => 'عدم تطابق في عدد الوسيطات والعناصر النائبة في المصفوفة، الاستعلام: "%s"',
            9 => 'محاولة استخدام عنصر نائب مصفوفة دون تحديد نوع البيانات لعناصرها',
            10 => 'حرفان `.` متتاليان في اسم عمود أو جدول',
            11 => '%s: خطأ في الاتصال بقاعدة البيانات: %s',
        ],
        'tr' => [
            0 => '%s: karakter kodlamasını ayarlama hatası: %s',
            1 => '%s: veritabanı adı belirtilmedi',
            2 => '%s: veritabanı seçim hatası: %s',
            3 => '%s: kütüphane "%s" için bilinmeyen mod belirtildi, izin verilen modları kullanın: "%s"',
            4 => '%s: SQL sorgusu iletilmedi',
            5 => '%s: SQL sorgusu yürütme hatası: %s; SQL: %s',
            6 => 'sorgu şablonu "%3$s" içinde "%2$s" türündeki yer tutucu için "%1$s" türünde değer belirtme girişimi',
            7 => '%s: sorgu "%s" içindeki yer tutucu sayısı iletilen argüman sayısıyla eşleşmiyor',
            8 => 'Dizideki argüman ve yer tutucu sayısında uyumsuzluk, sorgu: "%s"',
            9 => 'Elemanlarının veri türünü belirtmeden dizi yer tutucusu kullanma girişimi',
            10 => 'Sütun veya tablo adında ardışık iki `.` karakteri',
            11 => '%s: veritabanı bağlantı hatası: %s',
        ],
        'vi' => [
            0 => '%s: lỗi thiết lập mã hóa ký tự: %s',
            1 => '%s: tên cơ sở dữ liệu không được chỉ định',
            2 => '%s: lỗi chọn cơ sở dữ liệu: %s',
            3 => '%s: chế độ không xác định được chỉ định cho thư viện "%s", sử dụng các chế độ được phép: "%s"',
            4 => '%s: không có truy vấn SQL được truyền',
            5 => '%s: lỗi thực thi truy vấn SQL: %s; SQL: %s',
            6 => 'cố gắng chỉ định giá trị kiểu "%s" cho placeholder kiểu "%s" trong mẫu truy vấn "%s"',
            7 => '%s: số lượng placeholder trong truy vấn "%s" không khớp với số lượng đối số được truyền',
            8 => 'Không khớp số lượng đối số và placeholder trong mảng, truy vấn: "%s"',
            9 => 'Cố gắng sử dụng placeholder mảng mà không chỉ định kiểu dữ liệu của các phần tử của nó',
            10 => 'Hai ký tự `.` liên tiếp trong tên cột hoặc bảng',
            11 => '%s: lỗi kết nối cơ sở dữ liệu: %s',
        ],
    ];

    /**
     * @param string|null $server
     * @param string|null $username
     * @param string|null $password
     * @param int|string|null $port
     * @param int|string|null $socket
     * @return Mysql
     * @throws MySqlException
     */
    public static function create(
        ?string $server = null,
        ?string $username = null,
        ?string $password = null,
        int|string|null $port = null,
        int|string|null $socket = null
    ): Mysql {
        return new self($server, $username, $password, $port, $socket);
    }

    /**
     * Sets the error output language.
     *
     * @param string $lang
     * @return $this
     * @throws MySqlException
     */
    public function setErrorMessagesLang(string $lang): self
    {
        if (!array_key_exists($lang, $this->i18n_error_messages)) {
            throw new MySqlException(
                sprintf(
                    '%s: language "%s" is not supported, use any of: "%s". ' .
                    "Make a pull request for this library, or derive a new class from class 'Mysql' and add the " .
                    "internationalization language for your language to property self::\$exception_i18n_messages",
                    __METHOD__,
                    $lang,
                    implode('", "', array_keys($this->i18n_error_messages))
                )
            );
        }

        $this->lang = $lang;

        return $this;
    }

    /**
     * Specifies the character set that will be used when exchanging data with the database server.
     * Calling this method is equivalent to the following MySql server configuration setting:
     *
     * SET character_set_client = charset_name;
     * SET character_set_results = charset_name;
     * SET character_set_connection = charset_name;
     *
     * @param string $charset
     * @return Mysql
     * @throws MySqlException
     * @see mysqli::set_charset
     */
    public function setCharset(string $charset): Mysql
    {
        // Отлов в переменную $mysqli_sql_exception исключения при подключении
        // в режиме MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT
        $mysqli_sql_exception = null;

        try {
            // Подавление `собакой` вывода ошибки для режима MYSQLI_REPORT_ERROR
            $result = @$this->mysqli->set_charset($charset);
        } catch (mysqli_sql_exception $mysqli_sql_exception) {
            $result = false;
        }

        // Выявляем источник данных об ошибке
        $error_code = $mysqli_sql_exception ? $mysqli_sql_exception->getCode() : $this->mysqli->errno;
        $error_message = $mysqli_sql_exception ? $mysqli_sql_exception->getMessage() : $this->mysqli->error;

        if ($result === false) {
            throw new MySqlException(
                sprintf(
                    $this->i18n_error_messages[$this->lang][0],
                    __METHOD__,
                    $error_message
                ), $error_code, $mysqli_sql_exception
            );
        }

        return $this;
    }

    /**
     * Returns the encoding set for the database connection.
     *
     * @return string
     * @see mysqli::character_set_name
     */
    public function getCharset(): string
    {
        return $this->mysqli->character_set_name();
    }

    /**
     * Sets the name of the database to use.
     *
     * @param string $database_name
     * @return Mysql
     * @throws MySqlException
     * @see mysqli::select_db
     */
    public function setDatabaseName(string $database_name): Mysql
    {
        if (!$database_name) {
            throw new MySqlException(
                sprintf(
                    $this->i18n_error_messages[$this->lang][1],
                    __METHOD__
                )
            );
        }

        $this->database_name = $database_name;

        // Отлов в переменную $mysqli_sql_exception исключения при подключении
        // в режиме MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT
        $mysqli_sql_exception = null;

        try {
            // Подавление `собакой` вывода ошибки для режима MYSQLI_REPORT_ERROR
            $result = @$this->mysqli->select_db($this->database_name);
        } catch (mysqli_sql_exception $mysqli_sql_exception) {
            $result = false;
        }

        // Выявляем источник данных об ошибке
        $error_code = $mysqli_sql_exception ? $mysqli_sql_exception->getCode() : $this->mysqli->errno;
        $error_message = $mysqli_sql_exception ? $mysqli_sql_exception->getMessage() : $this->mysqli->error;

        if ($result === false) {
            throw new MySqlException(
                sprintf(
                    $this->i18n_error_messages[$this->lang][2],
                    __METHOD__,
                    $error_message
                ), $error_code, $mysqli_sql_exception
            );
        }

        return $this;
    }

    /**
     * Returns the name of the current database.
     *
     * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->database_name;
    }

    /**
     * Sets the behavior mode when the placeholder type and the argument type do not match.
     *
     * @param int $type
     * @return Mysql
     * @throws MySqlException
     */
    public function setTypeMode(int $type): Mysql
    {
        if (!in_array($type, [self::MODE_STRICT, self::MODE_TRANSFORM])) {
            throw new MySqlException(
                sprintf(
                    $this->i18n_error_messages[$this->lang][3],
                    __METHOD__,
                    $type,
                    implode('", "', [self::MODE_STRICT, self::MODE_TRANSFORM])
                )
            );
        }

        $this->type_mode = $type;

        return $this;
    }

    /**
     * Sets the $this->store_queries property, which is responsible
     * for accumulating executed queries in the $this->queries storage.
     *
     * @param bool $value
     * @return Mysql
     */
    public function setStoreQueries(bool $value): Mysql
    {
        $this->store_queries = $value;

        return $this;
    }

    /**
     * Executes a SQL query.
     * Accepts a mandatory parameter - a SQL query and, if any,
     * any number of arguments - placeholder values.
     *
     * @param mixed ...$args SQL query string and arguments for placeholders
     * @return bool|Statement On successful execution of queries that produce a result set,
     * such as SELECT, SHOW, DESCRIBE, or EXPLAIN, the method will return a Statement object.
     * For other successful queries, the method will return true.
     * On error, a MySqlException will be thrown.
     * @throws MySqlException
     * @see mysqli::query
     */
    public function query(mixed ...$args): bool|Statement
    {
        if (!func_num_args()) {
            throw new MySqlException(
                sprintf(
                    $this->i18n_error_messages[$this->lang][4],
                    __METHOD__
                )
            );
        }

        $query = $this->original_query = array_shift($args);

        $this->query = $this->parse($query, $args);

        // Отлов в переменную $mysqli_sql_exception исключения при подключении
        // в режиме MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT
        $mysqli_sql_exception = null;

        try {
            // Подавление `собакой` вывода ошибки для режима MYSQLI_REPORT_ERROR
            $result = @$this->mysqli->query($this->query);
        } catch (mysqli_sql_exception $mysqli_sql_exception) {
            $result = false;
        }

        if ($this->store_queries) {
            $this->queries[$this->query] = $this->original_query;
        }

        // Выявляем источник данных об ошибке
        $error_code = $mysqli_sql_exception ? $mysqli_sql_exception->getCode() : $this->mysqli->errno;
        $error_message = $mysqli_sql_exception ? $mysqli_sql_exception->getMessage() : $this->mysqli->error;

        if ($result === false) {
            throw new MySqlException(
                sprintf(
                    $this->i18n_error_messages[$this->lang][5],
                    __METHOD__,
                    $error_message,
                    $this->query
                ), $error_code, $mysqli_sql_exception
            );
        }

        if ($result instanceof mysqli_result) {
            return new Statement($result);
        }

        return $result;
    }

    /**
     * The behavior is similar to the self::query() method, except that the method takes only two parameters -
     * SQL query $query and an array of arguments $arguments, which will be replaced with substitutes in the order
     * in which they are presented in the $arguments array.
     *
     * @param string $query
     * @param array $arguments
     * @return bool|Statement On successful execution of queries that produce a result set,
     * such as SELECT, SHOW, DESCRIBE, or EXPLAIN, the method will return a Statement object.
     * For other successful queries, the method will return true.
     * On error, a MySqlException will be thrown.
     */
    public function queryArguments(string $query, array $arguments = []): Statement|bool
    {
        array_unshift($arguments, $query);

        return call_user_func_array([$this, 'query'], $arguments);
    }

    /**
     * Wrapper over the $this->parse() method.
     * Used for cases when the SQL query is formed in parts.
     *
     * Example:
     *     $db->prepare('WHERE `name` = "?s" OR `id` IN(?ai)', 'Василий', array(1, 2));
     * Result:
     *     WHERE `name` = "Василий" OR `id` IN(1, 2)
     *
     * @param mixed ...$args SQL query or part of it and arguments for placeholders
     * @return string
     * @throws MySqlException
     */
    public function prepare(mixed ...$args): string
    {
        if (!func_num_args()) {
            throw new MySqlException(
                sprintf(
                    $this->i18n_error_messages[$this->lang][4],
                    __METHOD__
                )
            );
        }

        $query = array_shift($args);

        return $this->parse($query, $args);
    }

    /**
     * Gets the number of rows affected by the previous MySQL operation.
     * Returns the number of rows affected by the last INSERT, UPDATE, or DELETE query.
     * If the last query was a DELETE without a WHERE clause,
     * all records in the table will be deleted, but the function will return zero.
     *
     * @return int
     * @see mysqli::affected_rows
     */
    public function getAffectedRows(): int
    {
        return $this->mysqli->affected_rows;
    }

    /**
     * Returns the last original SQL query before transformation.
     *
     * @return string|null
     */
    public function getOriginalQueryString(): ?string
    {
        return $this->original_query;
    }

    /**
     * Returns the last executed MySQL query (after transformation).
     *
     * @return string|null
     */
    public function getQueryString(): ?string
    {
        return $this->query;
    }

    /**
     * Returns an array containing all executed SQL queries within the current connection.
     *
     * @return array
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Returns the id generated by the previous INSERT operation.
     *
     * @return int
     * @see mysqli::insert_id
     */
    public function getLastInsertId(): int
    {
        return $this->mysqli->insert_id;
    }

    /**
     * Returns the original mysqli object.
     *
     * @return mysqli
     */
    public function getMysqli(): mysqli
    {
        return $this->mysqli;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return string[]
     */
    public function __sleep()
    {
        return [
            'server',
            'user',
            'password',
            'port',
            'socket',
            'database_name',
            'type_mode',
            'store_queries',
            'query',
            'original_query'
        ];
    }

    /**
     * @throws MySqlException
     */
    public function __wakeup()
    {
        $this
            ->connect()
            ->setDatabaseName($this->database_name);
    }

    /**
     * @param string|null $server
     * @param string|null $user
     * @param string|null $password
     * @param int|string|null $port
     * @param int|string|null $socket
     * @throws MySqlException
     */
    private function __construct(
        ?string $server = null,
        ?string $user = null,
        ?string $password = null,
        int|string|null $port = null,
        int|string|null $socket = null
    ) {
        $this->server = $server;
        $this->user = $user;
        $this->password = $password;
        $this->port = $port;
        $this->socket = $socket;

        $this->connect();
    }

    /**
     * Establishes a connection to the database.
     *
     * @return $this
     * @throws MySqlException
     * @see mysqli::connect
     */
    private function connect(): Mysql
    {
        if (is_null($this->mysqli)) {
            // Отлов в переменную $mysqli_sql_exception исключения при подключении
            // в режиме MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT
            $mysqli_sql_exception = null;

            try {
                // Подавление `собакой` вывода ошибки для режима MYSQLI_REPORT_ERROR
                $this->mysqli = @new mysqli(
                    $this->server,
                    $this->user,
                    $this->password,
                    null,
                    $this->port,
                    $this->socket
                );
            } catch (mysqli_sql_exception $mysqli_sql_exception) {
            }

            // Выявляем источник данных об ошибке
            $error_code = $mysqli_sql_exception ? $mysqli_sql_exception->getCode() : $this->mysqli->connect_errno;
            $error_message = $mysqli_sql_exception ? $mysqli_sql_exception->getMessage() : $this->mysqli->connect_error;

            if ($error_code && $error_message) {
                $this->mysqli = null;
                throw new MySqlException(
                    sprintf(
                        $this->i18n_error_messages[$this->lang][11],
                        __METHOD__,
                        $error_message
                    ), $error_code, $mysqli_sql_exception
                );
            }
        }

        return $this;
    }

    /**
     * Closes the MySQL connection.
     *
     * @return void
     * @see mysqli::close
     */
    private function close(): void
    {
        if ($this->mysqli instanceof mysqli) {
            $this->mysqli->close();
        }
    }

    /**
     * Returns an escaped string for a LIKE search placeholder (?S).
     *
     * @param string $string the line in which special characters need to be escaped
     * @param string $chars a set of characters that also need to be escaped.
     * By default, the following characters are escaped: `'"%_`.
     * @return string
     */
    private function escapeLike(string $string, string $chars = "%_"): string
    {
        $string = str_replace('\\', '\\\\', $string);
        $string = $this->mysqlRealEscapeString($string);

        if ($chars) {
            $string = addCslashes($string, $chars);
        }

        return $string;
    }

    /**
     * Escapes special characters in a string for use in a SQL expression,
     * using the current connection character set.
     *
     * @param string $value
     * @return string
     * @see mysqli_real_escape_string
     * @see mysqli::real_escape_string
     */
    private function mysqlRealEscapeString(string $value): string
    {
        return $this->mysqli->real_escape_string($value);
    }

    /**
     * Returns a string describing the error when the placeholder and argument types do not match.
     *
     * @param string $type filler type
     * @param mixed $value argument value
     * @param string $original_query original sql query
     * @return string
     */
    private function createErrorMessage(string $type, mixed $value, string $original_query): string
    {
        return sprintf(
            $this->i18n_error_messages[$this->lang][6],
            $type,
            gettype($value),
            $original_query
        );
    }

    /**
     * Parses the $query and substitutes the arguments from $args into it.
     *
     * @param string $query SQL query or part of it (in case of parsing the condition in brackets [])
     * @param array $args placeholder arguments
     * @param string|null $original_query "original", full SQL query
     * @return string SQL query for database execution
     * @throws MySqlException
     */
    private function parse(string $query, array $args, ?string $original_query = null): string
    {
        $original_query = $original_query ?? $query;

        $offset = 0;

        while (($posQM = mb_strpos($query, '?', $offset)) !== false) {
            $offset = $posQM;

            $placeholder_type = mb_substr($query, $posQM + 1, 1);

            // Любые ситуации с нахождением знака вопроса, который не является заполнителем.
            if (!in_array($placeholder_type, ['i', 'd', 's', 'S', 'n', 'A', 'a', 'f'])) {
                $offset += 1;
                continue;
            }

            if (!$args) {
                throw new MySqlException(
                    sprintf(
                        $this->i18n_error_messages[$this->lang][7],
                        __METHOD__,
                        $original_query
                    )
                );
            }

            $value = array_shift($args);

            $is_associative_array = false;

            switch ($placeholder_type) {
                // `LIKE` search escaping
                case 'S':
                    $is_like_escaping = true;

                // Simple string escaping
                // В случае установки MODE_TRANSFORM режима, преобразование происходит согласно правилам php типизации
                // http://php.net/manual/ru/language.types.string.php#language.types.string.casting
                // для bool, null и numeric типа.
                case 's':
                    $value = $this->getValueStringType($value, $original_query);
                    $value = !empty($is_like_escaping) ? $this->escapeLike($value) : $this->mysqlRealEscapeString(
                        $value
                    );
                    $query = static::mb_substr_replace($query, $value, $posQM, 2);
                    $offset += mb_strlen($value);
                    break;

                // Integer
                // В случае установки MODE_TRANSFORM режима, преобразование происходит согласно правилам php типизации
                // http://php.net/manual/ru/language.types.integer.php#language.types.integer.casting
                // для bool, null и string типа.
                case 'i':
                    $value = $this->getValueIntType($value, $original_query);
                    $query = static::mb_substr_replace($query, $value, $posQM, 2);
                    $offset += mb_strlen($value);
                    break;

                // double
                case 'd':
                    $value = $this->getValueFloatType($value, $original_query);
                    $query = static::mb_substr_replace($query, $value, $posQM, 2);
                    $offset += mb_strlen($value);
                    break;

                // NULL insert
                case 'n':
                    $value = $this->getValueNullType($value, $original_query);
                    $query = static::mb_substr_replace($query, $value, $posQM, 2);
                    $offset += mb_strlen($value);
                    break;

                // field or table name
                case 'f':
                    $value = $this->escapeFieldName($value, $original_query);
                    $query = static::mb_substr_replace($query, $value, $posQM, 2);
                    $offset += mb_strlen($value);
                    break;

                // Парсинг массивов.

                // Associative array
                case 'A':
                    $is_associative_array = true;

                // Simple array
                case 'a':
                    $value = $this->getValueArrayType($value, $original_query);

                    $next_char = mb_substr($query, $posQM + 2, 1);

                    if ($next_char != '' && preg_match('#[sid\[]#u', $next_char, $matches)) {
                        // Парсим выражение вида ?a[?i, "?s", "?s"]
                        if ($next_char == '[' and ($close = mb_strpos($query, ']', $posQM + 3)) !== false) {
                            // Выражение между скобками [ и ]
                            $array_parse = mb_substr($query, $posQM + 3, $close - ($posQM + 3));
                            $array_parse = trim($array_parse);
                            $placeholders = array_map('trim', explode(',', $array_parse));

                            if (count($value) != count($placeholders)) {
                                throw new MySqlException(
                                    sprintf(
                                        $this->i18n_error_messages[$this->lang][8],
                                        $original_query,
                                        implode('", "', $value)
                                    )
                                );
                            }

                            reset($value);
                            reset($placeholders);

                            $replacements = [];

                            $i = 0;
                            foreach ($value as $key => $val) {
                                $replacements[$key] = $this->parse($placeholders[$i], [$val], $original_query);
                                $i++;
                            }

                            if (!empty($is_associative_array)) {
                                $values = [];
                                foreach ($replacements as $key => $val) {
                                    $values[] = $this->escapeFieldName($key, $original_query) . ' = ' . $val;
                                }

                                $value = implode(',', $values);
                            } else {
                                $value = implode(', ', $replacements);
                            }

                            $query = static::mb_substr_replace(
                                $query,
                                $value,
                                $posQM,
                                4 + mb_strlen($array_parse)
                            );
                            $offset += mb_strlen($value);
                        } // Выражение вида ?ai, ?as, ?ad
                        else {
                            if (preg_match('#[sid]#u', $next_char, $matches)) {
                                $parts = [];

                                foreach ($value as $key => $val) {
                                    switch ($matches[0]) {
                                        case 's':
                                            $val = $this->getValueStringType($val, $original_query);
                                            $val = $this->mysqlRealEscapeString($val);
                                            break;
                                        case 'i':
                                            $val = $this->getValueIntType($val, $original_query);
                                            break;
                                        case 'd':
                                            $val = $this->getValueFloatType($val, $original_query);
                                            break;
                                    }

                                    if (!empty($is_associative_array)) {
                                        $parts[] = $this->escapeFieldName($key, $original_query) . ' = "' . $val . '"';
                                    } else {
                                        $parts[] = $matches[0] === 's' ? '"' . $val . '"' : $val;
                                    }
                                }

                                $value = implode(', ', $parts);
                                $value = $value !== '' ? $value : 'NULL';

                                $query = static::mb_substr_replace($query, $value, $posQM, 3);
                                $offset += mb_strlen($value);
                            }
                        }
                    } else {
                        throw new MySqlException(
                            $this->i18n_error_messages[$this->lang][9]
                        );
                    }

                    break;
            }
        }

        return $query;
    }

    /**
     * Depending on the mode type, returns either the string value $value, or throws an exception.
     *
     * @param mixed $value
     * @param string $original_query
     * @return string
     * @throws MySqlException
     */
    private function getValueStringType(mixed $value, string $original_query): string
    {
        if (!is_string($value) && $this->type_mode == self::MODE_STRICT) {
            // Если это числовой string, меняем его тип для вывода в тексте исключения его типа.
            if ($this->isInteger($value) || $this->isFloat($value)) {
                $value += 0;
            }

            throw new MySqlException(
                $this->createErrorMessage('string', $value, $original_query)
            );
        }

        // меняем поведение PHP в отношении приведения bool к string
        if (is_bool($value)) {
            return (string) (int) $value;
        }

        if (!is_string($value) && !(is_numeric($value) || is_null($value))) {
            throw new MySqlException(
                $this->createErrorMessage('string', $value, $original_query)
            );
        }

        return (string) $value;
    }

    /**
     * Depending on the mode type, returns either the string value of the number $value,
     * cast to type int, or throws an exception.
     *
     * @param mixed $value
     * @param string $original_query
     * @return mixed
     * @throws MySqlException
     */
    private function getValueIntType(mixed $value, string $original_query): mixed
    {
        if ($this->isInteger($value)) {
            return $value;
        }

        switch ($this->type_mode) {
            case self::MODE_TRANSFORM:
                if ($this->isFloat($value) || is_null($value) || is_bool($value)) {
                    return (int) $value;
                }

            case self::MODE_STRICT:
                // Если передали float в виде строки, то поменяем его на реальный тип float,
                // что бы в сообщении об ошибке корректно отображалась причина.
                if ($this->isFloat($value)) {
                    $value += 0;
                }
                throw new MySqlException(
                    $this->createErrorMessage('integer', $value, $original_query)
                );
        }
    }

    /**
     * Depending on the mode type, returns either the string value of the number $value,
     * cast to float, or throws an exception.
     *
     * Warning! The decimal separator returned by float may not match the DBMS separator.
     * To set the required decimal separator, use @param mixed $value
     * @param string $original_query
     * @return mixed
     * @throws MySqlException
     * @see setlocale
     */
    private function getValueFloatType(mixed $value, string $original_query): mixed
    {
        if ($this->isFloat($value)) {
            return $value;
        }

        switch ($this->type_mode) {
            case self::MODE_TRANSFORM:
                if ($this->isInteger($value) || is_null($value) || is_bool($value)) {
                    return (float) $value;
                }

            case self::MODE_STRICT:
                // Если передали int в виде строки, то поменяем его на реальный тип int,
                // что бы в сообщении об ошибке корректно отображалась причина.
                if ($this->isInteger($value)) {
                    $value += 0;
                }
                throw new MySqlException(
                    $this->createErrorMessage('double', $value, $original_query)
                );
        }
    }

    /**
     * Depending on the mode type, returns either the string value 'NULL', or throws an exception.
     *
     * @param mixed $value
     * @param string $original_query
     * @return string
     * @throws MySqlException
     */
    private function getValueNullType(mixed $value, string $original_query): string
    {
        if ($value !== null && $this->type_mode == self::MODE_STRICT) {
            // Если это числовой string, меняем его тип для вывода в тексте исключения его типа.
            if ($this->isInteger($value) || $this->isFloat($value)) {
                $value += 0;
            }

            throw new MySqlException(
                $this->createErrorMessage('NULL', $value, $original_query)
            );
        }

        return 'NULL';
    }

    /**
     * Always throws an exception if $value is not an array.
     * The original idea was to cast scalar data to array
     * type in self::MODE_TRANSFORM mode, but at the moment I consider this an
     * unnecessary concession for clients who will use this class.
     *
     * @param mixed $value
     * @param string $original_query
     * @return array
     * @throws MySqlException
     */
    private function getValueArrayType(mixed $value, string $original_query): array
    {
        if (!is_array($value)) {
            throw new MySqlException(
                $this->createErrorMessage('array', $value, $original_query)
            );
        }

        return $value;
    }

    /**
     * Escapes the name of a table field or column.
     *
     * @param mixed $value
     * @param string $original_query
     * @return string
     * @throws MySqlException
     */
    private function escapeFieldName(mixed $value, string $original_query): string
    {
        if (!is_string($value)) {
            throw new MySqlException(
                $this->createErrorMessage('field', $value, $original_query)
            );
        }

        $new_value = '';

        $replace = function ($value) {
            return '`' . str_replace("`", "``", $value) . '`';
        };

        // Признак обнаружения символа текущей базы данных
        $dot = false;

        if ($values = explode('.', $value)) {
            foreach ($values as $value) {
                if ($value === '') {
                    if (!$dot) {
                        $dot = true;
                        $new_value .= '.';
                    } else {
                        throw new MySqlException(
                            $this->i18n_error_messages[$this->lang][10]
                        );
                    }
                } else {
                    $new_value .= $replace($value) . '.';
                }
            }

            return rtrim($new_value, '.');
        } else {
            return $replace($value);
        }
    }

    /**
     * Checks if a value is an integer.
     *
     * @param mixed $val
     * @return bool
     */
    private function isInteger(mixed $val): bool
    {
        if (!is_scalar($val) || is_bool($val)) {
            return false;
        }

        if (is_int($val)) {
            return true;
        }

        if ($this->isFloat($val)) {
            return false;
        }

        return preg_match('~^((?:\+|-)?[0-9]+)$~', (string)$val) === 1;
    }

    /**
     * Checks if a value is a floating point number.
     *
     * @param mixed $val
     * @return bool
     */
    private function isFloat(mixed $val): bool
    {
        if (!is_scalar($val) || is_bool($val)) {
            return false;
        }

        if (gettype($val) === "double") {
            return true;
        }

        if (!is_string($val)) {
            return false;
        }

        return preg_match('/^[+-]?(\d*\.\d+|\d+\.\d*)$/', $val) === 1;
    }

    /**
     * Replaces the portion of string starting with the character with ordinal number start
     * and (optional) length with the string replacement and returns the result.
     *
     * @param string $string
     * @param string $replacement
     * @param int $start
     * @param int|null $length
     * @param string|null $encoding
     * @return string
     * @see substr_replace
     */
    private static function mb_substr_replace(
        string $string,
        string $replacement,
        int $start,
        ?int $length = null,
        ?string $encoding = null
    ): string {
        if (!$encoding) {
            $encoding = mb_internal_encoding();
        }

        if ($length == null) {
            return mb_substr($string, 0, $start, $encoding) . $replacement;
        } else {
            if ($length < 0) {
                $length = mb_strlen($string, $encoding) - $start + $length;
            }

            return
                mb_substr($string, 0, $start, $encoding) .
                $replacement .
                mb_substr($string, $start + $length, mb_strlen($string, $encoding), $encoding);
        }
    }
}