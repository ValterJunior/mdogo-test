<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core-objects
 */


/**
 * Abstract base model
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
 * @subpackage core-objects
 */
abstract class Mdogo_Model extends MdogoObject implements MdogoModelInterface, ArrayAccess, Countable, IteratorAggregate, Serializable
{
  /**
   * Mdogo_Model requires a $uid as primary key
   *
   * @var int
   */
  public $uid;


  /**
   * Loads model from database if conditions are given
   *
   * @param mixed $conditions (optional)
   * @return Mdogo_Model
   */
  public function __construct($conditions = array())
  {
    if (!empty($conditions)) {
      $this->load($conditions);
    }
  }


  /**
   * Saves this instance to database
   *
   * @param array $values (optional)
   * @return void
   */
  public function save($values = array())
  {
    foreach ((array) $values as $key => $value) {
      $this->$key = $value;
    }
    $this->transcode(true);
    $this->validate();

    $values = $this->prepareValues(call_user_func('get_object_vars', $this));

    if (empty($values)) {
      $query = sprintf('INSERT INTO `%s`', $this->getTable());
    }
    elseif (empty($values['uid'])) {
      $query = sprintf(
        'INSERT INTO `%s` (`%s`) VALUES (:%s)',
        $this->getTable(),
        implode('`, `', array_keys($values)),
        implode(', :', array_keys($values))
      );
    }
    else {
      $query = sprintf(
        'UPDATE `%s` SET %s WHERE `uid`=:uid',
        $this->getTable(),
        $this->preparePlaceholders(array_diff_key($values, array('uid' => 0)), ', ')
      );
    }
    $database = $this->getDatabase(true);

    $statement = $database->prepare($query);
    $statement->execute($values);

    $this->load($this->uid ?: $database->lastInsertId());
  }


  /**
   * Deletes this instance from database
   *
   * @return void
   */
  public function delete()
  {
    if (isset($this->uid)) {
      $query = 'DELETE FROM `'. $this->getTable() .'` WHERE `uid`=:uid';

      $statement = $this->getDatabase(true)->prepare($query);
      $statement->execute(array('uid' => (int) $this->uid));

      foreach (array_keys(static::getColumns()) as $key) {
        $this->$key = null;
      }
    }
  }


  /**
   * Loads row from database into this object
   *
   * @param array|int $conditions (optional)
   * @param string $order (optional)
   * @return void
   */
  public function load($conditions = array(), $order = '')
  {
    if (empty($conditions)) {
      $conditions = array('uid' => (int) $this->uid);
    }
    elseif (is_numeric($conditions)) {
      $conditions = array('uid' => (int) $conditions);
    }
    $conditions = static::prepareValues((array) $conditions);

    $query = 'SELECT * FROM `'. $this->getTable() .'` ';
    if (!empty($conditions)) {
      $query .= 'WHERE '. static::preparePlaceholders($conditions) .' ';
    }
    if (!empty($order)) {
      $query .= 'ORDER BY '. $order .' ';
    }
    $query .= 'LIMIT 1';

    $statement = $this->getDatabase()->prepare($query);
    $statement->setFetchMode(PDO::FETCH_INTO, $this);
    $statement->execute($conditions);
    $statement->fetch();

    $this->transcode();
  }


  /**
   * Loads all records representing this model
   *
   * @param array $conditions (optional)
   * @param int $limit (optional)
   * @param string $order (optional)
   * @return array
   */
  public static function loadAll($conditions = array(), $limit = 0, $order = '')
  {
    $conditions = static::prepareValues((array) $conditions);

    $query = 'SELECT * FROM `'. static::getTable() .'` ';
    if (!empty($conditions)) {
      $query .= 'WHERE '. static::preparePlaceholders($conditions) .' ';
    }
    if (!empty($order)) {
      $query .= 'ORDER BY '. $order .' ';
    }
    if (!empty($limit)) {
      $query .= 'LIMIT '. $limit;
    }
    $statement = static::getDatabase()->prepare($query);
    $statement->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, get_called_class());
    $statement->execute($conditions);

