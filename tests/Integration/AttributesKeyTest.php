<?php
namespace Mongolid\Laravel\Tests\Integration;

use Mongolid\Laravel\Tests\Integration\Stubs\ReferencedUser;

class AttributesKeyTest extends IntegrationTestCase
{
    public function testAttributesKeysShouldNotBeReserved(): void
    {
        $user = new ReferencedUser();
        $user->name = 'John';
        $user->email = 'john@doe.com';

        // attributes that used to be "reserved"
        $user->attributes = ['my', 'attributes'];
        $user->originalAttributes = ['my', 'original', 'attributes'];

        $this->assertSame('John', $user->name);
        $this->assertSame('john@doe.com', $user->email);
        $this->assertSame(['my', 'attributes'], $user->attributes);
        $this->assertSame(['my', 'original', 'attributes'], $user->originalAttributes);
        $this->assertSame(
            [
                'name' => 'John',
                'email' => 'john@doe.com',
                'attributes' => ['my', 'attributes'],
                'originalAttributes' => ['my', 'original', 'attributes'],
            ],
            $user->getDocumentAttributes()
        );
        $this->assertSame([], $user->getOriginalDocumentAttributes());

        // Save and refetch from database
        $this->assertTrue($user->save());
        $user = $user->first();

        $this->assertSame('John', $user->name);
        $this->assertSame('john@doe.com', $user->email);
        $this->assertSame(['my', 'attributes'], $user->attributes);
        $this->assertSame(['my', 'original', 'attributes'], $user->originalAttributes);
        $this->assertSame(
            [
                '_id' => $user->_id,
                'name' => 'John',
                'email' => 'john@doe.com',
                'attributes' => ['my', 'attributes'],
                'originalAttributes' => ['my', 'original', 'attributes'],
            ],
            $user->getDocumentAttributes()
        );
        $this->assertEquals(
            [
                '_id' => $user->_id,
                'name' => 'John',
                'email' => 'john@doe.com',
                'attributes' => ['my', 'attributes'],
                'originalAttributes' => ['my', 'original', 'attributes'],
            ],
            $user->getOriginalDocumentAttributes()
        );
    }
}
