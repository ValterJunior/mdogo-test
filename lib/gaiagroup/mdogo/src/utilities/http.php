<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage utilities
 */


/**
 * Performs a http get request
 *
 * @param string $url
 * @param array $params (optional)
 * @param array $headers (optional)
 * @return string
 */
function http_get($url, $params = array(), $headers = array())
{
  $response = http_request($url, 'GET', $params, $headers);

  if (200 == $response['status']) {
    return $response['body'];
  }
  else {
    return false;
  }
}



/**
 * Performs a http post request
 *
 * @param string $url
 * @param array $body (optional)
 * @param array $headers (optional)
 * @return string
 */
function http_post($url, $body = array(), $headers = array())
{
  $response = http_request($url, 'POST', $body, $headers);

  if (200 == $response['status']) {
    return $response['body'];
  }
  elseif (201 == $response['status']) {
    return true;
  }
  else {
    return false;
  }
}



/**
 * Performs a http request using curl
 *
 * @param string $url
 * @param string $method (optional)
 * @param array $body (optional)
 * @param array $headers (optional)
 * @param array $options (optional)
 * @return array
 */
function http_request($url, $method = 'GET', $body = array(), $headers = array(), $options = array())
{
  return MdogoCURL::request($url, $method, $body, $headers, $options);
}



/**
 * Rather flexible cURL based request class
 *
 * @package mdogo
 * @subpackage utilities
 */
class MdogoCURL
{
  /**
   * @var resource
   */
  protected $handle;

  /**
   * @var string
   */
  protected $url;

  /**
   * @var string
   */
  protected $method = 'GET';

  /**
   * @var array
   */
  protected $headers = array(
    'Accept'        => '*/*',
    'Content-Type'  => 'application/x-www-form-urlencoded'
  );

  /**
   * @var array
   */
  protected $body = array();

  /**
   * @var MdogoArray
   */
  public $response = null;


  /**
   * Creates and executes curl object
   *
   * @param string $url
   * @param string $method (optional)
   * @param array $body (optional)
   * @param array $headers (optional)
   * @param array $options (optional)
   * @return array
   */
  public static function request($url, $method = 'GET', $body = array(), $headers = array(), $options = array())
  {
    $instance = static::make($url, $method, $body, $headers, $options);

    $instance->setCookieJar();

    return $instance->execute();
  }


  /**
   * Creates new curl object setting basic parameters
   *
   * @param string $url
   * @param string $method (optional)
   * @param array $body (optional)
   * @param array $headers (optional)
   * @param array $options (optional)
   * @return MdogoCURL
   */
  public static function make($url, $method = 'GET', $body = array(), $headers = array(), $options = array())
  {
    $method = strtoupper($method);

    $class = get_called_class();
    $instance = new $class($options);

    $instance->setURL($url);
    $instance->setMethod($method);
    $instance->setHeaders($headers);

    if (in_array($method, array('POST', 'PUT'))) {
      $instance->setBody($body);
    }
    else {
      if (is_array($body)) {
        $instance->setParams($body);
      }
    }
    return $instance;
  }


  /**
   * Accepts options, initializes curl
   *
   * @param array $options
   * @return MdogoCURL
   */
  public function __construct($options = array())
  {
    $this->handle = curl_init();

    curl_setopt_array($this->handle, array_override(array(
      CURLOPT_CONNECTTIMEOUT  => 30,
      CURLOPT_TIMEOUT         => 120,
      CURLOPT_MAXREDIRS       => 10,
      CURLOPT_FOLLOWLOCATION  => true,
      CURLOPT_RETURNTRANSFER  => true,
      CURLOPT_HEADER          => true,
      CURLOPT_SSL_VERIFYPEER  => true,
      CURLOPT_USERAGENT       => 'mdogo/curl'
    ), $options));
  }


  /**
   * Cleans up
   *
   * @return void
   */
  public function __destruct()
  {
    curl_close($this->handle);
  }


  /**
   * Set request cookie jar
   *
   * @param string $file (optional)
   * @throws Error
   * @return MdogoCURL
   */
  public function setCookieJar($file = null)
  {
    if (empty($file)) {
      if (!empty($this->url)) {
        $filename = '/'. str_replace('.', '-', get_host($this->url)) .'.txt';
        $file = Mdogo::get('environment')->get('cache_dir') . $filename;
      }
    }
    if (!empty($file) && (is_writable($file) || is_writable(get_dirname($file)))) {
      curl_setopt_array($this->handle, array(
        CURLOPT_COOKIEJAR   => $file,
        CURLOPT_COOKIEFILE  => $file
      ));
    }
    else {
      trigger_error('could not initialize cookie jar');
    }
    return $this;
  }


  /**
   * Set request username/password
   *
   * @param string $username
   * @param string $password
   * @param int $mode (optional)
   * @return MdogoCURL
   */
  public function setAuth($username, $password, $mode = CURLAUTH_ANY)
  {
    curl_setopt_array($this->handle, array(
      CURLOPT_HTTPAUTH  => $mode,
      CURLOPT_USERPWD   => "$username:$password"
    ));
    return $this;
  }


  /**
   * Set request url
   *
   * @param string $url
   * @throws Error
   * @return MdogoCURL
   */
  public function setURL($url)
  {
    if (!is_url($url)) {
      trigger_error('invalid url: '. $url);
    }
    $this->url = $url;

    return $this;
  }


