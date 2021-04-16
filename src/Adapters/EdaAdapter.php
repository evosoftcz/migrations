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
    /** @var Nette\Database\Connection */
    private $conn;


    public function __construct(Nette\Database\Connection $ndb)
    {
        $this->conn = $ndb;
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
        return $this->conn->quote($value, PDO::PARAM_STR);
    }


    public function escapeInt($value)
    {
        return $this->conn->quote($value, PDO::PARAM_INT);
    }


    public function escapeBool($value)
    {
        return $this->escapeString((string) (int) $value);
    }


    public function escapeDateTime(DateTime $value)
    {
        return $this->conn->getSupplementalDriver()->formatDateTime($value);
    }


    public function escapeIdentifier($value)
    {
        return $this->conn->getSupplementalDriver()->delimite($value);
    }

}
