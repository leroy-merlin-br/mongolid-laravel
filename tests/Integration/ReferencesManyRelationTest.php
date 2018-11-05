<?php
namespace Mongolid\Laravel\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Laravel\Tests\Integration\Stubs\User;

class ReferencesManyRelationTest extends IntegrationTestCase
{
    public function testShouldRetrieveParentOfUser()
    {
        // create sibling
        $chuck = $this->createUser('Chuck');
        $john = $this->createUser('John');
        $john->attach('siblings', $chuck);

        $this->assertSiblings([$chuck], $john);
        // hit cache
        $this->assertSiblings([$chuck], $john);

        $mary = $this->createUser('Mary');
        $john->attach('siblings', $mary);

        $this->assertSiblings([$chuck, $mary], $john);
        // hit cache
        $this->assertSiblings([$chuck, $mary], $john);

        // remove one sibling
        $john->unembed('siblings', $chuck);
        $this->assertSiblings([$mary], $john);
        // hit cache
        $this->assertSiblings([$mary], $john);

        // replace siblings
        $bob = $this->createUser('Bob');
        unset($john->siblings);
        $john->attach('siblings', $bob);

        $this->assertSiblings([$bob], $john);
        // hit cache
        $this->assertSiblings([$bob], $john);

        // remove with unembed
        $john->unembed('siblings', $bob);

        $this->assertEmpty($john->siblings()->all());
    }

    private function createUser(string $name): User
    {
        $user = new User();
        $user->_id = new ObjectId();
        $user->name = $name;
        $this->assertTrue($user->save());

        return $user;
    }

    private function assertSiblings($expected, User $model)
    {
        $siblings = $model->siblings();
        $this->assertInstanceOf(CursorInterface::class, $siblings);
        $this->assertEquals($expected, $siblings->all());
    }
}
