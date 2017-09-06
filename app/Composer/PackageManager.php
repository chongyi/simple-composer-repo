<?php
/**
 * PackageManager.php
 *
 * Creator:         chongyi
 * Create Datetime: 2017/5/13 23:04
 */

namespace App\Composer;


use App\Composer\Events\SyncOnePackagesItem;
use App\Composer\Events\SyncProgressBuild;
use App\Composer\Events\SyncProgressFinish;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Symfony\Component\Process\Process;

class PackageManager
{
    use ConsoleOutputTrait;

    const DEFAULT_PROVIDERS_URL = '/p/%package%$%hash%.json';
    const SYNC_OPTION_PROGRESS = 1;
    const SYNC_OPTION_ASYNC = 2;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var Container
     */
    protected $application;

    /**
     * @var \Closure
     */
    protected $requestPerformer;

    /**
     * @var \Closure
     */
    protected $requestAsyncPerformer;

    /**
     * @var Filesystem|FilesystemManager
     */
    protected $storage;

    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @var PackageManager
     */
    protected $manager;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * PackageManager constructor.
     *
     * @param array     $config
     * @param Container $application
     */
    public function __construct(array $config, Container $application)
    {
        $this->config = $config;
        $this->application = $application;

        $this->storage = $this->application->make('filesystem')->disk('composer');
        $this->cache = $this->application->make('cache');
        $this->dispatcher = $this->application->make('events');
        $this->manager = $this;
    }

    /**
     * @return Container
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param $file
     *
     * @return string
     * @throws FileNotFoundException
     */
    public function getFileContent($file)
    {
        if (!$this->storage->exists($file)) {
            throw new FileNotFoundException();
        }

        return $this->storage->get($file);
    }

    /**
     * @return Filesystem|FilesystemManager
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @param $file
     * @param $content
     *
     * @return bool
     */
    public function putFileContent($file, $content)
    {
        if (is_array($content)) {
            $content = json_encode($content);
        }

        return $this->storage->put($file, $content);
    }

    /**
     * @param \Closure $callback
     *
     * @return $this
     */
    public function setRequestPerformer(\Closure $callback)
    {
        $this->requestPerformer = $callback;

        return $this;
    }

    /**
     * @param \Closure $callback
     *
     * @return $this
     */
    public function setRequestAsyncPerformer(\Closure $callback)
    {
        $this->requestAsyncPerformer = $callback;

        return $this;
    }


    public function request($uri)
    {
        return call_user_func($this->requestPerformer, 'GET', $uri);
    }

    public function requestAsync(\Iterator $requestPathIterator, \Closure $callback, $concurrency = 5)
    {
        call_user_func($this->requestAsyncPerformer, $requestPathIterator, $callback, $concurrency);
    }

    public function getPackagesAggregationJson()
    {
        $packagesJson = $this->loadPackagesJson();
        $providerIncludesJson = $this->loadProviderIncludesJson();

        return [
            'packages'          => $packagesJson,
            'provider-includes' => $providerIncludesJson,
            'providers-url'     => self::DEFAULT_PROVIDERS_URL,
        ];
    }

    protected function loadPackagesJson()
    {
        if ($this->storage->exists('packages.json')) {
            return json_decode($this->storage->get('packages.json'), true);
        }

        $this->storage->put('packages.json', '[]');
        return [];
    }

    protected function loadProviderIncludesJson()
    {
        if ($this->storage->exists('provider-includes.json')) {
            return json_decode($this->storage->get('provider-includes.json'), true);
        }

        $this->storage->put('provider-includes.json', '[]');
        return [];
    }

    public function sync($async = false)
    {
        $packagesJson = $this->request('packages.json');

        $providerIncludeLoader = new ProviderIncludeLoader($this);
        $proxyProviderIncludes = $providerIncludeLoader->loadProxyProviderIncludes($packagesJson);
        $localProviderIncludes = $providerIncludeLoader->loadLocalProviderIncludes();

        $syncQueue = [];

        foreach ($proxyProviderIncludes as $includeName => $proxyIncludeHash) {

            // 检查本地是否存在 provider includes，不存在则加入同步任务队列
            if (!isset($localProviderIncludes[$includeName])) {
                $syncQueue[$includeName] = [$proxyIncludeHash, null];
                continue;
            }

            $includeNameHash = md5($includeName);
            $proxyOldIncludeHash = null;
            if ($this->storage->exists($path = "provider-includes/{$includeNameHash}/proxy-latest-hash")) {
                if (($proxyOldIncludeHash = $this->storage->get($path)) === $proxyIncludeHash) {
                    continue;
                }
            }

            $syncQueue[$includeName] = [$proxyIncludeHash, $proxyOldIncludeHash];
        }

        foreach ($syncQueue as $includeName => list($proxyIncludeHash, $proxyOldIncludeHash)) {
            if ($async) {
                $proxyOldIncludeHash = $proxyOldIncludeHash ?: 'null';
                $parameters = [
                    "--include-name={$includeName}",
                    "--include-name={$includeName}",
                    "--proxy-include-hash={$proxyIncludeHash}",
                    "--provider-url={$packagesJson['providers-url']}",
                    "--proxy-old-include-hash={$proxyOldIncludeHash}",
                ];
                $process = new Process('/usr/bin/env php artisan repo:proxy-async -q ' . implode(' ', $parameters));
                $process->start();

                $this->output("==> <fg=blue;options=bold>Sync</> (async): <info>{$includeName}</info>, hash: {$proxyIncludeHash}, old: {$proxyOldIncludeHash}");
            } else {
                $this->output("==> <fg=blue;options=bold>Sync</>: <info>{$includeName}</info>, hash: {$proxyIncludeHash}, old: {$proxyOldIncludeHash}");
                list(, $includeHash) = $this->syncProviderInclude($includeName, $proxyIncludeHash,
                    $packagesJson['providers-url'],
                    $proxyOldIncludeHash, static::SYNC_OPTION_PROGRESS);

                $providerIncludes = json_decode($this->storage->get('provider-includes.json'), true);
                $providerIncludes[$includeName]['sha256'] = $includeHash;
                $this->storage->put('provider-includes.json', json_encode($providerIncludes));
            }

        }

    }

