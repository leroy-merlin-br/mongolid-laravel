<?php
namespace Mongolid\Laravel\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Laravel\Tests\Integration\Stubs\User;

class ReferencesOneRelationTest extends IntegrationTestCase
{
    public function testShouldRetrieveParentOfUser()
    {
        // create parent
        $chuck = $this->createUser('Chuck');
        $john = $this->createUser('John');
        $john->attach('parent', $chuck);

        $this->assertParent($chuck, $john);
        // hit cache
        $this->assertParent($chuck, $john);

        // replace parent
        $bob = $this->createUser('Bob');
        unset($john->parent);
        $john->attach('parent', $bob);

        $this->assertParent($bob, $john);
        // hit cache
        $this->assertParent($bob, $john);

        // remove with unembed
        $john->unembed('parent', $bob);

        $this->assertNull($john->parent());
    }

    private function createUser(string $name): User
    {
        $user = new User();
        $user->_id = new ObjectId();
        $user->name = $name;
        $this->assertTrue($user->save());

        return $user;
    }

    private function assertParent($expected, User $model)
    {
        $parent = $model->parent();
        $this->assertInstanceOf(User::class, $parent);
        $this->assertEquals($expected, $parent);
    }
}
