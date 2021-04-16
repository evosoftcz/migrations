<?php

namespace Migrations\Drivers;

use DateTime;
use Nextras\Migrations\Drivers\PgSqlDriver;
use Nextras\Migrations\Entities\Migration;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\LockException;
use Nextras\Migrations\Drivers\BaseDriver;

/**
 * Class OracleDriver
 * @package Migrations\Drivers
 */
class OracleDriver extends BaseDriver implements IDriver
{
    public function loadFile($path)
    {
//        $content = @file_get_contents($path);
//        if ($content === FALSE) {
//            throw new IOException("Cannot open file '$path'.");
//        }
        $queries = 0;
        dump('loadfile');
        $fp = file($path);
        $query = '';
        foreach ($fp as $line) {
            if ($line != '' && strpos($line, '--') === false) {
                $query .= $line;
//                if (substr($query, -1) === ';'.PHP_EOL.PHP_EOL || $line === PHP_EOL) {
                if ($line === PHP_EOL) {
//                    dump('query');
//                    dump($query);

                    if (substr($query, -1, 1) === ';') {
                        $query = substr($query, 0, -1);
                    }

                    try {
                        $this->dbal->exec($query);
                    } catch (\Exception $e) {
                        \Tracy\Debugger::log($e);
                    }
                    $queries++;
                    $query = '';
                }
            }
        }

        return $queries;
    }