    return array_map(
      function ($model) { $model->transcode(); return $model; },
      $statement->fetchAll()
    );
  }


  /**
   * Loads records matching sql condition
   *
   * @param string $where
   * @param array $conditions (optional)
   * @throws MdogoException
   * @return array
   */
  public static function loadWhere($where, $conditions = array())
  {
    $query = 'SELECT * FROM `'. static::getTable() .'` WHERE '. $where;

    $statement = static::getDatabase()->prepare($query);
    $statement->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, get_called_class());
    $statement->execute($conditions);

    return array_map(
      function ($model) { $model->transcode(); return $model; },
      $statement->fetchAll()
    );
  }


  /**
   * Loads number of records representing this model
   *
   * @param array $conditions (optional)
   * @return int
   */
  public static function countAll($conditions = array())
  {
    $conditions = static::prepareValues($conditions);

    $query = 'SELECT count(*) AS count FROM `'. static::getTable() .'` ';
    if (!empty($conditions)) {
      $query .= 'WHERE '. static::preparePlaceholders($conditions);
    }
    $statement = static::getDatabase()->prepare($query);
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $statement->execute($conditions);

    return array_get($statement->fetch(), 'count');
  }


  /**
   * Loads number of records matching sql condition
   *
   * @param string $where
   * @param array $conditions (optional)
   * @throws MdogoException
   * @return array
   */
  public static function countWhere($where, $conditions = array())
  {
    $query = 'SELECT count(*) AS count FROM `'. static::getTable() .'` WHERE '. $where;

    $statement = static::getDatabase()->prepare($query);
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $statement->execute($conditions);

    return array_get($statement->fetch(), 'count');
  }


  /**
   * Transcodes values before saving and after loading
   *
   * @param bool $decode (optional)
   * @return array
   */
  public function transcode($encode = false)
  {
    $definitions = static::prepareValues(
      (array) Mdogo::get('data')->get('model', static::getTable())
    );
    $transcoders = Mdogo::get('environment')->get(
      ($encode ? 'en' : 'de') .'coders'
    );
    foreach ($definitions as $key => $definition) {
      $type = array_get($definition, 'type');
      if ($type && array_key_exists($type, $transcoders)) {
        /* @noinspection PhpUsageOfSilenceOperatorInspection */
        $this->$key = @$transcoders[$type]($this->$key);
      }
    }
  }


  /**
   * Validates values before saving
   *
   * @param array $values
   * @throws MdogoException
   * @return void
   */
  public function validate()
  {
    $requirements = static::prepareValues(
      (array) Mdogo::get('data')->get('model', static::getTable())
    );
    foreach ($requirements as $key => $requirement) {
      if (!empty($requirement['required'])) {
        if (is_null($this->$key)) {
          throw new MdogoException('missing required value: '. $key);
        }
      }
      if (!empty($requirement['regex'])) {
        if (!preg_match("|{$requirement['regex']}|", $this->$key)) {
          throw new MdogoException('invalid value: '. $key);
        }
      }
      if (!empty($requirement['validator'])) {
        if (!call_user_func($requirement['validator'], $this->$key)) {
          throw new MdogoException('invalid value: '. $key);
        }
      }
    }
  }


  /**
   * Prepares values (or conditions) e.g. for saving
   *
   * @param array $values
   * @return array
   */
  protected static function prepareValues($values)
  {
    if (empty($values)) {
      return $values;
    }
    else {
      return array_intersect_key($values, static::getColumns());
    }
  }


  /**
   * Prepares placeholders for use in prepared statements
   *
   * @param array $values
   * @param string $separator
   * @return string
   */
  protected static function preparePlaceholders($values, $separator = ' AND ')
  {
    $columns = array();
    foreach ($values as $column => $value) {
      $columns[] = '`'. $column .'`=:'. $column;
    }
    return implode($separator, $columns);
  }


  /**
   * Gets columns from cache
   *
   * @param string|bool $table
   * @return string
   */
  public static function getColumns($table = false)
  {
    $table = $table ?: static::getTable();

    return static::getDatabase()->getColumns($table);
  }


  /**
   * Gets the table name to use
   *
   * @param bool|string $model (optional)
   * @throws MdogoException
   * @return string
   */
  public static function getTable($model = false)
  {
    $model = $model ?: get_called_class();

    if (defined($model .'::TABLE')) {
      $table = $model::TABLE;
    }
    else {
      $table = lcfirst(substr($model, strrpos($model, '_') + 1));
      if (!in_array($table, static::getDatabase()->getTables())) {
        throw new MdogoException('table not found: '. $table);
      }
    }
    return $table;
  }


  /**
   * Gets a child model name
   *
   * @param string $table
   * @throws MdogoException
   * @return string
   */
  public static function getModel($table)
  {
    $model = get_called_class() .'_'. ucfirst($table);

    if (class_exists($model, true)) {
      return $model;
    }
    else {
      throw new MdogoException('model not found: '. $model);
    }
  }


  /**
   * Gets the database to use
   *
   * @param bool $write
   * @return MdogoPDO
   */
  protected static function getDatabase($write = false)
  {
    return Mdogo::get('database')->open($write);
  }


  /**
   * Creates array of model properties
   *
   * @return array
   */
  public function &toArray()
  {
    $array = array();
    foreach (array_keys(static::getColumns()) as $key) {
      $array[$key] = &$this->$key;
    }
    return $array;
  }


  /**
   * Implement ArrayAccess
   */
  public function offsetExists($name)
  {
    return array_key_exists($name, $this->getColumns());
  }

  public function offsetGet($name)
  {
    if (!$this->offsetExists($name)) {
      throw new MdogoException('key not found: '. $name);
    }
    else {
      return $this->$name;
    }
  }

  public function offsetSet($name, $value)
  {
    if (!$this->offsetExists($name)) {
      throw new MdogoException('key not found: '. $name);
    }
    else {
      $this->$name = $value;
    }
  }

  public function offsetUnset($name)
  {
    if ($this->offsetExists($name)) {
      throw new MdogoException('key not found: '. $name);
    }
    unset($this->$name);
  }


  /**
   * Implements Countable
   */
  public function count()
  {
    return count($this->toArray());
  }


  /**
   * Implements IteratorAggregate
   */
  public function getIterator()
  {
    return new ArrayIterator($this->toArray());
  }


  /**
   * Implement Serializable
   */
  public function serialize()
  {
    return json_encode($this->toArray());
  }

  public function unserialize($json)
  {
    foreach (json_decode($json, true) as $key => $value) {
      $this->$key = $value;
    }
  }
}


/*
 * MySQL schema
 *
CREATE TABLE `example` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(128) NOT NULL DEFAULT '',
  `value` text,
  PRIMARY KEY (`uid`),
  KEY `key` (`key`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8
 *
 */


/**
 * SQLite schema
 *
CREATE TABLE example (
  uid INTEGER PRIMARY KEY,
  key TEXT NOT NULL,
  value TEXT DEFAULT NULL
);
CREATE INDEX example_key ON example (key);
 *
 */
