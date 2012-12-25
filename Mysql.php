<?php
/**
 * @author Vasiliy Makogon, makogon.vs@gmail.com
 * @link http://www.phpinfo.su/
 *
 * ������ ����� ���������� ���������� placeholders - ��� ������������ ���������� SQL-��������, � ������ ������� ������
 * �������� ������� ����������� �������������� ������� - �����������, � ���� ������ ���������� "�����", � ��������
 * ����������� ���������� ��������� ������, ������������ SQL-������ - Krugozor_Database_Mysql::query().
 * ������, ��������� ����� ������� placeholders, ������������ ������������ ��������� �������������,
 * � ����������� �� ���� ������������. �.�. ��� ��� ������������� ��������� ���������� � �������
 * ������������� ���� mysqli_real_escape_string($value) ��� ��������� �� � ��������� ���� ����� (int)$value.
 *
 *
 *    ���� ������������
 *
 * ?i - ����������� ��������� ����.
 *      � ������ MODE_TRANSFORM ������ ������������� ���������� � ���� integer.
 * ?s - ����������� ���������� ����, ������������ ������.
 *      � ������ MODE_TRANSFORM ������ ������������� ���������� � ���� string.
 * ?S - ����������� ���������� ���� ��� ����������� � ��������� LIKE ������, ������������ ������.
 *      � ������ MODE_TRANSFORM ������ ������������� ���������� � ���� string.
 * ?n - ����������� NULL ����.
 *      � ������ MODE_TRANSFORM ������ ������������� ���������� �� NULL.
 * ?At - ����������� �������������� ��������� �� �������������� ������� ("key_1" => "val_1", "key_2" => "val_2", ...)
 * ?at - ����������� ��������� �� ������ ������� ("val_1", "val_2", ...)
 *       ��� t - ���� �� �����:
 *       - i (int)
 *       - s (string)
 *       ������� �������������� � ������������� ����� ��, ��� � ��� ��������� ��������� �����.
 * ?A[?n, ?s, ?i] - ����������� �������������� ��������� � ����� ��������� ���� � ���������� ����������.
 * ?a[?n, ?s, ?i] - ����������� ��������� � ����� ��������� ���� � ���������� ����������.
 *
 *
 *    ������ ������.
 *
 * ���������� ��� ������ ������ ������:
 * Krugozor_Database_Mysql::MODE_STRICT    - ������� ����� ������������ ���� ����������� � ���� ���������.
 * Krugozor_Database_Mysql::MODE_TRANSFORM - ����� �������������� ��������� � ���� ����������� ��� ������������
 *                                             ���� ����������� � ���� ���������. ���������� �� ���������.
 *
 *
 *     MODE_STRICT
 *
 * � "�������" ������ MODE_STRICT ���������, ������������ � �������� �����
 * Krugozor_Database_Mysql::query(), ������ � �������� ��������������� ���� �����������.
 * �������� �������:
 *
 * $db->query('SELECT * FROM table WHERE field = ?i', '����'); - � ������ ������ ����� ��������� ����������
 *     "������� �������� ��� int �������� ���� ���� string � ������� ...", �.�.
 * ������ ��� ����������� ?i (int - ����� �����), � � �������� ��������� ���������� ������ '����'.
 *
 * $db->query('SELECT * FROM table WHERE field = "?s"', 123); - ����� ��������� ����������
 *     "������� �������� ��� string �������� 123 ���� integer � ������� ...", �.�.
 * ������ ��� ����������� ?s (string - ������), � � �������� ��������� ���������� ����� 123.
 *
 * $db->query('SELECT * FROM table WHERE field IN (?as)', array(null, 123, true, 'string')); - ����� ��������� ����������
 *     "������� �������� ��� string �������� ���� NULL � ������� ...", �.�. ����������� ������� ?a �������,
 * ��� ��� �������� �������-�������� ����� ���� s (string - ������), �� �� ���� ��� �������� ������� ������������ �����
 * ������ ��������� �����. ������ ��������� ������ �� ������ �������������� ���� - �� �������� ������� �� ��������� null.

 *
 *     MODE_TRANSFORM
 *
 * ����� MODE_TRANSFORM �������� "�������" ������� � ��� �������������� ���� ����������� � ��������� �� ����������
 * ����������, � �������� ������������� �������� � ������� ���� � ������������ � ��������� �������������� ����� � PHP.
 *
 * ����������� ��������� ��������������:
 *
 * � ���������� ���� ���������� ������ ���� boolean, numeric, NULL:
 *     - �������� boolean TRUE ������������� � ������ "1", � �������� FALSE ������������� � "" (������ ������)
 *     - �������� ���� numeric ������������� � ������ �������� �������� ��������������, ������������ ������
 *     - NULL ������������� � ������ ������
 * ��� ��������, �������� � �������� �������������� �� �����������.
 *
 * ������ ���������:
 *     $db->query('SELECT * FROM table WHERE f1 = "?s", f2 = "?s", f3 = "?s"', null, 123, true);
 * ��������� ��������������:
 *     SELECT * FROM table WHERE f1 = "", f2 = "123", f3 = "1"
 *
 * � �������������� ���� ���������� ������ ���� boolean, string, NULL:
 *     - �������� boolean FALSE ������������� � 0 (����), � TRUE - � 1 (�������).
 *     - �������� ���� string ������������� �������� �������� ��������������, ������������ ������
 *     - NULL ������������� � 0
 * ��� ��������, �������� � �������� �������������� �� �����������.
 *
 * ������ ���������:
 *     $db->query('SELECT * FROM table WHERE f1 = ?i, f2 = ?i, f3 = ?i, f4 = ?i', null, '123abc', 'abc', true);
 * ��������� ��������������:
 *     SELECT * FROM table WHERE f1 = 0, f2 = 123, f3 = 0, f4 = 1
 *
 * NULL ��� �������� �������� ��� ������ ���� ������.
 *
 *
 *    �������������� ������
 *
 * ������ ����� ��� ������������ SQL-������� �� ���������� ������������� �������������� ������� ��� ���������
 * ������������ ���������� ����, ����� ��� ?i � ?s. ��� ������� �� �������������� ������������, ��������������� �������
 * ����� ����� ������������ ��� ������������ SQL.
 * ��������, ���������
 *     $db->query('SELECT "Total: ?s"', '200');
 * ������ ������
 *     'Total: 200'
 * ���� �� �������, �������������� ��������� �������, ��������� �� �������������,
 * �� �������������� ������� ������� �� ������
 *     'Total: "200"'
 * ��� ���� �� �� ��������� ���������� ��� ������������.
 *
 * ��� �� �����, ��� ������������ ?as, ?ai, ?As � ?Ai �������������� ������� �������� �������������, �.�.
 * ������������ ������ ������������ � ��������, ��� ������� ������ ����������� ��� �� ������ ����:
 *
 *    $db->query('INSERT INTO test SET ?As', array('name' => '����', 'age' => '23', 'adress' => '������'));
 *    -> INSERT INTO test SET `name` = "����", `age` = "23", `adress` = "������"
 *
 *    $db->query('SELECT * FROM table WHERE field IN (?as)', array('55', '12', '132'));
 *    -> SELECT * FROM table WHERE field IN ("55", "12", "132")
 */
