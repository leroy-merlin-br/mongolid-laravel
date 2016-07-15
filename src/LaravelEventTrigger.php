<?php
namespace MongolidLaravel;

use Mongolid\Event\EventTriggerInterface;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Wraps the Laravel's event Dispatcher in order to trigger Mongolid events.
 */
class LaravelEventTrigger implements EventTriggerInterface
{
    /**
     * Laravel's Event dispatcher
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $dispatcher;

    /**
     * Injects a Laravel's event dispatcher instance
     *
     * @param Dispatcher $dispatcher Event dispatcher.
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Triggers / Dispatches a new event to the event handlers or listeners that
     * are being used.
     *
     * @param  string  $event   Identification of the event.
     * @param  mixed   $payload Data that is going to be sent to the event handler.
     * @param  boolean $halt    The output of the event handler will be used in a conditional inside the context of
     *                          where the event is being fired. This means that, if the event handler returns false,
     *                          it will probably stop the action being executed, for example, "saving".
     *
     * @return mixed            Event handler return. The importance of this return is determined by $halt
     */
    public function fire(string $event, $payload, bool $halt)
    {
        return $this->dispatcher->fire($event, $payload, $halt);
    }
}
