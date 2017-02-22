<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage utilities
 */


/**
 * Finds destination for path in patterns array
 *
 * @param string $string
 * @param array $patterns
 * @return string
 */
function route($string, array $patterns)
{
  $result = !!$specificity = 0.0;
  foreach (array_keys($patterns) as $pattern) {
    if ('*' === $pattern) {
      $is_match = !!$pattern_specificity = 0.0001;
    }
    else {
      $is_match = wildcard_match($pattern, $string, $pattern_specificity);
    }
    if ($is_match && $pattern_specificity > $specificity) {
      $result = array_get($patterns, $pattern);
      $specificity = $pattern_specificity;
      if (1.0 === $specificity) {
        break;
      }
    }
  }
  return $result;
}



/**
 * Simple wrapper around preg_match
 *
 * @param string $pattern
 * @param string $string
 * @param float &$specificity (optional)
 * @return bool
 */
function wildcard_match($pattern, $string, &$specificity = null)
{
  $patterns = array(
    '/^\(\\\\\*\)$/'  => '.*',
    '/^\(\\\\\*/'     => '.*(',
    '/\\\\\*\)$/'     => ').*',
    '/\\\\\*/'        => ').*('
  );
  $quoted = preg_quote($pattern, '/');
  $regex = preg_replace(
    array_keys($patterns), array_values($patterns), "($quoted)"
  );
  $result = !!preg_match('/^'. $regex .'$/', $string, $matches);

  $specificity = 0.0;
  if ($result) {
    $specified = implode(array_slice($matches, 1));
    $specificity = round(mb_strlen($specified) / mb_strlen($string), 3);
  }
  return $result;
}
