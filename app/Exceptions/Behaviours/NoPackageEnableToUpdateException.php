<?php
/**
 * NoPackageEnableToUpdateException.php
 *
 * Creator:         chongyi
 * Create Datetime: 2017/5/30 13:07
 */

namespace App\Exceptions\Behaviours;

/**
 * Class NoPackageEnableToUpdateException
 *
 * 未找到（已注册的）可更新的包
 *
 * @package App\Exceptions\Behaviours
 */
class NoPackageEnableToUpdateException extends ForbiddenException
{

}