class Krugozor_Database_Mysql
{
    /**
     * ������� ����� ���������.
     * ���� ��� ����������� �� ��������� � ����� ���������, �� ����� ��������� ����������.
     * ������ ����� ��������:
     *
     * $db->query('SELECT * FROM `table` WHERE `id` = ?i', '2+�����');
     *
     * - � ������ �������� ��� ����������� ?i - ����� ��� �������� ������,
     *   � � �������� ��������� ��������� ������ '2+�����' �� ���������� �� ������,
     *   �� �������� �������.
     *
     * @var int
     */
    const MODE_STRICT = 1;

    /**
     * ����� ��������������.
     * ���� ��� ����������� �� ��������� � ����� ���������, �������� ������������� ����� �������
     * � ������� ���� - � ���� �����������.
     * ������ ����� ��������:
     *
     * $db->query('SELECT * FROM `table` WHERE `id` = ?i', '2+�����');
     *
     * - � ������ �������� ��� ����������� ?i - ����� ��� �������� ������,
     *   � � �������� ��������� ��������� ������ '2+�����' �� ���������� �� ������,
     *   �� �������� �������.
     *   ������ '2+�����' ����� ������������� ��������� � ���� int - � ����� 2.
     *
     * @var int
     */
    const MODE_TRANSFORM = 2;

