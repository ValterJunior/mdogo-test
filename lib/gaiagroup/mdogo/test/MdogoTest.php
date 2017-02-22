<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */

require_once 'Fixture.php';


class MdogoTest extends PHPUnit_Framework_TestCase
{
  public function testGet()
  {
    $this->assertInstanceOf('MdogoEnvironment', Mdogo::get('environment'));
    $this->assertInstanceOf('Mdogo_Broker', Mdogo::get('broker'));
    $this->assertInstanceOf('Mdogo_Cache', Mdogo::get('cache'));
    $this->assertInstanceOf('Mdogo_Data', Mdogo::get('data'));
    $this->assertInstanceOf('Mdogo_Database', Mdogo::get('database'));
    $this->assertInstanceOf('Mdogo_Logger', Mdogo::get('logger'));
    $this->assertInstanceOf('Mdogo_Request', Mdogo::get('request'));
    $this->assertInstanceOf('Mdogo_Response', Mdogo::get('response'));
  }


  public function testMake()
  {
    $this->assertInstanceOf('MdogoEnvironment', Mdogo::make('environment'));
    $this->assertInstanceOf('Mdogo_Broker', Mdogo::make('broker'));
    $this->assertInstanceOf('Mdogo_Cache', Mdogo::make('cache'));
    $this->assertInstanceOf('Mdogo_Data', Mdogo::make('data'));
    $this->assertInstanceOf('Mdogo_Database', Mdogo::make('database'));
    $this->assertInstanceOf('Mdogo_Logger', Mdogo::make('logger'));
    $this->assertInstanceOf('Mdogo_Request', Mdogo::make('request'));
    $this->assertInstanceOf('Mdogo_Response', Mdogo::make('response'));
  }


  public function testPubSub()
  {
    $check = false;
    Mdogo::subscribe('test', function () use (&$check) { $check = true; });
    Mdogo::publish('test');
    $this->assertTrue($check);
  }
}
