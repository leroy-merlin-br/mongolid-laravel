<?php
namespace Mongolid\Laravel;

use Illuminate\Contracts\Hashing\Hasher;
use Mockery as m;
use Mongolid\Cursor\Cursor;
use Mongolid\Model\Exception\ModelNotFoundException;
use Mongolid\Query\Builder;

class AbstractModelTest extends TestCase
{
    public function testShouldValidateWithNoRules(): void
    {
        // Set
        $model = new class() extends AbstractModel
        {
        };

        // Actions
        $result = $model->isValid();

        // Assertions
        $this->assertTrue($result);
        $this->assertEmpty($model->errors()->all());
    }

    public function testShouldReturnNullForInvalidStaticCall(): void
    {
        // Set
        $model = new class() extends AbstractModel
        {
            protected $rules = [
                'name' => 'required',
                'address' => 'min:100',
            ];
        };

        // Actions
        $result = $model::invalid();

        // Assertions
        $this->assertNull($result);
    }

    public function testShouldNotValidateWithUnattendedRules(): void
    {
        // Set
        $model = new class() extends AbstractModel
        {
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

    public function testShouldValidateRulesWithCustomMessage(): void
    {
        // Set
        $model = new class() extends AbstractModel {
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
        $model = new class() extends AbstractModel
        {
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

    public function testShouldValidateChangedHashedAttributes(): void
    {
        // Set
        $model = new class() extends AbstractModel
        {
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

    public function testShouldSave(): void
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));

        $model = new class() extends AbstractModel
        {
            protected $collection = 'users';
        };

        // Expectations
        $builder->expects()
            ->save()
            ->withAnyArgs()
            ->andReturn(true);

        // Actions
        $result = $model->save();

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldMockSave(): void
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));
        $builder->makePartial();
        $model = new class() extends AbstractModel
        {
            protected $collection = 'models';
        };

        // Expectations
        $builder->expects()
            ->save()
            ->withAnyArgs()
            ->andReturn(true);

        // Actions
        $result = $model->save();

        // Assertions
        $this->assertTrue($result);
    }

    /**
     * @dataProvider getMethods
     */
    public function testShouldHashAttributesOnSaveAndUpdate($method): void
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));
        $hasher = $this->instance(Hasher::class, m::mock(Hasher::class));

        $model = new class() extends AbstractModel
        {
            protected $collection = 'users';

            protected $hashedAttributes = ['password'];
        };

        $model->password = '123456';
        $model->password_confirmation = '123456';

        // Expectations
        $builder->expects()
            ->{$method}()
            ->withAnyArgs()
            ->andReturn(true);

        $hasher->expects()
            ->make('123456')
            ->andReturn('HASHED_PASSWORD');

        // Actions
        $result = $model->$method();

