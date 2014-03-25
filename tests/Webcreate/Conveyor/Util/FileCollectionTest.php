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
        $this->assertEquals('..', $collection->getBasepath());

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
        $collection->add('tests/bootstrap.php');

        $files = iterator_to_array($collection);

        $this->assertContains('README.md', $files);
        $this->assertContains('vendor/autoload.php', $files);
        $this->assertContains('tests/bootstrap.php', $files);
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

    public function testAddArrayNotation()
    {
        $collection = new Webcreate\Conveyor\Util\FileCollection(__DIR__ . '/../../../../');
        $collection[] = 'vendor';

        $files = iterator_to_array($collection);

        $this->assertContains('vendor/autoload.php', $files);
    }

    public function testRemove()
    {
        $collection = new Webcreate\Conveyor\Util\FileCollection(__DIR__ . '/../../../../');

        $collection->add('vendor/');
        $collection->add('*.dist');
        $collection->add('conveyor.yml');

        $files = iterator_to_array($collection);

        $this->assertContains('conveyor.yml.dist', $files);
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
        $collection->add('.travis.yml');

        $matches = $collection->match('*.yml');

        $this->assertEquals(array('conveyor.yml.dist', '.travis.yml'), $matches);
    }

    /**
     * @dataProvider dataproviderHas
     */
    public function testHas($input, $expected)
    {
        $collection = new Webcreate\Conveyor\Util\FileCollection(__DIR__ . '/../../../../');

        $collection->add('vendor/');
        $collection->add('conveyor.yml.dist');

        $this->assertEquals($expected, $collection->has($input));
    }

    public function dataproviderHas()
    {
        return array(
            // positives
            array('*.dist', true),
            array('conveyor.yml.dist', true),
            array('vendor/', true),
            array('vendor/autoload.php', true),
            array('vendor/composer/', true),
            array('vendor/composer/installed.json', true),

            // negatives
            array('conveyor.yml', false),
            array('vendor/composer', false),
            array('you_dont_have_me', false),
        );
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
