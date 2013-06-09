<?php
/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class FilePathTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider pathsProvider
     */
    public function testJoin($arguments, $expected)
    {
        $result = call_user_func_array(array('\Webcreate\Conveyor\Util\FilePath', 'join'), $arguments);

        $this->assertEquals($expected, $result);
    }

    public function pathsProvider()
    {
        return array(
            array(
                array('test', ''),
                'test',
            ),
            array(
                array('test', 'work', 'blaat'),
                'test/work/blaat',
            ),
            array(
                array('test', '', 'blaat'),
                'test/blaat',
            ),
            array(
                array('test', '', ''),
                'test',
            ),
            array(
                array('a/b/test', 'x', 'y/z'),
                'a/b/test/x/y/z',
            ),
        );
    }
}
