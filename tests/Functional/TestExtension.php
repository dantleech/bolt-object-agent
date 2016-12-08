<?php

namespace Psi\Bridge\ObjectAgent\Bolt\Tests\Functional;

use Silex\ServiceProviderInterface;
use Silex\Application;
use Psi\Bridge\ObjectAgent\Bolt\Tests\Functional\TestExtension;
use Psi\Bridge\ObjectAgent\Bolt\BoltObjectAgentExtension;
use Bolt\Extension\DatabaseSchemaTrait;
use Bolt\Storage\Repository;
use Bolt\Extension\StorageTrait;
use Psi\Bridge\ObjectAgent\Bolt\Tests\Functional\Example\PageTable;
use Psi\Bridge\ObjectAgent\Bolt\Tests\Functional\Model\Page;

class TestExtension extends BoltObjectAgentExtension
{
    use DatabaseSchemaTrait;
    use StorageTrait;

    public function register(Application $app)
    {
        $this->extendDatabaseSchemaServices();
        $this->extendRepositoryMapping();
    }

    protected function registerExtensionTables()
    {
        return [
            'page' => PageTable::class,
        ];
    }

    protected function registerRepositoryMappings()
    {
        return [
            'page' => [Page::class => Repository::class],
        ];
    }
}
