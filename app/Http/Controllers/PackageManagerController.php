<?php

namespace App\Http\Controllers;

use App\Composer\PackageManager;
use App\Composer\Repository\PackageDistArchive;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Filesystem\FilesystemManager;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PackageManagerController extends Controller
{
    public function getPackagesJson(PackageManager $manager, Repository $repository)
    {
        if ($repository->has('packages.json')) {
            return $repository->get('packages.json');
        }

        $packagesJson = $manager->getPackagesAggregationJson();

        $repository->put('packages.json', $packagesJson, 5);

        return $packagesJson;
    }

    public function getPackageIncludes(PackageManager $manager, $provider, $hash)
    {
        if (substr($provider, 0, 8) === 'provider') {
            $pathHash = md5('p/' . $provider . '$%hash%.json');
            $path = 'provider-includes/' . $pathHash;

            if ($manager->getStorage()->exists($jsonPath = $path . '/local-' . $hash)) {
                return json_decode($manager->getStorage()->get($jsonPath), true);
            } else {
                return response()->json([], 404);
            }
        }

        return response()->json(['status' => 404], 404);
    }

    public function getPackage(PackageManager $manager, $vendor, $package, $hash)
    {
        $index = [
            substr($vendor, 0, 1),
            substr($package, 0, 1),
            $vendor,
            $package,
            $hash
        ];

        $data = json_decode($manager->getFileContent('packages/' . implode('/', $index)), true);

        if (is_null($data)) {
            return response()->json(['status' => 404], 404);
        }

        return $data;
    }

    public function getPackageDist(FilesystemManager $manager, $vendor, $package, $hash, $type)
    {
        $index = composer_repo_path_index([$vendor, $package], [$type, $hash]);
        if (!$manager->disk('composer')->exists($target = 'dist-map/' . implode('/', $index))) {
            throw new NotFoundHttpException();
        }

        $remoteUri = $manager->disk('composer')->get($target);

        $mime = [
            'zip' => 'application/zip',
            'tar' => 'application/x-tar',
            'tgz' => 'application/x-compressed',
            'gz'  => 'application/x-gzip',
            'rar' => 'application/x-rar-compressed'
        ];

        $publicRoot = rtrim(config('filesystems.disks.composer-public.root'), '/');
        $path = "{$vendor}/{$package}/{$hash}.{$type}";

        // 验证路径合法性
        if (!preg_match('#^\w+([-_.]\w+)*/\w+([-_.]\w+)*/\w+\.\w+(\.\w+)*#', $path)) {
            throw new NotFoundHttpException();
        }

        if (!is_dir($dir = $publicRoot . '/' . dirname($path))) {
            @mkdir($dir, 0764, true);
        }

        $reader = new \SplFileObject($remoteUri, 'r');
        $output = new \SplFileObject('php://output', 'w');
        $cache = new \SplFileObject($publicRoot . '/' . $path, 'w');

        $response = new StreamedResponse(function () use ($cache, $output, $reader) {
            while (!$reader->eof()) {
                $buffer = $reader->fread(1024);
                $output->fwrite($buffer);
                $cache->fwrite($buffer);
            }

        }, 200, ['Content-Type' => $mime[$type]]);

        return $response;
    }
}
