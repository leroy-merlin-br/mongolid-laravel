<?php
namespace Mongolid\Laravel;

use Illuminate\Contracts\Events\Dispatcher;
use Mockery as m;
use MongoDB\BSON\ObjectID;

class EventTriggerTest extends TestCase
{
    public function testShouldFire(): void
    {
        // Set
        $dispatcher = m::mock(Dispatcher::class);
        $trigger = new EventTrigger($dispatcher);
        $event = 'collection:saved';
        $payload = ['_id' => new ObjectID()];
        $halt = false;

        // Expectations
        $dispatcher->expects()
            ->dispatch($event, $payload, $halt)
            ->andReturn(true);

        // Actions
        $result = $trigger->fire($event, $payload, $halt);

        // Actions
        $this->assertTrue($result);
    }
}
