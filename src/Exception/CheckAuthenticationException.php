<?php declare(strict_types=1);

namespace danielburger1337\SteamOpenId\Exception;

class CheckAuthenticationException extends \Exception implements ExceptionInterface
{
    final public const ACCESS_DENIED = 0;
    final public const STEAM_ERROR = 1;
}
