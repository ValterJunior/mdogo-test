<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage utilities
 */


/**
 * Checks if haystack contains needle
 *
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function strinc($haystack, $needle)
{
  return false !== strpos($haystack, $needle);
}



/**
 * Checks if haystack starts with needle
 *
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function strstt($haystack, $needle)
{
  return 0 === strpos($haystack, $needle);
}



/**
 * Checks if haystack ends with needle
 *
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function strend($haystack, $needle)
{
  return 0 === strpos(strrev($haystack), strrev($needle));
}



/**
 * Filters input and removes potentially dangerous characters
 *
 * @param string $string
 * @return string
 */
function sanitize($string)
{
  return str_replace(
    array('\\', '`'), '',
    filter_var($string, FILTER_SANITIZE_URL)
  );
}



/**
 * Decodes json into a php value (preferably an array)
 *
 * @param string $string
 * @return mixed
 */
function json_decode_assoc($string)
{
  return json_decode($string, true);
}


/**
 * Parses ini string with sections
 *
 * @param string $string
 * @return array
 */
function parse_ini_string_assoc($string)
{
  return parse_ini_string($string, true);
}



/**
 * Decodes www-form-urlencoded data into a php value
 *
 * @param string $string
 * @return array
 */
function http_parse_query($string)
{
  parse_str($string, $result);
  return $result;
}



/**
 * Decodes http digest header
 *
 * @param string $string
 * @return array
 */
function http_parse_digest($string)
{
  $data = array();
  $parts = explode(':', 'nonce:nc:cnonce:qop:username:uri:response');

  $regex = sprintf(
    '/(%s)=(?:([\'"])([^\2]+?)\2|([^\s,]+))/',
    implode('|', $parts)
  );
  if (preg_match_all($regex, $string, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
      $data[$match[1]] = $match[3] ? $match[3] : $match[4];
    }
  }
  return array_diff_key($data, array_flip($parts)) ? false : $data;
}



/**
 * Replace format placeholders with
 * values from array
 *
 * @param string $format
 * @param array $values
 * @param string $prefix
 * @param bool|string $suffix
 * @return string
 */
function replace_tokens($format, array $values, $prefix = '@', $suffix = false)
{
  $prefix = preg_quote($prefix, '|');
  if ($suffix) {
    $suffix = preg_quote($suffix, '|');
  }
  return preg_replace(
    array_map(function($key) use ($prefix, $suffix) {
      return "|$prefix\s*$key" . (($suffix) ? "\s*$suffix|" : '|');
    }, array_keys($values)),
    array_values($values),
    $format
  );
}



/**
 * Converts underscore_separated string to camelCase
 *
 * @param string $string
 * @return string
 */
function camelize($string)
{
  $parts = explode('_', $string);
  return array_shift($parts) . implode(array_map('ucfirst', $parts));
}



/**
 * Converts camelCase string to underscore_separated
 *
 * @param string $string
 * @return string
 */
function uncamelize($string) {
  if (preg_match_all('|[A-Z]*[a-z0-9]+|', $string, $matches)) {
    $result = array();
    foreach ($matches[0] as $match) {
      $result[] = strtolower($match);
    }
    return implode('_', $result);
  }
  else {
    return $string;
  }
}
