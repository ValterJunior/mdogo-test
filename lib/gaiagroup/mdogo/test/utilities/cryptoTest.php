<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */


class cryptoTest extends PHPUnit_Framework_TestCase
{
  public function testBcrypt()
  {
    $plaintext = random(rand(8, 256));
    $hash = bcrypt($plaintext);
    $this->assertTrue(bcheck($plaintext, $hash));
    $this->assertFalse(bcheck('a'. $plaintext, $hash));
  }


  public function testRandom()
  {
    $checks = array();
    for ($i = 0; $i < 1000; $i++) {
      $checks[random(8)] = true;
    }
    $this->assertCount(1000, $checks);
  }


  public function testUuid()
  {
    $checks = array();
    for ($i = 0; $i < 1000; $i++) {
      $uuid = uuid();
      if (is_uuid($uuid)) {
        $checks[$uuid] = true;
      }
    }
    $this->assertCount(1000, $checks);
  }
}
