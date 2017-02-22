<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage controllers
 */


/**
 * Aggregating/minifying css/js controller
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
 * @subpackage controllers
 */
class Mdogo_Controller_Script extends Mdogo_Controller_File
{
  /**
   * @var bool
   */
  protected $minify;

  /**
   * @var string
   */
  protected $extension;

  /**
   * @var string
   */
  protected $source_file;

  /**
   * @var string
   */
  protected $source_dir;


  /**
   * Accepts request method/path
   *
   * @param string &$method
   * @param string &$path
   * @return void
   */
  public function bootstrap(&$method, &$path)
  {
    parent::bootstrap($method, $path);
    $this->extension = get_extension($this->path);
  }


  /**
   * Maps url paths to fs paths, checks if we have a folder
   *
   * @throws MdogoNotFoundException
   * @return void
   */
  protected function resolve()
  {
    $path = str_replace('.min.', '.', $this->path, $this->minify);

    if (is_file($path = MDOGO_ROOT .'/'. $this->basepath .'/'. $path)) {
      $this->source_file = $path;
    }
    elseif (is_dir($path = get_dirname($path) .'/'. get_filename($path))) {
      $this->source_dir = $path;
    }
    else {
      throw new MdogoNotFoundException();
    }
    $this->process();

    if (!is_file($this->file)) {
      throw new MdogoNotFoundException();
    }
  }


  /**
   * Processes source file
   *
   * @throws MdogoException
   * @return void
   */
  protected function process()
  {
    if (!$this->minify && !empty($this->source_file)) {
      $this->file = $this->source_file;
    }
    else {
      $this->file = $this->environment->get('cache_dir') .'/'. $this->path;
      if (!is_file($this->file) || filemtime($this->file) < $this->getMtime()) {
        mkdirp(get_dirname($this->file));
        file_put_contents(
          $this->file,
          $this->minify ? $this->getMinified() : $this->getRaw()
        );
      }
    }
  }


  /**
   * Minifies file/dir contents
   *
   * @throws MdogoException
   * @return string
   */
  protected function getMinified()
  {
    $source = $this->getRaw();
    if (empty($source)) { return ' '; }

    if ('css' === $this->extension) {
      $minified = $this->minifyCSS($source);
    }
    else {
      $minified = $this->minifyJS($source);
    }
    return $minified;
  }


  /**
   * Minifies CSS source code
   *
   * @param string $source
   * @return string
   */
  protected function minifyCSS($source)
  {
    return trim(preg_replace(
      array('/\/\*.*?\*\//s', '/\s+/', '/\s?([,:;{}])\s?/'),
      array('', ' ', '$1'),
      $source
    ));
  }


  /**
   * Minifies JS source code
   *
   * @param string $source
   * @throws MdogoException
   * @return string
   */
  protected function minifyJS($source)
  {
    $source = trim($source);
    if (!strlen($source)) {
      return $source;
    }
    $response = http_post(
      'https://closure-compiler.appspot.com/compile',
      array('js_code' => $source, 'output_info' => 'compiled_code', 'language' => 'ECMASCRIPT5')
    );
    if (!$response || !strlen(trim($response))) {
      throw new MdogoException('could not minify script.');
    }
    return $response;
  }


  /**
   * Gets file/dir contents
   *
   * @return string
   */
  protected function getRaw()
  {
    if (!empty($this->source_dir)) {
      $source = dir_get_contents($this->source_dir, $this->extension);
    }
    else {
      $source = file_get_contents($this->source_file);
    }
    return $source;
  }


  /**
   * Gets file/dir mtime
   *
   * @return string
   */
  protected function getMtime()
  {
    if (!empty($this->source_dir)) {
      $mtime = dirmtime($this->source_dir, $this->extension);
    }
    else {
      $mtime = filemtime($this->source_file);
    }
    return $mtime;
  }
}
