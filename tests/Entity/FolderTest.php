<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Entity\Folder;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Folder::class)]
final class FolderTest extends AbstractEntityTestCase
{
    protected function createEntity(): Folder
    {
        return new Folder();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'title' => ['title', 'Test Folder'];
        yield 'description' => ['description', 'Test Description'];
        yield 'path' => ['path', 'test-folder'];
    }

    public function testFolderConstruction(): void
    {
        $folder = new Folder();

        $this->assertNull($folder->getId());
        $this->assertTrue($folder->isActive());
        $this->assertFalse($folder->isPublic());
        $this->assertNull($folder->getParent());
        $this->assertNull($folder->getUser());
        $this->assertTrue($folder->isRoot());
        $this->assertFalse($folder->hasChildren());
        $this->assertFalse($folder->hasFiles());
    }

    public function testSettersAndGetters(): void
    {
        $folder = new Folder();

        $folder->setName('Test Folder');
        $this->assertEquals('Test Folder', $folder->getName());

        $folder->setDescription('Test Description');
        $this->assertEquals('Test Description', $folder->getDescription());

        $folder->setPath('test-folder');
        $this->assertEquals('test-folder', $folder->getPath());

        $folder->setIsActive(false);
        $this->assertFalse($folder->isActive());

        $folder->setIsPublic(true);
        $this->assertTrue($folder->isPublic());
    }

    public function testParentChildRelationship(): void
    {
        $parent = new Folder();
        $parent->setName('Parent Folder');
        $parent->setPath('parent');

        $child = new Folder();
        $child->setName('Child Folder');
        $child->setPath('child');

        $child->setParent($parent);
        $parent->addChild($child);

        $this->assertEquals($parent, $child->getParent());
        $this->assertTrue($parent->getChildren()->contains($child));
        $this->assertFalse($child->isRoot());
        $this->assertTrue($parent->hasChildren());

        // Test remove child
        $parent->removeChild($child);
        $this->assertFalse($parent->getChildren()->contains($child));
        $this->assertNull($child->getParent());
    }

    public function testFileRelationship(): void
    {
        $folder = new Folder();
        $folder->setName('Test Folder');
        $folder->setPath('test');

        $file = new File();
        $file->setOriginalName('test.txt');
        $file->setFileName('test.txt');
        $file->setFilePath('test/test.txt');
        $file->setMimeType('text/plain');
        $file->setFileSize(100);

        $folder->addFile($file);
        $file->setFolder($folder);

        $this->assertEquals($folder, $file->getFolder());
        $this->assertTrue($folder->getFiles()->contains($file));
        $this->assertTrue($folder->hasFiles());

        // Test remove file
        $folder->removeFile($file);
        $this->assertFalse($folder->getFiles()->contains($file));
        $this->assertNull($file->getFolder());
        $this->assertFalse($folder->hasFiles());
    }

    public function testFullPath(): void
    {
        $root = new Folder();
        $root->setName('Root');
        $root->setPath('root');

        $child = new Folder();
        $child->setName('Child');
        $child->setPath('child');
        $child->setParent($root);

        $grandChild = new Folder();
        $grandChild->setName('GrandChild');
        $grandChild->setPath('grandchild');
        $grandChild->setParent($child);

        $this->assertEquals('root', $root->getFullPath());
        $this->assertEquals('root/child', $child->getFullPath());
        $this->assertEquals('root/child/grandchild', $grandChild->getFullPath());
    }

    public function testIsAnonymous(): void
    {
        $folder = new Folder();
        $this->assertTrue($folder->isAnonymous());

        $mockUser = $this->createMock(UserInterface::class);
        $folder->setUser($mockUser);
        $this->assertFalse($folder->isAnonymous());
    }

    public function testToString(): void
    {
        $folder = new Folder();
        $folder->setName('Test Folder');

        $expected = '#new Test Folder';
        $this->assertEquals($expected, (string) $folder);

        // Use reflection to set ID for testing
        $reflection = new \ReflectionClass($folder);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($folder, 123);

        $expected = '#123 Test Folder';
        $this->assertEquals($expected, (string) $folder);
    }

    public function testToArray(): void
    {
        $folder = new Folder();
        $folder->setName('Test Folder');
        $folder->setDescription('Test Description');
        $folder->setPath('test-folder');
        $folder->setIsPublic(true);

        $array = $folder->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Test Folder', $array['name']);
        $this->assertEquals('Test Description', $array['description']);
        $this->assertEquals('test-folder', $array['path']);
        $this->assertEquals('test-folder', $array['fullPath']);
        $this->assertTrue($array['isPublic']);
        $this->assertTrue($array['isRoot']);
        $this->assertFalse($array['hasChildren']);
        $this->assertFalse($array['hasFiles']);
    }
}
