<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */

class RequestTest extends PHPUnit_Framework_TestCase
{
  protected $request;

  protected function setUp()
  {
    $this->request = Mdogo::get('request');
  }


  public function testGet()
  {
    $this->assertEquals($_SERVER['PHP_SELF'], $this->request->get('server', 'php_self'));
  }
}
