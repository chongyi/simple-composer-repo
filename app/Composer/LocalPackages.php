<?php
/**
 * LocalPackages.php
 *
 * Creator:         chongyi
 * Create Datetime: 2017/5/30 11:25
 */

namespace App\Composer;


class LocalPackages
{
    const LOCAL_REGISTER_JSON = 'local/register.json';

    protected $packageRegisters;

    /**
     * @var PackageManager
     */
    protected $manager;

    protected $storage;

    /**
     * LocalPackages constructor.
     * @param PackageManager $manager
     */
    public function __construct(PackageManager $manager)
    {
        $this->manager = $manager;
        $this->storage = $manager->getStorage();
    }

    public function loadRegister()
    {
        if (!$this->storage->exists(self::LOCAL_REGISTER_JSON)) {
            $this->storage->put(self::LOCAL_REGISTER_JSON, json_encode([]));

            return $this->packageRegisters = [];
        }

        return $this->packageRegisters = json_decode($this->storage->get(self::LOCAL_REGISTER_JSON), true);
    }

    /**
     * @return array
     */
    public function getPackageRegisters()
    {
        if (is_null($this->packageRegisters)) {
            $this->loadRegister();
        }

        return $this->packageRegisters;
    }

    public function hasPackage($name)
    {
        return isset($this->packageRegisters[$name]);
    }

    protected function getPackagesByProviderInclude($providerIncludeName)
    {
        $generator = function ($providerIncludeName) {
            foreach ($this->packageRegisters as $register) {
                if (!isset($register['provider-include']) || $register['provider-include'] != $providerIncludeName) {
                    continue;
                }

                $index = composer_repo_path_index($register['package'], 'packages.json');

                if (!$this->storage->exists($path = 'local/packages/' . implode('/', $index))) {
                    continue;
                }

                $packagesJson = json_decode($this->storage->get($path), true);

                yield $register['package'] => $packagesJson;
            }
        };

        return $generator($providerIncludeName);
    }

    public function push($name, $version, $composerJson)
    {
        $index = composer_repo_path_index($name, 'packages.json');
        $path = 'local/packages/' . implode('/', $index);
        $packages = [];

        if ($this->storage->exists($path)) {
            $packages = json_decode($this->storage->get($path), true);
        }

        $packages['packages'][$name][$version] = $composerJson;

        return $this->storage->put($path, json_encode($packages));
    }

    public function register($package, $providerInclude = null)
    {
        $this->packageRegisters[$package] = [
            'package' => $package,
            'provider-include' => $providerInclude,
        ];

        $this->update();

        return $this;
    }

    protected function update()
    {
        $this->storage->put(self::LOCAL_REGISTER_JSON, json_encode($this->packageRegisters));
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemManager
     */
    public function getStorage()
    {
        return $this->storage;
    }


}