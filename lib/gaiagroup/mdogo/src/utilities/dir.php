<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage utilities
 */


/**
 * Recursively creates directories
 *
 * @param string $path
 * @param int $umask
 * @return bool
 */
function mkdirp($path, $umask = 0755)
{
  if (!is_dir($path)) {
    mkdir($path, $umask, true);
  }
  return is_dir($path);
}



/**
 * Recursively copies directory
 *
 * @param string $source
 * @param string $destination
 * @return void
 */
function dir_copy($source, $destination)
{
  mkdirp($destination);
  $directory = opendir($source);
  while ($file = readdir($directory)) {
    if (!in_array($file, array('.', '..'))) {
      if (is_dir($source .'/'. $file)) {
        dir_copy($source .'/'. $file, $destination .'/'. $file);
      }
      else {
        copy($source .'/'. $file, $destination .'/'. $file);
      }
    }
  }
  closedir($directory);
}


/**
 * Gets concatenated contents from files in directory
 *
 * @param string $path
 * @param bool|string $extension (optional)
 * @return string
 */
function dir_get_contents($path, $extension = false)
{
  return implode(dir_get_data($path, $extension));
}


/**
 * Gets data from files in a directory
 *
 * @param string $path
 * @param bool|string $extension (optional)
 * @param bool $recursive (optional)
 * @param bool|callable $decode (optional)
 * @return array
 */
function dir_get_data($path, $extension = false, $recursive = false, $decode = false)
{
  $data = dir_get_files($path, $extension, $recursive);
  $decode = $decode ?: array_get(array(
    'ini'  => 'parse_ini_string_assoc',
    'json' => 'json_decode_assoc',
    'phpd' => 'unserialize'
  ), $extension);
  $parse = function (&$content) use (&$parse, &$decode) {
    if (is_array($content)) {
      array_walk($content, $parse);
    }
    else {
      $content = file_get_contents($content);
      if (is_callable($decode)) {
        $content = call_user_func($decode, $content);
      }
    }
  };
  array_walk($data, $parse);
  return $data;
}



/**
 * Gets an array of files in a directory
 * Optionally filtered by extension
 *
 * @param string $path
 * @param $extension (optional)
 * @param bool $recursive (optional)
 * @return array
 */
function dir_get_files($path, $extension = false, $recursive = false)
{
  $directory = opendir($path);
  $files = array();
  while ($file = readdir($directory)) {
    $filepath = $path .'/'. $file;

    if (is_dir($filepath)) {
      if ($recursive && !in_array($file, array('.', '..'))) {
        $files[$file] = dir_get_files($filepath, $extension, $recursive);
      }
    }
    elseif (!$extension || 0 === substr_compare($file, $extension, -strlen($extension), strlen($extension))) {
      $files[basename($file, '.'. $extension)] = $filepath;
    }
  }
  closedir($directory);
  ksort($files);
  return $files;
}



/**
 * Gets last modified time for directory (similar to filemtime())
 * Optionally filtered by extension
 *
 * @param string $path
 * @param $extension (optional)
 * @param $recursive (optional)
 * @return int (timestamp)
 */
function dirmtime($path, $extension = false, $recursive = false)
{
  $getmtime = function ($result, $file) use (&$getmtime) {
    if (is_array($file)) {
      return array_reduce($file, $getmtime, $result);
    }
    else {
      return max($result, filemtime($file));
    }
  };
  return array_reduce(dir_get_files($path, $extension, $recursive), $getmtime, 0);
}
