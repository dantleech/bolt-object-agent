<?php

namespace Psi\Bridge\ObjectAgent\Bolt\Tests\Functional;

use Psi\Component\ObjectAgent\Capabilities;
use Psi\Component\ObjectAgent\Exception\BadMethodCallException;
use Psi\Component\ObjectAgent\Exception\ObjectNotFoundException;
use Psi\Component\ObjectAgent\Query\Query;
use Psi\Bridge\ObjectAgent\Bolt\Tests\Functional\Model\Page;

trait AgentTestTrait
{
    /**
     * It should return its capabilities.
     */
    public function testCapabilities()
    {
        $capabilities = $this->agent->getCapabilities();
        $this->assertInstanceOf(Capabilities::class, $capabilities);
    }

    /**
     * It should find a object.
     */
    public function testFind()
    {
        $page = $this->createPage();

        $object = $this->agent->find($page->id, Page::class);

        // bolt will not return the same instance...
        $this->assertSame((int) $page->id, (int) $object->id); // and it returns a fucking string
    }

    /**
     * It should find many.
     */
    public function testFindMany()
    {
        $page1 = $this->createPage('Foobar');
        $page2 = $this->createPage('Hello');
        $page3 = $this->createPage('Hello');

        $pages = $this->agent->findMany([
            $page1->id,
            $page2->id,
        ], Page::class);

        $this->assertCount(2, $pages);
        if (!is_array($pages)) {
            $pages = iterator_to_array($pages);
        }
        $this->assertEquals($page1->id, array_shift($pages)->id);
        $this->assertEquals($page2->id, array_shift($pages)->id);
    }

    /**
     * It should throw an exception if the object was not found.
     *
     * @expectedException Psi\Component\ObjectAgent\Exception\ObjectNotFoundException
     * @expectedExceptionMessage Could not find object
     */
    public function testFindNotFound()
    {
        $this->createPage('Foobar');
        $this->createPage('Hello');
        $fo = $this->agent->find('asd', Page::class);
    }

    /**
     * It should save.
     */
    public function testSave()
    {
        $page = $this->createPage();
        $this->agent->persist($page);
        $this->agent->flush();
    }

    /**
     * It should remove.
     */
    public function testDelete()
    {
        $page = $this->createPage();
        $this->agent->remove($page);
        $this->agent->flush();

        try {
            $object = $this->agent->find($page->id, Page::class);
            $this->fail('Object was not removed');
        } catch (ObjectNotFoundException $e) {
        }
    }

    /**
     * It should return a object's identifier (a UUID).
     */
    public function testGetIdentifier()
    {
        $page = $this->createPage();
        $identifier = $this->agent->getIdentifier($page);
        $this->assertNotNull($identifier);
    }

    /**
     * It should say if it supports a given object.
     */
    public function testSupports()
    {
        $this->assertTrue($this->agent->supports(Page::class));
        $this->assertFalse($this->agent->supports(\stdClass::class));
    }

    /**
     * It should perform a query.
     */
    public function testQuery()
    {
        $this->createPage('Foobar');
        $this->createPage('Hello');
        $query = Query::create(Page::class, Query::composite(
            'and',
            Query::comparison('eq', 'title', 'Hello')
        ));
        $results = $this->agent->query($query);
        $this->assertCount(1, $results);
    }

    /**
     * It should return the total number of records that could
     * be reached by a query.
     */
    public function testQueryCount()
    {
        if (false === $this->agent->getCapabilities()->canQueryCount()) {
            $this->setExpectedException(BadMethodCallException::class);
            $query = Query::create(Page::class, null, [], 1, 2);
            $this->assertEquals(4, $this->agent->queryCount($query));

            return;
        }

        $this->createPage('Foobar');
        $this->createPage('Hello');
        $this->createPage('Goodbye');
        $this->createPage('Barfood');

        $query = Query::create(Page::class, null, [], 1, 2);
        $this->assertEquals(4, $this->agent->queryCount($query));
    }

    /**
     * It should return all objects if no expression is provided.
     */
    public function testQueryNoExpression()
    {
        $this->createPage();
        $this->createPage();
        $this->createPage();
        $this->createPage();
        $query = Query::create(Page::class);
        $results = $this->agent->query($query);
        $this->assertCount(4, $results);
    }

    /**
     * It should limit the results.
     */
    public function testQueryLimit()
    {
        $this->createPage('aaaa');
        $this->createPage('aaaa');
        $this->createPage('aaaa');
        $this->createPage('zzzz');
        $query = Query::create(Page::class, null, [], 0, 2);
        $results = $this->agent->query($query);
        $this->assertCount(2, $results);
    }

    /**
     * It should set the first result offset.
     */
    public function testQueryOffset()
    {
        $this->createPage('aaaa');
        $this->createPage('aaaa');
        $this->createPage('aaaa');
        $this->createPage('zzzz');
        $query = Query::create(Page::class, null, [], 3, 2);
        $results = $this->agent->query($query);
        $this->assertCount(1, $results);
        $first = $results->first();
        $this->assertEquals('zzzz', $first->title);
    }

    /**
     * It should order query results.
     */
    public function testQueryOrder()
    {
        $this->createPage('aaaa');
        $this->createPage('zzzz');
        $query = Query::create(Page::class, null, [
            'title' => 'desc',
        ]);
        $results = $this->agent->query($query);
        $first = $results->first();
        $this->assertNotFalse($first);
        $this->assertEquals('zzzz', $first->title);
    }

    private function createPage($title = 'Hello World')
    {
    }
}
