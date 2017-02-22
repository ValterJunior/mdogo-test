<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core-entities
 */


/**
 * Minimal pdo factory supporting r/w-splitting and load balancing
 *
 * @package mdogo
 * @subpackage core-entities
 */
class Mdogo_Database implements MdogoDatabaseInterface
{
  /**
   * @var MdogoEnvironment
   */
  protected $environment;

  /**
   * @var array
   */
  protected $connections = array();


  /**
   * Accepts dependencies
   *
   * @param MdogoEnvironmentInterface $environment
   * @return Mdogo_Database
   */
  public function __construct(MdogoEnvironmentInterface $environment)
  {
    $this->environment = $environment;
  }


  /**
   * Returns pdo ready for reading/writing
   *
   * @param bool $write (optional)
   * @param string $name (optional)
   * @throws MdogoException
   * @return PDO
   */
  public function open($write = true, $name = 'db')
  {
    $mode = $write ? 'write' : 'read';
    $connection = $name .'-'. $mode;

    if (!isset($this->connections[$connection])) {
      $config = $this->environment->get($name);
      $url = null;
      if (is_string(($config))) {
        $url = $config;
      }
      elseif (is_array($config)) {
        if (is_list($config)) {
          $url = $config[array_rand($config)];
        }
        elseif (isset($config[$mode])) {
          if (is_string(($config[$mode]))) {
            $url = $config[$mode];
          }
          elseif (is_list($config[$mode])) {
            $url = $config[$mode][array_rand($config[$mode])];
          }
        }
      }
      if (empty($url)) {
        throw new MdogoException('invalid database name');
      }
      $this->connections[$connection] = $this->connect($url);
    }
    return $this->connections[$connection];
  }


  /**
   * Instantiates mysql pdo using pseudo-url
   *
   * @param string $url
   * @throws MdogoException
   * @return PDO
   */
  public function connect($url)
  {
    switch(substr($url, 0, strpos($url, ':'))) {
      case 'mysql':
        return $this->connectMySQL($url);
      case 'sqlite':
        return $this->connectSQLite($url);
      default:
        throw new MdogoException('incompatible db engine');
    }
  }


  /**
   * Instantiates mysql pdo using pseudo-url
   *
   * @param string $url
   * @throws MdogoException
   * @return PDO
   */
  protected function connectMySQL($url)
  {
    if (!is_url($url)) {
      throw new MdogoException('invalid database url');
    }
    $config = array_override(
      array(
        'host' => 'localhost', 'path' => 'mdogo',
        'user' => null, 'pass' => null
      ),
      parse_url($url)
    );
    $dsn = sprintf(
      'mysql:host=%s%s;dbname=%s',
      $config['host'],
      isset($config['port']) ? ';port='. $config['port'] : '',
      trim($config['path'], '/')
    );
    return new MdogoMySQL($dsn, $config['user'], $config['pass'], array(
      PDO::ATTR_ERRMODE             => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES    => false,
      PDO::ATTR_STRINGIFY_FETCHES   => false,
      PDO::MYSQL_ATTR_INIT_COMMAND  => 'SET NAMES utf8'
    ));
  }


  /**
   * Instantiates sqlite pdo using dsn
   *
   * @param string $dsn
   * @return PDO
   */
  protected function connectSQLite($dsn)
  {
    return new MdogoSQLite($dsn, null, null, array(
      PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES  => false
    ));
  }
}



/**
 * Micro-wrapper around pdo
 *
 * @package mdogo
 * @subpackage core-entities
 */
class MdogoMySQL extends MdogoPDO
{
  /**
   * @var array
   */
  protected $statements;


  /**
   * Checks if db is supported
   *
   * @param string $dsn
   * @param string $username
   * @param string $password
   * @param array $driver_options
   * @throws MdogoException
   * @return MdogoMySQL
   */
  public function __construct($dsn, $username, $password, $driver_options)
  {
    parent::__construct($dsn, $username, $password, $driver_options);
    if ('mysql' !== $this->getAttribute(PDO::ATTR_DRIVER_NAME)) {
      throw new MdogoException('incompatible db engine');
    }
  }


  /**
   * Gets the tables in this db
   *
   * @return array
   */
  public function getTables()
  {
    $key = 'tables:'. $this->key;
    $tables = $this->cache->get($key, $success);
    if (!$success) {
      $tables = array();
      $query = 'SHOW TABLES';
      $statement = $this->prepare($query);
      $statement->execute();
      foreach ($statement->fetchAll(PDO::FETCH_NUM) as $table) {
        $tables[] = $table[0];
      }
      $this->cache->set($key, $tables, $this->ttl);
    }
    return $tables;
  }


