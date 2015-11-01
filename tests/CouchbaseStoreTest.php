<?php

class CouchbaseStoreTest extends \TestCase
{
    /** @var \Ytake\LaravelCouchbase\Cache\CouchbaseStore */
    protected $store;

    protected function setUp()
    {
        parent::setUp();
        $cluster = $this->app['db']->connection('couchbase')->getCouchbase();
        $this->store = new \Ytake\LaravelCouchbase\Cache\CouchbaseStore(
            $cluster, 'testing', 'testing'
        );
    }

    public function testAddAlreadyKey()
    {
        $this->assertTrue($this->store->add('test', 'test', 120));
        $this->assertFalse($this->store->add('test', 'test', 120));
        $this->store->forget('test');
    }

    public function testAddArrayableKey()
    {
        $this->store->add(['test', 'test2'], 'test', 120);
        $result = $this->store->get(['test', 'test2']);
        foreach ($result as $row) {
            $this->assertSame('test', $row->value);
        }

        $this->store->forget(['test', 'test2']);
    }

    public function testPrefix()
    {
        $this->assertSame('testing:', $this->store->getPrefix());
    }

    public function testNotFoundKey()
    {
        $this->assertNull($this->store->get('notFoundTest'));
    }

    /**
     * @expectedException \Ytake\LaravelCouchbase\Exceptions\FlushException
     */
    public function testDisableFleshException()
    {
        $this->store->flush();
    }

    public function testIncrement()
    {
        $this->assertSame(1, $this->store->increment('test', 1));
        $this->assertSame(11, $this->store->increment('test', 10));
        $this->store->forget('test');
    }

    public function testDecrement()
    {
        $this->assertSame(-1, $this->store->decrement('test', 1));
        $this->assertSame(-11, $this->store->decrement('test', 10));
        $this->assertSame(-21, $this->store->decrement('test', -10));
        $this->store->forget('test');
    }

    public function testUpsert()
    {
        $value = ['message' => 'testing'];
        $this->store->put('test', json_encode($value), 400);
        $this->assertSame(json_encode($value), $this->store->get('test')->value);
        $value = ['message' => 'testing2'];
        $this->store->put('test', json_encode($value), 400);
        $this->assertSame(json_encode($value), $this->store->get('test')->value);
        $this->store->forget('test');
    }

    public function testCacheableComponentInstance()
    {
        /** @var Illuminate\Cache\Repository $cache */
        $cache = $this->app['cache']->driver('couchbase');
        $this->assertInstanceOf(get_class($this->store), $cache->getStore());
        $cache->add('test', 'testing', 400);
        $this->assertSame('testing', $this->store->get('test')->value);
        $this->store->forget('test');
    }
}