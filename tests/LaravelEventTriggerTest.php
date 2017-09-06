<?php

namespace MongolidLaravel;

use Illuminate\Contracts\Events\Dispatcher;
use Mockery as m;
use MongoDB\BSON\ObjectID;
use TestCase;

class LaravelEventTriggerTest extends TestCase
{
    public function testShouldFire()
    {
        // Set
        $dispatcher = m::mock(Dispatcher::class);
        $trigger = new LaravelEventTrigger($dispatcher);
        $event = 'collection:saved';
        $payload = ['_id' => new ObjectID()];
        $halt = false;

        // Expectations
        $dispatcher->shouldReceive('fire')
            ->once()
            ->with($event, $payload, $halt)
            ->andReturn(true);

        // Actions
        $result = $trigger->fire($event, $payload, $halt);

        // Actions
        $this->assertTrue($result);
    }
}
