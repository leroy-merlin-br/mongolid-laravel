<?php

namespace MongolidLaravel;

use Illuminate\Cache\Repository;
use Mockery as m;
use Mongolid\Serializer\Serializer;
use TestCase;

class LaravelCacheComponentTest extends TestCase
{
    public function testShouldGet()
    {
        // Set
        $cacheRepo = m::mock(Repository::class);
        $serializer = m::mock(Serializer::class);
        $component = new LaravelCacheComponent($cacheRepo, $serializer);
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

    public function testShouldPut()
    {
        // Set
        $cacheRepo = m::mock(Repository::class);
        $serializer = m::mock(Serializer::class);
        $component = new LaravelCacheComponent($cacheRepo, $serializer);
        $key = 'foo';
        $value = [(object) ['name' => 'batata']];

        // Expectations
        $serializer->shouldReceive('convert')
            ->once()
            ->with([['name' => 'batata']])
            ->andReturn([['name' => 'chips']]);

        $cacheRepo->shouldReceive('put')
            ->once()
            ->with($key, [['name' => 'chips']], 3)
            ->andReturn($value);

        // Assertion
        $component->put($key, $value, 3);
    }

    public function testShouldCheckIfHave()
    {
        // Set
        $cacheRepo = m::mock(Repository::class);
        $serializer = m::mock(Serializer::class);
        $component = new LaravelCacheComponent($cacheRepo, $serializer);
        $key = 'foo';
        $exists = true;

        // Expectations
        $cacheRepo->shouldReceive('has')
            ->once()
            ->with($key)
            ->andReturn($exists);

        // Assertion
        $this->assertEquals($exists, $component->has($key));
    }
}
