<?php
namespace Mongolid\Laravel;

use Mockery as m;
use Mockery\Matcher\Closure;
use Mongolid\Container\Container;
use Mongolid\Laravel\Tests\Util\UTCDateTimeComparator;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        Container::setContainer($this->app);
        $this->registerComparator(new UTCDateTimeComparator());
    }

    protected function tearDown(): void
    {
        $this->addToAssertionCount(
            m::getContainer()->mockery_getExpectationCount()
        );
        AbstractModel::clearMocks();
        m::close();
        parent::tearDown();
    }

    /**
     * There are some cases where a simple `$mock->with($a)` might give false positives
     * and in these cases this helper might be used, like `$mock->with($this->expectEquals($a))`.
     * In the past of our system, this would been solved with `andReturnUsing`, but this new method
     * is preferred for clarity and API abstraction.
     *
     * @param mixed $expected Expected value for a with() parameter
     * @param float $delta    Possible delta variation, useful for dates
     */
    protected function expectEquals($expected, float $delta = 100.0): Closure
    {
        return m::on(
            function ($value) use ($expected, $delta) {
                    $this->assertEqualsWithDelta($expected, $value, $delta, '');

                    return true;
            }
        );
    }
}
