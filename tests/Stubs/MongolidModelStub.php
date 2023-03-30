<?php

namespace MongolidLaravel\Stubs;

use MongolidLaravel\MongolidModel;

class MongolidModelStub extends MongolidModel
{
    /**
     * @var string
     */
    protected $collection = 'collection_name';

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $attribute => $value) {
            $this->$attribute = $value;
        }
    }
}
