<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */

class DispatcherTest extends PHPUnit_Framework_TestCase
{
  protected $dispatcher;

  protected function setUp()
  {
    $this->dispatcher = Mdogo::get('dispatcher');
  }


  public function testGetResponse()
  {
    trespass($this->dispatcher, 'method', 'GET');
    trespass($this->dispatcher, 'path', 'index.html');
    $response = coerce($this->dispatcher, 'getResponse');
    $this->assertInstanceOf('Mdogo_Response', $response);
  }


  public function testGetCachedResponse()
  {
    trespass($this->dispatcher, 'method', 'GET');
    trespass($this->dispatcher, 'path', 'index.html');
    $response = coerce($this->dispatcher, 'getCachedResponse');
    $this->assertInstanceOf('Mdogo_Response', $response);
  }
}
