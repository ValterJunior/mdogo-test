<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */

class TestCase extends PHPUnit_Framework_TestCase
{
  const CONTROLLER = '';

  const PATH = '';

  const METHOD = 'GET';

  protected $controller;

  protected function setUp()
  {
    $class = static::CONTROLLER;
    $this->controller = new $class();
    $method = static::METHOD;
    $path = static::PATH;
    call_user_func_array(
      array($this->controller, 'bootstrap'),
      array(&$method, &$path)
    );
    $this->response = $this->controller->respond();
  }


  public function testRespond()
  {
    $this->assertInstanceOf('Mdogo_Response', $this->response);
  }
}
