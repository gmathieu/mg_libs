<?php
/**
 * Mg Libs
 *
 * @author Guillaume Mathieu
 * @year   2011
 */

/**
 * This data service is a singleton gateway between the DB records and some
 * arbitrary data objects.
 *
 * @uses      Zend_Db
 * @uses       Mg_Data_ServiceException
 * @uses       Mg_Filter_CamelCaseToUnderscore
 * @category   Mg
 * @package    Mg_Data
 */
abstract class Mg_Data_AbstractService
{
    const BY    = 'By';
    const FIND  = 'find';
    const FETCH = 'fetch';

    /**
     * The Zend DB table object
     * 
     * @var Zend_Db_Table
     */
    public $table;

    /**
     * The Zend DB adapter
     * 
     * @var Zend_Db_Adapter
     */
    public $adapter;

    /**
     * The consistent select statement
     * 
     * @var Zend_Db_Select
     */
    public $select;

    /**
     * The data object class used when a Mg_Data_Set row item is instantiated
     * 
     * @var string
     */
    protected $_dataObjectClass;

    /**
     * The DB table class name
     * 
     * @var string
     */
    protected $_dbTableClass;

    /**
     * The DB table row class
     * 
     * (default value: 'Mg_Db_Table_Row')
     * 
     * @var string
     */
    protected $_dbTableRowClass = 'Mg_Db_Table_Row';

    /**
     * Query result pagination: page
     * 
     * (default value: 0)
     * 
     * @var int
     */
    private $_page  = 0;

    /**
     * Query result pagination: maximum items per page
     * 
     * (default value: 0)
     * 
     * @var int
     */
    private $_limit = 0;

    /**
     * Stored singleton instances
     * 
     * (default value: array())
     * 
     * @var array
     */
    private static $_instances = array();

