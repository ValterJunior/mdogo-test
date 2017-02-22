<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core
 */


/**
 * Abstract base class adding a little convenience and extensibility
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
 * @subpackage core
 */
abstract class MdogoObject implements MdogoObjectInterface
{
  /**
   * @var MdogoExtender
   */
  protected $extensions;


  /**
   * Extends object properties using mdogo entities
   *
   * @param string $name
   * @return bool
   */
  public function __isset($name)
  {
    return MdogoContainer::acknowledge($name);
  }


  /**
   * Extends object properties adding mdogo entities
   *
   * @param string $name
   * @throws MdogoException
   * @return mixed
   */
  public function __get($name)
  {
    if (MdogoContainer::acknowledge($name)) {
      return MdogoContainer::get($name);
    }
    else {
      throw new MdogoException('property not found: '. $name);
    }
  }


  /**
   * Returns object (or extension) method as closure
   *
   * @param string $method
   * @param ... $args (optional)
   * @return MdogoClosureInterface
   */
  public function toClosure($name)
  {
    if (!call_user_func('is_callable', array($this, $name))) {
      throw new MdogoException('unknown/private method: '. $name);
    }
    return new MdogoClosure($name, $this, array_slice(func_get_args(), 1));
  }


  /**
   * Creates array of public properties
   *
   * @return array
   */
  public function toArray()
  {
    return call_user_func('get_object_vars', $this);
  }
}
