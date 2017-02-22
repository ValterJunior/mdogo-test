<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage controllers
 */


/**
 * File controller
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
 * @subpackage controllers
 */
class Mdogo_Controller_File extends Mdogo_Controller
{
  /**
   * @var string
   */
  protected $basepath = 'pub';

  /**
   * @var string
   */
  protected $file;


  /**
   * Prepares response object
   *
   * @return Mdogo_Response
   */
  public function respond()
  {
    $this->resolve();
    $this->response->setBody($this->file, get_extension($this->file));
    return $this->response;
  }


  /**
   * Finds requested file
   *
   * @throws MdogoNotFoundException
   * @return void
   */
  protected function resolve()
  {
    $this->file = MDOGO_ROOT .'/'. $this->basepath .'/'. $this->path;

    if (!is_file($this->file)) {
      throw new MdogoNotFoundException();
    }
  }
}
