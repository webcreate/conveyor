<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Util;

use Symfony\Component\Finder\Glob;
use Symfony\Component\Finder\Finder;

class FileCollection implements \IteratorAggregate, \Countable, \ArrayAccess
{
    protected $basepath;
    protected $files = array();

    public function __construct($basepath = null)
    {
        if (null !== $basepath) {
            $this->setBasepath($basepath);
        }
    }

    public function setBasepath($basepath)
    {
        $this->basepath = rtrim($basepath, '/');

        return $this;
    }

    public function getBasepath()
    {
        return $this->basepath;
    }

    /**
     * @param $pattern
     * @param bool $force assumes the $pattern is a absolute file and adds it, even if it doesn't exist
     * @return $this
     * @throws \LogicException
     */
    public function add($pattern, $force = false)
    {
        if (null === $this->basepath) {
            throw new \LogicException('You need to call "setBasepath()" first before adding files');
        }

        $filepath = $this->basepath . '/' . $pattern;
        if ($force || is_file($filepath)) {
            $this->files[] = $pattern;

            return $this;
        }

        $regex = Glob::toRegex($pattern, false);
        $regex = str_replace('$', '', $regex);

        $finder = new Finder();
        $finder
            ->files()
            ->ignoreDotFiles(false)
            ->in($this->basepath)
            ->path($regex)
        ;

        $this->files = array_merge($this->files, $this->mapFinder($finder));
        $this->files = array_unique($this->files);

        return $this;
    }

    protected function mapFinder($finder)
    {
        return array_map(function ($file) {
            return $file->getRelativePathname();
        }, iterator_to_array($finder, false));
    }

    public function remove($pattern)
    {
        if ('*' === $pattern) {
            $this->files = array();
        } else {
            $matching = $this->match($pattern);

            $this->files = array_filter($this->files, function ($file) use ($pattern, $matching) {
                return false === in_array($file, $matching);
            });

            $this->files = array_values($this->files);
        }

        return $this;
    }

    public function merge(FileCollection $collection)
    {
        $this->files = array_merge($this->files, $collection->toArray());
    }

    public function toArray()
    {
        return $this->files;
    }

    public function intersect($array)
    {
        if ($array instanceof self) {
            $array = $array->toArray();
        }

        $this->files = array_intersect($array, $this->files);
    }

    public function match($pattern)
    {
        if ('*' === $pattern) {
            return $this->files;
        }

        $retval = array_filter($this->files, function ($file) use ($pattern) {
            $regex = Glob::toRegex($pattern, false);
            $regex = str_replace('$', '', $regex);

            return (1 === preg_match($regex, $file));
        });

        return array_values($retval); // reindex
    }

    public function has($pattern)
    {
        $regex = Glob::toRegex($pattern, false);
        $regex = str_replace('$', '', $regex);

        foreach ($this->files as $file) {
            if (1 === preg_match($regex, $file)) {
                return true;
            }
        }

        return false;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->files);
    }

    public function count()
    {
        return count($this->files);
    }

    public function offsetExists($offset)
    {
        return isset($this->files[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->files[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->files[] = $value;
        } else {
            $this->files[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->files[$offset]);
    }
}
