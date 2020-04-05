<?php


namespace Laradic\Config;


use ReflectionClass;
use ReflectionMethod;
use Laradic\Support\Dot;
use ReflectionException;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;

class Parser
{
    /** @var \Symfony\Component\ExpressionLanguage\ExpressionLanguage */
    protected $exl;

    /** @var \Laradic\Support\Dot */
    protected $data;

    /** @var string[] */
    protected $excludes = [];
    /** @var string[] */
    protected $includes = [];

    public function __construct(ExpressionLanguage $exl)
    {
        $this->exl  = $exl;
        $this->data = new Dot();
    }

    /**
     * @param string|string[] $patterns
     */
    public function exclude($patterns)
    {
        return $this->mergeUnique($this->excludes, $patterns);
    }

    /**
     * @param string|string[] $patterns
     */
    public function include($patterns)
    {
        return $this->mergeUnique($this->includes, $patterns);
    }

    protected function mergeUnique(&$target, $items)
    {
        $items  = Arr::wrap($items);
        $target = array_unique(array_merge($target, $items));
        return $this;
    }

    public function shouldParse($key)
    {
        $excluded = $this->isExcluded($key);
        $included = $this->isIncluded($key);

        if ( ! $excluded && ! $included) {
            return true;
        }
        if ( ! $excluded && $included) {
            return true;
        }
        if ($excluded && ! $included) {
            return false;
        }
        if ($excluded && $included) {
            if ($this->includedWeight($key) >= $this->excludedWeight($key)) {
                return true;
            }
        }
        return false;
    }

    protected function isExcluded($key)
    {
        return $this->hasString($this->excludes, $key);
    }

    protected function isIncluded($key)
    {
        return $this->hasString($this->includes, $key);
    }

    public function setExcludes($excludes)
    {
        $this->excludes = $excludes;
        return $this;
    }

    public function setIncludes($includes)
    {
        $this->includes = $includes;
        return $this;
    }

    protected function excludedWeight($key)
    {
        return $this->getStringLengths($this->matchString($this->excludes, $key))->max();
    }

    protected function includedWeight($key)
    {
        return $this->getStringLengths($this->matchString($this->includes, $key))->max();
    }

    protected function getStringLengths($strings)
    {
        return collect($strings)->mapWithKeys(function ($string) {
            return [ $string => strlen($string) ];
        });
    }

    protected function matchString($patterns, $string)
    {
        return array_filter($patterns, static function ($value) use ($string) {
            $pattern = (string)$value;
            return Str::is($pattern, $string);
        }, ARRAY_FILTER_USE_BOTH);
    }

    protected function hasString($patterns, $string, $count = false)
    {
        $items = $this->matchString($patterns, $string);
        return $count ? count($items) : count($items) > 0;
    }

    public function parse($target, $data = [], $key = null)
    {
        if ( ! $this->shouldParse($key)) {
            return $target;
        }

        $data = $this->prepareData($data);


        /*
         * If the target is an array
         * then parse it recursively.
         */
        if (is_array($target)) {
            foreach ($target as $k => &$value) {
                $value = $this->parse($value, $data, $key === null ? $k : $key . '.' . $k);
            }
        }

        /*
         * if the target is a string and is in a parsable
         * format then parse the target with the payload.
         */
        if (is_string($target) && Str::contains($target, [ '{{', '}}' ])) {
            $target = $this->evaluate($target, $data);
        }
        return $target;
    }

    protected function evaluate($value, $data = [])
    {
        $matched = preg_match_all('/\{\{(.*?)\}\}/', $value, $matches);
        if ( ! $matched) {
            return $value;
        }

        foreach ($matches[ 0 ] as $i => $original) {
            $expression = trim($matches[ 1 ][ $i ]);
            $result     = $this->exl->evaluate($expression, $data);
            $result     = $this->parse($result, $data);
            $value      = str_replace($original, $result, $value);
        }
        return $value;
    }

    protected function prepareData($data)
    {
        $prepared = $this->data->dot();
        $prepared->mergeRecursive($data);
        return $prepared->toArray();
    }

    public function registerFunction($name, $callback)
    {
        $this->exl->register($name, static function (...$params) {
            throw new \BadMethodCallException('compile not implemented');
        }, static function ($arguments, ...$params) use ($name, $callback) {
            return $callback(...$params);
        });
        return $this;
    }

    public function registerPhpFunction($phpName, $expName = null)
    {
        $this->exl->addFunction(ExpressionFunction::fromPhp($phpName, $expName));
        return $this;
    }

    public function registerPhpFunctions(array $names)
    {
        collect($names)->call([ $this, 'registerPhpFunction' ], [], false);
        return $this;
    }

    public function registerClassMethods($class)
    {
        try {
            if ( ! $class instanceof ReflectionClass) {
                $class = new ReflectionClass($class);
            }
            foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $methodName = $method->getName();
                $this->exl->register($method->getName(), static function (...$params) {
                    throw new \BadMethodCallException('compile not implemented');
                }, static function ($arguments, ...$params) use ($methodName) {
                    return $arguments->{$methodName}(...$params);
                });
            }

//            if (Str::contains($class->getDocComment(), '@mixin')) {
//                if (preg_match('/@mixin (.*)/', $class->getDocComment(), $matches) === 1 && isset($matches[ 1 ]) && class_exists($matches[ 1 ])) {
//                    static::registerClassMethods($exl, $matches[ 1 ]);
//                }
//            }
        }
        catch (ReflectionException $e) {
        }
        return $this;
    }

    public function getExpressionLanguage()
    {
        return $this->exl;
    }

    public static function createDefault()
    {
        return new Parser(new \Laradic\Config\ExpressionLanguage());
    }
}