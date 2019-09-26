<?php
namespace Mongolid\Laravel\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Laravel\Tests\Integration\Stubs\ReferencedUser;

class ReferencesManyRelationTest extends IntegrationTestCase
{
    public function testShouldRetrieveSiblingsOfUser(): void
    {
        // create sibling
        $chuck = $this->createUser('Chuck');
        $john = $this->createUser('John');
        $john->siblings()->attach($chuck);

        $this->assertSiblings([$chuck], $john);

        $mary = $this->createUser('Mary');
        $john->siblings()->attachMany([$mary]);

        $this->assertSiblings([$chuck, $mary], $john);

        // remove one sibling
        $john->siblings()->detach($chuck);
        $this->assertSiblings([$mary], $john);

        // replace siblings
        $bob = $this->createUser('Bob');

        // unset
        $john->siblings()->replace([$bob]);
        $this->assertSiblings([$bob], $john);
        unset($john->siblings_ids);
        $this->assertEmpty($john->siblings_ids);
        $this->assertEmpty($john->siblings->all());

        // detachAll
        $john->siblings()->attach($bob);
        $this->assertSiblings([$bob], $john);
        $john->siblings()->detachAll();
        $this->assertEmpty($john->siblings_ids);
        $this->assertEmpty($john->siblings->all());

        // detach
        $john->siblings()->attach($bob);
        $this->assertSiblings([$bob], $john);
        $john->siblings()->detach($bob);
        $this->assertEmpty($john->siblings_ids);
        $this->assertEmpty($john->siblings->all());

        // changing the field directly
        $john->siblings()->attach($bob);
        $this->assertSiblings([$bob], $john);
        $john->siblings_ids = [$chuck->_id];
        $this->assertSiblings([$chuck], $john);

        $john->siblings()->detachAll();

        // changing the field with fillable
        $john->siblings()->attach($bob);
        $this->assertSiblings([$bob], $john);
        $john = ReferencedUser::fill(['siblings_ids' => [$chuck->_id]], $john, true);
        $this->assertSiblings([$chuck], $john);

        // detach not attached has no problems
        $john->siblings()->detach(new ReferencedUser());
        $this->assertSiblings([$chuck], $john);
    }

    public function testShouldRetrieveGrandsonsOfUserUsingCustomKey(): void
    {
        // create sibling
        $chuck = $this->createUser('Chuck', '010');
        $john = $this->createUser('John', '369');
        $john->grandsons()->attach($chuck);

        $this->assertSame(['010'], $john->grandsons_codes);
        $this->assertGrandsons([$chuck], $john);

        $mary = $this->createUser('Mary', '222');
        $john->grandsons()->attach($mary);

        $this->assertSame(['010', '222'], $john->grandsons_codes);
        $this->assertGrandsons([$chuck, $mary], $john);

        // remove one sibling
        $john->grandsons()->detach($chuck);

        $this->assertSame(['222'], $john->grandsons_codes);
        $this->assertGrandsons([$mary], $john);

        // replace grandsons
        $john->grandsons()->detach($mary);
        $bob = $this->createUser('Bob', '987');

        // unset
        $john->grandsons()->attach($bob);
        $this->assertGrandsons([$bob], $john);
        unset($john->grandsons_codes);
        $this->assertEmpty($john->grandsons_codes);
        $this->assertEmpty($john->grandsons->all());

        // detachAll
        $john->grandsons()->attach($bob);
        $this->assertGrandsons([$bob], $john);
        $john->grandsons()->detachAll();
        $this->assertEmpty($john->grandsons_codes);
        $this->assertEmpty($john->grandsons->all());

        // detach
        $john->grandsons()->attach($bob);
        $this->assertGrandsons([$bob], $john);
        $john->grandsons()->detach($bob);
        $this->assertEmpty($john->grandsons_codes);
        $this->assertEmpty($john->grandsons->all());

        // changing the field directly
        $john->grandsons()->attach($bob);
        $this->assertGrandsons([$bob], $john);
        $john->grandsons_codes = [$chuck->code];
        $this->assertGrandsons([$chuck], $john);

        $john->grandsons()->detachAll();

        // changing the field with fillable
        $john->grandsons()->attach($bob);
        $this->assertGrandsons([$bob], $john);
        $john = ReferencedUser::fill(['grandsons_codes' => [$chuck->code]], $john, true);
        $this->assertGrandsons([$chuck], $john);
    }

    private function createUser(string $name, string $code = null): ReferencedUser
    {
        $user = new ReferencedUser();
        $user->_id = new ObjectId();
        $user->name = $name;
        if ($code) {
            $user->code = $code;
        }
        $this->assertTrue($user->save());

        return $user;
    }

    private function assertSiblings($expected, ReferencedUser $model)
    {
        $siblings = $model->siblings;
        $this->assertInstanceOf(CursorInterface::class, $siblings);
        $this->assertEquals($expected, $siblings->all());

        foreach ($expected as $expectedModel) {
            $ids[] = $expectedModel->_id;
        }

        $this->assertSame($ids, $model->siblings_ids);

        // hit cache
        $siblings = $model->siblings;
        $this->assertInstanceOf(CursorInterface::class, $siblings);
        $this->assertEquals($expected, $siblings->all());

        $ids = [];
        foreach ($expected as $expectedModel) {
            $ids[] = $expectedModel->_id;
        }

        $this->assertSame($ids, $model->siblings_ids);
    }

    private function assertGrandsons($expected, ReferencedUser $model)
    {
        $grandsons = $model->grandsons;
        $this->assertInstanceOf(CursorInterface::class, $grandsons);
        $this->assertEquals($expected, $grandsons->all());

        foreach ($expected as $expectedModel) {
            $codes[] = $expectedModel->code;
        }

        $this->assertSame($codes, $model->grandsons_codes);

        // hit cache
        $grandsons = $model->grandsons;
        $this->assertInstanceOf(CursorInterface::class, $grandsons);
        $this->assertEquals($expected, $grandsons->all());

        $codes = [];
        foreach ($expected as $expectedModel) {
            $codes[] = $expectedModel->code;
        }

        $this->assertSame($codes, $model->grandsons_codes);
    }
}
