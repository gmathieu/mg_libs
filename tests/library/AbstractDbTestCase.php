<?php
abstract class AbstractDbTestCase extends Zend_Test_PHPUnit_DatabaseTestCase
{
    private $_connectionMock;

    /**
     * Returns the test database connection.
     *
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        if($this->_connectionMock == null) {
            $connection = Zend_Db::factory('Pdo_Mysql', array(
                'hosts'    => $GLOBALS['DB_DSN'],
                'username' => $GLOBALS['DB_USER'],
                'password' => $GLOBALS['DB_PASSWD'],
                'dbname'   => $GLOBALS['DB_DBNAME'],
            ));
            $this->_connectionMock = $this->createZendDbConnection(
                $connection, $GLOBALS['DB_DBNAME']
            );
            Zend_Db_Table_Abstract::setDefaultAdapter($connection);
        }
        return $this->_connectionMock;
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return $this->createFlatXmlDataSet(
            TEST_ROOT . '/fixtures/globalDataSet.xml'
        );
    }
}