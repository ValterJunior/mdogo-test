<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage utilities
 */


/**
 * Renders template using a subset of Mustache (no partials)
 * http://mustache.github.com/mustache.5.html
 *
 * Celebrating Movember 2012
 *
 * @param string $template
 * @param array $view
 * @return string
 */
function stache_render($template, array $view)
{
  return preg_replace_callback(
    array(
      '|{{([\^#])\s*(.+?)\s*}}(.+?)(\s*){{/\s*\2\s*}}|s',
      '|{{([{!&]?)\s*(.+?)\s*}}}?|'
    ),
    function ($matches) use ($view) {
      $data = array_get_recursive($view, explode('.', trim($matches[2])));
      $result = '';
      if (3 === count($matches)) {
        if (is_scalar($data) && !is_bool($data)) {
          if ('' === $matches[1]) {
            $result = htmlspecialchars($data);
          }
          elseif ('{' === $matches[1] || '&' === $matches[1]) {
            $result = $data;
          }
        }
      }
      elseif (5 === count($matches)) {
        if ('#' === $matches[1]) {
          if (is_callable($data)) {
            $result = call_user_func($data, $matches[3] . $matches[4]);
          }
          elseif (is_list($data)) {
            foreach($data as $datum) {
              if (!is_list($datum = (array) $datum)) {
                $result .= stache_render($matches[3], $datum);
              }
            }
            $result .= $matches[4];
          }
          elseif (!empty($data)) {
            if (is_array($data)) {
              $result = stache_render($matches[3] . $matches[4], $data);
            }
            else {
              $result = $matches[3] . $matches[4];
            }
          }
        }
        elseif ('^' === $matches[1]) {
          if (empty($data)) {
            $result = $matches[3] . $matches[4];
          }
        }
      }
      return $result;
    },
    $template
  );
}
