<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core
 */


/**
 * Volatile system cache
 *
 * @final
 *
 * @package mdogo
 * @subpackage core
 */
/* static */ final class MdogoCache
{
  /**
   * @var string
   */
  private static $revision;

  /**
   * @var bool
   */
  private static $enabled;


  /**
   * Prevent instantiation
   */
  private function __construct() {}
  private function __clone() {}


  /**
   * (Re)sets cache revision
   *
   * @param bool|int $mtime (optional)
   * @return string
   */
  public static function initialize($mtime = false)
  {
    $key = 'MDOGO:'. MDOGO_HOST .':environment';
    if (self::$enabled = extension_loaded('apc')) {
      if ($mtime) {
        self::$revision = dechex($mtime);
        apc_store($key, self::$revision);
      }
      else {
        self::$revision = apc_fetch($key);
      }
    }
  }


  /**
   * Retrieves data from cache
   *
   * @param string $key
   * @param bool &$success (optional)
   * @param bool|int &$mtime (optional)
   * @return mixed
   */
  public static function get($key, &$success = false, &$mtime = false)
  {
    $key = self::prefix($key);
    if (self::$enabled && ($array_object = apc_fetch($key, $success))) {
      list($value, $mtime) = $array_object->getArrayCopy();
    }
    else {
      $value = $mtime = false;
    }
    return $value;
  }


  /**
   * Stores data in cache, adds jitter to ttl
   *
   * @param string $key
   * @param mixed $value
   * @param int $ttl
   * @return mixed
   */
  public static function set($key, $value, $ttl = 0)
  {
    $key = self::prefix($key);
    if (self::$enabled && (-1 !== ($ttl = (int) $ttl))) {
      if ($ttl) {
        $ttl = mt_rand(ceil(0.75 * $ttl), floor(1.25 * $ttl));
      }
      apc_store($key, new ArrayObject(array($value, time())), $ttl);
    }
    return $value;
  }


  /**
   * Removes data from cache
   *
   * @param string $key
   * @return mixed
   */
  public static function remove($key)
  {
    $key = self::prefix($key);
    $result = false;
    if (self::$enabled) {
      $result = apc_delete($key);
    }
    return $result;
  }


  /**
   * Prefixes cache key with hostname, config revision
   *
   * @param string $key
   * @return string
   */
  private static function prefix($key)
  {
    return 'MDOGO:'. MDOGO_HOST .':'. self::$revision .':'. $key;
  }
}
