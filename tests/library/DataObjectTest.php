<?php
class DataObjectTest extends PHPUnit_Framework_TestCase
{
    public $dataObj;
    public $data;
    public $newData;

    public function setUp()
    {
        $this->data = array(
            'user_id'    => '1',
            'first_name' => 'John',
            'last_name'  => 'Smith',
            'active'     => true,
        );

        $this->newData = array(
            'user_id'    => '2',
            'first_name' => 'Smith',
            'last_name'  => 'Arnold',
            'active'     => false,
        );

        $this->dataObj = new Mg_Data_Object($this->data);
    }

    public function testEmptyModel()
    {
        $emptyModel = new Mg_Data_Object();
        $this->assertEmpty($emptyModel->getRawData());
    }

    public function testRawData()
    {
        $this->assertEquals($this->dataObj->getRawData(), $this->data);
    }

    public function testPropertyGetter()
    {
        foreach ($this->data as $key => $value) {
            $this->assertEquals($this->dataObj->$key, $value);
        }
    }

    public function testPropertyGetterFromCamelCase()
    {
        $this->assertEquals($this->dataObj->firstName, $this->data['first_name']);
    }

    public function testPropertyGetterFunction()
    {
        $this->assertEquals($this->dataObj->getUserId(), $this->data['user_id']);
        $this->assertEquals($this->dataObj->getFirstName(), $this->data['first_name']);
        $this->assertEquals($this->dataObj->getLastName(), $this->data['last_name']);
        $this->assertEquals($this->dataObj->getActive(), $this->data['active']);
    }

    public function testPropertyGetterFunctionWithUnderscores()
    {
        $this->assertEquals($this->dataObj->getUser_id(), $this->data['user_id']);
    }

    public function testPropertyGetterDefaultValueFunction()
    {
        // set value to NULL
        $this->dataObj->active = null;

        $this->assertEquals('yes', $this->dataObj->getActive('yes'));
    }

    public function testPropertySetter()
    {
        foreach ($this->newData as $key => $value) {
            $this->dataObj->$key = $value;
        }
        $this->assertEquals($this->dataObj->getRawData(), $this->newData);
    }

    public function testPropertySetterFunction()
    {
        $this->dataObj->setUserId($this->newData['user_id']);
        $this->dataObj->setFirstName($this->newData['first_name']);
        $this->dataObj->setLastName($this->newData['last_name']);
        $this->dataObj->setActive($this->newData['active']);
        $this->assertEquals($this->dataObj->getRawData(), $this->newData);
    }

    public function testSetFromArray()
    {
        $this->dataObj->setFromArray($this->newData);
        $this->assertEquals($this->dataObj->getRawData(), $this->newData);
    }

    /**
     * @expectedException Mg_Data_ObjectException
     */
    public function testPropertyNotFoundFromGetterFunction()
    {
        $this->dataObj->getFoo();
    }

    /**
     * @expectedException Mg_Data_ObjectException
     */
    public function testPropertyNotFound()
    {
        $this->dataObj->foo;
    }
}