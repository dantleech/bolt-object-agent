<?php

namespace Psi\Bridge\ObjectAgent\Bolt\Tests\Functional\Example;

use Bolt\Storage\Database\Schema\Table\BaseTable;

class PageTable extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        $this->table->addColumn('id', 'integer', ['autoincrement' => true]);
        $this->table->addColumn('title', 'string');
        $this->table->addColumn('path', 'string', [ 'notnull' => false ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }

    /**
     * {@inheritdoc}
     */
    protected function addForeignKeyConstraints()
    {
    }
}
