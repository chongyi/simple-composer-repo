# Composer Repository

## 环境配置项说明

- `COMPOSER_STORAGE` 所有 Composer 仓库的元信息（composer.json）数据保存的地方 
- `COMPOSER_DIST_STORAGE` Composer 仓库 dist 包保存的地方
- `COMPOSER_DIST_PREFIX` Dist 包下载地址路由路径前缀，
- `COMPOSER_DIST_URL` Dist 包下载地址的 HOST 值，需要带 Schema （即 http:// 或 https://）
- `COMPOSER_CACHE_TIME` http(s)//:<URL>/packages.json 的缓存时间，建议和自动同步时间保持一直
- `COMPOSER_PROXY_URL` 代理对象的地址，默认为 https://packagist.phpcomposer.com，若服务器在境外建议为 https://packagist.org