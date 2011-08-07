<?
/**
 * Mg Libs
 *
 * @author Guillaume Mathieu
 * @year   2011
 */

/**
 * Filters camelCase value to underscore
 *
 * @uses       \Zend_Filter_Interface
 * @category   Mg
 * @package    Mg_Filter
 */
class Mg_Filter_CamelCaseToUnderscore implements \Zend_Filter_Interface
{
    /**
     * Defined by \Zend_Filter_Interface
     *
     * Returns the string $value, converting camelCase to underscore
     * Example: firstName to first_name
     *
     * @param  string $value
     * @return string
     */
    public function filter($value)
    {
        // convert camelCase to underscore (ex. firstName to first_name)
        return strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $value));
    }
}