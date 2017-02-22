<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage controllers
 */


/**
 * Image controller
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
class Mdogo_Controller_Image extends Mdogo_Controller_File
{
  /**
   * @var string
   */
  protected $extension;

  /**
   * @var string
   */
  protected $source;

  /**
   * @var string
   */
  protected $mode;

  /**
   * @var int
   */
  protected $width;

  /**
   * @var int
   */
  protected $height;


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

    $regex = '/^(.*?)(-([a-z]*)?([0-9]+)(x([0-9]+))?)$/';
    if (preg_match($regex, get_filename($this->path), $matches)) {
      $this->source = sprintf(
        '%s/%s/%s/%s.%s',
        MDOGO_ROOT, $this->basepath, get_dirname($this->path),
        $matches[1], $this->extension
      );
      $this->mode = $matches[3] ?: 'sc';
      $this->width = (int) $matches[4];
      $this->height = (int) isset($matches[6]) ? $matches[6] : $matches[4];
    }
  }


  /**
   * Processes path, determines file and processing mode
   *
   * @throws MdogoNotFoundException
   * @return void
   */
  protected function resolve()
  {
    $this->file = MDOGO_ROOT .'/'. $this->basepath .'/'. $this->path;
    if (isset($this->source)) {
      if (!is_file($this->source)) {
        throw new MdogoNotFoundException();
      }
      $this->file = $this->environment->get('cache_dir') .'/'. $this->path;
      if (!is_file($this->file) || filemtime($this->file) < filemtime($this->source)) {
        $this->process();
      }
    }
    if (!is_file($this->file)) {
      throw new MdogoNotFoundException();
    }
  }


  /**
   * Prepares/dispatches image processing mode
   *
   * @return void
   */
  protected function process()
  {
    mkdirp(get_dirname($this->file));

    list($old_width, $old_height) = getimagesize($this->source);

    $new_width = min($this->width, $old_width);
    $new_height = min($this->height, $old_height);

    $new_ratio = $new_width / $new_height;
    $old_ratio = $old_width / $old_height;

    $method = array_get(
      array('c' => 'crop', 's' => 'scale', 'sc' => 'scalecrop'),
      $this->mode, 'scalecrop'
    );
    $this->$method(
      $new_width, $new_height,
      $old_width, $old_height,
      $new_ratio, $old_ratio
    );
  }


  /**
   * Crops image to maximum dimensions
   *
   * @param int $new_width
   * @param int $new_height
   * @param int $old_width
   * @param int $old_height
   * @return void
   */
  protected function crop($new_width, $new_height, $old_width, $old_height)
  {
    $offset_x = ($old_width - $new_width) / 2;
    $offset_y = ($old_height - $new_height) /2;

    $this->resize(
      (int) $new_width, (int) $new_height,
      (int) $new_width, (int) $new_height,
      (int) $offset_x, (int) $offset_y
    );
  }


  /**
   * Scales image to maximum dimensions
   *
   * @param int $new_width
   * @param int $new_height
   * @param int $old_width
   * @param int $old_height
   * @param int $new_ratio
   * @param int $old_ratio
   * @return void
   */
  protected function scale($new_width, $new_height, $old_width, $old_height, $new_ratio, $old_ratio)
  {
    if ($new_ratio > $old_ratio) {
      $new_width  = $new_height * $old_ratio;
    }
    elseif ($new_ratio < $old_ratio) {
      $new_height = $new_width / $old_ratio;
    }
    $this->resize(
      (int) $old_width, (int) $old_height,
      (int) $new_width, (int) $new_height
    );
  }


  /**
   * Scale and crops image to maximum dimensions
   *
   * @param int $new_width
   * @param int $new_height
   * @param int $old_width
   * @param int $old_height
   * @param int $new_ratio
   * @param int $old_ratio
   * @return void
   */
  protected function scalecrop($new_width, $new_height, $old_width, $old_height, $new_ratio, $old_ratio)
  {
    $offset_x = 0;
    $offset_y = 0;

    if ($new_ratio > $old_ratio) {
      $offset_y = ($old_height - ($new_height * $old_width / $new_width)) / 2;
      $old_height = $old_height - ($offset_y * 2);
    }
    elseif ($new_ratio < $old_ratio) {
      $offset_x = ($old_width - ($new_width * $old_height / $new_height)) / 2;
      $old_width = $old_width - ($offset_x * 2);
    }
    $this->resize(
      (int) $old_width, (int) $old_height,
      (int) $new_width, (int) $new_height,
      (int) $offset_x, (int) $offset_y
    );
  }


  /**
   * Scales image to given dimensions
   *
   * @param int $new_width
   * @param int $new_height
   * @param int $old_width
   * @param int $old_height
   * @param int $offset_x
   * @param int $offset_y
   * @return void
   */
  public function resize($old_width, $old_height, $new_width, $new_height, $offset_x = 0, $offset_y = 0)
  {
    if (file_exists($this->source)) {
      $target = imagecreatetruecolor($new_width, $new_height);

      switch($this->extension) {
        case "gif":
          $source = imagecreatefromgif($this->source); break;
        case "jpg":
        case "jpeg":
          $source = imagecreatefromjpeg($this->source); break;
        case "png":
          imagealphablending($target, false);
          imagesavealpha($target, true);
          $source = imagecreatefrompng($this->source);
          break;
        default:
          $source = null;
      }

      imagecopyresampled(
        $target, $source,
        0, 0, $offset_x, $offset_y,
        $new_width, $new_height, $old_width, $old_height
      );

      switch($this->extension) {
        case "gif":
          imagegif($target, $this->file); break;
        case "jpg":
        case "jpeg":
          imagejpeg($target, $this->file, 90); break;
        case "png":
          imagepng($target, $this->file, 0); break;
      }

      imagedestroy($source);
      imagedestroy($target);
    }
  }
}
