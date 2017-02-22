<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage interfaces
 */


/**
 * Abstract base class adding a little convenience
 *
 * @package mdogo
 * @subpackage core
 */
interface MdogoObjectInterface
{
  /**
   * Returns object method as closure
   *
   * @param string $method
   * @param mixed ... (optional)
   * @return MdogoClosureInterface
   */
  public function toClosure($method);

  /**
   * Creates array of public properties
   *
   * @return array
   */
  public function toArray();
}



/**
 * Abstract base class adding magic array methods and array-like behavior
 *
 * @package mdogo
 * @subpackage core
 */
interface MdogoArrayInterface
{
  /**
   * Traverses array using provided arguments and checks if key exists
   *
   * @param array $arguments
   * @param ...   $argument (optional)
   * @return mixed
   */
  public function has($arguments);

  /**
   * Traverses data using provided arguments
   *
   * @param array $arguments
   * @param ...   $argument (optional)
   * @return mixed
   */
  public function get($arguments);

  /**
   * Traverses array using provided arguments and sets value
   *
   * @param array $arguments
   * @param ...   $argument (optional)
   * @param mixed $value
   * @return mixed
   */
  public function set($arguments, $value);

  /**
   * Traverses array using provided arguments and removes value
   *
   * @param array $arguments
   * @param ...   $argument (optional)
   * @return void
   */
  public function remove($arguments);

  /**
   * Checks if this array is immutable
   *
   * @return bool
   */
  public function isImmutable();

  /**
   * Returns data as array
   *
   * @return array
   */
  public function &toArray();

  /**
   * Returns object method as closure
   *
   * @param string $method
   * @param mixed ... (optional)
   * @return MdogoClosureInterface
   */
  public function toClosure($method);
}




/**
 * Simple closure class
 *
 * @package mdogo
 * @subpackage core
 */
interface MdogoClosureInterface
{}



/**
 * Message broker (i.e. event dispatcher)
 *
 * @package mdogo
 * @subpackage interfaces
 */
interface MdogoBrokerInterface
{
  /**
   * Calls all callbacks subscribed to a topic
   *
   * @param string $topic
   * @param array $arguments (optional)
   * @return void
   */
  public function publish($topic, $arguments = array());

  /**
   * Subscribes callback to topic
   *
   * @param string $topic
   * @param callback $callback
   * @param object $context (optional)
   * @return void
   */
  public function subscribe($topic, $callback, $context = null);

  /**
   * Unsubscribes callback(s) from topic
   *
   * @param string $topic
   * @param callable $callback (optional)
   * @param object $context (optional)
   * @return void
   */
  public function unsubscribe($topic, $callback = null, $context = null);
}



/**
 * Simple cache
 *
 * @package mdogo
 * @subpackage interfaces
 */
interface MdogoCacheInterface extends MdogoArrayInterface
{
  /**
   * Checks if data exists in cache
   *
   * @param string $key
   * @param int &$mtime (optional)
   */
  public function has($key, &$mtime = false);

  /**
   * Gets data from cache
   *
   * @param string $key
   * @param bool &$success (optional)
   * @param bool|int $mtime (optional)
   * @return mixed
   */
  public function get($key, &$success = false, &$mtime = false);

  /**
   * Stores data in cache
   *
   * @param string $key
   * @param mixed $value
   * @param int $ttl (optional)
   * @return mixed
   */
  public function set($key, $value, $ttl = 0);

  /**
   * Removes data from cache
   *
   * @param string $key
   * @return mixed
   */
  public function remove($key);
}



/**
 * Abstract base collection
 *
 * @package mdogo
 * @subpackage interfaces
 */
interface MdogoCollectionInterface extends MdogoArrayInterface
{
  /**
   * Sets up data
   *
   * @return Mdogo_Collection
   */
  public function __construct();

  /**
   * Loads all records
   *
   * @return array
   */
  public function load();

  /**
   * Loads count of all records matched by condition
   *
   * @return void
   */
  public function count();

  /**
   * Exports models as csv or xml
   *
   * @param string $format (optional)
   * @param bool $header (optional)
   * @param string $delimiter (optional)
   * @param string $enclosure (optional)
   * @throws MdogoException
   * @return array
   */
  public function export($format = 'csv');
}



/**
 * Request controller
 *
 * @package mdogo
 * @subpackage interfaces
 */
interface MdogoControllerInterface extends MdogoObjectInterface
{
  /**
   * Accepts request method/path
   *
   * @param string &$method
   * @param string &$path
   * @return void
   */
  public function bootstrap(&$method, &$path);

  /**
   * Prepares a response object
   *
   * @return Mdogo_Response
   */
  public function respond();
}



/**
 * Data provider and json-to-array converter
 *
 * @package mdogo
 * @subpackage interfaces
 */
interface MdogoDataInterface extends MdogoArrayInterface
{}



/**
 * Minimal pdo manager
 *
 * @package mdogo
 * @subpackage interface
 */
interface MdogoDatabaseInterface
{
  /**
   * Returns pdo ready for reading/writing
   *
   * @param bool $write (optional)
   * @param string $name (optional)
   * @return PDO
   */
  public function open($write = true, $name = 'db');
}



/**
 * Request dispatcher / front controller
 *
 * @package mdogo
 * @subpackage interfaces
 */
interface MdogoDispatcherInterface
{
  /**
   * Dispatches request processing
   *
   * @handler
   *
   * @param MdogoRequestInterface $request
   * @return void
   */
  public function handleRequest(MdogoRequestInterface $request);
}



