<?php /** @noinspection PhpUnused */

/** @noinspection ReturnTypeCanBeDeclaredInspection */

namespace Laradic\Config;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Contracts\Config\Repository as RepositoryContract;

class ConfigBootstrapper
{
    /** @var \Illuminate\Contracts\Foundation\Application */
    protected $app;
    protected $replacer;
    protected $repositoryClass = \Laradic\Config\Repository::class;

    protected function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function bootstrap(Closure $callback = null)
    {
        $this->app->afterBootstrapping(LoadConfiguration::class, function (Application $app) use ($callback){
            $replacer = $this->getReplacer();
            $config   = $replacer($app->config, $app);
            $app->instance('config', $config);
            if($callback!== null) {
                $callback($config, $app);
            }
        });
    }

    protected function getDefaultReplacer()
    {
        return function (RepositoryContract $old, Application $app) {
            $repositoryClass = $this->repositoryClass;
            /** @var Repository|\Laradic\Config\ParsesConfigData $config */
            $config = new $repositoryClass($old->all());
            $parser = $this->makeParser();
            static::registerParserFeatures($parser);
            $config->setParser($parser);
            return $config;
        };
    }

    public function setRepositoryClass($repositoryClass)
    {
        $this->repositoryClass = $repositoryClass;
        return $this;
    }

    public function setReplacer(Closure $replacer)
    {
        $this->replacer = $replacer;
        return $this;
    }

    protected function getReplacer()
    {
        return $this->replacer ?? $this->getDefaultReplacer();
    }

    protected function makeParser()
    {
        return new Parser(new ExpressionLanguage());
    }

    public static function registerParserFeatures(Parser $parser)
    {
        $functions = [
            'app',
            'app_path',
            'base_path',
            'config_path',
            'database_path',
            'public_path',
            'resource_path',
            'storage_path',
        ];

        foreach($functions as $fn) {
            $parser->registerFunction($fn, function (...$params) use ($fn) {
                return $fn(...$params);
            });
        }
    }

    public static function make(Application $app)
    {
        return new static($app);
    }

}
