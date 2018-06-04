<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\enums;

use craft\helpers\StringHelper;

abstract class BaseEnum
{
    // Properties
    // =========================================================================

    /**
     * Holds the reflected constants for the enum.
     *
     * @var array|null
     */
    private static $_constants = [];

    // Public Methods
    // =========================================================================

    /**
     * Checks to see if the given name is valid in the enum.
     *
     * @param      $name   The name to search for.
     * @param bool $strict Defaults to false. If set to true, will do a case sensitive search for the name.
     *
     * @return bool true if it is a valid name, false otherwise.
     * @throws \ReflectionException
     */
    public static function isValidName($name, $strict = false)
    {
        $constants = static::_getConstants();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map(['Craft\StringHelper', 'toLowerCase'], array_keys($constants));
        return in_array(StringHelper::toLowerCase($name), $keys);
    }

    /**
     * Checks to see if the given value is valid in the enum.
     *
     * @param      $value  The value to search for.
     * @param bool $strict Defaults to false. If set the true, will do a case sensitive search for the value.
     *
     * @return bool true if it is a valid value, false otherwise.
     * @throws \ReflectionException
     */
    public static function isValidValue($value, $strict = false)
    {
        $values = array_values(static::_getConstants());
        return in_array($value, $values, $strict);
    }

    /**
     * @return array|null
     * @throws \ReflectionException
     */
    public static function getConstants()
    {
        return static::_getConstants();
    }

    // Private Methods
    // =========================================================================

    /**
     * @return null
     * @throws \ReflectionException
     */
    private static function _getConstants()
    {
        $class = get_called_class();

        // static:: chokes PHP here because PHP sucks.
        if (!isset(self::$_constants[$class])) {
            $reflect = new \ReflectionClass($class);
            self::$_constants[$class] = $reflect->getConstants();
        }

        return self::$_constants[$class];
    }
}
