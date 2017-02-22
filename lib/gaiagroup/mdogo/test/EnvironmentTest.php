<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */

require_once 'Fixture.php';


class EnvironmentTest extends PHPUnit_Framework_TestCase
{
  protected $environment;

  protected function setUp()
  {
    $this->environment = Mdogo::get('environment');
  }


  public function testGet()
  {
    $this->assertEquals('OK', $this->environment->get('status', 200));
  }


  public function testResolve()
  {
    $this->assertEquals('Mdogo_Controller_HTML', $this->environment->resolve('routes', 'GET:'));
    $this->assertEquals('Mdogo_Controller_HTML', $this->environment->resolve('routes', 'GET:file'));
    $this->assertEquals('Mdogo_Controller_HTML', $this->environment->resolve('routes', 'GET:dir/file'));
    $this->assertEquals('Mdogo_Controller_Data', $this->environment->resolve('routes', 'GET:file.json'));
    $this->assertEquals('Mdogo_Controller_Data', $this->environment->resolve('routes', 'GET:dir/file.json'));
  }


  public function testAutoload()
  {
    $class = 'Mdogo_Controller_HTML';
    $this->assertFalse(class_exists($class, false));
    MdogoLoader::autoload($class);
    $this->assertTrue(class_exists($class, false));
  }
}
