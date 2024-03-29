<?php

namespace MongolidLaravel\Migrations;

/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

use Illuminate\Console\OutputStyle;
use MongolidLaravel\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class MigrationTest extends TestCase
{
    public function testGetConnection()
    {
        // Set
        $output = new OutputStyle(new ArrayInput([]), new NullOutput());
        $migration = new class ($output) extends Migration {
            /**
             * {@inheritdoc}
             */
            protected $connection = 'mongolid';
        };

        // Actions
        $result = $migration->getConnection();

        // Assertions
        $this->assertSame('mongolid', $result);
    }
}
