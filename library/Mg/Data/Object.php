<?php
/**
 * Mg Libs
 *
 * @author Guillaume Mathieu
 * @year   2011
 */

/**
 * Data object wrapper
 *
 * @uses       Mg_Data_ObjectException
 * @uses       Mg_Filter_CamelCaseToUnderscore
 * @category   Mg
 * @package    Mg_Data
 */
class Mg_Data_Object
{
    /**
     * Data array of key/value pairs
     * 
     * @var array
     */
    protected $_data;

    /**
     * Constructor.
     *
     * Data must be an array of key/value pairs
     *
     * @param  array $data OPTIONAL Array of key/value pairs
     * @return void
     */
    public function __construct(array $data = array())
    {
        $this->_data = $data;

        $this->init();
    }

    /**
     * Retrieve data field value
     * 
     * @param string $key
     * @return mixed
     * @throws Mg_Data_ObjectException if the $function doesn't match any key
     */
    public function __get($key)
    {
        $key = $this->_transformKey($key);

        if (isset($this->$key)) {
            return $this->_data[$key];
        } else {
            // throws exception when key doesn't exist
            throw new Mg_Data_ObjectException("{$key} not found");
        }
    }

    /**
     * Set data field with value.
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws Mg_Data_ObjectException if the $function doesn't match any key
     */
    public function __set($key, $value)
    {
        $key = $this->_transformKey($key);

        if (isset($this->$key)) {
            $this->_data[$key] = $value;
        } else {
            // throws exception when key doesn't exist
            throw new Mg_Data_ObjectException("{$key} not found");
        }
    }

    /**
     * Test existence of key
     *
     * @param  string  $key
     * @return boolean
     */
    public function __isset($key)
    {
        $key = $this->_transformKey($key);
        return array_key_exists($key, $this->_data);
    }

    /**
     * Overloading: set or retrieve key value
     *
     * You can retrieve any key value by calling getFooBar(mixed $defaultValue)
     * The $defaultValue will only be returned if the current key value is NULL
     * You can set any key with getFooBar(mixed $newValue)
     *
     * Note: getFooBar will look for the "foo_bar" attribute unless you modify
     * the _transformKey() return value.
     *
     * @param  string $function
     * @param  mixed $args
     * @return mixed
     * @throws Mg_Data_ObjectException if the $function doesn't begin with "set" or "get"
     */
    public function __call($function, $args)
    {
        $action = substr($function, 0, 3);

        // only allow get and set
        if ('get' == $action || 'set' == $action) {
            // get the key name
            $key = $this->_transformKey(substr($function, 3));

            // get action
            if ('get' == $action) {

                // return default value if passed and value is NULL
                if (null === $this->$key && isset($args[0])) {
                    return $args[0];
                } else {
                    // otherwise return key value
                    return $this->$key;
                }

            // set action
            } else {
                $this->_data[$key] = $args[0];
            }
        } else {
            throw new Mg_Data_ObjectException("{$function} must start with 'set' or 'get'.");
        }
    }

    /**
     * Initialize object
     *
     * Called from {@link __construct()}; as final step of object instantiation.
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Returns raw data
     * 
     * @return array
     */
    public function getRawData()
    {
        return $this->_data;
    }

    /**
     * Returns a subset of data defined by the prefix and separator
     * 
     * @param string $prefix
     * @param string $separator (default: '_')
     * @return array
     */
    public function getDataWithKeyPrefix(string $prefix, $separator = '_')
    {
        $output                   = array();
        $prefixAndSeperator       = $prefix . $separator;
        $prefixAndSeperatorLength = strlen($prefixAndSeperator);

        foreach ($this->_data as $key => $value) {
            if ($prefixAndSeperator == substr($key, 0, $prefixAndSeperatorLength)) {
                $prefixLessKey = substr($key, $prefixAndSeperatorLength, strlen($key));
                $output[$prefixLessKey] = $value;
            }
        }

        return $output;
    }

    /**
     * Sets all data in model from an array.
     * 
     * @param array $data
     * @return void
     */
    public function setFromArray(array $data)
    {
        $this->_data = array_merge($this->_data, $data);
    }

    /**
     * Returns the column/value data as an array.
     * 
     * @return array
     */
    public function toArray()
    {
        return $this->_data;
    }

    /**
     * Returns the column/value data as an array.
     * 
     * @return array
     */
    public function toJson()
    {
        return $this->toArray();
    }

    /**
     * Transform a key name from the user-specified form
     * to the physical form used in the _data array.
     *
     * @param string $key key name given.
     * @return string The key after transformation applied (default: camelCase to underscore)
     */
    protected function _transformKey($key)
    {
        $filter = new Mg_Filter_CamelCaseToUnderscore();
        return $filter->filter($key);
    }
}