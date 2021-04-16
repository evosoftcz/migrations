<?php

declare(strict_types=1);

namespace Migrations\DI;

/**
 * Class MigrationExtension
 * @package Migrations\DI
 */
class MigrationsExtension extends \Nextras\Migrations\Bridges\NetteDI\MigrationsExtension
{
    /**
     *
     */
    public function loadConfiguration(): void
    {
        parent::loadConfiguration();
        
        $config = $this->loadFromFile(__DIR__ . '/config.neon');
        $this->compiler->loadDefinitionsFromConfig($config['services']);

        $builder = $this->getContainerBuilder();
        $this->config = (array)$this->config;
    }

    /** @var array */
    protected $dbals = [
        'dibi' => 'Nextras\Migrations\Bridges\Dibi\DibiAdapter',
        'dibi2' => 'Nextras\Migrations\Bridges\Dibi\Dibi2Adapter',
        'dibi3' => 'Nextras\Migrations\Bridges\Dibi\Dibi3Adapter',
        'dibi4' => 'Nextras\Migrations\Bridges\Dibi\Dibi3Adapter',
        'doctrine' => 'Nextras\Migrations\Bridges\DoctrineDbal\DoctrineAdapter',
        'nette' => 'Nextras\Migrations\Bridges\NetteDatabase\NetteAdapter',
        'nextras' => 'Nextras\Migrations\Bridges\NextrasDbal\NextrasAdapter',
        // evosoft
        'eda' => 'Migrations\Adapters\EdaAdapter'
    ];

    /** @var array */
    protected $drivers = [
        'mysql' => 'Nextras\Migrations\Drivers\MySqlDriver',
        'pgsql' => 'Nextras\Migrations\Drivers\PgSqlDriver',
        // evosoft
        'oracle' => 'Migrations\Drivers\OracleDriver'
    ];
}
