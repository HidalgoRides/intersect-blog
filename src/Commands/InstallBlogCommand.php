<?php

namespace Intersect\Blog\Commands;

use Intersect\Core\Storage\FileStorage;
use Intersect\Database\Migrations\Runner;
use Intersect\Core\Command\AbstractCommand;
use Intersect\Database\Connection\Connection;

class InstallBlogCommand extends AbstractCommand {

    private $runner;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->runner = new Runner($connection, new FileStorage(), $this->logger, null);
        $this->runner->setMigrationDirectory(__DIR__ . '/../Migrations');
    }

    public function getDescription()
    {
        return 'Installs the current blog schema';
    }

    public function execute($data = [])
    {
        $this->runner->migrate();
    }

}