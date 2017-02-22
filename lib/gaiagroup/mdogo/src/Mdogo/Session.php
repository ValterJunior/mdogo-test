<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core-entities
 */


/**
 * Caching db session handler
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
class Mdogo_Session extends MdogoArray implements MdogoSessionInterface
{
  /**
   * @var string
   */
  protected static $model = 'Mdogo_Model_Session';

  /**
   * @var Mdogo_Cache
   */
  protected $cache;

  /**
   * @var array
   */
  protected $config;

  /**
   * @var string
   */
  public $id;


  /**
   * Accepts dependencies, configures session
   *
   * @param MdogoEnvironmentInterface $environment
   * @param MdogoCacheInterface $cache
   * @throws MdogoException
   * @return Mdogo_Session
   */
  public function __construct(MdogoEnvironmentInterface $environment, MdogoCacheInterface $cache)
  {
    Mdogo::validate('model', static::$model);

    $this->cache = $cache;
    $this->config = $environment->get('session');

    if (session_id() !== '') {
      throw new MdogoException('session already started');
    }
    session_name($this->config['cookie_name']);
    session_set_cookie_params(
      $this->config['cookie_ttl'],
      $this->config['cookie_path'],
      $this->config['cookie_domain'],
      $this->config['cookie_secure'],
      $this->config['cookie_httponly']
    );
    session_set_save_handler(
      function () { return true; },
      function () { return true; },
      array($this, 'handleRead'),
      array($this, 'handleWrite'),
      array($this, 'handleDestroy'),
      function () { return true; }
    );
    session_start();
    $this->id = session_id();

    Mdogo::subscribe('shutdown', 'session_write_close');

    parent::__construct($_SESSION);
  }


  /**
   * Regenerates session id
   *
   * @return void
   */
  public function regenerate()
  {
    session_regenerate_id(true);

    $this->id = session_id();
  }


  /**
   * Destroys current session
   *
   * @return void
   */
  public function destroy()
  {
    session_unset();

    $this->regenerate();
  }


  /**
   * Gets csrf protection token
   *
   * @return string
   */
  public function getToken()
  {
    if (!$this->has('csrf_token')) {
      $this->set('csrf_token', uniqid(hash('md5', random(16, true))));
    }
    return $this->get('csrf_token');
  }


  /**
   * (Re-)sets cookie and db ttls
   *
   * @param int $ttl (optional)
   * @return void
   */
  public function setTTL($ttl = null)
  {
    if (is_null($ttl)) {
      $cookie_ttl = intval($this->config['cookie_ttl']);
      $db_ttl = intval($this->config['db_ttl']);
    }
    else {
      $cookie_ttl = $db_ttl = intval($ttl);
    }
    if (0 !== $cookie_ttl) {
      $cookie_ttl += time();
    }
    if (0 !== $db_ttl) {
      $db_ttl += time();
    }
    else {
      $db_ttl = intval($this->config['db_ttl']) + time();
    }
    $params = session_get_cookie_params();
    setcookie(
      session_name(), session_id(), $cookie_ttl,
      $params['path'], $params['domain'],
      $params['secure'], $params['httponly']
    );
    /* @var Mdogo_Model_Session $model */
    $model = new static::$model(array('session_id' => $this->id));
    $model->save(array('session_id' => $this->id, 'timestamp' => $db_ttl));
  }


  /**
   * Reads session data from cache or db, updates timestamp
   *
   * @handler
   *
   * @param int $session_id
   * @return mixed
   */
  public function handleRead($session_id)
  {
    $key = 'session:'. $session_id;
    $data = $this->cache->get($key, $success);
    if (!$success) {
      /* @var Mdogo_Model_Session $model */
      $model = new static::$model(array('session_id' => $session_id));
      if (isset($model->uid)) {
        $model->save(array(
          'timestamp' => max($model->timestamp, time() + $this->config['db_ttl'])
        ));
        $data = $model->data;

        $this->cache->set($key, $data, $this->config['cache_ttl']);
      }
    }
    return $data;
  }


  /**
   * Writes session data to db and cache
   *
   * @handler
   *
   * @param int $session_id
   * @param string $session_data
   * @return bool
   */
  public function handleWrite($session_id, $session_data)
  {
    $key = 'session:'. $session_id;
    $cached_data = $this->cache->get($key, $success);
    if (!$success || $session_data != $cached_data) {
      /* @var Mdogo_Model_Session $model */
      $model = new static::$model(array('session_id' => $session_id));
      $model->save(array(
        'session_id'  => $session_id,
        'data'        => $session_data,
        'timestamp'   => max($model->timestamp, time() + $this->config['db_ttl'])
      ));
      $this->cache->set($key, $session_data, $this->config['cache_ttl']);
    }
  }


  /**
   * Removes session from db and cache
   *
   * @handler
   *
   * @param int $session_id
   * @return bool
   */
  public function handleDestroy($session_id)
  {
    /* @var Mdogo_Model_Session $model */
    $model = new static::$model(array('session_id' => $session_id));
    $model->delete();

    $this->cache->remove('session:'. $session_id);

    return true;
  }


  /**
   * Cleans stale session data from db
   *
   * @handler
   *
   * @return bool
   */
  public static function handleCleanup()
  {
    /* @var Mdogo_Model_Session $class */
    $class = static::$model;
    array_call(
      $class::loadWhere('`timestamp`<=:time', array('time' => time())),
      'delete'
    );
  }
}


/*
 * MySQL schema
 *
CREATE TABLE `session` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(128) NOT NULL DEFAULT '',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `data` text,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `session_id` (`session_id`) USING BTREE,
  KEY `timestamp` (`timestamp`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8
 *
 */


/**
 * SQLite schema
 *
CREATE TABLE session (
  uid INTEGER PRIMARY KEY,
  session_id TEXT NOT NULL,
  timestamp INTEGER NOT NULL,
  data TEXT DEFAULT NULL
);
CREATE UNIQUE INDEX session_session_id ON session (session_id);
CREATE INDEX session_timestamp ON session (timestamp);
 *
 */