  /**
   * Set/extend request params
   *
   * @param array $params
   * @return MdogoCURL
   */
  public function setParams($params)
  {
    if (!is_array($params)) {
      trigger_error('invalid params');
    }
    if (!empty($params)) {
      if (strinc($this->url, '?')) {
        $querystring = get_query($this->url);
        $query = http_parse_query($querystring);
        $this->url = str_replace("?$querystring", '', $this->url);
      }
      else {
        $query = array();
      }
      $this->url .= '?'. http_build_query(array_override($query, $params));
    }
    return $this;
  }


  /**
   * Set request method
   *
   * @param string $method
   * @return MdogoCURL
   */
  public function setMethod($method)
  {
    $method = strtoupper($method);
    $methods = array('GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS');

    if (!in_array($method, $methods)) {
      trigger_error('invalid method: '. $method);
    }
    $this->method = $method;

    return $this;
  }


  /**
   * Set request content type
   *
   * @param string $type
   * @return MdogoCURL
   */
  public function setType($type)
  {
    $content_type = Mdogo::get('environment')->get('mime', $type) ?: $type;
    $this->setHeaders(array(
      'Content-Type' => $content_type,
      'Accept'       => $content_type .',*/*;q=0.9'
    ));
    return $this;
  }


  /**
   * Set/extend request headers
   *
   * @param array $headers
   * @return MdogoCURL
   */
  public function setHeaders($headers)
  {
    $this->headers = array_override($this->headers, $headers);

    return $this;
  }


  /**
   * Set/extend request body
   *
   * @param mixed $body
   * @return MdogoCURL
   */
  public function setBody($body)
  {
    if (is_array($this->body) && is_array($body)) {
      $this->body = array_override($this->body, $body);
    }
    else {
      $this->body = $body;
    }
    return $this;
  }


  /**
   * Reset request body
   *
   * @param mixed $body
   * @return MdogoCURL
   */
  public function resetBody($body)
  {
    $this->body = $body;
    return $this;
  }


  /**
   * Actually perform curl request
   *
   * @throws Error
   * @return mixed
   */
  public function execute()
  {
    // Prepare request
    $headers = array();
    foreach($this->headers as $key => $value) {
      array_push($headers, "$key: $value");
    }
    curl_setopt_array($this->handle, array(
      CURLOPT_URL           => $this->url,
      CURLOPT_CUSTOMREQUEST => $this->method,
      CURLOPT_HTTPHEADER    => $headers
    ));
    if (in_array($this->method, array('POST', 'PUT'))) {
      curl_setopt($this->handle, CURLOPT_POSTFIELDS, $this->prepareBody());
    }
    // Excute request
    $raw = curl_exec($this->handle);
    $info = curl_getinfo($this->handle);
    $error = curl_error($this->handle);

    // Process response
    $headers = substr($raw, 0, $info['header_size']);
    $raw = substr($raw, -$info['size_download']);

    $data = array_merge(
      array(
        'status'  => $info['http_code'],
        'headers' => static::parseHeaders($headers),
        'body'    => static::parseBody($raw, $info['content_type'])
      ),
      compact('raw', 'info', 'error')
    );
    return $this->response = new MdogoArray($data, true);
  }


  /**
   * Encode request body
   *
   * @throws Error
   * @return mixed
   */
  protected function prepareBody()
  {
    $raw = $this->body;
    if (is_array($raw)) {
      $environment = Mdogo::get('environment');
      $type = array_get(
        array_flip($environment->get('mime')),
        array_get($this->headers, 'Content-Type')
      );
      $contains_files = array_any($raw, function ($value) {
        return is_string($value) && strstt($value, '@');
      });
      if ($contains_files) {
        if ('url' === $type) {
          $this->headers['Content-Type'] = 'multipart/form-data';
          return $raw;
        }
        else {
          foreach ($raw as &$value) {
            if (strstt($value, '@')) {
              $value = file_get_contents(substr($value, 1));
            }
          }
        }
      }
      $encoders = $environment->get('encoders');
      if (empty($type) || !array_key_exists($type, $encoders)) {
        trigger_error('unsupported encoding');
      }
      /* @noinspection PhpUsageOfSilenceOperatorInspection */
      return @$encoders[$type]($raw);
    }
    else {
      return $raw;
    }
  }


  /**
   * Decode response body data
   *
   * @param string $raw
   * @param array $headers
   * @throws Error
   * @return array
   */
  protected static function parseBody($raw, $content_type)
  {
    if (strinc($content_type, ';')) {
      $content_type = substr($content_type, 0, strpos($content_type, ';'));
    }
    if (!empty($raw)) {
      $environment = Mdogo::get('environment');
      $type = array_get(array_flip($environment->get('mime')), $content_type);
      $decoders = $environment->get('decoders');
      if (empty($type) || !array_key_exists($type, $decoders)) {
        return $raw;
      }
      /* @noinspection PhpUsageOfSilenceOperatorInspection */
      return @$decoders[$type]($raw);
    }
    else {
      return $raw;
    }
  }


  /**
   * Parse response headers
   * Based on http://php.net/manual/en/function.http-parse-headers.php
   *
   * @return array
   */
  protected static function parseHeaders($string)
  {
    $headers = preg_split('/\r?\n\r?\n/', $string, -1, PREG_SPLIT_NO_EMPTY);
    $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', end($headers)));
    $result = array();
    foreach ($fields as $field) {
      if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
        $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
        if (!isset($result[$match[1]])) {
          $result[$match[1]] = trim($match[2]);
        }
      }
    }
    return $result;
  }
}
