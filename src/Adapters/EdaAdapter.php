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
        try {
            $result = $this->conn->query($sql);
            $x = $result->fetchAll();
        } catch (\Exception $e) {
            dump($e->getMessage());
            exit;
        }

        return $x;
    }


    public function exec($sql)
    {
        return $this->conn->query($sql)->getRowCount();
    }


    public function escapeString($value)
    {
        return $this->conn->getDriver()->getParent()->getTranslator()::normalizeStringType($value, true);
    }


    public function escapeInt($value)
    {
        return $this->conn->getDriver()->getParent()->getTranslator()::normalizeIntegerType($value, true);
    }


    public function escapeBool($value)
    {
        return $this->conn->getDriver()->getParent()->getTranslator()::normalizeBoolType($value, true);
    }


    public function escapeDateTime(DateTime $value)
    {
        return $this->conn->getDriver()->getParent()->getTranslator()::normalizeDateTimeType($value, true);
    }


    public function escapeIdentifier($value)
    {
        if ($this->conn->isOracle()) {
            return str_replace('"', '""', $value);
        } else {
            return '"' . str_replace('"', '""', $value) . '"';
        }
    }

}
