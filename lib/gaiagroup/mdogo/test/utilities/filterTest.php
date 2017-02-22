<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */


class filterTest extends PHPUnit_Framework_TestCase
{
  public function testSanitize()
  {
    $this->assertEquals('fobr', sanitize("\0föobâr"));
  }
}
