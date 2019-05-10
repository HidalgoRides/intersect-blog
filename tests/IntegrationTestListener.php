<?php

namespace Tests;

use Intersect\Core\Container;
use PHPUnit\Framework\TestSuite;
use Intersect\Database\Model\Model;
use PHPUnit\Framework\TestListener;
use Intersect\Core\Logger\ConsoleLogger;
use Intersect\Core\Command\CommandRunner;
use Intersect\Database\Connection\Connection;
use Intersect\Blog\Commands\InstallBlogCommand;
use Intersect\Database\Exception\DatabaseException;
use Intersect\Database\Connection\ConnectionFactory;
use Intersect\Database\Connection\ConnectionSettings;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use Intersect\Database\Connection\ConnectionRepository;

class IntegrationTestListener implements TestListener {
    use TestListenerDefaultImplementation;

    /** @var Connection */
    private $connection;

    /** @var Container */
    private $container;

    /** @var ConsoleLogger */
    private $logger;

    private $databaseName = 'integration_tests';
    private $testSuiteName = 'Tests';

    public function __construct()
    {
        $connectionSettings = ConnectionSettings::builder('db', 'root', 'password')
            ->port(3306)
            ->database('app')
            ->build();
        $this->connection = ConnectionFactory::get('mysql', $connectionSettings);
        ConnectionRepository::register($this->connection);
        ConnectionRepository::registerAlias('ib_conn');

        $container = new Container();
        $container->getCommandRegistry()->register('blog:install', new InstallBlogCommand($this->connection));

        $this->container = $container;
        $this->logger = new ConsoleLogger();
    }

    public function startTestSuite(TestSuite $suite): void
    {
        if ($suite->getName() == $this->testSuiteName)
        {
            $this->logger->info('');
            $this->logger->info('Starting integration tests');
            $this->logger->info('');

            $this->createDatabaseAndUse();

            $this->logger->info('');
            $this->logger->info('Running database migrations');
            $this->logger->info('');

            try {
                $commandRunner = new CommandRunner($this->container);
                $commandRunner->run(['', 'blog:install']);
            } catch (DatabaseException $e) {}

            $this->logger->info('');
            $this->logger->info('Finished running database migrations');
            $this->logger->info('');

            $this->logger->info('');
        }
    }

    public function endTestSuite(TestSuite $suite): void
    {
        if ($suite->getName() == $this->testSuiteName)
        {
            $this->logger->info('');
            $this->logger->info('');
            $this->logger->info('Ending test suite');

            $this->dropDatabase();
        }
    }

    private function createDatabaseAndUse()
    {
        $this->logger->info('Creating database ' . $this->databaseName);
        $this->connection->query('CREATE DATABASE IF NOT EXISTS ' . $this->databaseName);

        $this->logger->info('Switching database to ' . $this->databaseName);
        $this->connection->switchDatabase($this->databaseName);
    }

    private function dropDatabase()
    {
        $this->logger->info('Dropping database ' . $this->databaseName);
        $this->connection->query('DROP DATABASE IF EXISTS ' . $this->databaseName);
    }

}