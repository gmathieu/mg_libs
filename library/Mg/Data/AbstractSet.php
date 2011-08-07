<?php
/**
 * Mg Libs
 *
 * @author Guillaume Mathieu
 * @year   2011
 */

/**
 * A data set that stores and instantiates each row only when iterated through
 * @uses       SeekableIterator
 * @uses       Countable
 * @uses       ArrayAccess
 * @uses       Mg_Data_SetException
 * @category   Mg
 * @package    Mg_Data
 */
abstract class Mg_Data_AbstractSet implements \SeekableIterator, \Countable, \ArrayAccess
{
    /**
     * The original data for each row.
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Zend_Db_Table_Row_Abstract class name.
     *
     * @var Closer
     */
    protected $_dataObjectInitializer;

    /**
     * Iterator pointer.
     *
     * @var integer
     */
    protected $_pointer = 0;

    /**
     * How many data rows there are.
     *
     * @var integer
     */
    protected $_count;

    /**
     * Collection of instantiated Zend_Db_Table_Row objects.
     *
     * @var array
     */
    protected $_instantiatedDataSet = array();

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $data, Closure $dataObjectInitializer)
    {
        // save original data
        $this->_data = $data;

        // save object initializer
        $this->_dataObjectInitializer = $dataObjectInitializer;

        // set the count of rows
        $this->_count = count($this->_data);

        $this->init();
    }

    /**
     * Store data, class names, and state in serialized object
     *
     * @return array
     */
    public function __sleep()
    {
        return array('_data', '_dataObjectInitializer', '_pointer', '_count', '_instantiatedDataSet');
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
     * Rewind the Iterator to the first element.
     * Similar to the reset() function for arrays in PHP.
     * Required by interface Iterator.
     *
     * @return \Mg\Data\AbstractRowset Fluent interface.
     */
    public function rewind()
    {
        $this->_pointer = 0;
        return $this;
    }

    /**
     * Return the current element.
     * Similar to the current() function for arrays in PHP
     * Required by interface Iterator.
     *
     * @return mixed current element from the collection
     */
    public function current()
    {
        if ($this->valid() === false) {
            return null;
        }

        // do we already have a row object for this position?
        if (empty($this->_instantiatedDataSet[$this->_pointer])) {
            // call intializer and store result
            $dataObjectInitializer = $this->_dataObjectInitializer;
            $this->_instantiatedDataSet[$this->_pointer] = $dataObjectInitializer($this->_data[$this->_pointer]);
        }

        // return the row object
        return $this->_instantiatedDataSet[$this->_pointer];
    }

    /**
     * Return the identifying key of the current element.
     * Similar to the key() function for arrays in PHP.
     * Required by interface Iterator.
     *
     * @return int
     */
    public function key()
    {
        return $this->_pointer;
    }

    /**
     * Move forward to next element.
     * Similar to the next() function for arrays in PHP.
     * Required by interface Iterator.
     *
     * @return void
     */
    public function next()
    {
        ++$this->_pointer;
    }

    /**
     * Check if there is a current element after calls to rewind() or next().
     * Used to check if we've iterated to the end of the collection.
     * Required by interface Iterator.
     *
     * @return bool False if there's nothing more to iterate over
     */
    public function valid()
    {
        return $this->_pointer >= 0 && $this->_pointer < $this->_count;
    }

    /**
     * Returns the number of elements in the collection.
     *
     * Implements Countable::count()
     *
     * @return int
     */
    public function count()
    {
        return $this->_count;
    }

    /**
     * Take the Iterator to position $position
     * Required by interface SeekableIterator.
     *
     * @param int $position the position to seek to
     * @return \Mg\Data\AbstractRowset
     * @throws Mg_Data_SetException
     */
    public function seek($position)
    {
        $position = (int) $position;
        if ($position < 0 || $position >= $this->_count) {
            throw new SetException("Illegal index $position");
        }
        $this->_pointer = $position;
        return $this;
    }

    /**
     * Check if an offset exists
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->_data[(int) $offset]);
    }

    /**
     * Get the row for the given offset
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     * @return \Zend\Db\Table\AbstractRow
     */
    public function offsetGet($offset)
    {
        $offset = (int) $offset;
        if ($offset < 0 || $offset >= $this->_count) {
            require_once 'Zend/Db/Table/Rowset/Exception.php';
            throw new Zend_Db_Table_Rowset_Exception("Illegal index $offset");
        }
        $this->_pointer = $offset;

        return $this->current();
    }

    /**
     * Does nothing
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
    }

    /**
     * Does nothing
     * Required by the ArrayAccess implementation
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
    }

    /**
     * Returns a Zend_Db_Table_Row from a known position into the Iterator
     *
     * @param int $position the position of the row expected
     * @param bool $seek wether or not seek the iterator to that position after
     * @return mixed
     * @throws Mg_Data_SetException
     */
    public function getRow($position, $seek = false)
    {
        $key = $this->key();
        try {
            $this->seek($position);
            $row = $this->current();
        } catch (SetException $e) {
            throw new SetException('No row could be found at position ' . (int) $position, 0, $e);
        }
        if ($seek == false) {
            $this->seek($key);
        }
        return $row;
    }

    /**
     * Returns all data as an array.
     *
     * Updates the $_data property with current row object values.
     *
     * @return array
     */
    public function toArray()
    {
        // @todo This works only if we have iterated through
        // the result set once to instantiate the rows.
        foreach ($this->_instantiatedDataSet as $i => $row) {
            $this->_data[$i] = $row->toArray();
        }
        return $this->_data;
    }
}
