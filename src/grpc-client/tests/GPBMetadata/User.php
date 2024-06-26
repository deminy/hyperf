<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
# source: user.proto

namespace GPBMetadata;

class User
{
    public static $is_initialized = false;

    public static function initOnce()
    {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
            return;
        }
        $pool->internalAddGeneratedFile(
            '
�

user.protoUserService"
UserId

id ("4
UserInfo

id (
name (	
gender (2C
UserService4
info.UserService.UserId.UserService.UserInfo" B	Zpb/userbproto3',
            true
        );

        static::$is_initialized = true;
    }
}
