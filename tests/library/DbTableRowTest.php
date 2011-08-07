<?php
require_once('AbstractDbTestCase.php');
class DbTableRowTest extends AbstractDbTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->table = new TestApp_Model_DbTable_Products();
        $this->table->setRowClass('Mg_Db_Table_Row');
    }

    public function testOnInsert()
    {
        $row = $this->table->createRow(array('name' => 'test'));
        $this->assertTrue(is_null($row->created_on));
        $row->save();
        $this->assertFalse(is_null($row->created_on));
    }

    public function testOnUpdate()
    {
        $row = $this->table->find(1)->current();
        $this->assertTrue(is_null($row->updated_on));
        $row->save();
        $this->assertFalse(is_null($row->updated_on));
    }
}