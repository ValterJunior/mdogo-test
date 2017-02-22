<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core
 */

/**
 * Core time constant
 */
defined('MDOGO_TIME') || define('MDOGO_TIME', microtime(true));


/**
 * Base and core classes
 */
require 'src/interfaces.php';
require 'src/exceptions.php';

require 'src/array.php';
require 'src/object.php';
require 'src/closure.php';

require 'src/cache.php';
require 'src/container.php';
require 'src/loader.php';
require 'src/environment.php';


/**
 * Utilites
 */
require 'src/utilities/array.php';
require 'src/utilities/crypto.php';
require 'src/utilities/dir.php';
require 'src/utilities/export.php';
require 'src/utilities/helper.php';
require 'src/utilities/http.php';
require 'src/utilities/mail.php';
require 'src/utilities/mustache.php';
require 'src/utilities/route.php';
require 'src/utilities/string.php';
require 'src/utilities/svg.php';


/**
 * Core environment constants
 */
defined('MDOGO_ROOT') || define('MDOGO_ROOT', realpath(__DIR__ .'/../../..'));
defined('MDOGO_HOST') || define('MDOGO_HOST', sanitize(array_get($_SERVER, 'HTTP_HOST', '_')));



/**
 * Facade, entry point, initializer
 *
 * @final
 *
 * @method static mixed get($entity)
 * @method static mixed make($entity)
 * @method static bool acknowledge($entity)
 * @method static bool validate($entity, $class)
 * @method static void publish($topic, $arguments = array())
 * @method static void subscribe($topic, $callback)
 * @method static void unsubscribe($topic, $uncallback = false)
 *
 * @package mdogo
 * @subpackage core
 */
/* static */ final class Mdogo
{
  /**
   * Prevent instantiation: Mdogo is meant to be used statically
   */
  private function __construct() {}
  private function __clone() {}


  /**
   * Adds some convenient methods (e.g. Mdogo::publish(), Mdogo::getCache())
   *
   * @param string $name
   * @param array $arguments
   * @throws MdogoException
   * @return mixed
   */
  public static function __callStatic($name, $arguments)
  {
    if (method_exists('MdogoContainer', $name)) {
      return call_user_func_array(
        array('MdogoContainer', $name),
        $arguments
      );
    }
    elseif (method_exists($broker = MdogoContainer::get('broker'), $name)) {
      return call_user_func_array(
        array($broker, $name),
        $arguments
      );
    }
    else {
      $arguments = array_merge(explode('_', uncamelize($name)), $arguments);
      $method = array_shift($arguments);
      if ($method !== $name) {
        return self::__callStatic($method, $arguments);
      }
      else {
        throw new MdogoException('method not found: '. $name);
      }
    }
  }


  /**
   * Initializes environment: autoloader, d.i. container, cache...
   *
   * @return void
   */
  public static function bootstrap()
  {
    MdogoCache::initialize();
    MdogoContainer::initialize();
    MdogoLoader::initialize();

    self::publish('bootstrap');
  }


  /**
   * Entry point: initiates request processing
   *
   * @return void
   */
  public static function run()
  {
    self::bootstrap();
    self::publish('request', self::get('request'));
  }
}
