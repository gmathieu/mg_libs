<?php
/**
 * Mg Libs
 *
 * @author Guillaume Mathieu
 * @year   2011
 */

/**
 * Adds current date on insert and update
 *
 * @uses      \Zend_Db_Table_Row
 * @category   Mg
 * @package    Mg_Data
 * @subpackage Table
 */
class Mg_Db_Table_Row extends Zend_Db_Table_Row
{
    public function _insert()
    {
        $this->created_on = Zend_Date::now()->getIso();
    }

    public function _update()
    {
        $this->updated_on = Zend_Date::now()->getIso();
    }
}