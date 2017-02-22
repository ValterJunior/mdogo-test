<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage controllers
 */


/**
 * Html controller
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
class Mdogo_Controller_HTML extends Mdogo_Controller_File
{
  /**
   * Finds html file
   *
   * @throws MdogoNotFoundException
   * @return void
   */
  protected function resolve()
  {
    $file_path = MDOGO_ROOT .'/'. $this->basepath .'/'. $this->path;

    if (is_file($this->file = $file_path)) {}
    elseif (is_file($this->file = $file_path .'.html')) {}
    elseif (is_file($this->file = str_replace('//', '/', $file_path .'/index.html'))) {}
    else {
      throw new MdogoNotFoundException();
    }
  }
}