    /**
     * ����� ������ ��� �������, ����� ��� ����������� �� ������������� ���� ���������.
     * ��. �������� �������� self::MODE_STRICT � self::MODE_TRANSFORM.
     *
     * @var int
     */
    protected $type_mode = self::MODE_TRANSFORM;

    protected $server;

    protected $user;

    protected $password;

    /**
     * ��� ������� ��.
     *
     * @var string
     */
    private $database_name;

    /**
     * ������ ���������� � ��.
     *
     * @var mysqli
     */
    private $lnk;

    /**
     * ������ ���������� SQL-������� �� ��������������.
     *
     * @var string
     */
    private $original_query;

    /**
     * ������ ���������� SQL-�������.
     *
     * @var string
     */
    private $query;

    /**
     * ������ �� ����� ���������, ������� ���� ��������� ��������.
     * ����� - SQL �� ��������������, �������� - �����.
     *
     * @var array
     */
    private static $queries = array();

    /**
     * ������ ���� ����� ����������� �������.
     * ��� �������� ��� ��������������� �����������, ��. ����� $this->getListFields().
     * �� ������ � ������ ���������� ������������� ������� ��� �������� � ����� $this->getListFields()
     * �.�. � ������ ������ ��� ������������ ������������� �� ������������ �������� � �������� ������������� � ����
     * ������ �����. ����, ����������, ��� �������� ����� � ������ ��������, ���� ��� ������ �� ��� ����������.
     *
     * @var array
     */
    private static $list_fields = array();

    /**
     * ������� ������� ������� ������.
     *
     * @param string $server ��� �������
     * @param string $username ��� ������������
     * @param string $password ������
     */
    public static function create($server, $username, $password)
    {
        return new self($server, $username, $password);
    }

    /**
     * @see mysqli_set_charset
     * @param string $charset
     * @return Krugozor_Database_Mysql
     */
    public function setCharset($charset)
    {
        if (!mysqli_set_charset($this->lnk, $charset))
        {
            throw new Exception(__METHOD__ . ': ' . mysqli_error($this->lnk));
        }

        return $this;
    }

    /**
     * @see mysqli_character_set_name
     * @param void
     * @return string
     */
    public function getCharset()
    {
        return mysqli_character_set_name($this->lnk);
    }

    /**
     * ������������� ��� ������������ ����.
     *
     * @param string ��� ���� ������
     * @return Krugozor_Database_Mysql
     */
    public function setDatabaseName($database_name)
    {
        if (!is_object($this->lnk))
        {
            $this->connect();
        }

        if (!$database_name)
        {
            throw new Exception(__METHOD__ . ': �� ������� ��� ���� ������');
        }

        $this->database_name = $database_name;

        if (!mysqli_select_db($this->lnk, $this->database_name))
        {
            throw new Exception(__METHOD__ . ': ' . mysqli_error($this->lnk));
        }

        return $this;
    }

    /**
     * ���������� ��� ������� ��.
     *
     * @param void
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->database_name;
    }

    /**
     * ������������� ����� ��������� ��� ������������ ���� ����������� � ���������.
     *
     * @param $value int
     * @return Krugozor_Database_Mysql
     */
    public function setTypeMode($value)
    {
        $this->type_mode = $value;

        return $this;
    }

