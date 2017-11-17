<?php

namespace MongolidLaravel;

use Illuminate\Cache\Repository;
use Mockery as m;
use TestCase;

class LaravelCacheComponentTest extends TestCase
{
    public function testShouldGet()
    {
        // Set
        $cacheRepo = m::mock(Repository::class);
        $component = new LaravelCacheComponent($cacheRepo);
        $key = 'foo';
        $value = 'bar';

        // Expectations
        $cacheRepo->shouldReceive('get')
            ->once()
            ->with($key, null)
            ->andReturn($value);

        // Assertion
        $this->assertEquals($value, $component->get($key));
    }

    public function testShouldGetFromInMemoryCache()
    {
        // Set
        $cacheRepo = m::mock(Repository::class);
        $component = new LaravelCacheComponent($cacheRepo);
        $key = 'foo';
        $value = 'bar';

        // Expectations
        $cacheRepo->shouldReceive('get')
            ->once()
            ->with($key, null)
            ->andReturn($value);

        // Actions
        $component->get($key);
        $result = $component->get($key);


        // Assertion
        $this->assertEquals($value, $result);
    }

    public function testShouldPut()
    {
        // Set
        $cacheRepo = m::mock(Repository::class);
        $component = new LaravelCacheComponent($cacheRepo);
        $key = 'foo';
        $value = [(object) ['name' => 'batata']];

        // Expectations
        $cacheRepo->shouldReceive('put')
            ->once()
            ->with($key, [['name' => 'batata']], 3)
            ->andReturn($value);

        // Assertion
        $component->put($key, $value, 3);
    }

    public function testShouldCheckIfHave()
    {
        // Set
        $cacheRepo = m::mock(Repository::class);
        $component = new LaravelCacheComponent($cacheRepo);
        $key = 'foo';

        // Expectations
        $cacheRepo->shouldReceive('has')
            ->once()
            ->with($key)
            ->andReturn(true);

        // Assertion
        $this->assertTrue($component->has($key));
    }
}
