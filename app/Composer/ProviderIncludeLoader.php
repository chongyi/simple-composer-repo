<?php
/**
 * ProviderIncludeLoader.php
 *
 * @copyright 袁野 <yuanye@yunsom.com>
 * @link      https://insp.top
 */

namespace App\Composer;


class ProviderIncludeLoader
{
    /**
     * @var PackageManager
     */
    protected $manager;

    /**
     * ProviderIncludeLoader constructor.
     *
     * @param PackageManager $manager
     */
    public function __construct(PackageManager $manager)
    {
        $this->manager = $manager;
    }

    public function loadProxyProviderIncludes(array $packagesJson = null)
    {
        if (is_null($packagesJson)) {
            $packagesJson = $this->manager->request('packages.json');
            return $this->loadProxyProviderIncludes($packagesJson);
        }

        return composer_json_map(isset($packagesJson['provider-includes']) ? $packagesJson['provider-includes'] : []);
    }

    public function loadLocalProviderIncludes()
    {
        if ($this->manager->getStorage()->exists('provider-includes.json')) {
            return composer_json_map(json_decode($this->manager->getStorage()->get('provider-includes.json'), true));
        }

        $this->manager->getStorage()->put('provider-includes.json', json_encode([]));

        return [];
    }
}