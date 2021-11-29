<?php
namespace Mongolid\Laravel\Tests\Integration;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Laravel\Tests\Integration\Stubs\EmbeddedUser;
use Mongolid\Model\Exception\InvalidFieldNameException;

class EmbedsOneRelationTest extends IntegrationTestCase
{
    public function testShouldRetrieveParentOfUser(): void
    {
        // create parent
        $chuck = $this->createUser('Chuck');
        $john = $this->createUser('John');
        $john->parent()->add($chuck);

        $this->assertParent($chuck, $john);

        // replace parent
        $bob = $this->createUser('Bob');

        // unset
        $john->parent()->add($bob);
        $this->assertParent($bob, $john);
        unset($john->embedded_parent);

        $this->assertNull($john->embedded_parent);
        $this->assertNull($john->parent);

        // remove all
        $john->parent()->add($bob);
        $this->assertParent($bob, $john);
        $john->parent()->remove();
        $this->assertNull($john->embedded_parent);
        $this->assertNull($john->parent);

        // remove
        $john->parent()->add($bob);
        $this->assertParent($bob, $john);
        $john->parent()->remove();
        $this->assertNull($john->embedded_parent);
        $this->assertNull($john->parent);

        // changing the field directly
        $john->parent()->add($bob);
        $this->assertParent($bob, $john);
        $john->embedded_parent = $chuck;
        $this->assertParent($chuck, $john);

        $john->parent()->remove();

        // changing the field with fillable
        $john->parent()->add($bob);
        $this->assertParent($bob, $john);
        $john = EmbeddedUser::fill(['embedded_parent' => $chuck], $john, true);
        $this->assertParent($chuck, $john);
    }

    public function testShouldRetrieveSonOfUserUsingCustomKey(): void
    {
        // create parent
        $chuck = $this->createUser('Chuck');
        $john = $this->createUser('John');
        $john->son()->add($chuck);

        $this->assertSon($chuck, $john);

        // replace son
        $bob = $this->createUser('Bob');

        // unset
        $john->son()->add($bob);
        $this->assertSon($bob, $john);
        unset($john->arbitrary_field);
        $this->assertNull($john->arbitrary_field);
        $this->assertNull($john->son);

        // remove all
        $john->son()->add($bob);
        $this->assertSon($bob, $john);
        $john->son()->remove();
        $this->assertNull($john->arbitrary_field);
        $this->assertNull($john->son);

        // remove
        $john->son()->add($bob);
        $this->assertSon($bob, $john);
        $john->son()->remove();
        $this->assertNull($john->arbitrary_field);
        $this->assertNull($john->son);

        // changing the field directly
        $john->son()->add($bob);
        $this->assertSon($bob, $john);
        $john->arbitrary_field = $chuck;
        $this->assertSon($chuck, $john);

        $john->son()->remove();

        // changing the field with fillable
        $john->son()->add($bob);
        $this->assertSon($bob, $john);
        $john = EmbeddedUser::fill(['arbitrary_field' => $chuck], $john, true);
        $this->assertSon($chuck, $john);
    }

    public function testShouldCatchInvalidFieldNameOnRelations(): void
    {
        // Set
        $user = new EmbeddedUser();

        // Expectations
        $this->expectException(InvalidFieldNameException::class);
        $this->expectExceptionMessage('The field for relation "sameName" cannot have the same name as the relation');

        // Actions
        $user->sameName;
    }

    private function createUser(string $name): EmbeddedUser
    {
        $user = new EmbeddedUser();
        $user->_id = new ObjectId();
        $user->name = $name;
        $this->assertTrue($user->save());

        return $user;
    }

    private function assertParent($expected, EmbeddedUser $model)
    {
        $parent = $model->parent;
        $this->assertInstanceOf(EmbeddedUser::class, $parent);
        $this->assertInstanceOf(UTCDateTime::class, $parent->created_at);
        $this->assertEquals($expected, $parent);
        $this->assertSame($expected, $model->embedded_parent);

        // hit cache
        $parent = $model->parent;
        $this->assertInstanceOf(EmbeddedUser::class, $parent);
        $this->assertInstanceOf(UTCDateTime::class, $parent->created_at);
        $this->assertEquals($expected, $parent);
        $this->assertSame($expected, $model->embedded_parent);
    }

    private function assertSon($expected, EmbeddedUser $model)
    {
        $son = $model->son;
        $this->assertInstanceOf(EmbeddedUser::class, $son);
        $this->assertInstanceOf(UTCDateTime::class, $son->created_at);
        $this->assertEquals($expected, $son);
        $this->assertSame($expected, $model->arbitrary_field);

        // hit cache
        $son = $model->son;
        $this->assertInstanceOf(EmbeddedUser::class, $son);
        $this->assertInstanceOf(UTCDateTime::class, $son->created_at);
        $this->assertEquals($expected, $son);
        $this->assertSame($expected, $model->arbitrary_field);
    }
}
