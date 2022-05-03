<?php
namespace MongolidLaravel;

use Illuminate\Contracts\Hashing\Hasher;
use Mockery as m;
use Mongolid\Cursor\CursorInterface;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Model\Exception\ModelNotFoundException;

class LegacyMongolidModelTest extends TestCase
{
    public function testShouldValidateWithNoRules()
    {
        // Set
        $model = new class() extends LegacyMongolidModel {
        };

        // Actions
        $result = $model->isValid();

        // Assertions
        $this->assertTrue($result);
        $this->assertEmpty($model->errors()->all());
    }

    public function testShouldNotValidateWithUnattendedRules()
    {
        // Set
        $model = new class() extends LegacyMongolidModel {
            protected $rules = [
                'name' => 'required',
                'address' => 'min:100',
            ];
        };

        $model->address = 'small address';

        $expectedErrors = [
            'The name field is required.',
            'The address must be at least 100 characters.',
        ];

        // Actions
        $result = $model->isValid();

        // Assertions
        $this->assertFalse($result);
        $this->assertEquals($expectedErrors, $model->errors()->all());
    }

    public function testShouldValidateRulesWithCustomMessage()
    {
        // Set
        $model = new class() extends LegacyMongolidModel {
            protected $rules = [
                'name' => 'required',
            ];

            public function messages(): array
            {
                return [
                    'name.required' => 'The name must be fielded.',
                ];
            }
        };

        $expectedErrors = [
            'The name must be fielded.',
        ];

        // Actions
        $result = $model->isValid();

        // Assertions
        $this->assertFalse($result);
        $this->assertEquals($expectedErrors, $model->errors()->all());
    }

    public function testValidateShouldSkipUnchangedHashedAttributes()
    {
        // Set
        $model = new class() extends LegacyMongolidModel {
            public $rules = [
                'name' => 'required',
            ];

            protected $hashedAttributes = ['password'];
        };

        $model->name = 'name';
        $model->password = 'HASHED_PASSWORD';

        // Actions
        $result = $model->isValid();

        // Assertions
        $this->assertTrue($result);
        $this->assertEmpty($model->errors()->all());
    }

    public function testShouldValidateChangedHashedAttributes()
    {
        // Set
        $model = new class() extends LegacyMongolidModel {
            public $rules = [
                'password' => 'required',
            ];

            protected $hashedAttributes = ['password'];
        };

        $model->password = 'HASHED_PASSWORD';

        // Actions
        $result = $model->isValid();

        // Assertions
        $this->assertTrue($result);
        $this->assertEmpty($model->errors()->all());
    }

    public function testShouldSave()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        $model = new class() extends LegacyMongolidModel {
            protected $collection = 'users';
        };

        // Expectations
        $dataMapper->shouldReceive('setSchema')->passthru();

        $dataMapper->shouldReceive('save')
            ->once()
            ->withAnyArgs()
            ->andReturn(true);

        // Actions
        $result = $model->save();

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldMockSave()
    {
        // Set
        $model = new class() extends LegacyMongolidModel {
        };

        // Expectations
        $model->shouldReceiveSave()
            ->once()
            ->withNoArgs()
            ->andReturn(true);

        // Actions
        $result = $model->save();

        // Assertions
        $this->assertTrue($result);
    }

