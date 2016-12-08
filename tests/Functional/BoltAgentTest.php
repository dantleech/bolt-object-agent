<?php

namespace Psi\Bridge\ObjectAgent\Bolt\Tests\Functional;

use Psi\Bridge\ObjectAgent\Bolt\BoltAgent;
use Psi\Bridge\ObjectAgent\Bolt\Tests\Functional\Example\Article;
use Psi\Bridge\ObjectAgent\Bolt\Tests\Functional\Model\Page;

class BoltAgentTest extends ApplicationTestCase
{
    use AgentTestTrait;

    /**
     * @var BoltAgent
     */
    private $agent;

    /**
     * @var EntityManager
     */
    private $storage;

    public function setUp()
    {
        $this->storage = $this->getService('storage');
        $this->agent = new BoltAgent($this->storage);
        $this->initDatabase();
    }

    /**
     * It should throw a BadMethodCallException if set parent is called.
     *
     * @expectedException \BadMethodCallException
     */
    public function testSetParent()
    {
        $parent = $this->createPage();
        $page = new Page();
        $this->agent->setParent($page, $parent);
    }

    private function createPage($title = 'Hello World')
    {
        $page = new Page();
        $page->title = $title;
        $this->storage->save($page);

        return $page;
    }
}