    /**
     * ��������� SQL-������.
     * ��������� ������������ �������� - SQL-������ �, � ������ �������,
     * ����� ���������� ���������� - �������� ������������.
     *
     * @param string ������ SQL-�������
     * @param mixed ��������� ��� ������������
     * @return bool|Krugozor_Database_Mysql_Statement
     */
    public function query()
    {
        $this->connect();

        if (!func_num_args())
        {
            return false;
        }

        $args = func_get_args();

        $query = $this->original_query = array_shift($args);

        $this->query = $this->parse($query, $args);

        $result = mysqli_query($this->lnk, $this->query);

        self::$queries[$this->original_query] = $this->query;

        if ($result === false)
        {
            throw new Exception(__METHOD__ . ': ' . mysqli_error($this->lnk) . '; SQL: ' . $this->query);
        }

        if (is_object($result) && $result instanceof mysqli_result)
        {
            return new Krugozor_Database_Mysql_Statement($result);
        }

        return $result;
    }

    /**
     * ��������� ���������� ������ self::query(), ������ ����� ��������� ������ ��� ��������� -
     * SQL ������ $query � ������ ���������� $arguments, ������� � ����� ������� �� ���������� � ���
     * ������������������, � ������� ��� ������������� � ������� $arguments.
     *
     * @param string
     * @param array
     * @return bool|Krugozor_Database_Mysql_Statement
     */
    public function queryArguments($query, array $arguments=array())
    {
    	array_unshift($arguments, $query);

    	return call_user_func_array(array($this, 'query'), $arguments);
    }

    /**
     * �������� ���������� �����,
     * ��������������� � ���������� MySQL-��������.
     * ���������� ���������� �����,
     * ��������������� � ��������� ������� INSERT, UPDATE ��� DELETE.
     * ���� ��������� �������� ��� DELETE ��� ��������� WHERE,
     * ��� ������ ������� ����� �������, �� ������� ��������� ����.
     *
     * @param void
     * @return int
     */
    public function getAffectedRows()
    {
        return mysqli_affected_rows($this->lnk);
    }

    /**
     * ���������� ��������� ������������ SQL-������ �� ��������������.
     *
     * @param void
     * @return string
     */
    public function getOriginalQueryString()
    {
        return $this->original_query;
    }

    /**
     * ���������� ��������� ����������� MySQL-������.
     *
     * @param void
     * @return string
     */
    public function getQueryString()
    {
        return $this->query;
    }

    /**
     * ���������� ������ �� ����� ������������ SQL-��������� � ������ �������� �������.
     *
     * @param void
     * @return array
     */
    public function getQueries()
    {
        return self::$queries;
    }

