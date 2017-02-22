<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */


class BrokerTest extends PHPUnit_Framework_TestCase
{
  protected $broker;

  protected function setUp()
  {
    $this->broker = Mdogo::get('broker');
  }


  public function testPublishSubscribe()
  {
    $check = false;
    $this->broker->subscribe(__METHOD__, function () use (&$check) { $check = true; });
    $this->broker->publish(__METHOD__);
    $this->assertTrue($check);
  }


  public function testUnsubscribe()
  {
    $check = false;
    $callback = function () use (&$check) { $check = true; };
    $this->broker->subscribe(__METHOD__, $callback);
    $this->broker->unsubscribe(__METHOD__, $callback);
    $this->broker->publish(__METHOD__);
    $this->assertFalse($check);
  }
}