        // Assertions
        $this->assertTrue($result);
        $this->assertEquals('HASHED_PASSWORD', $model->password);
        $this->assertNull($model->password_confirmation);
    }

    public function testShouldNotAttemptToSaveWhenInvalid(): void
    {
        // Set
        $model = new class() extends AbstractModel
        {
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

    public function testShouldForceSaving(): void
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));
        $builder->makePartial();
        $model = new class() extends AbstractModel
        {
            protected $collection = 'models';

            public $rules = [
                'name' => 'required',
                'address' => 'min:100',
            ];
        };

        $model->address = 'small address';

        // Expectations
        $builder->expects()
            ->save()
            ->withAnyArgs()
            ->andReturn(true);

        // Actions
        $result = $model->save(true);

        // Assertions
        $this->assertTrue($result);
        $this->assertFalse($model->errors()->any());
    }

    public function testShouldDelete(): void
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));

        $model = new class() extends AbstractModel
        {
            protected $collection = 'collection_name';
        };

        // Expectations
        $builder->expects()
            ->delete()
            ->withAnyArgs()
            ->andReturn(true);

        // Actions
        $result = $model->delete();

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldMockDelete(): void
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));
        $builder->makePartial();
        $model = new class() extends AbstractModel
        {
            protected $collection = 'models';
        };

        // Expectations
        $builder->expects()
            ->delete()
            ->withAnyArgs()
            ->andReturn(true);

        // Actions
        $result = $model->delete();

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldGetFirst(): void
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));

        $model = new class() extends AbstractModel
        {
            protected $collection = 'collection_name';
        };

        // Expectations
        $builder->expects()
            ->first(m::type(get_class($model)), '123', [])
            ->andReturn($model);

        // Actions
        $result = $model->first('123');

        // Assertions
        $this->assertEquals($model, $result);
    }

    public function testShouldMockFirst(): void
    {
        // Set
        $model = new class() extends AbstractModel
        {
            protected $collection = 'collection_name';
        };

        // Expectations
        $model::expects()
            ->first('123')
            ->andReturn($model);

        // Actions
        $result = $model->first('123');

        // Assertions
        $this->assertEquals($model, $result);
    }

    public function testShouldGetFirstOrNew(): void
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));

        $model = new class() extends AbstractModel
        {
            protected $collection = 'collection_name';
        };

        // Expectations
        $builder->expects()
            ->first(m::type(get_class($model)), '123', [])
            ->andReturn($model);

        // Actions
        $result = $model->firstOrNew('123');

        // Assertions
        $this->assertEquals($model, $result);
    }

    public function testShouldMockFirstOrNew(): void
    {
        // Set
        $model = new class() extends AbstractModel
        {
            protected $collection = 'collection_name';
        };

        // Expectations
        $model::expects()
            ->firstOrNew('123')
            ->andReturn($model);

        // Actions
        $result = $model->firstOrNew('123');

        // Assertions
        $this->assertEquals($model, $result);
    }

    public function testShouldGetFirstOrFailAndFoundIt(): void
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));

        $model = new class() extends AbstractModel
        {
            protected $collection = 'collection_name';
        };

        // Expectations
        $builder->expects()
            ->firstOrFail()
            ->withAnyArgs()
            ->andReturn($model);

        // Actions
        $result = $model->firstOrFail('123');

        // Assertions
        $this->assertEquals($model, $result);
    }

    public function testShouldGetFirstOrFailAndFail(): void
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));

        $model = new class() extends AbstractModel
        {
            protected $collection = 'collection_name';
        };

        // Expectations
        $this->expectException(ModelNotFoundException::class);

        $builder->expects()
            ->firstOrFail()
            ->withAnyArgs()
            ->andThrow(ModelNotFoundException::class);

        // Actions
        $model->firstOrFail('123');
    }

    public function testShouldMockFirstOrFail(): void
    {
        // Set
        $model = new class() extends AbstractModel
        {
            protected $collection = 'collection_name';
        };

        // Expectations
        $model::expects()
            ->firstOrFail('123')
            ->andReturn($model);

        // Actions
        $result = $model->firstOrFail('123');

        // Assertions
        $this->assertEquals($model, $result);
    }

    public function testShouldGetWhere(): void
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));
        $cursor = m::mock(Cursor::class);

        $model = new class() extends AbstractModel
        {
            protected $collection = 'collection_name';
        };

        // Expectations
        $builder->expects()
            ->where()
            ->withAnyArgs()
            ->andReturn($cursor);

        // Actions
        $result = $model->where(['field' => '123']);

        // Assertions
        $this->assertEquals($cursor, $result);
    }

    public function testShouldGetAll(): void
    {
        // Set
        $builder = $this->instance(Builder::class, m::mock(Builder::class));
        $cursor = m::mock(Cursor::class);

        $model = new class() extends AbstractModel
        {
            protected $collection = 'collection_name';
        };

        // Expectations
        $builder->expects()
            ->all()
            ->withAnyArgs()
            ->andReturn($cursor);

        // Actions
        $result = $model->all();

        // Assertions
        $this->assertEquals($cursor, $result);
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
