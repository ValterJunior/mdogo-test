<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core-entities
 */


/**
 * Simple Memcached based cache falling back to MdogoCache/APC
 *
 * @method bool all($callable)
 * @method bool any($callable)
 * @method bool contains($value)
 * @method mixed find($callable)
 * @method mixed where($conditions)
 * @method mixed reduce($callable, $result = null)
 * @method array map($callable)
 * @method array walk($callable)
 * @method array diff($array)
 * @method array merge($array)
 * ...
 *
 * @package mdogo
 * @subpackage core-entities
 */
class Mdogo_Cache extends MdogoArray implements MdogoCacheInterface
{
  /**
   * @var Memcached
   */
  protected $memcached = false;


  /**
   * Accepts dependencies
   *
   * @param MdogoEnvironmentInterface $environment
   * @return Mdogo_Cache
   */
  public function __construct(MdogoEnvironmentInterface $environment)
  {
    if (extension_loaded('memcached')) {
      $buckets = array_filter((array) $environment->get('memcached'));
      if (!empty($buckets)) {
        $this->memcached = new Memcached('MDOGO:'. MDOGO_HOST);
        if (0 === count($this->memcached->getServerList())) {
          $this->memcached->setOption(Memcached::OPT_NO_BLOCK, true);
          $this->memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
          $this->memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
          $this->memcached->addServers(array_map(
            function ($bucket) {
              return array_override(
                array('localhost', 11211),
                explode(':', $bucket)
              );
            },
            $buckets
          ));
        }
      }
      $this->data = array();
      $this->immutable = true;
    }
  }


  /**
   * Checks if data exists in cache
   *
   * @param string $key
   * @param int &$mtime (optional)
   */
  public function has($key, &$mtime = false)
  {
    $this->get($key, $success, $mtime);

    return $success;
  }


  /**
   * Gets data from cache
   *
   * @param string $key
   * @param bool &$success (optional)
   * @param bool|int &$mtime (optional)
   * @return mixed
   */
  public function get($key, &$success = false, &$mtime = false)
  {
    if ($this->memcached) {
      if (array_key_exists($key, $this->data)) {
        list($value, $mtime) = $this->data[$key];
        $success = true;
      }
      else {
        list($value, $mtime) = $this->memcached->get($this->prefix($key));
        if (Memcached::RES_SUCCESS === $this->memcached->getResultCode()) {
          $this->data[$key] = array($value, $mtime);
          $success = true;
        }
      }
    }
    else {
      $value = MdogoCache::get($key, $success, $mtime);
    }
    return $value;
  }


  /**
   * Stores data in cache, adds jitter to ttl
   *
   * @param string $key
   * @param mixed $value
   * @param int $ttl (optional)
   * @return mixed
   */
  public function set($key, $value, $ttl = 0)
  {
    if (-1 !== ($ttl = (int) $ttl)) {
      if ($this->memcached) {
        if ($ttl) {
          $ttl = mt_rand(ceil(0.75 * $ttl), floor(1.25 * $ttl));
        }
        $this->data[$key] = array($value, time());
        $this->memcached->set($this->prefix($key), $this->data[$key], $ttl);
      }
      else {
        MdogoCache::set($key, $value, $ttl);
      }
    }
    return $value;
  }


  /**
   * Removes data from cache
   *
   * @param string $key
   * @return mixed
   */
  public function remove($key)
  {
    if ($this->memcached) {
      if (array_key_exists($key, $this->data)) {
        array_unset($this->data, $key);
      }
      return $this->memcached->delete($this->prefix($key));
    }
    else {
      return MdogoCache::remove($key);
    }
  }


  /**
   * Prefixes cache key with hostname
   *
   * @param string $key
   * @return string
   */
  protected function prefix($key)
  {
    return 'MDOGO:'. MDOGO_HOST .':'. $key;
  }
}
