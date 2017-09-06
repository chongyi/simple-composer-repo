<?php
/**
 * helper.php
 *
 * @copyright 袁野 <yuanye@yunsom.com>
 * @link      https://insp.top
 */

if (!function_exists('composer_json_map')) {
    function composer_json_map($target)
    {
        if (is_string($target)) {
            $target = json_decode($target, true);
        }

        return array_map(function ($item) {
            return current($item);
        }, $target);
    }
}

if (!function_exists('composer_repo_path_index')) {
    function composer_repo_path_index($packageName, $merge = null)
    {
        list($vendorName, $packageName) = is_array($packageName) ? $packageName : explode('/', $packageName);

        $data = [
            substr($vendorName, 0, 1),
            substr($packageName, 0, 1),
            $vendorName,
            $packageName,
        ];

        if (!is_null($merge)) {
            $data = array_merge($data, (array)$merge);
        }

        return $data;
    }
}

if (!function_exists('composer_repo_deep_path_index')) {
    function composer_repo_deep_path_index($fullName, $merge = [])
    {
        if (strlen($fullName) < 10) {
            throw new InvalidArgumentException();
        }

        $index = [
            substr($fullName, 0, 1),
            substr($fullName, 1, 1),
            substr($fullName, 2, 8),
            $fullName
        ];

        return array_merge($index, $merge);
    }
}