    /**
     * Singleton instance
     *
     * @return mixed
     */
    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(self::$_instances[$class])) {
            self::$_instances[$class] = new $class();
        }
        return self::$_instances[$class];
    }

    /**
     * Constructor
     *
     * Instantiate using {@link getInstance()}; data service is a singleton
     * object.
     *
     * Instantiates the DB table and DB adapter object.
     * Assigns the DB table row class is specified through {@link $_dbTableRowClass};
     * Calls {@link init()}; hook once everything is instantiated
     *
     * @return void
     */
    protected function __construct()
    {
        $this->table   = $this->_getDbTable();
        $this->adapter = $this->table->getAdapter();

        // set custom db row class object
        if ($this->_dbTableRowClass) {
            $this->table->setRowClass($this->_dbTableRowClass);
        }

        $this->init();
    }

    /**
     * Overloading: retrieve Db records into data objects
     *
     * Possible function calls include:
     * - find, fetch: Defaults to searching on primary key
     * - findBy[Column1][And][Column2][…]: search on specified columns
     * - fetchBy[Column1][And][Column2][…]
     *
     * Find returns one value or NULL
     * Fetch will always return Mg_Data_Set (empty or not)
     * 
     * @param  string $function
     * @param  mixed $args column values to search on
     * @return mixed
     * @throws Mg_Data_ServiceException
     */
    public function __call($function, $args)
    {
        $columns = array();

        // reset previous call
        $this->reset();

        // setup regex for (fetch, find, fetchBy, findBy)
        $matches = array();
        $pattern = '/^(' . self::FIND . '|' . self::FETCH . '){1}(' . self::BY . ')?/';

        preg_match_all($pattern, $function, $matches, PREG_SET_ORDER);

        // make sure find or fetch is called
        if (0 === count($matches)) {
            throw new Mg_Data_ServiceException("'{$function}' function doesn't exist.");
        }

        // setup action (find or fetch)
        $action = $matches[0][1];

        // determine whether findBy or fetchBy was passed
        $by = isset($matches[0][2]);

        // custom columns
        if ($by) {
            // strip out action and "By" from function
            $actionByLength = strlen($matches[0][0]);
            $functionLength = strlen($function);
            $columnString   = substr($function, $actionByLength, $functionLength);

            // separate out different columns
            $camelCaseCols  = explode('And', $columnString);

            foreach ($camelCaseCols as $column) {
                $columns[] = $this->_transformDbColumn($column);
            }
        }

        // default to primary keys
        if (0 === count($columns)) {
            $columns = $this->table->info('primary');
        }

        // make sure that the number of arguments matches the number of columns
        if (count($args) !== count($columns)) {
            throw new Mg_Data_ServiceException("Number of arguments doesn't match number of columns.");
        }

        // add where clauses to select statement
        $index = 0;
        foreach ($columns as $column) {
            $column = $this->table->info('name') . '.' . $column;
            $this->select->where($column . ' = ?', $args[$index++]);
        }

        // find all list items based on column
        $list = $this->fetchAll();

        if (self::FIND === $action) {
            // find returns one item or null
            return $list->current();
        } else {
            // fetch always returns a Mg_Data_Set (empty or not)
            return $list;
        }
    }

    /**
     * Init sets up the default select statement
     * 
     * @return void
     */
    public function init()
    {
        $this->select = $this->adapter->select()
                             ->from($this->table->info('name'));
    }

    /**
     * Resets pagination and select statement to default by calling
     * {@link init()};
     * 
     * @return Data_Service_Abstract
     */
    public function reset()
    {
        // reset to default statement
        $this->init();

        // reset pagination
        $this->_page  = 0;
        $this->_limit = 0;

        return $this;
    }

    /**
     * Determines the Data Object class name based on two options:
     * - the singular name of this class
     * - the {@link $_dataObjectClass}; value
     * 
     * @return string
     */
    public function getDataObjectClass()
    {
        // data object class can be overwriten
        if ($this->_dataObjectClass) {
            return $this->_dataObjectClass;
        } else {
            // strip the s from this model
            $className = get_class($this);
            $this->_dataObjectClass = substr($className, 0, -1);
        }

        return $this->_dataObjectClass;
    }

    /**
     * Only returns the keys in the $data array that match the DB table columns
     * 
     * @param array $data
     * @return array
     */
    public function getSanitizedDbRowData(array $data)
    {
        $tableColumns = $this->table->info('cols');
        return array_intersect_key($data, array_flip($tableColumns));
    }

    /**
     * Returns an array of key/value pairs where the key is in the format of
     * "{$prefix}_DB_COLUMN_NAME" and the value is the DB column name
     * 
     * @param string $prefix
     * @return array
     */
    public function getDbColumnsWithPrefix($prefix)
    {
        $output = array();

        foreach ($this->table->info('cols') as $col) {
            $output["{$prefix}_{$col}"] = $col;
        }

        return $output;
    }

    /**
     * Finds the DB row of the associated Data Object
     * 
     * @param Mg_Data_Object $dataObj
     * @return Zend_Db_Table_Row or $_customDbRowClass
     */
    public function findDbRow(Mg_Data_Object $dataObj)
    {
        // setup DB info
        $select      = $this->table->select();
        $primaryKeys = $this->table->info('primary');

        foreach ($primaryKeys as $key) {
            $select->where("{$key} = ?", $dataObj->$key);
        }

        // find existing record
        return $this->table->fetchAll($select)->current();
    }

    /**
     * Fetches all DB table rows based on the select statement. Adds pagination
     * when necesarry and calls {@link reset()};
     * 
     * @return Mg_Data_Set of all DB table rows
     */
    public function fetchAll()
    {
        // set limit
        if ($this->_page > 0 && $this->_limit > 0) {
            $this->select->limitPage($this->_page, $this->_limit);
        }

        // fetch all and make sure result is an array
        $result = $this->adapter->fetchAll($this->select);
        $result = ($result) ? $result : array();

        // get data object class
        $dataObjectClass = $this->getDataObjectClass();

        $rowset = new Mg_Data_Set($result, function($data) use($dataObjectClass) {
            $dataObjectClass = $dataObjectClass;
            return new $dataObjectClass($data);
        });

        // reset previous call
        $this->reset();

        return $rowset;
    }

    /**
     * Sets the page limit and row count
     * 
     * @param int $page
     * @param int $limit
     * @return void
     */
    public function setPageLimit($page, $limit)
	{
		$this->_page  = $page;
		$this->_limit = $limit;
	}

	/**
	 * Saves Data Object into the database and updates it with the new DB data
	 * 
	 * @param Mg_Data_Object $dataObj
	 * @return boolean if the record was saved
	 */
	public function insert(Mg_Data_Object $dataObj)
	{
        // get raw data from data object
        $rawData = $dataObj->getRawData();

        // filter data
        $sanitizedData = $this->getSanitizedDbRowData($rawData);

        // create user record
        $dbRow    = $this->table->createRow($sanitizedData);
        $wasSaved = $dbRow->save();

        // update model with new DB data
        $dataObj->setFromArray($dbRow->toArray());

        // return database row
        return $wasSaved;
    }

	/**
	 * Updates an existing DB record with the Data Object's data
	 * 
	 * @param Mg_Data_Object $dataObj
	 * @return boolean if the record was saved
	 */
    public function update(Mg_Data_Object $dataObj)
    {
        // get raw data from data object
        $rawData = $dataObj->getRawData();

        // filter data
        $sanitizedData = $this->getSanitizedDbRowData($rawData);

        // find existing record
        $dbRow = $this->findDbRow($dataObj);

        // update DB row and save
        $dbRow->setFromArray($sanitizedData);
        $wasSaved = $dbRow->save();

        // update model with new DB data
        $dataObj->setFromArray($dbRow->toArray());

        return $wasSaved;
    }

	/**
	 * Finds and deletes a DB record associated with the Data Object
	 * 
	 * @param Mg_Data_Object $dataObj
	 * @return boolean if the record was deleted
	 */
    public function delete(Mg_Data_Object $dataObj)
    {
        // find existing record
        $dbRow = $this->findDbRow($dataObj);

        // return delete result
        return $dbRow->delete();
    }

    /**
     * Transform a DB column name from the user-specified form
     * to the physical form used in the DB table.
     *
     * @param string $column Column name given.
     * @return string The key after transformation applied (default: camelCase to underscore)
     */
    private function _transformDbColumn($column)
    {
        $filter = new Mg_Filter_CamelCaseToUnderscore();
        return $filter->filter($column);
    }

    /**
     * Gets a table object based on the ZF DbTable naming convention.
     * This value can be overwritten using {@link $_dbTableRowClass};
     * 
     * @return Zend_Db_Table
     */
    private function _getDbTable()
    {
        // DB table class can be overwriten
        if (!$this->_dbTableClass) {
            // explode class name into array(NAMESPACE, Model, MODEL_NAME)
            $explodedClassName = explode('_', get_class($this));

            // insert DbTable after Model: array(NAMESPACE, Model, DbTable, MODEL_NAME)
            $insertDbTable = array('DbTable');
            array_splice($explodedClassName, count($explodedClassName) - 1, 0, $insertDbTable);

            // reconstruct DB table class name
            $dbTableClass = implode('_', $explodedClassName);

            // store DB table class name for future calls
            $this->_dbTableClass = $dbTableClass;
        }

        // check existing of class
        if (!class_exists($this->_dbTableClass)) {
            throw new Mg_Data_ServiceException("{$this->_dbTableClass} isn't defined. " .
                                                'You can overwrite this with $_dbTableRowClass.');
        }

        return new $this->_dbTableClass();
    }
}