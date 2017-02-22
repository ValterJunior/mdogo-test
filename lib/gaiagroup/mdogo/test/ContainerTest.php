<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */

require_once 'Fixture.php';


class ContainerTest extends PHPUnit_Framework_TestCase
{
  public function testGet()
  {
    $this->assertInstanceOf('MdogoEnvironment', MdogoContainer::get('environment'));
    $this->assertInstanceOf('Mdogo_Broker', MdogoContainer::get('broker'));
    $this->assertInstanceOf('Mdogo_Cache', MdogoContainer::get('cache'));
    $this->assertInstanceOf('Mdogo_Data', MdogoContainer::get('data'));
    $this->assertInstanceOf('Mdogo_Database', MdogoContainer::get('database'));
    $this->assertInstanceOf('Mdogo_Logger', MdogoContainer::get('logger'));
    $this->assertInstanceOf('Mdogo_Request', MdogoContainer::get('request'));
    $this->assertInstanceOf('Mdogo_Response', MdogoContainer::get('response'));

    $this->setExpectedException('MdogoException');
    MdogoContainer::get('tapir');
  }


  public function testMake()
  {
    $this->assertInstanceOf('MdogoEnvironment', MdogoContainer::make('environment'));
    $this->assertInstanceOf('Mdogo_Broker', MdogoContainer::make('broker'));
    $this->assertInstanceOf('Mdogo_Cache', MdogoContainer::make('cache'));
    $this->assertInstanceOf('Mdogo_Data', MdogoContainer::make('data'));
    $this->assertInstanceOf('Mdogo_Database', MdogoContainer::make('database'));
    $this->assertInstanceOf('Mdogo_Logger', MdogoContainer::make('logger'));
    $this->assertInstanceOf('Mdogo_Request', MdogoContainer::make('request'));
    $this->assertInstanceOf('Mdogo_Response', MdogoContainer::make('response'));

    $this->setExpectedException('MdogoException');
    MdogoContainer::make('tapir');
  }


  public function testAcknowledge()
  {
    $this->assertTrue(MdogoContainer::acknowledge('environment'));
    $this->assertTrue(MdogoContainer::acknowledge('broker'));
    $this->assertTrue(MdogoContainer::acknowledge('cache'));
    $this->assertTrue(MdogoContainer::acknowledge('data'));
    $this->assertTrue(MdogoContainer::acknowledge('database'));
    $this->assertTrue(MdogoContainer::acknowledge('logger'));
    $this->assertTrue(MdogoContainer::acknowledge('request'));
    $this->assertTrue(MdogoContainer::acknowledge('response'));
    $this->assertTrue(MdogoContainer::acknowledge('session'));

    $this->assertFalse(MdogoContainer::acknowledge('tapir'));
  }
}
