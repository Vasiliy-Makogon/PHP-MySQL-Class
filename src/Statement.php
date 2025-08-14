<?php

namespace Krugozor\Database;

use mysqli_result;
use stdClass;

/**
 * @author Vasiliy Makogon
 * @link https://github.com/Vasiliy-Makogon/Database/
 */
class Statement
{
    /**
     * @var mysqli_result
     */
    protected mysqli_result $mysqli_result;

    /**
     * @param mysqli_result $mysqli_result
     */
    public function __construct(mysqli_result $mysqli_result)
    {
        $this->mysqli_result = $mysqli_result;
    }

    /**
     * Retrieves the resulting row as an associative array.
     *
     * @return array|null
     */
    public function fetchAssoc(): ?array
    {
        return $this->mysqli_result->fetch_assoc();
    }

    /**
     * Retrieves the resulting row as an array.
     *
     * @return array|null
     */
    public function fetchRow(): ?array
    {
        return $this->mysqli_result->fetch_row();
    }

    /**
     * Retrieves the resulting row as an object.
     *
     * @return stdClass|null
     */
    public function fetchObject(): ?stdClass
    {
        return $this->mysqli_result->fetch_object();
    }

    /**
     * Returns the result as an array of associative arrays.
     *
     * @return array
     */
    public function fetchAssocArray(): array
    {
        $array = [];

        while ($row = $this->mysqli_result->fetch_assoc()) {
            $array[] = $row;
        }

        return $array;
    }

    /**
     * Returns the result as an array of arrays.
     *
     * @return array
     */
    public function fetchRowArray(): array
    {
        $array = [];

        while ($row = $this->mysqli_result->fetch_row()) {
            $array[] = $row;
        }

        return $array;
    }

    /**
     * Returns the result as an array of \stdClass objects.
     *
     * @return array
     */
    public function fetchObjectArray(): array
    {
        $array = [];

        while ($row = $this->mysqli_result->fetch_object()) {
            $array[] = $row;
        }

        return $array;
    }

    /**
     * Returns the value of the first field of the resulting table.
     *
     * @return string|null
     */
    public function getOne(): ?string
    {
        if ($row = $this->mysqli_result->fetch_row()) {
            return $row[0];
        }

        return null;
    }

    /**
     * Returns the number of rows in the result.
     * This command is only valid for SELECT statements.
     *
     * @return int
     * @see mysqli_num_rows
     */
    public function getNumRows(): int
    {
        return $this->mysqli_result->num_rows;
    }

    /**
     * Returns a mysqli_result object.
     *
     * @return mysqli_result
     */
    public function getResult(): mysqli_result
    {
        return $this->mysqli_result;
    }

    /**
     * Frees memory occupied by query results.
     *
     * @return void
     */
    public function free(): void
    {
        $this->mysqli_result->free();
    }

    public function __destruct()
    {
        $this->free();
    }
}
