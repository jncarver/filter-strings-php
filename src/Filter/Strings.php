<?php

namespace TraderInteractive\Filter;

use TraderInteractive\Exceptions\FilterException;
use TypeError;

/**
 * A collection of filters for strings.
 */
final class Strings
{
    /**
     * Filter a string.
     *
     * Verify that the passed in value  is a string.  By default, nulls are not allowed, and the length is restricted
     * between 1 and PHP_INT_MAX.  These parameters can be overwritten for custom behavior.
     *
     * The return value is the string, as expected by the \TraderInteractive\Filterer class.
     *
     * @param mixed $value The value to filter.
     * @param bool $allowNull True to allow nulls through, and false (default) if nulls should not be allowed.
     * @param int $minLength Minimum length to allow for $value.
     * @param int $maxLength Maximum length to allow for $value.
     * @return string|null The passed in $value.
     *
     * @throws FilterException if the value did not pass validation.
     * @throws \InvalidArgumentException if one of the parameters was not correctly typed.
     */
    public static function filter(
        $value = null,
        bool $allowNull = false,
        int $minLength = 1,
        int $maxLength = PHP_INT_MAX
    ) {
        self::validateMinimumLength($minLength);
        self::validateMaximumLength($maxLength);

        if (self::valueIsNullAndValid($allowNull, $value)) {
            return null;
        }

        $value = self::enforceValueCanBeCastAsString($value);

        self::validateStringLength($value, $minLength, $maxLength);

        return $value;
    }

    /**
     * Explodes a string into an array using the given delimiter.
     *
     * For example, given the string 'foo,bar,baz', this would return the array ['foo', 'bar', 'baz'].
     *
     * @param string $value The string to explode.
     * @param string $delimiter The non-empty delimiter to explode on.
     * @return array The exploded values.
     *
     * @throws \InvalidArgumentException if the delimiter does not pass validation.
     */
    public static function explode($value, string $delimiter = ',')
    {
        self::validateIfObjectIsAString($value);

        if (empty($delimiter)) {
            throw new \InvalidArgumentException(
                "Delimiter '" . var_export($delimiter, true) . "' is not a non-empty string"
            );
        }

        return explode($delimiter, $value);
    }

    /**
     * This filter takes the given string and translates it using the given value map.
     *
     * @param string $value    The string value to translate
     * @param array  $valueMap Array of key value pairs where a key will match the given $value.
     *
     * @return string
     */
    public static function translate(string $value, array $valueMap) : string
    {
        if (!array_key_exists($value, $valueMap)) {
            throw new FilterException("The value '{$value}' was not found in the translation map array.");
        }

        return $valueMap[$value];
    }

    /**
     * This filter prepends $prefix and appends $suffix to the string value.
     *
     * @param mixed  $value  The string value to which $prefix and $suffix will be added.
     * @param string $prefix The value to prepend to the string.
     * @param string $suffix The value to append to the string.
     *
     * @return string
     *
     * @throws FilterException Thrown if $value cannot be casted to a string.
     */
    public static function concat($value, string $prefix = '', string $suffix = '') : string
    {
        self::enforceValueCanBeCastAsString($value);
        return "{$prefix}{$value}{$suffix}";
    }

    /**
     * Strip HTML and PHP tags from a string. Unlike the strip_tags function this method will return null if a null
     * value is given. The native php function will return an empty string.
     *
     * @param string|null $value The input string
     *
     * @return string|null
     */
    public static function stripTags(string $value = null)
    {
        if ($value === null) {
            return null;
        }

        return strip_tags($value);
    }

    private static function validateMinimumLength(int $minLength)
    {
        if ($minLength < 0) {
            throw new \InvalidArgumentException('$minLength was not a positive integer value');
        }
    }

    private static function validateMaximumLength(int $maxLength)
    {
        if ($maxLength < 0) {
            throw new \InvalidArgumentException('$maxLength was not a positive integer value');
        }
    }

    private static function validateStringLength(string $value = null, int $minLength, int $maxLength)
    {
        $valueLength = strlen($value);
        if ($valueLength < $minLength || $valueLength > $maxLength) {
            $format = "Value '%s' with length '%d' is less than '%d' or greater than '%d'";
            throw new FilterException(
                sprintf($format, $value, $valueLength, $minLength, $maxLength)
            );
        }
    }

    private static function valueIsNullAndValid(bool $allowNull, $value = null) : bool
    {
        if ($allowNull === false && $value === null) {
            throw new FilterException('Value failed filtering, $allowNull is set to false');
        }

        return $allowNull === true && $value === null;
    }

    private static function validateIfObjectIsAString($value)
    {
        if (!is_string($value)) {
            throw new FilterException("Value '" . var_export($value, true) . "' is not a string");
        }
    }

    private static function enforceValueCanBeCastAsString($value)
    {
        try {
            $value = (
                function (string $str) : string {
                    return $str;
                }
            )($value);
        } catch (TypeError $te) {
            throw new FilterException(sprintf("Value '%s' is not a string", var_export($value, true)));
        }

        return $value;
    }
}
