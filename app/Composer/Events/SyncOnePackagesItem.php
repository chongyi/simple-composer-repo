<?php
/**
 * SyncOnePackageItem.php
 *
 * Creator:         chongyi
 * Create Datetime: 2017/5/21 16:50
 */

namespace App\Composer\Events;


class SyncOnePackagesItem
{
    public $packagesName;

    public $proxyPackagesHash;

    /**
     * SyncOnePackagesItem constructor.
     * @param $packagesName
     * @param $proxyPackagesHash
     */
    public function __construct($packagesName, $proxyPackagesHash)
    {
        $this->packagesName = $packagesName;
        $this->proxyPackagesHash = $proxyPackagesHash;
    }


}