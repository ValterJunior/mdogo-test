<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core-entities
 */


/**
 * Filtered request data
 *
 * @method bool all($callable)
 * @method bool any($callable)
 * @method bool contains($value)
 * @method mixed find($callable)
 * @method mixed where($conditions)
 * @method mixed reduce($callable, $result)
 * @method array map($callable)
 * @method array walk($callable)
 * @method array diff($array)
 * @method array merge($array)
 * ...
 *
 * @package mdogo
 * @subpackage core-entities
 */
class Mdogo_Request extends MdogoArray implements MdogoRequestInterface
{
  /**
   * @var MdogoEnvironment
   */
  protected $environment;

  /**
   * @var string
   */
  protected $input;


  /**
   * Accepts dependencies
   *
   * @param MdogoEnvironmentInterface $environment
   * @return Mdogo_Request
   */
  public function __construct(MdogoEnvironmentInterface $environment)
  {
    $this->environment = $environment;
    $this->input = file_get_contents('php://input');

    $data = $this->process();
    parent::__construct($data, true);

    if (in_array($data['method'], array('POST', 'PUT'))) {
      $data['body'] = $this->decode();
    }

  }


  /**
   * Gets value as bool
   *
   * @param ... $argument (optional)
   * @return bool
   */
  public function getBool()
  {
    return !!($this->get(func_get_args()));
  }


  /**
   * Gets value as int
   *
   * @param ... $argument (optional)
   * @return int
   */
  public function getInt()
  {
    return intval($this->get(func_get_args()));
  }


  /**
   * Gets value as float
   *
   * @param ... $argument (optional)
   * @return float
   */
  public function getFloat()
  {
    return floatval($this->get(func_get_args()));
  }


  /**
   * Gets value as string with html stripped
   *
   * @param ... $argument (optional)
   * @return string
   */
  public function getString()
  {
    return filter_var($this->get(func_get_args()), FILTER_SANITIZE_STRING);
  }


  /**
   * Gets value as string safe for use as sql identifier
   * Attention: string still needs to be escaped in backticks!
   *
   * @param ... $argument (optional)
   * @return string
   */
  public function getSafe()
  {
    return sanitize($this->get(func_get_args()));
  }


  /**
   * Gets transmitted files
   *
   * @throws MdogoException
   * @return array
   */
  public function getFiles()
  {
    $data = array();
    if (!empty($_FILES)) {
      $basedir = $this->environment->get('cache_dir') .'/uploads/';
      mkdirp($basedir);
      foreach ($_FILES as $key => $files) {
        $data[$key] = array();
        foreach ((array) $files as $file) {
          $file_path = $basedir . sanitize(basename($file['name']));
          /* @noinspection PhpUsageOfSilenceOperatorInspection */
          if (@move_uploaded_file($file['tmp_name'], $file_path)) {
            $data[$key][] = $file_path;
          }
          else {
            throw new MdogoException('could not process file upload');
          }
        }
      }
    }
    return $data;
  }


  /**
   * Gets real client ip - optionally anonymized
   *
   * @param bool $anonymize (optional)
   * @return string
   */
  public function getClient($anonymize = false)
  {
    if ($for = $this->get('server', 'http_x_forwarded_for')) {
      $client = trim(array_first(explode(',', $for)));
    }
    if (empty($client)) {
      $client = $this->get('server', 'remote_addr') ?: '0.0.0.0';
    }
    if ($anonymize) {
      if ($pos = strrpos($client, '.')) {
        $client = substr($client, 0, $pos).'.XXX';
      }
      else {
        $client = 'XXX';
      }
    }
    return $client;
  }


  /**
   * Gets format preferences
   *
   * @return array
   */
  public function getFormats()
  {
    return $this->getQualified(
      $this->get('server', 'http_accept'),
      '/([a-z\*\-\.]+\/([a-z\*\-\.]+)).*?(;\s*q=([0-9\.]*))?/i'
    );
  }


  /**
   * Gets language preferences
   *
   * @return array
   */
  public function getCharsets()
  {
    return $this->getQualified(
      $this->get('server', 'http_accept_charset'),
      '/(\*|([a-z]{3}[a-z0-9\-]*)).*?(;\s*q=([0-9\.]*))?/i'
    );
  }


  /**
   * Gets language preferences
   *
   * @return array
   */
  public function getLocales()
  {
    return $this->getQualified(
      $this->get('server', 'http_accept_language'),
      '/([a-z]{2}(-[a-z]+)?).*?(;\s*q=([0-9\.]*))?/i'
    );
  }


  /**
   * Gets weighted preferences
   *
   * @param string $string
   * @param string $regex
   * @return array
   */
  public function getQualified($string, $regex)
  {
    preg_match_all($regex, $string, $matches);
    $results = array();
    if (!empty($matches[1])) {
      $options = array_combine($matches[1], $matches[4]);
      foreach ($options as $option => $priority) {
        $results[$option] = (float) ($priority ?: 1);
      }
      arsort($results, SORT_NUMERIC);
    }
    return $results;
  }


  /**
   * Detects encoding and decodes body (default: urlencoded)
   *
   * @param bool|string $type
   * @throws MdogoUnsupportedMediaTypeException
   * @return mixed
   */
  protected function decode(&$type = false)
  {
      //Mdogo::get('logger')->log(json_encode($this->get('server')));
    if (empty($type)) {
      //$format = $this->get('server', 'content_type');
      $format = $this->get('server', 'http_content_type');
      if (empty($format)) {
        $format = '*/*';
      }
      elseif (false !== ($pos = strpos($format, ';'))) {
        $format = substr($format, 0, $pos);
      }
      $types = array_flip($this->environment->get('mime'));
      $types += array('*/*' => 'url');
      if (array_key_exists($format, $types)) {
        $type = $types[$format];
      }
    }
    $decoders = $this->environment->get('decoders');
    if (empty($type) || !array_key_exists($type, $decoders)) {
      throw new MdogoUnsupportedMediaTypeException('unsupported input encoding');
    }
    /* @noinspection PhpUsageOfSilenceOperatorInspection */
    return @$decoders[$type]($this->input);
  }


  /**
   * Collects, processes and filters request data
   *
   * @return array
   */
  protected function process()
  {
    $data = array(
      'server' => array_change_key_case(
        (array) filter_input_array(INPUT_SERVER, FILTER_SANITIZE_STRING)
      ),
      'cookie' => array_change_key_case(
        (array) filter_input_array(INPUT_COOKIE, FILTER_SANITIZE_STRING)
      ),
      'params' => array_change_key_case(
        (array) filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING)
      )
    );
    
    $data['method'] = strtoupper(array_get($data['server'], 'request_method'));
    $data['path'] = trim(get_path(array_get($data['server'], 'request_uri')), '/');
    $data['hash'] = hash('md5', $data['method'] . array_get($data['server'], 'request_uri'));
    $data['rid'] = uniqid(substr($data['hash'], 0, 7));

    return $data;
  }
}
