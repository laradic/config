<?php


namespace Laradic\Config;


use Illuminate\Contracts\Support\Arrayable;

class GetterObjectDecorator implements \ArrayAccess, Arrayable, \IteratorAggregate
{
    protected $obj;

    public function __construct($obj)
    {
        $this->obj = $obj;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([ $this->obj, $name ], $arguments);
    }

    public function __get($name)
    {
        return $this->obj[ $name ];
    }

    public function __set($name, $value)
    {
        $this->obj[ $name ] = $value;
    }

    public function __isset($name)
    {
        return isset($this->obj[ $name ]);
    }

    public function __unset($name)
    {
        unset($this->obj[ $name ]);
    }


    public function toArray()
    {
        return $this->obj->toArray();
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }

    public function offsetExists($name)
    {
        return isset($this->obj[ $name ]);
    }

    public function offsetGet($name)
    {
        return is_array($this->obj[ $name ]) ? new static($this->obj[ $name ]) : $this->obj[ $name ];
    }

    public function offsetSet($name, $value)
    {
        $this->obj[ $name ] = $value;
    }

    public function offsetUnset($name)
    {
        unset($this->obj[ $name ]);
    }
}