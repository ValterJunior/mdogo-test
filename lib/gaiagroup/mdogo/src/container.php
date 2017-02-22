<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core
 */


/**
 * Dependency injection container
 *
 * @final
 *
 * @package mdogo
 * @subpackage core
 */
/* static */ final class MdogoContainer
{
  /**
   * @var array
   */
  private static $entities = array();

  /**
   * @var array
   */
  private static $instances = array();


  /**
   * Prevent instantiation
   */
  private function __construct() {}
  private function __clone() {}


  /**
   * Initializes instances, entities
   *
   * @param MdogoEnvironment $environment
   * @return void
   */
  public static function initialize(MdogoEnvironmentInterface $environment = null)
  {
    if (!$environment) {
      $environment = new MdogoEnvironment();
    }
    self::$entities = $environment->get('entities');
    self::$instances = compact('environment');
  }


  /**
   * Gets a 'singletonized' object
   *
   * @param string $name
   * @return mixed
   */
  public static function get($name)
  {
    if (!isset(self::$instances[$name])) {
      self::$instances[$name] = self::make($name);
    }
    return self::$instances[$name];
  }


  /**
   * Creates an object with an arbitrary number of dependencies
   *
   * @param string $name
   * @throws MdogoException
   * @return mixed
   */
  public static function make($name)
  {
    if (self::acknowledge($name)) {
      $dependencies = explode(':', self::$entities[$name]);
      $class = array_shift($dependencies);

      self::validate($name, $class);

      $reflector = new ReflectionClass($class);
      if (empty($dependencies)) {
        $instance = $reflector->newInstance();
      } else {
        $instance = $reflector->newInstanceArgs(
          array_map(array('self', 'get'), $dependencies)
        );
      }
      return $instance;
    }
    else {
      throw new MdogoException('entity not found: '. $name);
    }
  }


  /**
   * Checks if Mdogo can help with this kind of entity
   *
   * @param string $name
   * @return bool
   */
  public static function acknowledge($name)
  {
    return array_key_exists($name, self::$entities);
  }


  /**
   * Checks if entity implements interface
   *
   * @param string $name
   * @param mixed $class
   * @throws MdogoException
   * @return bool
   */
  public static function validate($name, $class)
  {
    $interface = 'Mdogo'. ucfirst($name) .'Interface';
    if (!is_string($class) && !is_object($class)) {
      throw new MdogoException('entity must be something: '. $name);
    }
    if (interface_exists($interface, false)
        && !in_array($interface, class_implements($class))) {
      throw new MdogoException('entity must implement '. $interface .': '. $name);
    }
    return true;
  }
}