    public function syncProviderInclude(
        $includeName,
        $proxyIncludeHash,
        $providerUrl,
        $proxyOldIncludeHash = null,
        $options = 0
    ) {
        $includeNameHash = md5($includeName);
        $response = $this->request(str_replace('%hash%', $proxyIncludeHash, $includeName));

        if (isset($response['providers'])) {
            $newProxyProviders = composer_json_map($response['providers']);
        } else {
            throw new \RuntimeException();
        }


        if (!is_null($proxyOldIncludeHash)) {
            if ($this->storage->exists($path = "provider-includes/{$includeNameHash}/proxy-{$proxyOldIncludeHash}")) {
                $oldProxyProviders = composer_json_map(json_decode($this->storage->get($path), true)['providers']);
            } else {
                $oldProxyProviders = [];
            }
        } else {
            $oldProxyProviders = [];
        }

        $needUpdate = array_diff($newProxyProviders, $oldProxyProviders);
        $requests = function () use ($needUpdate, $providerUrl) {
            foreach ($needUpdate as $packagesName => $packagesHash) {
                yield $packagesName . '+' . $packagesHash => str_replace(['%hash%', '%package%'],
                    [$packagesHash, $packagesName], $providerUrl);
            }
        };

        if ($options ^ static::SYNC_OPTION_ASYNC == static::SYNC_OPTION_PROGRESS && $this->dispatcher) {
            $this->dispatcher->dispatch(new SyncProgressBuild(count($needUpdate), $this->output));
        }


        $providers = [];

        $this->requestAsync($requests(),
            $this->processProviderPackages(function ($packagesName, $hash) use (&$providers) {
                $providers[$packagesName]['sha256'] = $hash;
            }, $options));

        if ($this->storage->exists($path = "provider-includes/{$includeNameHash}/local-latest")) {
            $localLatestInclude = json_decode($this->storage->get($path), true);
        } else {
            $localLatestInclude = ['providers' => []];
        }

        $finalProviders = array_merge($localLatestInclude['providers'], $providers);
        ksort($finalProviders);

        $include = ['providers' => $finalProviders];
        $this->storage->put($path, $localIncludeJson = json_encode($include));
        $includeHash = hash('sha256', $localIncludeJson);
        $this->storage->put("provider-includes/{$includeNameHash}/local-{$includeHash}", $localIncludeJson);
        $this->storage->put("provider-includes/{$includeNameHash}/local-latest-hash", $includeHash);
        $this->storage->delete("provider-includes/{$includeNameHash}/proxy-{$proxyOldIncludeHash}");
        $this->storage->put("provider-includes/{$includeNameHash}/proxy-{$proxyIncludeHash}", json_encode($response));
        $this->storage->put("provider-includes/{$includeNameHash}/proxy-latest-hash", $proxyIncludeHash);

        if ($options ^ static::SYNC_OPTION_PROGRESS == 0) {
            if ($options ^ static::SYNC_OPTION_ASYNC == static::SYNC_OPTION_PROGRESS && $this->dispatcher) {
                $this->dispatcher->dispatch(new SyncProgressFinish());
            }

            return [$includeName, $includeHash];
        }

        if ($options == static::SYNC_OPTION_ASYNC) {
            $providerIncludes =
                json_decode($this->storage->get('provider-includes.json'), true);
            $providerIncludes[$includeName]['sha256'] = $includeHash;
            $this->storage->put('provider-includes.json', json_encode($providerIncludes));
        }

        return 0;
    }

    private function processProviderPackages(\Closure $providerPusher, $options)
    {
        return function ($response, $index) use ($providerPusher, $options) {
            list($packagesName, $proxyPackagesHash) = explode('+', $index);

            foreach ($response['packages'] as $packageName => &$package) {
                foreach ($package as $version => &$packageJson) {
                    if (!isset($packageJson['dist'])) {
                        continue;
                    }

                    $type = $packageJson['dist']['type'];

                    $index = composer_repo_path_index($packageName,
                        [$type, $hash = sha1($version . '$' . $packageJson['dist']['url'])]);

                    // dist 包地址映射文件保存位置
                    $mapPath = "dist-map/" . implode('/', $index);

                    // dist 包外部访问地址
                    $uri = "/storage/{$packageName}/{$hash}.{$type}";

                    if ($this->storage->exists($mapPath)) {
                        $packageJson['dist']['url'] = config('repo.dist_url') . $uri;
                        continue;
                    }

                    $this->storage->put($mapPath, $packageJson['dist']['url']);

                    $packageJson['dist']['url'] = config('repo.dist_url') . $uri;
                }
            }

            $localPackagesHash = hash('sha256', $localPackagesJson = json_encode($response));
            $localPath = implode('/', composer_repo_path_index($packagesName, $localPackagesHash));

            if (!$this->storage->exists("packages/{$localPath}")) {
                $this->storage->put("packages/{$localPath}", $localPackagesJson);
            }

            $providerPusher($packagesName, $localPackagesHash);
            $this->dispatcher->dispatch(new SyncOnePackagesItem($packagesName, $proxyPackagesHash));
        };
    }
}