    /**
     * @dataProvider getMethods
     */
    public function testShouldHashAttributesOnSaveAndUpdate($method)
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));
        $hasher = $this->instance(Hasher::class, m::mock(Hasher::class));

        $model = new class() extends LegacyMongolidModel {
            protected $collection = 'users';

            protected $hashedAttributes = ['password'];
        };

        $model->password = '123456';
        $model->password_confirmation = '123456';

        // Expectations
        $dataMapper->shouldReceive('setSchema')->passthru();

        $dataMapper->shouldReceive($method)
            ->once()
            ->withAnyArgs()
            ->andReturn(true);

        $hasher->shouldReceive('make')
            ->once()
            ->with('123456')
            ->andReturn('HASHED_PASSWORD');

        // Actions
        $result = $model->$method();

        // Assertions
        $this->assertTrue($result);
        $this->assertEquals('HASHED_PASSWORD', $model->password);
        $this->assertNull($model->password_confirmation);
    }

    public function testShouldNotAttemptToSaveWhenInvalid()
    {
        // Set
        $model = new class() extends LegacyMongolidModel {
            public $rules = [
                'name' => 'required',
                'address' => 'min:100',
            ];
        };

        $expectedErrors = [
            'The name field is required.',
            'The address must be at least 100 characters.',
        ];

        $model->address = 'small address';

        // Actions
        $result = $model->save();

        // Assertions
        $this->assertFalse($result);
        $this->assertEquals($expectedErrors, $model->errors()->all());
    }

    public function testShouldForceSaving()
    {
        // Set
        $model = new class() extends LegacyMongolidModel {
            public $rules = [
                'name' => 'required',
                'address' => 'min:100',
            ];
        };

        $model->address = 'small address';

        // Expectations
        $model->shouldReceiveSave()
            ->once()
            ->withNoArgs()
            ->andReturn(true);

        // Actions
        $result = $model->save(true);

        // Assertions
        $this->assertTrue($result);
        $this->assertEmpty($model->errors()->all());
    }

    public function testShouldDelete()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        $model = new class() extends LegacyMongolidModel {
            protected $collection = 'collection_name';
        };

        // Expectations
        $dataMapper->shouldReceive('setSchema')->passthru();

        $dataMapper->shouldReceive('delete')
            ->once()
            ->withAnyArgs()
            ->andReturn(true);

        // Actions
        $result = $model->delete();

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldMockDelete()
    {
        // Set
        $model = new class() extends LegacyMongolidModel {
        };

        // Expectations
        $model->shouldReceiveDelete()
            ->once()
            ->with()
            ->andReturn(true);

        // Actions
        $result = $model->delete();

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldGetFirst()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        $model = new class() extends LegacyMongolidModel {
            protected $collection = 'collection_name';
        };

        // Expectations
        $dataMapper->shouldReceive('setSchema')->passthru();

        $dataMapper->shouldReceive('first')
            ->once()
            ->withAnyArgs()
            ->andReturn($model);

        // Actions
        $result = $model->first('123');

        // Assertions
        $this->assertEquals($model, $result);
    }

    public function testShouldMockFirst()
    {
        // Set
        $model = new class() extends LegacyMongolidModel {
            protected $collection = 'collection_name';
        };

        // Expectations
        $model::shouldReceive('first')
            ->once()
            ->withAnyArgs()
            ->andReturn($model);

        // Actions
        $result = $model->first('123');

        // Assertions
        $this->assertEquals($model, $result);
    }

    public function testShouldGetFirstOrNew()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        $model = new class() extends LegacyMongolidModel {
            protected $collection = 'collection_name';
        };

        // Expectations
        $dataMapper->shouldReceive('setSchema')->passthru();

        $dataMapper->shouldReceive('first')
            ->once()
            ->withAnyArgs()
            ->andReturn($model);

        // Actions
        $result = $model->firstOrNew('123');

        // Assertions
        $this->assertEquals($model, $result);
    }

    public function testShouldMockFirstOrNew()
    {
        // Set
        $model = new class() extends LegacyMongolidModel {
            protected $collection = 'collection_name';
        };

        // Expectations
        $model::shouldReceive('firstOrNew')
            ->once()
            ->withAnyArgs()
            ->andReturn($model);

        // Actions
        $result = $model->firstOrNew('123');

        // Assertions
        $this->assertEquals($model, $result);
    }

    public function testShouldGetFirstOrFailAndFoundIt()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        $model = new class() extends LegacyMongolidModel {
            protected $collection = 'collection_name';
        };

        // Expectations
        $dataMapper->shouldReceive('setSchema')->passthru();

        $dataMapper->shouldReceive('firstOrFail')
            ->once()
            ->withAnyArgs()
            ->andReturn($model);

        // Actions
        $result = $model->firstOrFail('123');

        // Assertions
        $this->assertEquals($model, $result);
    }

    public function testShouldGetFirstOrFailAndFail()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));

        $model = new class() extends LegacyMongolidModel {
            protected $collection = 'collection_name';
        };

        // Expectations
        $this->expectException(ModelNotFoundException::class);

        $dataMapper->shouldReceive('setSchema')->passthru();

        $dataMapper->shouldReceive('firstOrFail')
            ->once()
            ->withAnyArgs()
            ->andThrow(ModelNotFoundException::class);

        // Actions
        $model->firstOrFail('123');
    }

    public function testShouldMockFirstOrFail()
    {
        // Set
        $model = new class() extends LegacyMongolidModel {
            protected $collection = 'collection_name';
        };

        // Expectations
        $model::shouldReceive('firstOrFail')
            ->once()
            ->withAnyArgs()
            ->andReturn($model);

        // Actions
        $result = $model->firstOrFail('123');

        // Assertions
        $this->assertEquals($model, $result);
    }

    public function testShouldGetWhere()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));
        $cursor = m::mock(CursorInterface::class);

        $model = new class() extends LegacyMongolidModel {
            protected $collection = 'collection_name';
        };

        // Expectations
        $dataMapper->shouldReceive('setSchema')->passthru();

        $dataMapper->shouldReceive('where')
            ->once()
            ->withAnyArgs()
            ->andReturn($cursor);

        // Actions
        $result = $model->where(['field' => '123']);

        // Assertions
        $this->assertEquals($cursor, $result);
    }

    public function testShouldGetAll()
    {
        // Set
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class));
        $cursor = m::mock(CursorInterface::class);

        $model = new class() extends LegacyMongolidModel {
            protected $collection = 'collection_name';
        };

        // Expectations
        $dataMapper->shouldReceive('setSchema')->passthru();

        $dataMapper->shouldReceive('all')
            ->once()
            ->withAnyArgs()
            ->andReturn($cursor);

        // Actions
        $result = $model->all();

        // Assertions
        $this->assertEquals($cursor, $result);
    }

    public function testShouldIgnoreInvalidStaticCalls()
    {
        // Set
        $model = new class() extends LegacyMongolidModel {
        };

        // Actions
        $result = $model::foobar();

        // Assertions
        $this->assertNull($result);
    }

    /**
     * Retrieves methods which should hash attributes before send data to DB.
     */
    public function getMethods()
    {
        return [
            ['save'],
            ['update'],
        ];
    }
}
