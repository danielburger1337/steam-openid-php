<?php declare(strict_types=1);

namespace danielburger1337\SteamOpenId;

use danielburger1337\SteamOpenId\Exception\InvalidParameterException;

/**
 * @see http://openid.net/specs/openid-authentication-2_0.html#positive_assertions
 */
class Assertions
{
    public function __construct(
        private array $parameters,
        private bool $areDotsSpaces = true
    ) {
    }

    /**
     * Simple assertion that the given key exactly matches the given value.
     *
     * @param string $key   The key to check.
     * @param string $value The expected value.
     *
     * @return string The parameter value.
     */
    public function assertKeyValuePair(string $key, string $value): string
    {
        $parameterValue = $this->ensureParameterExists($key);

        if ($parameterValue !== $value) {
            throw new InvalidParameterException($key, "The \"{$key}\" parameter must always equal \"{$value}\".");
        }

        return $value;
    }

    /**
     * Helper method that ensures that the given parameter name exists.
     *
     * @param string $key The parameter to check.
     *
     * @return string The parameter value.
     */
    public function ensureParameterExists(string $key): string
    {
        $key = $this->resolveKey($key);

        if (!array_key_exists($key, $this->parameters)) {
            throw new InvalidParameterException($key, "The parameter \"{$key}\" was not found.");
        }

        if (!is_string($this->parameters[$key])) {
            throw new InvalidParameterException($key, "The parameter \"{$key}\" is not of type string.");
        }

        return $this->parameters[$key];
    }

    /**
     * Dots and spaces in variable names are converted to underscores. For example <input name="a.b" /> becomes $_REQUEST["a_b"].
     *
     * @see https://www.php.net/manual/en/language.variables.external.php
     */
    private function resolveKey(string $key): string
    {
        if ($this->areDotsSpaces) {
            return str_replace('.', '_', $key);
        }

        return $key;
    }
}
