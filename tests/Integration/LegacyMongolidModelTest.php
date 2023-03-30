<?php

namespace MongolidLaravel\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Model\Exception\ModelNotFoundException;
use MongolidLaravel\Stubs\LegacyMongolidModelStub;

class LegacyMongolidModelTest extends IntegrationTestCase
{
    public function testShouldGetFirst(): void
    {
        // Set
        $id = $this->getPersistedModel();
        $expected = [
            '_id' => $id,
            'field_1' => 'Field 1',
            'field_2' => 1.99,
        ];

        // Actions
        $result = LegacyMongolidModelStub::first()->toArray();
        $resultByObjectId = LegacyMongolidModelStub::first($id)->toArray();
        $resultByString = LegacyMongolidModelStub::first(
            (string) $id
        )->toArray();
        $resultByFirstOrFail = LegacyMongolidModelStub::firstOrFail(
            $id
        )->toArray();
        unset($result['created_at'], $result['updated_at']);
        unset($resultByObjectId['created_at'], $resultByObjectId['updated_at']);
        unset($resultByString['created_at'], $resultByString['updated_at']);
        unset($resultByFirstOrFail['created_at'], $resultByFirstOrFail['updated_at']);

        // Assertions
        $this->assertEquals($expected, $result);
        $this->assertEquals($expected, $resultByObjectId);
        $this->assertEquals($expected, $resultByString);
        $this->assertEquals($expected, $resultByFirstOrFail);
    }

    public function testShouldFailWhenFirstOrFailMethodIsCalled(): void
    {
        // Expectations
        $this->expectException(ModelNotFoundException::class);

        // Actions
        LegacyMongolidModelStub::firstOrFail('invalid-id');
    }

    public function testShouldGetFirstOrNew(): void
    {
        // Set
        $id = new ObjectId();
        $expected = [
            '_id' => $id,
        ];

        // Actions
        $result = LegacyMongolidModelStub::firstOrNew($id)->toArray();

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function testShouldGetFilteredRecords(): void
    {
        // Set
        $id1 = $this->getPersistedModel();
        $id2 = $this->getPersistedModel();
        $this->getPersistedModel(); // Persisted but should not be returned

        // Actions
        $result = LegacyMongolidModelStub::where(
            ['_id' => ['$in' => [$id1, $id2]]]
        )
            ->sort(['created_at' => 1])
            ->toArray();
        $result = array_column($result, '_id');

        $this->assertCount(2, $result);
        $this->assertEquals($id1, $result[0]);
        $this->assertEquals($id2, $result[1]);
    }

    public function testShouldGetAllRecords(): void
    {
        // Set
        $id1 = $this->getPersistedModel();
        $id2 = $this->getPersistedModel();
        $id3 = $this->getPersistedModel();

        // Actions
        $result = LegacyMongolidModelStub::all()
            ->sort(['created_at' => 1])
            ->toArray();
        $result = array_column($result, '_id');

        $this->assertCount(3, $result);
        $this->assertEquals($id1, $result[0]);
        $this->assertEquals($id2, $result[1]);
        $this->assertEquals($id3, $result[2]);
    }

    public function testShouldUpdate(): void
    {
        // Set
        $id = $this->getPersistedModel();

        $expectedBeforeUpdate = [
            '_id' => $id,
            'field_1' => 'Field 1',
            'field_2' => 1.99,
        ];
        $expectedAfterUpdate = [
            '_id' => $id,
            'field_1' => 'Updated Field',
            'field_2' => 1.99,
        ];

        // Actions
        $model = LegacyMongolidModelStub::first($id);
        $beforeUpdate = $model->toArray();
        unset($beforeUpdate['created_at'], $beforeUpdate['updated_at']);

        $model->field_1 = 'Updated Field';
        $model->update();
        $model = LegacyMongolidModelStub::first($id);
        $afterUpdate = $model->toArray();
        unset($afterUpdate['created_at'], $afterUpdate['updated_at']);

        // Assertions
        $this->assertEquals($expectedBeforeUpdate, $beforeUpdate);
        $this->assertEquals($expectedAfterUpdate, $afterUpdate);
    }

    public function testShouldDelete(): void
    {
        // Set
        $id = $this->getPersistedModel();

        // Actions
        $deleteResult = LegacyMongolidModelStub::first($id)->delete();
        $model = LegacyMongolidModelStub::first($id);

        // Assertions
        $this->assertTrue($deleteResult);
        $this->assertNull($model);
    }

    private function getPersistedModel(): ObjectId
    {
        $model = new LegacyMongolidModelStub();
        $model->field_1 = 'Field 1';
        $model->field_2 = 1.99;
        $model->password = 'test123';
        $model->save();

        return $model->_id;
    }
}
