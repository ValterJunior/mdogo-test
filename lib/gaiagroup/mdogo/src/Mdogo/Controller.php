<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core-objects
 */


/**
 * Abstract base controller
 *
 * @abstract
 *
 * @property MdogoEnvironment $environment
 * @property Mdogo_Broker     $broker
 * @property Mdogo_Cache      $cache
 * @property Mdogo_Data       $data
 * @property Mdogo_Database   $database
 * @property Mdogo_Logger     $logger
 * @property Mdogo_Request    $request
 * @property Mdogo_Response   $response
 * @property Mdogo_Session    $session
 *
 * @package mdogo
 * @subpackage core-objects
 */
abstract class Mdogo_Controller extends MdogoObject implements MdogoControllerInterface
{
  /**
   * @var string
   */
  protected $method;

  /**
   * @var string
   */
  protected $path;


  /**
   * Accepts request method/path
   *
   * @param string &$method
   * @param string &$path
   * @return void
   */
  public function bootstrap(&$method, &$path)
  {
    $this->method = $method;
    $this->path = $path;
  }


  /**
   * Prepares a response object
   *
   * @return Mdogo_Response
   */
  abstract public function respond();
}