    /**
     * @param bool $enabled
     */
    private function setEnabledConstrains(bool $enabled = true)
    {
        $result = $this->dbal->query("SELECT ('alter table ' || table_name || ' " . ($enabled ? 'enable' : 'disable') . " constraint ' ||  constraint_name) AS a FROM
        (
            SELECT DISTINCT a.table_name, a.constraint_name
            FROM all_cons_columns a
            JOIN all_constraints c ON a.owner = c.owner
            AND a.constraint_name = c.constraint_name
            JOIN all_constraints c_pk ON c.r_owner = c_pk.owner
            AND c.r_constraint_name = c_pk.constraint_name
            WHERE c.constraint_type = 'R'
        )
        ");

        foreach ($result as $row) {
            try {
                $this->dbal->exec($row['a']);
            } catch (\Exception $exception) {
            }
        }
    }

    public function setupConnection()
    {
        dump('setup');
        parent::setupConnection();
        $this->dbal->exec('ALTER SESSION SET NLS_DATE_FORMAT = "YYYY-MM-DD HH24:MI:SS"');
//        $this->dbal->exec('SET foreign_key_checks = 0');
        $this->setEnabledConstrains(false);
        $this->dbal->exec('ALTER SESSION SET nls_sort=Latin_AI');
        $this->dbal->exec('ALTER SESSION SET NLS_COMP=linguistic');
    }

    public function emptyDatabase()
    {
        dump('empty');

        try {
            $tables = $this->dbal->exec("
                SELECT 'drop table ' || table_name || ' cascade constraints' AS a FROM user_tables
                UNION SELECT 'drop sequence ' || SEQUENCE_name AS a  FROM user_sequences
                UNION SELECT 'drop view ' || view_name AS a FROM user_views
                UNION SELECT 'drop materialized view log on ' || master AS a FROM all_mview_logs
                UNION SELECT 'drop INDEX ' || index_name AS a FROM user_indexes WHERE INDEX_name LIKE 'IDX%'
            ");
            $views = $this->dbal->exec("
                SELECT 'drop view ' || view_name AS a FROM user_views
                UNION SELECT 'drop materialized view ' || name AS a FROM all_snapshots
                UNION SELECT 'drop materialized view log on ' || master AS a FROM all_mview_logs
                UNION SELECT 'drop INDEX ' || index_name AS a FROM user_indexes WHERE INDEX_name LIKE 'IDX%'

            ");
        } catch (\Exception $exception) {
            \Tracy\Debugger::log($exception);
        }

        foreach ($views ?? [] as $row) {
            try {
                $this->dbal->exec($row['a']);
            } catch (\Exception $exception) {
            }
        }

        foreach ($tables ?? [] as $row) {
            try {
                $this->dbal->exec($row['a']);
            } catch (\Exception $exception) {
            }
        }

        $this->dbal->exec('purge recyclebin');
    }

    public function beginTransaction()
    {
        dump('begin');
        $this->dbal->exec('SET TRANSACTION READ WRITE');
    }


    public function commitTransaction()
    {
        dump('commit');
        $this->dbal->exec('COMMIT');
    }


    public function rollbackTransaction()
    {
        dump('rollback');
        $this->dbal->exec('ROLLBACK');
    }


    public function lock()
    {
    }


    public function unlock()
    {
    }


    public function createTable()
    {
        dump('crt');
        $this->dbal->exec($this->getInitTableSource());
    }


    public function dropTable()
    {
        dump('drp');
        $this->dbal->exec("DROP TABLE {$this->tableNameQuoted}");
    }


    public function insertMigration(Migration $migration)
    {
        dump('ins');
        $this->dbal->exec("
        INSERT INTO {$this->tableNameQuoted} (\"GROUP\", \"FILE\", CHECKSUM, EXECUTED, READY) VALUES (" .
            $this->dbal->escapeString($migration->group) . "," .
            $this->dbal->escapeString($migration->filename) . "," .
            $this->dbal->escapeString($migration->checksum) . "," .
            $this->dbal->escapeDateTime($migration->executedAt) . "," .
            $this->dbal->escapeBool(FALSE) .
        ")
		");
        $migration->id = (int) $this->dbal->query('SELECT MAX(ID) FROM ' . $this->tableNameQuoted)[0]['id'];

    }


    public function markMigrationAsReady(Migration $migration)
    {
        dump('rdy');
        $this->dbal->exec("
			UPDATE {$this->tableNameQuoted}
			SET ready = 1
			WHERE id = " . $this->dbal->escapeInt($migration->id)
        );
    }


    public function getAllMigrations()
    {
        dump('all');
        $migrations = array();
        $result = $this->dbal->query("SELECT * FROM {$this->tableNameQuoted} ORDER BY EXECUTED");
        foreach ($result as $row) {
            if (is_string($row['EXECUTED'])) {
                $executedAt = new DateTime($row['EXECUTED']);

            } elseif ($row['EXECUTED'] instanceof \DateTimeImmutable) {
                $executedAt = new DateTime('@' . $row['EXECUTED']->getTimestamp());

            } else {
                $executedAt = $row['EXECUTED'];
            }

            $migration = new Migration;
            $migration->id = (int) $row['ID'];
            $migration->group = $row['GROUP'];
            $migration->filename = $row['FILE'];
            $migration->checksum = $row['CHECKSUM'];
            $migration->executedAt = $executedAt;
            $migration->completed = (bool) $row['READY'];

            $migrations[] = $migration;
        }

        return $migrations;
    }


    public function getInitTableSource()
    {
        return preg_replace('#^\t{3}#m', '', trim('
            DECLARE
            BEGIN
                EXECUTE IMMEDIATE \'CREATE TABLE ' . $this->tableNameQuoted . '
                (
                    ID NUMBER(38) generated as identity
                        constraint PK_' . $this->tableNameQuoted . '
                            primary key,
                    "GROUP" VARCHAR2(100) not null,
                    "FILE" VARCHAR2(100) not null,
                    CHECKSUM CHAR(32) not null,
                    EXECUTED DATE not null,
                    READY CHAR(1) default 0 not null,
                    CONSTRAINT UNQ_' . $this->tableNameQuoted . ' UNIQUE ("GROUP", "FILE")
                )
                \';
                exception when others then if SQLCODE = -955 then null; else raise; end if;
            END;
		'));
    }


    public function getInitMigrationsSource(array $files)
    {
        dump('init');
        $out = '';
        foreach ($files as $file) {
            $x = "INSERT INTO {$this->tableNameQuoted} (`GROUP`, `FILE`, `CHECKSUM`, `EXECUTED`, `READY`) VALUES (" .
                $this->dbal->escapeString($file->group->name) . ", " .
                $this->dbal->escapeString($file->name) . ", " .
                $this->dbal->escapeString($file->checksum) . ", " .
                $this->dbal->escapeDateTime(new DateTime('now')) . ", " .
                $this->dbal->escapeBool(TRUE) .
                ");\n";

            dump($x);

            $out .= $x;
        }
        return $out;
    }

}
