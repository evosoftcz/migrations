<?php

namespace Migrations\Adapters;

use DateTime;
use Nette;
use Nextras\Migrations\IDbal;
use PDO;

/**
 * Class EdaAdapter
 * @package Migrations\Adapters
 */
class EdaAdapter implements IDbal
{
    /** @var \Eda\Connection */
    private $conn;

    /**
     * EdaAdapter constructor.
     * @param \Eda\Connection $conn
     */
    public function __construct(\Eda\Connection $conn)
    {
        $this->conn = $conn;
    }

    public function query($sql)
    {
        return array_map(
            function ($row) { return (array) $row; },
            $this->conn->fetchAll($sql)
        );
    }


    public function exec($sql)
    {
        return $this->conn->query($sql)->getRowCount();
    }


    public function escapeString($value)
    {
        return $this->conn->getDriver()->escapeText($value);
    }


    public function escapeInt($value)
    {
        return $this->conn->getDriver()->escapeIdentifier($value);
    }


    public function escapeBool($value)
    {
        return $this->escapeString((string) (int) $value);
    }


    public function escapeDateTime(DateTime $value)
    {
        return $this->conn->getDriver()->escapeDateTime($value);
    }


    public function escapeIdentifier($value)
    {
        return $this->conn->getDriver()->escapeIdentifier($value);
    }

}
