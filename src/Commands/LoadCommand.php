<?php

declare(strict_types=1);

namespace Migrations\Commands;

use Nextras\Migrations\Bridges\SymfonyConsole\BaseCommand;
use Nextras\Migrations\Engine\Runner;
use Nextras\Migrations\Entities\Group;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class LoadCommand extends BaseCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:load';

    protected function configure()
    {
        $this->setName(self::$defaultName);
        $this->setDescription('Play custom migrations group.');
        $this->setHelp("Play custom migrations group.");
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->runMigrations(Runner::MODE_INIT, $this->config);
    }


    /**
     * @return string
     */
    protected function getTypeArgDescription()
    {
        $options = [];
        $groups = $this->config->getGroups();
        usort($groups, function (Group $a, Group $b) {
            return strcmp($a->name, $b->name);
        });

        foreach ($groups as $i => $group) {
            for ($j = 1; $j < strlen($group->name); $j++) {
                $doesCollideWithPrevious = isset($groups[$i - 1]) && strncmp($group->name, $groups[$i - 1]->name, $j) === 0;
                $doesCollideWithNext = isset($groups[$i + 1]) && strncmp($group->name, $groups[$i + 1]->name, $j) === 0;
                if (!$doesCollideWithPrevious && !$doesCollideWithNext) {
                    $options[] = substr($group->name, 0, $j) . '(' . substr($group->name, $j) . ')';
                    break;
                }
            }
        }

        return implode(' or ', array_filter([
            implode(', ', array_slice($options, 0, -1)),
            array_slice($options, -1)[0],
        ]));
    }
}
