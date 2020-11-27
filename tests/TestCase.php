<?php
namespace MongolidLaravel;

use Mockery as m;
use Mongolid\Container\Ioc;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        Ioc::setContainer($this->app);
    }

    protected function tearDown(): void
    {
        $this->addToAssertionCount(
            m::getContainer()->mockery_getExpectationCount()
        );
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
     *
     * @return m\Matcher\Closure
     */
    protected function expectEquals($expected, float $delta = 100)
    {
        return m::on(
            function ($value) use ($expected, $delta) {
                    $this->assertEqualsWithDelta($expected, $value, $delta);

                    return true;
            }
        );
    }
}
