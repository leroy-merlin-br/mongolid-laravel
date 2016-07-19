<?php

use Mongolid\Container\Ioc;

class TestCase extends Orchestra\Testbench\TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        Ioc::setContainer($this->app);
    }
}
