<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */

class DataTest extends PHPUnit_Framework_TestCase
{
  protected $data;

  protected function setUp()
  {
    $this->data = new Mdogo_Data(Mdogo::get('cache'));
  }


  public function testGet()
  {
    $this->assertEquals('aardvark', $this->data->get('example', 'animals', 0));
  }
}
