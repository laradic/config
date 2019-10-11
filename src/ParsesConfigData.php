<?php


namespace Laradic\Config;

use Illuminate\Support\Arr;
use Laradic\Support\Macros\Arr\Merge;

/** @mixin Repository */
trait ParsesConfigData
{
    /** @var \Laradic\Config\Parser */
    protected $parser;

    public function getParser()
    {
        return $this->parser;
    }

    public function setParser($parser)
    {
        $this->parser = $parser;
        return $this;
    }

    public function get($key, $default = null)
    {
        $value = parent::get($key, $default);
        $value = $this->process($value, $key, $default);
        return $value;
    }

    public function raw($key, $default = null)
    {
        return parent::get($key, $default);
    }

    public function process($value, $key = null, $default = null)
    {
        return $this->parser->parse($value,$this->items);
    }

    public function getContext()
    {
        $context = collect($this->all())->transform(function ($value) {
            if (Arr::accessible($value)) {
                $value = new static($value);
            }
            return $value;
        })->all();
        return $context;
    }

    public function merge($data)
    {
        $merger      = new Merge();
        $this->items = $merger()($this->items, $data);
        return $this;
    }

    public function mergeAt($key, $data)
    {
        $merger = new Merge();
        $value  = $this->raw($key, []);
        $value  = $merger()($value, $data);
        $this->set($key, $value);
        return $this;
    }
}