/**
 * Configuration container
 *
 * @package mdogo
 * @subpackage interfaces
 */
interface MdogoEnvironmentInterface extends MdogoArrayInterface
{
  /**
   * Routes in property values using key
   *
   * @param string $property
   * @param string $key
   * @return mixed
   */
  public function resolve($property, $key);
}



/**
 * Configurable logger and error handler
 *
 * @package mdogo
 * @subpackage interfaces
 */
interface MdogoLoggerInterface
{
  /**
   * Writes a message to a file in configured log dir
   *
   * @param string $message
   * @param string $type (optional)
   * @return void
   */
  public function log($message, $type = 'debug');

  /**
   * Composes error message, writes to error log
   *
   * @handler
   *
   * @param Exception $exception
   * @return void
   */
  public function handleException(Exception $exception);

  /**
   * Initiates writing to {access|error} log
   *
   * @handler
   *
   * @return void
   */
  public function handleShutdown();
}



/**
 * Base model
 *
 * @package mdogo
 * @subpackage interfaces
 */
interface MdogoModelInterface extends MdogoObjectInterface
{
  /**
   * Mdogo_Model requires a $uid as primary key
   *
   * @var int
   */
  // public $uid;

  /**
   * Loads model from database if conditions are given
   *
   * @param mixed $conditions (optional)
   * @return MdogoModelInterface
   */
  public function __construct($conditions = array());

  /**
   * Saves this instance to database
   *
   * @param array $values (optional)
   * @return void
   */
  public function save($values = array());

  /**
   * Deletes this instance from database
   *
   * @return void
   */
  public function delete();

  /**
   * Loads row from database into this object
   *
   * @param mixed $conditions (optional)
   * @param string $order (optional)
   * @return void
   */
  public function load($conditions = array(), $order = '');

  /**
   * Loads all records representing this model
   *
   * @param array $conditions (optional)
   * @param int $limit (optional)
   * @param string $order (optional)
   * @return array
   */
  // public static function loadAll($conditions = array(), $limit = 0, $order = '');

  /**
   * Loads records matching sql condition
   *
   * @param string $where
   * @param array $conditions (optional)
   * @throws MdogoException
   * @return array
   */
  // public static function loadWhere($where, $conditions = array());

  /**
   * Loads number of records representing this model
   *
   * @param array $conditions (optional)
   * @return int
   */
  // public static function countAll($conditions = array());

  /**
   * Loads number of records matching sql condition
   *
   * @param string $where
   * @param array $conditions (optional)
   * @throws MdogoException
   * @return array
   */
  // public static function countWhere($where, $conditions = array());

  /**
   * Gets the table name to use
   *
   * @param string $class (optional)
   * @return string
   */
  // public static function getTable($class = false);

  /**
   * Gets a child model name
   *
   * @param string $table
   * @return string
   */
  // public static function getModel($table);
}



/**
 * Filtered request data
 *
 * @package mdogo
 * @subpackage interfaces
 */
interface MdogoRequestInterface extends MdogoArrayInterface
{
  /**
   * Gets value as bool
   *
   * @param ... $argument (optional)
   * @return bool
   */
  public function getBool();

  /**
   * Gets value as int
   *
   * @param ... $argument (optional)
   * @return int
   */
  public function getInt();

  /**
   * Gets value as float
   *
   * @param ... $argument (optional)
   * @return float
   */
  public function getFloat();

  /**
   * Gets value as string with html stripped
   *
   * @param ... $argument (optional)
   * @return string
   */
  public function getString();

  /**
   * Gets value as string safe for use as sql identifier
   *
   * @param ... $argument (optional)
   * @return string
   */
  public function getSafe();

  /**
   * Gets real client ip - optionally anonymized
   *
   * @param bool $anonymize (optional)
   * @return string
   */
  public function getClient($anonymize = false);

  /**
   * Gets format preferences
   *
   * @return array
   */
  public function getFormats();

  /**
   * Gets language preferences
   *
   * @return array
   */
  public function getCharsets();

  /**
   * Gets language preferences
   *
   * @return array
   */
  public function getLocales();
}



/**
 * Serializable/cacheable response
 *
 * @package mdogo
 * @subpackage interfaces
 */
interface MdogoResponseInterface extends MdogoArrayInterface
{
  /**
   * Sets response parameters
   *
   * @param mixed $body
   * @param bool|string $type (optional)
   * @param int $status (optional)
   * @return void
   */
  public function setBody($body, $type = false, $status = 200);

  /**
   * Sends headers and contents to browser
   *
   * @return void
   */
  public function deliver();
}



/**
 * Session handler
 *
 * @package mdogo
 * @subpackage interfaces
 */
interface MdogoSessionInterface extends MdogoArrayInterface
{
  /**
   * Regenerates session id
   *
   * @return void
   */
  public function regenerate();

  /**
   * Destroys current session
   *
   * @return void
   */
  public function destroy();

  /**
   * Gets csrf protection token
   *
   * @return string
   */
  public function getToken();

  /**
   * Changes cookie and db ttls
   *
   * @param int $ttl (optional)
   * @return void
   */
  public function setTTL($ttl = null);
}



/**
 * Minimal view
 *
 * @package mdogo
 * @subpackage interfaces
 */
interface MdogoViewInterface extends MdogoObjectInterface
{
  /**
   * Sets up template string
   *
   * @param bool|string $template
   * @return MdogoViewInterface
   */
  public function __construct($template = false);

  /**
   * Renders template using supplied values
   *
   * @param mixed $values (optional)
   * @return string
   */
  public function render($values = array());
}
