<?php declare(strict_types=1);

namespace danielburger1337\SteamOpenId\Exception;

class InvalidParameterException extends \Exception implements ExceptionInterface
{
    public function __construct(
        private string $parameter,
        ?string $message = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message ?? "The parameter \"{$parameter}\" is invalid.", $code, $previous);
    }

    /**
     * Get the invalid OpenID parameter name.
     */
    public function getParameter(): string
    {
        return $this->parameter;
    }
}
