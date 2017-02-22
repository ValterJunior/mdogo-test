<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */

class ResponseTest extends PHPUnit_Framework_TestCase
{
  protected $response;

  protected function setUp()
  {
    $this->response = Mdogo::get('response');
  }


  public function testSet()
  {
    $body = 'sloth';
    $this->response->setBody($body, 'txt');
    $this->assertEquals($body, $this->response->get('body'));
    $this->assertEquals('text/plain', $this->response->get('mime'));
    $this->assertEquals(hash('md5', $body), $this->response->get('hash'));
    $this->assertEquals('+1 year', $this->response->get('expires'));
  }
}
