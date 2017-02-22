<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage controllers
 */


/**
 * Simple data read access controller
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
class Mdogo_Controller_Data extends Mdogo_Controller
{
  /**
   * @var string
   */
  protected $extension;

  /**
   * @var array
   */
  protected $segments;

  /**
   * @var array
   */
  protected $body;


  /**
   * Accepts request method/path
   *
   * @param string &$method
   * @param string &$path
   * @return void
   */
  public function bootstrap(&$method, &$path)
  {
    parent::bootstrap($method, $path);
    $this->extension = get_extension($this->path);

    $basepath = str_replace('.'. ($this->extension ?: ' '), '', $this->path);
    $this->segments = explode('/', $basepath);
  }


  /**
   * Produces serialized response and returns it
   *
   * @return Mdogo_Response
   */
  public function respond()
  {
    $this->resolve();
    $this->response->setBody($this->body, $this->extension);
    return $this->response;
  }


  /**
   * Finds requested data
   *
   * @throws MdogoNotFoundException
   * @return mixed
   */
  protected function resolve()
  {
    $this->body = $this->data->get($this->segments);
    if (empty($this->body)) {
      throw new MdogoNotFoundException();
    }
  }
}
