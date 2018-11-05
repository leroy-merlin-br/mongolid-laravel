<?php
namespace Mongolid\Laravel;

use Illuminate\Contracts\Events\Dispatcher;
use Mockery as m;
use MongoDB\BSON\ObjectID;

class EventTriggerTest extends TestCase
{
    public function testShouldFire()
    {
        // Set
        $dispatcher = m::mock(Dispatcher::class);
        $trigger = new EventTrigger($dispatcher);
        $dispatcher = m::mock(new class implements Dispatcher {
            public function fire($event, $payload = [], $halt = false)
            {
            }

            public function listen($events, $listener = null)
            {
            }

            public function hasListeners($eventName)
            {
            }

            public function subscribe($subscriber)
            {
            }

            public function until($event, $payload = [])
            {
            }

            public function dispatch($event, $payload = [], $halt = false)
            {
            }

            public function push($event, $payload = [])
            {
            }

            public function flush($event)
            {
            }

            public function forget($event)
            {
            }

            public function forgetPushed()
            {
            }
        });
        $trigger = new LaravelEventTrigger($dispatcher);
        $event = 'collection:saved';
        $payload = ['_id' => new ObjectID()];
        $halt = false;

        // Expectations
        $dispatcher->expects()
            ->fire($event, $payload, $halt)
            ->andReturn(true);

        // Actions
        $result = $trigger->fire($event, $payload, $halt);

        // Actions
        $this->assertTrue($result);
    }

    public function testShouldFireOnNewerVersions()
    {
        // Set
        $dispatcher = m::mock(Dispatcher::class);
        $trigger = new LaravelEventTrigger($dispatcher);
        $event = 'collection:saved';
        $payload = ['_id' => new ObjectID()];
        $halt = false;

        // Expectations
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with($event, $payload, $halt)
            ->andReturn(true);

        // Actions
        $result = $trigger->fire($event, $payload, $halt);

        // Actions
        $this->assertTrue($result);
    }
}
