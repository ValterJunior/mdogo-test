<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core-entities
 */


/**
 * Serializable/cacheable response - cache before delivery!
 *
 * @property MdogoEnvironment $environment
 * @property Mdogo_Broker     $broker
 * @property Mdogo_Cache      $cache
 * @property Mdogo_Database   $database
 * @property Mdogo_Logger     $logger
 * @property Mdogo_Request    $request
 * @property Mdogo_Response   $response
 * @property Mdogo_Session    $session
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
class Mdogo_Response extends MdogoArray implements MdogoResponseInterface, Serializable
{
  /**
   * @var int
   */
  public $ttl;


  /**
   * Initialize ttl to default
   *
   * @param MdogoEnvironmentInterface $environment
   * @return Mdogo_Response
   */
  public function __construct(MdogoEnvironmentInterface $environment)
  {
    $this->ttl = $environment->get('response_ttl');
    parent::__construct();
  }


  /**
   * Magically adds Mdogo entities
   *
   * @param string $name
   * @throws MdogoException
   * @return mixed
   */
  public function __get($name)
  {
    if (Mdogo::acknowledge($name)) {
      return Mdogo::get($name);
    }
    else {
      return parent::__get($name);
    }
  }


  /**
   * Magically adds Mdogo entities
   *
   * @param string $name
   * @return bool
   */
  public function __isset($name)
  {
    return Mdogo::acknowledge($name) || parent::__isset($name);
  }


  /**
   * Sets response parameters
   *
   * @param mixed $body
   * @param bool|string $type (optional)
   * @param int $status (optional)
   * @param array $headers (optional)
   * @return void
   */
  public function setBody($body, $type = false, $status = 200, $headers = array())
  {
    if (is_stream($body)) {
      $body = array_get(stream_get_meta_data($body), 'uri');
    }
    if (!is_string($body)) {
      $raw = $body;
      unset($body);
    }
    elseif (is_filename($body)) {
      $file = $body;
      $size = filesize($body);
      $hash = hash_file('md5', $body);
      $type = $type ?: get_type(get_info($body));
      unset($body);
    }
    else {
      $size = strlen($body);
      $hash = hash('md5', $body);
      $type = $type ?: get_type(get_info($body));
    }
    $mime = $this->environment->get('mime', $type) ?: 'application/octet-stream';
    $expires = $this->environment->get('expires', $type) ?: 0;

    $this->data = get_defined_vars();
  }


  /**
   * Sends headers and contents to browser
   *
   * @return void
   */
  public function deliver()
  {
    if (!headers_sent()) {
      $headers = array_merge(
        $this->environment->get('headers'),
        array('X-Request-ID' => $this->request->get('rid'))
      );
      $method = $this->request->get('server', 'request_method');
      $deliver_body = ('HEAD' !== $method) || ($this->data['status'] === 204);

      if (array_key_exists('raw', $this->data)) {
        $type = array_get($this->data, 'type', false);
        if (empty($type)) {
          $headers['Vary'] = 'Accept';
        }
        $this->setBody(
          $this->encode($type), $type,
          $this->data['status'], $this->data['headers']
        );
      }
      $this->setRedirectHeaders($headers, $deliver_body);
      $this->setCacheHeaders($headers, $method);
      $this->setEtagHeaders($headers, $deliver_body);
      $this->setSendfileHeaders($headers, $deliver_body);
      $this->setContentHeaders($headers);

      $this->deliverHeaders(array_override($headers, $this->data['headers']));
      if ($deliver_body) {
        $this->deliverBody();
      }
    }
  }


  /**
   * Sends http status, etag and other headers to browser
   *
   * @param array $headers
   * @return void
   */
  protected function deliverHeaders($headers)
  {
    $status = $this->data['status'];
    header('HTTP/1.1 '. $status .' '. $this->environment->get('status', $status));
    foreach ($headers as $header => $value) {
      header($header .': '. $value);
    }
  }


  /**
   * Delivers body to browser
   *
   * @return void
   */
  protected function deliverBody()
  {
    if (isset($this->data['file'])) {
      readfile($this->data['file']);
    }
    else {
      echo $this->data['body'];
    }
  }


  /**
   * Conditionally adds redirect headers
   *
   * @param array &$headers
   * @param bool &$deliver_body
   * @return void
   */
  protected function setRedirectHeaders(&$headers, &$deliver_body)
  {
    if (in_array($this->data['status'], array_merge(range(301, 303), range(305, 308)))) {
      $headers['Location'] = $this->data['body'] ?: '/';
      $this->data['size'] = 0;
      $deliver_body = false;
    }
  }


  /**
   * Conditionally adds cache-control / expires headers
   *
   * @param array &$headers
   * @param string $method
   * @return void
   */
  protected function setCacheHeaders(&$headers, $method)
  {
    if ($this->isUncacheable($method)) {
      $headers['Cache-Control'] = 'private, no-cache, no-store, must-revalidate, max-age=0, pre-check=0, post-check=0';
    }
    elseif (empty($this->data['expires'])) {
      $headers['Cache-Control'] = 'public, must-revalidate';
    }
    else {
      $time = strtotime($this->data['expires']);
      $headers['Cache-Control'] = 'public, max-age='. max(0, $time - time());
      $headers['Expires'] = gmdate('D, d M Y H:i:s T', $time);
    }
  }


  /**
   * Determines cacheability
   *
   * @param string $method
   * @return bool
   */
  protected function isUncacheable($method)
  {
    return -1 == $this->data['expires']
           || !in_array($method, array('GET', 'HEAD'))
           || strinc(implode(headers_list()), 'Set-Cookie:')
           || $this->environment->get('debug');
  }


  /**
   * Conditionally adds etag header and 304 status
   *
   * @param array &$headers
   * @param bool &$deliver_body
   * @return void
   */
  protected function setEtagHeaders(&$headers, &$deliver_body)
  {
    if (!empty($this->data['hash'])) {
      $request_etag = $this->request->get('server', 'http_if_none_match');
      if (!empty($request_etag)) {
        if ($this->data['hash'] === str_replace('&#34;', '', $request_etag)) {
          $this->data['status'] = 304;
          $this->data['size'] = 0;
          $deliver_body = false;
        }
      }
      $headers['Etag'] = '"'. $this->data['hash'] .'"';
    }
  }


  /**
   * Conditionally adds apache / nginx sendfile headers
   *
   * @param array &$headers
   * @param bool &$deliver_body
   * @return void
   */
  protected function setSendfileHeaders(&$headers, &$deliver_body)
  {
    $sendfile = $this->environment->get('sendfile');
    $file = array_get($this->data, 'file');
    if ($deliver_body && $sendfile && $file) {
      if ('nginx' === $sendfile) {
        $headers['X-Accel-Redirect'] = str_replace(MDOGO_ROOT, '', $file);
      }
      else {
        $headers['X-Sendfile'] = $file;
      }
      $deliver_body = false;
    }
  }


  /**
   * Adds content metadata headers
   *
   * @param array &$headers
   * @throws MdogoNotAcceptableException
   * @return void
   */
  protected function setContentHeaders(&$headers)
  {
    $mime = $this->data['mime'];
    if (300 > $this->data['status']) {
      $formats = array_keys($this->request->getFormats());
      if (empty($formats)) {
        $formats = array('*/*');
      }
      if (!in_array($mime, $formats) && !in_array('*/*', $formats)) {
        throw new MdogoNotAcceptableException('unsupported content type');
      }
      if (strstt($mime, 'text/')) {
        $charsets = array_keys($this->request->getCharsets());
        if (empty($charsets)) {
          $charsets = array('*');
        }
        if (!in_array('utf-8', $charsets) && !in_array('*', $charsets)) {
          throw new MdogoNotAcceptableException('unsupported charset');
        }
        $mime .= '; charset=utf-8';
      }
    }
    $headers['Content-Type'] = $mime;
    $headers['Content-Length'] = $this->data['size'];
  }


  /**
   * Negotiates encoding and encodes raw content
   *
   * @param bool|string $type
   * @throws MdogoNotAcceptableException
   * @return string
   */
  protected function encode(&$type = false)
  {
    if (empty($type)) {
      $formats = array_keys($this->request->getFormats());
      if (empty($formats)) {
        $formats = array('*/*');
      }
      $types = array_flip($this->environment->get('mime'));
      if (array_key_exists($this->data['mime'], $types)) {
        $types += array('*/*' => $types[$this->data['mime']]);
      }
      foreach ($formats as $format) {
        if (array_key_exists($format, $types)) {
          $type = $types[$format]; break;
        }
      }
    }
    $encoders = $this->environment->get('encoders');
    if (empty($type) || !array_key_exists($type, $encoders)) {
      throw new MdogoNotAcceptableException('unsupported output encoding');
    }
    /* @noinspection PhpUsageOfSilenceOperatorInspection */
    return @$encoders[$type]($this->data['raw']);
  }


  /**
   * Implement Serializable
   */
  public function serialize()
  {
    return json_encode(get_object_vars($this));
  }

  public function unserialize($json)
  {
    foreach (json_decode($json, true) as $key => $value) {
      $this->$key = $value;
    }
  }
}
