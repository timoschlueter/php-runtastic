<?php

/*

The MIT License (MIT)

Copyright (c) 2014 Timo Schlueter <timo.schlueter@me.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

namespace Runtastic;

class RuntasticActivityList implements \ArrayAccess, \Countable
{
    /**
     * RuntasticActivityList constructor.
     *
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->_set($items);
    }

    /**
     * @param string $aFilter
     */
    public function filterBy($aFilter)
    {
        $tmp = [];

        foreach ($this as $oActivity) {
            $blKeep = false;

            foreach ($aFilter as $key => $val) {
                if ($oActivity->$key == $val) {
                    $blKeep = true;
                } else {
                    $blKeep = false;
                    break;
                }
            }

            if ($blKeep) {
                $tmp[] = $oActivity;
            }
        }

        $this->_set($tmp, true);
    }

    /**
     * @param  array $data
     * @param  bool  $blClean
     * @return RuntasticActivityList
     */
    private function _set($data, $blClean = false)
    {
        if ($blClean) {
            $this->_reset();
        }

        foreach ($data AS $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }

    private function _reset()
    {
        foreach ($this as $key => $val) {
            unset($this->$key);
        }
    }

    /**
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        if (isset($this->$offset)) {
            return true;
        }

        return false;
    }

    /**
     * @param  mixed $offset
     * @return bool
     */
    public function offsetGet($offset)
    {
        if (isset($this->$offset)) {
            return $this->$offset;
        }

        return false;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->_set($value);
        } else {
            $this->_set(array($offset => $value));
        }
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count((array) $this);
    }
}
