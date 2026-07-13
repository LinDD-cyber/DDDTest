<?php
 
namespace Shared\Providers;
 
use Illuminate\Support\ServiceProvider;
 
class SharedCommonServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/apiResponse.php', 'apiResponse'
        );
        $this->mergeConfigFrom(
            __DIR__.'/../../config/apiMessage.php', 'apiMessage'
        );
 
        $this->app->extend('translation.loader', function ($loader, $app) {
            if ($loader instanceof \Illuminate\Translation\FileLoader) {
                if ($loader instanceof \Shared\Translation\MixedFileLoader) {
                    $loader->addPath(__DIR__.'/../../lang');
                    return $loader;
                }
 
                $mixedLoader = new \Shared\Translation\MixedFileLoader($app['files'], $app->langPath());
 
                $reflection = new \ReflectionClass($loader);
                foreach (['hints', 'jsonPaths'] as $propName) {
                    if ($reflection->hasProperty($propName)) {
                        $prop = $reflection->getProperty($propName);
                        $prop->setAccessible(true);
                        $val = $prop->getValue($loader);
 
                        $mixedProp = new \ReflectionProperty($mixedLoader, $propName);
                        $mixedProp->setAccessible(true);
                        $mixedProp->setValue($mixedLoader, $val);
                    }
                }
 
                $mixedLoader->addPath(__DIR__.'/../../lang');
                return $mixedLoader;
            }
 
            return $loader;
        });
    }
 
    public function boot(): void
    {
        //
    }
}
