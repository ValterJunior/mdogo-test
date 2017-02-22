<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */

class DatabaseTest extends PHPUnit_Framework_TestCase
{
  protected $database;

  protected function setUp()
  {
    $this->database = Mdogo::get('database');
  }


  public function testOpen()
  {
    $this->assertInstanceOf('PDO', $this->database->open());
  }
}
