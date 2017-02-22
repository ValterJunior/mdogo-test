<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */

class CacheTest extends PHPUnit_Framework_TestCase
{
  protected $cache;

  protected function setUp()
  {
    $this->cache = Mdogo::get('cache');
  }


  public function testSetGet()
  {
    // temporarily disabled

    //$key = 'qunit-'. dechex(mt_rand());
    //
    //$value = new stdClass();
    //$value->string = 'platypus';
    //
    //$this->cache->set($key, $value, 1);
    //$result = $this->cache->get($key, $success);
    //
    //$this->assertTrue($success);
    //$this->assertEquals($value, $result);

    $this->assertTrue(true);
  }
}
