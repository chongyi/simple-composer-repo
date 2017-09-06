<?php

namespace App\Providers;

use App\Composer\GitlabGatewayHook;
use App\Composer\LocalPackages;
use App\Composer\Model;
use App\Composer\PackageManager;
use App\Exceptions\ProxyTransferException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPackageManager();
        $this->registerLocalPackageManager();
        $this->registerSyncGatewayHook();
    }

    private function registerSyncGatewayHook()
    {
        $this->app->bind('gateway.hook.gitlab', function (Application $app) {
            return new GitlabGatewayHook($app['request'], $app);
        });
    }

    private function getRequestCallback(Client $client)
    {
        return function ($method, $uri) use ($client) {
            try {
                $response = $client->request($method, $uri);
                return json_decode($response->getBody()->getContents(), true);
            } catch (RequestException $exception) {
                throw new ProxyTransferException($exception->getMessage(), $exception->getCode(), $exception);
            }
        };
    }

    private function getRequestAsyncCallback(Client $client)
    {
        return function (\Iterator $iterator, \Closure $callback, $concurrency = 5) use ($client) {
            $requests = function (\Iterator $requestPathIterator) use ($client) {
                foreach ($requestPathIterator as $index => $path) {
                    yield $index => function () use ($client, $path) {
                        return $client->getAsync($path);
                    };
                }
            };

            $pool = new Pool($client, $requests($iterator), [
                'concurrency' => $concurrency,
                'fulfilled' => function (Response $response, $index) use ($callback) {
                    call_user_func($callback, json_decode($response->getBody()->getContents(), true), $index);
                },
                'rejected' => function (RequestException $exception, $index) {

                },
            ]);

            $pool->promise()->wait();
        };
    }

    private function registerPackageManager()
    {
        $this->app->singleton(PackageManager::class, function ($app) {
            $manager = new PackageManager($config = $app['config']['repo'], $app);
            $client = new Client(['base_uri' => $config['proxy']['url'], 'verify' => false]);

            $manager->setRequestPerformer($this->getRequestCallback($client));
            $manager->setRequestAsyncPerformer($this->getRequestAsyncCallback($client));

            return $manager;
        });

        $this->app->alias(PackageManager::class, 'package-manager');
    }

    private function registerLocalPackageManager()
    {
        $this->app->singleton(LocalPackages::class, function ($app) {
            $localPackage = new LocalPackages($app['package-manager']);
            $localPackage->loadRegister();

            return $localPackage;
        });

        $this->app->alias(LocalPackages::class, 'local-package-manager');
    }
}
