<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class FileCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $basepath = '../';

        $collection = new Webcreate\Conveyor\Util\FileCollection($basepath);

        $this->assertInstanceOf('Webcreate\Conveyor\Util\FileCollection', $collection);
        $this->assertEquals($basepath, $collection->getBasepath());

        $collection = new Webcreate\Conveyor\Util\FileCollection();

        $this->assertInstanceOf('Webcreate\Conveyor\Util\FileCollection', $collection);
        $this->assertEquals(null, $collection->getBasepath());
    }

    public function testAdd()
    {
        $collection = new Webcreate\Conveyor\Util\FileCollection(__DIR__ . '/../../../../');

        $collection->add('vendor');
        $collection->add('*.yml');
        $collection->add('*.md');
        $collection->add('composer.*');

        $files = iterator_to_array($collection);

        $this->assertContains('README.md', $files);
        $this->assertContains('vendor/autoload.php', $files);
        $this->assertNotContains('lorem.txt', $files);
    }

    public function testAddAll()
    {
        $collection = new Webcreate\Conveyor\Util\FileCollection(__DIR__ . '/../../../../');

        $collection->add('*');

        $files = iterator_to_array($collection);

        $this->assertContains('vendor/autoload.php', $files);
        $this->assertContains('README.md', $files);
        $this->assertContains('src/Webcreate/Conveyor/Conveyor.php', $files);
    }

    public function testRemove()
    {
        $collection = new Webcreate\Conveyor\Util\FileCollection(__DIR__ . '/../../../../');

        $collection->add('vendor/');
        $collection->add('*.dist');
        $collection->add('conveyor.yml');

        $files = iterator_to_array($collection);

        $this->assertContains('conveyor.yml', $files);
        $this->assertContains('phpunit.xml.dist', $files);
        $this->assertContains('vendor/webcreate/util/.travis.yml', $files);

        $collection->remove('vendor/webcreate');
        $collection->remove('con*.yml');

        $files = iterator_to_array($collection);

        $this->assertContains('phpunit.xml.dist', $files);
        $this->assertNotContains('conveyor.yml', $files);
        $this->assertNotContains('vendor/webcreate/util/.travis.yml', $files);
    }

    public function testRemoveAll()
    {
        $collection = new Webcreate\Conveyor\Util\FileCollection(__DIR__ . '/../../../../');

        $collection->add('vendor/');
        $collection->add('*.dist');
        $collection->add('conveyor.yml');

        $this->assertGreaterThan(0, count($collection));

        $collection->remove('*');

        $this->assertCount(0, $collection);
    }

    public function testMatch()
    {
        $collection = new Webcreate\Conveyor\Util\FileCollection(__DIR__ . '/../../../../');

        $collection->add('vendor/');
        $collection->add('*.dist');
        $collection->add('conveyor.yml');

        $matches = $collection->match('*.yml');

        $this->assertEquals(array(0 => 'conveyor.yml'), $matches);
    }

    public function testHas()
    {
        $collection = new Webcreate\Conveyor\Util\FileCollection(__DIR__ . '/../../../../');

        $collection->add('vendor/');
        $collection->add('*.dist');
        $collection->add('conveyor.yml');

        $this->assertTrue($collection->has('*.yml'));
        $this->assertFalse($collection->has('you_dont_have_me'));
    }

    public function testHasWildcard()
    {
        $collection = new Webcreate\Conveyor\Util\FileCollection(__DIR__ . '/../../../../');

        $collection->add('vendor/');
        $collection->add('*.dist');
        $collection->add('conveyor.yml');

        $this->assertTrue($collection->has('*'));

        $collection->remove('*');

        $this->assertFalse($collection->has('*'));
    }
}