<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage utilities
 */


/**
 * Determines file's/string's mime type
 *
 * @param string $string
 * @param int $options
 * @return string
 */
function get_info($string, $options = FILEINFO_MIME_TYPE)
{
  $finfo = finfo_open($options);
  if (is_filename($string)) {
    $result = finfo_file($finfo, $string);
  }
  else {
    $result = finfo_buffer($finfo, $string);
  }
  finfo_close($finfo);

  return $result;
}



/**
 * Determines mime type's corresponding mdogo type
 *
 * @param string $mime
 * @return string
 */
function get_type($mime)
{
  static $types;
  if (!isset($types)) {
    $types = array_flip(Mdogo::get('environment')->get('mime'));
  }
  $mime = substr($mime, 0, strpos($mime, ';') ?: strlen($mime));

  return array_get($types, $mime);
}



/**
 * Gets the current locale
 *
 * @param int $category
 */
function getlocale($category = LC_ALL)
{
  return setlocale($category, "0");
}



/**
 * Gets the dirname of a filename/path
 *
 * @param string $path
 * @return string
 */
function get_dirname($path)
{
  return pathinfo($path, PATHINFO_DIRNAME);
}



/**
 * Gets the extension of a filename/path
 *
 * @param string $path
 * @return string
 */
function get_extension($path)
{
  return pathinfo($path, PATHINFO_EXTENSION);
}



/**
 * Gets the filename from a path
 *
 * @param string $path
 * @return string
 */
function get_filename($path)
{
  return pathinfo($path, PATHINFO_FILENAME);
}



/**
 * Gets the hostname from an url
 *
 * @param string $url
 * @return string
 */
function get_host($url)
{
  return parse_url($url, PHP_URL_HOST);
}



/**
 * Gets the password from an url
 *
 * @param string $url
 * @return string
 */
function get_password($url)
{
  return parse_url($url, PHP_URL_PASS);
}



/**
 * Gets the path from an url
 *
 * @param string $url
 * @return string
 */
function get_path($url)
{
  return parse_url($url, PHP_URL_PATH);
}



/**
 * Gets the port from an url
 *
 * @param string $url
 * @return string
 */
function get_port($url)
{
  return parse_url($url, PHP_URL_PORT);
}



/**
 * Gets the query string from an url
 *
 * @param string $url
 * @return string
 */
function get_query($url)
{
  return parse_url($url, PHP_URL_QUERY);
}



/**
 * Gets the scheme from an url
 *
 * @param string $url
 * @return string
 */
function get_scheme($url)
{
  return parse_url($url, PHP_URL_SCHEME);
}



/**
 * Gets the username from an url
 *
 * @param string $url
 * @return string
 */
function get_username($url)
{
  return parse_url($url, PHP_URL_USER);
}



/**
 * Validates email
 *
 * @param string $string
 * @return bool
 */
function is_email($string)
{
  return (bool) filter_var($string, FILTER_VALIDATE_EMAIL);
}



/**
 * Validates ip address
 *
 * @param string $string
 * @return bool
 */
function is_ip($string)
{
  return (bool) filter_var($string, FILTER_VALIDATE_IP);
}



/**
 * Validates url
 *
 * @param string $string
 * @return bool
 */
function is_url($string)
{
  return (bool) filter_var($string, FILTER_VALIDATE_URL);
}



/**
 * Validates string as float
 *
 * @param mixed $string
 */
function is_floaty($string)
{
  return is_numeric($string) && (intval($string) != floatval($string));
}



/**
 * Validates string as int
 *
 * @param mixed $string
 */
function is_inty($string)
{
  return is_numeric($string) && (intval($string) == floatval($string));
}



/**
 * Validates string as filename
 *
 * @param string $string
 * @return void
 */
function is_filename($string)
{
  return (sanitize($string) === $string) && is_file($string);
}



/**
 * Validates handle as stream ressource
 *
 * @param resource $stream
 * @return void
 */
function is_stream($stream)
{
  return is_resource($stream) && 'stream' === get_resource_type($stream);
}



/**
 * Validates list (numeric key array)
 *
 * @param array $array
 * @return bool
 */
function is_list($array)
{
  return is_array($array) && ctype_digit(implode(array_keys($array)));
}



/**
 * Checks if (simple) crontab schedule is due
 *
 * @param string $schedule
 * @param mixed $timestamp (optional)
 * @return string|bool
 */
function is_scheduled($schedule, $timestamp = null)
{
  $result = false;
  if (is_schedule($schedule, $matches)) {
    $current = explode(':', date('i:G:j:n:w', $timestamp ?: time()));
    $current[0] = ltrim($current[0], '0') ?: '0';

    $result = trim(array_pop($matches));
    foreach (array_slice($matches, 1) as $i => $value) {
      if (!( ($value === '*')
          || ($value === $current[$i])
          || (strstt($value, '*/') && 0 === ($current[$i] % substr($value, 2)))
          || (strinc($value, ',') && in_array($current[$i], explode(',', $value))) )) {
        $result = false;
        break;
      }
    }
  }
  return $result;
}



/**
 * Validate string as simple cron schedule
 *
 * @param string $schedule
 * @param array $matches
 * @return bool
 */
function is_schedule($schedule, &$matches = array())
{
  $map = array(
    '@yearly'   => '0 0 1 1 *',
    '@monthly'  => '0 0 1 * *',
    '@weekly'   => '0 0 * * 0',
    '@daily'    => '0 0 * * *',
    '@hourly'   => '0 * * * *'
  );
  $schedule = str_replace(array_keys($map), array_values($map), $schedule);

  $regex = vsprintf(
    '/^(%s+)\s+(%s+)\s+(%s+)\s+(%s+)\s+(%s+)\s+(.+)$/',
    array_fill(0, 5, '[0-9\*\/\,]')
  );
  return preg_match($regex, $schedule, $matches);
}
