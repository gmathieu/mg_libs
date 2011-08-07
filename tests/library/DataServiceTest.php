<?php
require_once('AbstractDbTestCase.php');
class DataServiceTest extends AbstractDbTestCase
{
    public $products;

    public function setUp()
    {
        parent::setUp();

        $this->products      = TestApp_Model_Products::getInstance();
        $this->productColors = TestApp_Model_Product_Colors::getInstance();
    }

    public function testGetDbTable()
    {
        // default search
        $this->assertInstanceOf('TestApp_Model_DbTable_Products',
                                $this->products->table);
        // overidden
        $this->assertInstanceOf('TestApp_Model_DbTable_ProductSkus',
                                TestApp_Model_Product_Skus::getInstance()->table);
    }

    /**
     * @expectedException Mg_Data_ServiceException
     */
    public function testGetDbTableNotFound()
    {
        $this->assertInstanceOf('TestApp_Model_DbTable_ProductSkus',
                                TestApp_Model_Product_Items::getInstance()->table);
    }

    public function testGetDataObjectClass()
    {
        // default search
        $this->assertEquals('TestApp_Model_Product',
                            $this->products->getDataObjectClass());
        // overidden
        $this->assertEquals('Mg_Data_Object',
                            $this->productColors->getDataObjectClass());
    }

    public function testFind()
    {
        // known product
        $product = $this->products->find(1);
        $this->assertInstanceOf('TestApp_Model_Product', $product);
        $this->assertEquals($product->id, 1);

        // unknown product
        $this->assertTrue(is_null($this->products->find(99)));

        // multiple primary keys
        $productColor = $this->productColors->find(1, 'black');
        $this->assertInstanceOf('Mg_Data_Object', $productColor);
        $this->assertEquals(array('product_id' => '1', 'color' => 'black'), $productColor->getRawData());
    }

    public function testFindBy()
    {
        // known product
        $product = $this->products->findByName('Black/White Product');
        $this->assertInstanceOf('TestApp_Model_Product', $product);
        $this->assertEquals($product->id, 1);

        // unknown product
        $this->assertTrue(is_null($this->products->findByName('foo')));

        // multi column
        $productColor = $this->productColors->findByProductIdAndColor(1, 'black');
        $this->assertInstanceOf('Mg_Data_Object', $productColor);
        $this->assertEquals(array('product_id' => '1', 'color' => 'black'), $productColor->getRawData());
    }

    public function testFetch()
    {
        // known product
        $products = $this->products->fetchByName('Black/White Product');
        $product  = $products->current();

        $this->assertInstanceOf('Mg_Data_Set', $products);
        $this->assertEquals(1, count($products));
        $this->assertInstanceOf('TestApp_Model_Product', $product);
        $this->assertEquals($product->id, 1);

        // unknown product
        $unknown = $this->products->fetchByName('foo');
        $this->assertInstanceOf('Mg_Data_Set', $unknown);
        $this->assertEquals(0, count($unknown));

        // multi column
        $productColors = $this->productColors->fetchByProductIdAndColor(1, 'black');
        $productColor  = $productColors->current();
        $this->assertInstanceOf('Mg_Data_Set', $productColors);
        $this->assertInstanceOf('Mg_Data_Object', $productColor);
        $this->assertEquals(array('product_id' => '1', 'color' => 'black'), $productColor->getRawData());
    }

    public function testLimit()
    {
        // page 1
        $this->products->setPageLimit(1, 2);
        $products = $this->products->fetchAll();
        $this->assertEquals(2, count($products));

        // page 2
        $this->products->setPageLimit(2, 2);
        $products = $this->products->fetchAll();
        $this->assertEquals(1, count($products));
    }

    public function testGetSanitizedData()
    {
        $data     = array(
            'id'          => 1,
            'product_id' => 99,
            'name'       => 'Product Name',
            'foo'        => 'bar'
        );
        $expected = array(
            'id'   => 1,
            'name' => 'Product Name',
        );

        $sanitizedData = $this->products->getSanitizedDbRowData($data);

        $this->assertEquals($expected, $sanitizedData);
    }

    public function testGetDbColumnsWithPrefix()
    {
        $expectedData    = array(
            'products_id'         => 'id',
            'products_name'       => 'name',
            'products_created_on' => 'created_on',
            'products_updated_on' => 'updated_on',
        );
        $prefixedColumns = $this->products->getDbColumnsWithPrefix('products');
        $this->assertEquals($expectedData, $prefixedColumns);
    }

    public function testInsert()
    {
        $newProduct = new TestApp_Model_Product(array(
            'name' => 'New Product'
        ));
        $this->products->insert($newProduct);

        // these two values are set from the DB
        $this->assertFalse(is_null($newProduct->id));
        $this->assertFalse(is_null($newProduct->created_on));
    }

    public function testUpdate()
    {
        $product = $this->products->find(1);
        $product->name = 'Updated Product';
        $this->products->update($product);

        $this->assertEquals($product->name, 'Updated Product');
        $this->assertFalse(is_null($product->updated_on));
    }

    public function testDelete()
    {
        $product = $this->products->find(1);
        $this->products->delete($product);

        $this->assertTrue(is_null($this->products->find(1)));
    }
}