    /**
     * ���������� id, ��������������� ���������� ��������� INSERT.
     *
     * @param void
     * @return int
     */
    public function getLastInsertId()
    {
        return mysqli_insert_id($this->lnk);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * ���������� ������ �������� stdClass, ���������� �������� ������� $table.
     *
     * @param string ��� �������
     * @return array
     */
    public function getListFields($table)
    {
    	if (!isset(self::$list_fields[$table]))
        {
        	$result = $this->query('SELECT * FROM `' . $this->database_name . '`.`' . $table . '` LIMIT 1');

        	$finfo = mysqli_fetch_fields($result->getResult());

    		foreach ($finfo as $obj)
    		{
                self::$list_fields[$table][$obj->name] = $obj;
            }
        }

        return self::$list_fields[$table];
    }

    /**
     * @param string $server
     * @param string $username
     * @param string $password
     * @return void
     */
    private function __construct($server, $user, $password)
    {
        $this->server   = $server;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * ������������� ���������� � ����� ������.
     *
     * @param void
     * @return void
     */
    private function connect()
    {
        if (!is_object($this->lnk) || !$this->lnk instanceof mysqli)
        {
            if (!$this->lnk = @mysqli_connect($this->server, $this->user, $this->password))
            {
                throw new Exception(__METHOD__ . ': ' . mysqli_connect_error());
            }
        }
    }

    /**
     * ��������� MySQL-����������.
     *
     * @param void
     * @return Krugozor_Database_Mysql
     */
    private function close()
    {
        if (is_object($this->lnk) && $this->lnk instanceof mysqli)
        {
            @mysqli_close($this->lnk);
        }

        return $this;
    }

    /**
     * ���������� �������������� ������ ��� placeholder-� ������ LIKE.
     * �������� ������ ��������� ��� LIKE-������ ��. http://phpfaq.ru/slashes#prepared
     *
     * @param string $var ������ � ������� ���������� ������������ ����. �������
     * @param string $chars ����� ��������, ������� ��� �� ���������� ������������.
     *                      �� ��������� ������������ ��������� �������: '"%_
     * @return string
     */
    private function escape_like($var, $chars = "%_")
    {
        $var = str_replace('\\','\\\\',$var);
        $var = $this->mysqlRealEscapeString($var);

        if ($chars)
        {
            $var = addCslashes($var, $chars);
        }

        return $var;
    }

    /**
    * @see mysqli_real_escape_string
    * @param string
    * @return string
    */
    private function mysqlRealEscapeString($value)
    {
        return mysqli_real_escape_string($this->lnk, $value);
    }

    /**
     * ���������� ������ �������� ������ ��� ������������ ����� ������������ � ����������.
     *
     * @param string $type ��� �����������
     * @param mixed $value �������� ���������
     * @param string $original_query ������������ SQL-������
     * @return string
     */
    private function createErrorMessage($type, $value, $original_query)
    {
        return __CLASS__ . ': ������� �������� ��� ' . $type . ' �������� ' . print_r($value, true) . ' ���� ' .
               gettype($value) . ' � ������� ' . $original_query;
    }

    /**
     * ������ ������ $query � ����������� � ���� ��������� �� $args.
     *
     * @param string $query
     * @param array $args
     * @param string $original_query
     * @return string
     */
    private function parse($query, array $args, $original_query=null)
    {
        $original_query = $original_query ? $original_query : $query;

        $offset = 0;

        while (($posQM = strpos($query, '?', $offset)) !== false)
        {
            $offset = $posQM;

            if (!isset($query[$posQM + 1]))
            {
                continue;
            }

            if (!$args)
            {
                throw new Exception(__METHOD__ . ': ���������� ������������ � ������� ' . $original_query . ' �� ������������� ����������� ���������� ����������');
            }

            $value = array_shift($args);

            switch ($query[$posQM + 1])
            {
                // `LIKE` search escaping
                case 'S':
                    $is_like_escaping = true;

                // Simple string escaping
                // � ������ ��������� MODE_TRANSFORM ������, �������������� ���������� �������� �������� php ���������
                // http://php.net/manual/ru/language.types.string.php#language.types.string.casting
                // ��� bool, null � numeric ����.
                case 's':
                    $value = $this->getValueStringType($value, $original_query);
                    $value = !empty($is_like_escaping) ? $this->escape_like($value) : $this->mysqlRealEscapeString($value);
                    $query = substr_replace($query, $value, $posQM, 2);
                    $offset += strlen($value);
                    break;

                // Integer
                // � ������ ��������� MODE_TRANSFORM ������, �������������� ���������� �������� �������� php ���������
                // http://php.net/manual/ru/language.types.integer.php#language.types.integer.casting
                // ��� bool, null � string ����.
                case 'i':
                    $value = $this->getValueIntType($value, $original_query);
                    $query = substr_replace($query, $value, $posQM, 2);
                    $offset += strlen($value);
                    break;

                // NULL insert
                case 'n':
                    $value = $this->getValueNullType($value, $original_query);
                    $query = substr_replace($query, $value, $posQM, 2);
                    $offset += strlen($value);
                    break;

                // ������� ��������.

                // Associative array
                case 'A':
                    $is_associative_array = true;

                // Simple array
                case 'a':
                    if (!is_array($value))
                    {
                        if ($this->type_mode == self::MODE_STRICT)
                        {
                            throw new Exception($this->createErrorMessage('array', $value, $original_query));
                        }
                        else
                        {
                            $value = (array)$value;
                        }
                    }

                    if (isset($query[$posQM+2]) && preg_match('#[si\[]#', $query[$posQM+2], $matches))
                    {
                        // ������ ��������� ���� ?a[?i, "?s", "?s"]
                        if ($query[$posQM+2] == '[' and ($close = strpos($query, ']', $posQM+3)) !== false)
                        {
                        	// ��������� ����� �������� [ � ]
                            $array_parse = substr($query, $posQM+3, $close - ($posQM+3));
                            $array_parse = trim($array_parse);
                            $placeholders = array_map('trim', explode(',', $array_parse));

                            if (count($value) != count($placeholders))
                            {
                            	throw new Exception('������������ ���������� ���������� � ������������ � �������, ������ ' . $original_query);
                            }

                            reset($value);
                            reset($placeholders);

                            $replacements = array();

                            foreach ($placeholders as $placeholder)
                            {
                            	list($key, $val) = each($value);
                            	$replacements[$key] = $this->parse($placeholder, array($val), $original_query);
                            }

                            if (!empty($is_associative_array))
                            {
                                foreach ($replacements as $key => $val)
                                {
                                    $values[] = ' `' . $key . '` = ' . $val;
                                }

                                $value = implode(',', $values);
                            }
                            else
                            {
                                $value = implode(', ', $replacements);
                            }

                            $query = substr_replace($query, $value, $posQM, 4 + strlen($array_parse));
                            $offset += strlen($value);
                        }
                        // ��������� ���� ?ai ��� ?as
                        else if (preg_match('#[si]#', $query[$posQM+2], $matches))
                        {
                            $sql = '';
                            $parts = array();

                            foreach ($value as $key => $val)
                            {
                                switch ($matches[0])
                                {
                                    case 's':
                                        $val = $this->getValueStringType($val, $original_query);
                                        $val = $this->mysqlRealEscapeString($val);
                                        break;
                                    case 'i':
                                        $val = $this->getValueIntType($val, $original_query);
                                        break;
                                }

                                if (!empty($is_associative_array))
                                {
                                    $parts[] = ' `' . $key . '` = "' . $val . '"';
                                }
                                else
                                {
                                    $parts[] = '"' . $val . '"';
                                }
                            }

                            $value = implode(', ', $parts);
                            $query = substr_replace($query, $value, $posQM, 3);
                            $offset += strlen($value);
                        }
                    }
                    else
                    {
                        throw new Exception('������� ��������������� ������������ ������� ��� �������� ���� ������ ��� ���������');
                    }
                    break;
            }
        }

        return $query;
    }

    /**
     * � ����������� �� ���� ������ ���������� ���� ��������� �������� $value,
     * ���� ������ ����������.
     *
     * @param mixed $value
     * @param string $original_query ������������ SQL ������
     * @return string
     */
    private function getValueStringType($value, $original_query)
    {
        if (!is_string($value))
        {
            if ($this->type_mode == self::MODE_STRICT)
            {
                throw new Exception($this->createErrorMessage('string', $value, $original_query));
            }
            else if ($this->type_mode == self::MODE_TRANSFORM)
            {
                if (is_numeric($value) || is_null($value) || is_bool($value))
                {
                    $value = (string)$value;
                }
                else
                {
                    throw new Exception($this->createErrorMessage('string', $value, $original_query));
                }
            }
        }

        return $value;
    }

    /**
     * � ����������� �� ���� ������ ���������� ���� ��������� �������� ����� $value,
     * ���� ������ ����������.
     *
     * @param mixed $value
     * @param string $original_query ������������ SQL ������
     * @return string
     */
    private function getValueIntType($value, $original_query)
    {
        if (!is_numeric($value))
        {
            if ($this->type_mode == self::MODE_STRICT)
            {
                throw new Exception($this->createErrorMessage('int', $value, $original_query));
            }
            else if ($this->type_mode == self::MODE_TRANSFORM)
            {
                if (is_string($value) || is_null($value) || is_bool($value))
                {
                    $value = (int)$value;
                }
                else
                {
                    throw new Exception($this->createErrorMessage('int', $value, $original_query));
                }
            }
        }

        return (string)$value;
    }

    /**
     * � ����������� �� ���� ������ ���������� ���� ��������� �������� 'NULL',
     * ���� ������ ����������.
     *
     * @param mixed $value
     * @param string $original_query ������������ SQL ������
     * @return string
     */
    private function getValueNullType($value, $original_query)
    {
        if ($value !== null)
        {
            if ($this->type_mode == self::MODE_STRICT)
            {
                throw new Exception($this->createErrorMessage('NULL', $value, $original_query));
            }
        }

        return 'NULL';
    }
}