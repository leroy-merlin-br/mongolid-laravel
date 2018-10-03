<?php
namespace MongolidLaravel\Migrations;

/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

use MongolidLaravel\TestCase;

class MigrationTest extends TestCase
{
    public function testGetConnection()
    {
        // Set
        $migration = new class() extends Migration {
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