  /**
   * Loads the columns in a table
   *
   * @param string $table
   * @return array
   */
  public function getColumns($table)
  {
    $key = 'columns:'. $this->key .':'. $table;
    $columns = $this->cache->get($key, $success);
    if (!$success) {
      $columns = array();
      $query = 'SHOW COLUMNS FROM `'. $table .'`';
      $statement = $this->prepare($query);
      $statement->execute();
      $columns = array();
      foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $columns[$column['Field']] = $column['Type'];
      }
      $this->cache->set($key, $columns, $this->ttl);
    }
    return $columns;
  }


  /**
   * Returns (cached) prepared statement
   *
   * @param string $statement
   * @param array $driver_options (optional)
   * @param array $values
   */
  public function prepare($statement, $driver_options = array())
  {
    $key = hash('md5', json_encode(func_get_args()));
    if (empty($this->statements[$key])) {
      $this->statements[$key] = call_user_func(
        'parent::prepare', $statement, $driver_options
      );
    }
    return $this->statements[$key];
  }
}



/**
 * Micro-wrapper around pdo
 *
 * @package mdogo
 * @subpackage core-entities
 */
class MdogoSQLite extends MdogoPDO
{
  /**
   * Checks if db is supported
   *
   * @param string $dsn
   * @param string $username
   * @param string $password
   * @param array $driver_options
   * @throws MdogoException
   * @return MdogoSQLite
   */
  public function __construct($dsn, $username, $password, $driver_options)
  {
    parent::__construct($dsn, $username, $password, $driver_options);
    if ('sqlite' !== $this->getAttribute(PDO::ATTR_DRIVER_NAME)) {
      throw new MdogoException('incompatible db engine');
    }
  }


  /**
   * Gets the tables in this db
   *
   * @return array
   */
  public function getTables()
  {
    $key = 'tables:'. $this->key;
    $tables = $this->cache->get($key, $success);
    if (!$success) {
      $tables = array();
      $query = 'SELECT name FROM sqlite_master WHERE `type`=:type';
      $statement = $this->prepare($query);
      $statement->execute(array('type' => 'table'));
      foreach ($statement->fetchAll(PDO::FETCH_NUM) as $table) {
        $tables[] = $table[0];
      }
      $this->cache->set($key, $tables, $this->ttl);
    }
    return $tables;
  }


  /**
   * Loads the columns in a table
   *
   * @param string $table
   * @return array
   */
  public function getColumns($table)
  {
    $key = 'columns:'. $this->key .':'. $table;
    $columns = $this->cache->get($key, $success);
    if (!$success) {
      $columns = array();
      $query = 'SELECT sql FROM sqlite_master WHERE `tbl_name`=:table AND `type`=:type';
      $statement = $this->prepare($query);
      $statement->execute(array('table' => $table, 'type' => 'table'));
      $result = $statement->fetch(PDO::FETCH_ASSOC);
      preg_match_all(
        '/^(\s+|\s*")(.*?)"?\s+(.*?)(,|\s).*$/m',
        $result['sql'], $matches
      );
      $columns = array_combine($matches[2], $matches[3]);
      $this->cache->set($key, $columns, $this->ttl);
    }
    return $columns;
  }
}



/**
 * Micro-wrapper around pdo
 *
 * @package mdogo
 * @subpackage core-entities
 */
abstract class MdogoPDO extends PDO
{
  /**
   * @var Mdogo_Cache
   */
  protected $cache;

  /**
   * @var int
   */
  protected $ttl;

  /**
   * @var string
   */
  protected $key;


  /**
   * Sets up custom pdo
   *
   * @param string $dsn
   * @param string $username
   * @param string $password
   * @param array $driver_options
   * @throws MdogoException
   * @return MdogoSQLite
   */
  public function __construct($dsn, $username, $password, $driver_options)
  {
    $this->cache = Mdogo::get('cache');
    $this->ttl = Mdogo::get('environment')->get('config_ttl');
    $this->key = hash('md5', json_encode(func_get_args()));

    parent::__construct($dsn, $username, $password, $driver_options);
  }


  /**
   * Gets the tables in this db
   *
   * @return array
   */
  abstract public function getTables();


  /**
   * Loads the columns in a table
   *
   * @param string $table
   * @return array
   */
  abstract public function getColumns($table);
}
