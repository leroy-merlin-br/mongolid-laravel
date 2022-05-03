<?php
namespace MongolidLaravel\Stubs;

use MongolidLaravel\LegacyMongolidModel;

class LegacyMongolidModelStub extends LegacyMongolidModel
{
    /**
     * @var ?string
     */
    protected $collection = 'collection_name';

    /**
     * @var string[]
     */
    protected $hashedAttributes = [
        'password',
    ];

    /**
     * @var array
     */
    private $messages;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $attribute => $value) {
            $this->$attribute = $value;
        }
    }

    public function setRules(array $rules): void
    {
        $this->rules = $rules;
    }

    public function setCollection(?string $collection): void
    {
        $this->collection = $collection;
    }

    public function setMessages(array $messages): void
    {
        $this->messages = $messages;
    }

    /**
     * @inheritdoc
     */
    public function messages(): array
    {
        return $this->messages ?? [];
    }
}
