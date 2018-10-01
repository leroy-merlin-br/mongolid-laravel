<?php
/*
* Copyright (c) Taylor Otwell, Leroy Merlin Brasil
* Copyrights licensed under the MIT License.
* See the accompanying LICENSE file for terms.
*/

namespace MongolidLaravel\Migrations;

abstract class Migration
{
    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;

    /**
     * Get the migration connection name.
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
