<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core
 */


/**
 * PSR-0 compliant configurable caching class autoloader
 *
 * @final
 *
 * @package mdogo
 * @subpackage core
 */
/* static */ final class MdogoLoader
{
  /**
   * Prevent instantiation
   */
  private function __construct() {}
  private function __clone() {}


  /**
   * Registers self as autoloader
   *
   * @return void
   */
  public static function initialize()
  {
    spl_autoload_register(array(__CLASS__, 'autoload'));
  }


  /**
   * Autoloads or generates classes
   *
   * @param string $class
   * @return void
   */
  public static function autoload($class)
  {
    if ($file = self::resolve($class)) {
      /* @noinspection PhpIncludeInspection */
      require $file;
    }
    else {
      self::generate($class);
    }
  }


  /**
   * Resolves class name to file path
   *
   * @param string $class
   * @return string
   */
  private static function resolve($class)
  {
    $key = 'autoload:'. $class;
    $file = MdogoCache::get($key, $success);
    if (!$success) {
      $success = !!$file = array_get(self::getClassmap(), $class);
      if (!$success) {
        $class = ltrim($class, '\\');
        $file = route($class, self::getNamespaces()) .'/';
        if ($pos = strrpos($class, '\\')) {
          $file .= str_replace('\\', '/', substr($class, 0, $pos)) .'/';
          $class = substr($class, $pos + 1);
        }
        $file .= str_replace('_', '/', $class) .'.php';
        if (!file_exists($file)) {
          $file = false;
        }
      }
      MdogoCache::set($key, $file);
    }
    return $file;
  }


  /**
   * Gets composer classmap
   *
   * @return array
   */
  private static function getClassmap()
  {
    $key = 'autoload:classmap';
    $classmap = MdogoCache::get($key, $success);
    if (!$success) {
      $file = MDOGO_ROOT . '/lib/composer/autoload_classmap.php';
      $classmap = (file_exists($file)) ? require $file : array();
      MdogoCache::set($key, $classmap);
    }
    return $classmap;
  }


  /**
   * Gets composer/mdogo namespaces
   *
   * @return array
   */
  private static function getNamespaces()
  {
    $key = 'autoload:namespaces';
    $namespaces = MdogoCache::get($key, $success);
    if (!$success) {
      $namespaces = MdogoContainer::get('environment')->get('classpaths');
      $file = MDOGO_ROOT . '/lib/composer/autoload_namespaces.php';
      $map = (file_exists($file)) ? require $file : array();
      if (!empty($map)) {
        $namespaces = array_override(
          $namespaces,
          array_combine(
            array_map(
              function ($namespace) { return "$namespace*"; },
              array_keys($map)
            ),
            array_map(
              function ($path) { return is_array($path) ? reset($path) : $path; },
              array_values($map)
            )
          )
        );
      }
      MdogoCache::set($key, $namespaces);
    }
    return $namespaces;
  }


  /**
   * Attempts to generate class by extending an existing base class
   *
   * @param string $class
   * @return bool
   */
  private static function generate($class)
  {
    $base = MdogoContainer::get('environment')->resolve('baseclasses', $class);

    if ($base && class_exists($base, true)) {
      $code  = "class $class extends $base\n";
      $code .= "{\n";
      if (is_a($base, 'Mdogo_Model', true)) {
        $table = $base::getTable($class);
        $code .= "\tconst TABLE = '$table';\n";
        $code .= array_reduce(
          array_keys($base::getColumns($table)),
          function ($result, $key) {
            //$key = str_replace("`", "", $key);
            return "$result\n\tpublic $$key = null;\n";
          }
        );
      }
      $code .= "}\n";
      eval($code);
    }
  }
}
