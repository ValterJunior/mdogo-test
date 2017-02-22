<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage utilities
 */


/**
 * Basic svg line chart class
 *
 * @package mdogo
 * @subpackage utilities
 */
class MdogoChart extends MdogoSVG
{
  /**
   * @var array
   */
  protected $lines = array();

  /**
   * @var array
   */
  protected $grid = array();

  /**
   * @var array
   */
  protected $points;

  /**
   * @var string
   */
  protected $prefix;


  /**
   * Adds line graph
   *
   * @param array $points
   * @param string $label (optional)
   * return void
   */
  public function addLine($points, $label = null)
  {
    $xs = $ys = array();
    foreach ($points as &$point) {
      list($xs[], $ys[]) = $point = array(
        array_get($point, 'x', array_get($point, 0, 0)),
        array_get($point, 'y', array_get($point, 1, 0))
      );
    }
    $this->setLimits(min($xs), min($ys), max($xs), max($ys), false);

    if (empty($label)) {
      $this->lines[] = $points;
    }
    else {
      $this->lines[$label] = $points;
    }
  }


  /**
   * Adds grid lines
   *
   * @param array $gridx
   * @param array $gridy
   * @return void
   */
  public function addGrid($gridx = array(), $gridy = array())
  {
    foreach (array('x', 'y') as $dim) {
      $grid = 'grid'. $dim;
      $min = 'min'. $dim;
      $max = 'max'. $dim;
      if (empty($$grid)) {
        $$min = array_get($this->limits, $min);
        $$min = array_get($this->limits, $max);
      }
      else {
        $$min = min($$grid);
        $$max = max($$grid);
      }
    }
    $this->setLimits($minx, $miny, $maxx, $maxy, false);
    $this->grid = array('x' => $gridx, 'y' => $gridy);
  }


  /**
   * Renders svg image
   *
   * @param bool $full (optional)
   * @param string $file (optional)
   * @return string
   */
  public function render($full = false, $file = false)
  {
    $this->setPrefix();
    $this->setDimensions();

    $this->renderGrid();
    $this->renderLines();
    $this->renderPoints();

    return parent::render($full, $file);
  }


  /**
   * Sets id prefix
   *
   * @param string $prefix
   * @return void
   */
  public function setPrefix($prefix = null)
  {
    if (!empty($prefix)) {
      $this->prefix = $prefix;
    }
    elseif (empty($this->prefix)) {
      $this->prefix = 'svg-'. uniqid();
    }
    $this->resetAttribute('id', $this->prefix);
  }


  /**
   * Injects css styles into svg
   *
   * @param string $css
   * @return void
   */
  public function setCSS($css = null)
  {
    $this->setPrefix();

    return parent::setCSS($css);
  }


  /**
   * Actually renders line graphs
   *
   * @return void
   */
  protected function renderLines()
  {
    if ($lines = $this->dom->getElementById($this->prefix .'-lines')) {
      $this->dom->removeChild($lines);
    }
    $lines = $this->dom->createElement('g');
    $lines->setAttribute('class', 'lines');
    $lines->setAttribute('id', $this->prefix .'-lines');
    foreach ($this->lines as $label => $points) {
      $points = implode(' ', array_reduce(
        $this->normalize($points),
        function ($points, $point) {
          $points[] = $point[0] .','. $point[1];
          return $points;
        },
        array()
      ));
      $line = $this->dom->createElement('polyline');
      $line->setAttribute('points', $points);
      $line->setAttribute('id', $this->prefix .'-line-'. $label);
      $line->setAttribute('class', 'line '. $this->prefix .'-line');
      $lines->appendChild($line);
    }
    $this->appendChild($lines);
  }


  /**
   * Renders points onto lines
   *
   * @return void
   */
  protected function renderPoints()
  {
    if ($lines = $this->dom->getElementById($this->prefix .'-points')) {
      $this->dom->removeChild($lines);
    }
    $lines = $this->dom->createElement('g');
    $lines->setAttribute('class', 'points');
    $lines->setAttribute('id', $this->prefix .'-points');
    foreach ($this->lines as $base_label => $points) {
      foreach ($this->normalize($points) as $label => $point) {
        $x = array_get($point, 'x', array_get($point, 0, 0));
        $y = array_get($point, 'y', array_get($point, 1, 0));
        $line = $this->dom->createElement('line');
        $line->setAttribute('x1', $x); $line->setAttribute('x2', $x);
        $line->setAttribute('y1', $y); $line->setAttribute('y2', $y);
        $line->setAttribute('id', $this->prefix .'-point-'. $base_label .'-'. $label);
        $line->setAttribute('class', implode(' ', array(
          'point', 'point-'. $base_label, 'point-'. $base_label .'-'. $label,
          $this->prefix .'-point', $this->prefix .'-point-'. $base_label,
          $this->prefix .'-point-'. $base_label .'-'. $label
        )));
        $line->setAttribute('desc', implode('x', $points[$label]));
        $lines->appendChild($line);
      }
    }
    $this->appendChild($lines);
  }


  /**
   * Renders grid lines
   *
   * @return void
   */
  protected function renderGrid()
  {
    extract($this->limits);

    $grid = array();
    foreach (array_get($this->grid, 'x', array()) as $label => $value) {
      $grid['x-'. $label] = array(array($value, $miny), array($value, $maxy));
    }
    foreach (array_get($this->grid, 'y', array()) as $label => $value) {
      $grid['y-'. $label] = array(array($minx, $value), array($maxx, $value));
    }
    if ($lines = $this->dom->getElementById($this->prefix .'-grid')) {
      $this->dom->removeChild($lines);
    }
    $lines = $this->dom->createElement('g');
    $lines->setAttribute('class', 'grid');
    $lines->setAttribute('id', $this->prefix .'-grid');
    foreach ($grid as $label => $points) {
      $is_x = strstt($label, 'x-');
      $suffix = $is_x ? 'x' : 'y';

      list($start, $end) = $this->normalize($points);
      $line = $this->dom->createElement('line');
      $line->setAttribute('x1', $start[0]); $line->setAttribute('x2', $end[0]);
      $line->setAttribute('y1', $start[1]); $line->setAttribute('y2', $end[1]);
      $line->setAttribute('id', $this->prefix .'-grid-'. $label);
      $line->setAttribute('class', implode(' ', array(
        'grid', 'grid-'. $label, 'grid-'. $suffix,
        $this->prefix .'-grid', $this->prefix .'-grid-'. $label,
        $this->prefix .'-grid-'. $suffix,
      )));
      $line->setAttribute('desc', $is_x ? $points[0][0] : $points[0][1]);
      $lines->appendChild($line);
    }
    $this->appendChild($lines);
  }


  /**
   * Gets default chart css
   *
   * @return string
   */
  protected function getCSS()
  {
    return replace_tokens(
      "\n#@prefix-lines { stroke: #C00; stroke-width: 2; stroke-linecap: round; fill: transparent }".
      "\n#@prefix-points { stroke: #C00; stroke-width: 10; stroke-linecap: round; }".
      "\n#@prefix-grid { stroke: #CCC; stroke-width: .5; }\n",
      array('prefix' => $this->prefix)
    );
  }
}



/**
 * Basic svg wrapper class
 *
 * @package mdogo
 * @subpackage utilities
 */
class MdogoSVG
{
  /**
   * @var DOMDocument
   */
  protected $dom;

  /**
   * @var DOMElement
   */
  protected $svg;

  /**
   * @var int
   */
  protected $width;

  /**
   * @var int
   */
  protected $height;

  /**
   * @var int
   */
  protected $pad;

  /**
   * @var array
   */
  protected $limits;


  /**
   * Sets up DOM
   *
   * @return MdogoSVG
   */
  public function __construct()
  {
    $this->dom = new DOMDocument();
    $this->dom->validateOnParse = true;

    $this->svg = $this->dom->createElement('svg');
    $this->svg->setAttribute('xmlns', 'http://www.w3.org/2000/svg');

    $this->dom->appendChild($this->svg);
  }


  /**
   * Extends class by proxying to svg
   *
   * @param string $name
   * @param array $arguments
   * @return mixed
   */
  public function __call($name, $arguments)
  {
    if (method_exists($this->svg, $name)) {
      return call_user_func_array(array($this->svg, $name), $arguments);
    }
    elseif (method_exists($this->dom, $name)) {
      return call_user_func_array(array($this->dom, $name), $arguments);
    }
    else {
      trigger_error('unknown method: '. $name);
    }
  }


  /**
   * Helps setting attributes on elements
   *
   * @param string $key
   * @param string $value
   * @return void
   */
  public function resetAttribute($key, $value)
  {
    if ($this->svg->hasAttribute($key)) {
      $this->svg->removeAttribute($key);
    }
    $this->svg->setAttribute($key, $value);
  }


  /**
   * Renders svg image
   *
   * @param bool $full (optional)
   * @param string $file (optional)
   * @return string
   */
  public function render($full = null, $file = null)
  {
    $this->setDimensions();
    $this->updateViewbox();

    $xml = $this->dom->saveXML($full ? $this->dom : $this->svg);

    if (!empty($file)) {
      file_put_contents($file, $xml);
    }
    return $xml;
  }


  /**
   * Injects css styles into svg
   *
   * @param string $css
   * @return void
   */
  public function setCSS($css = null)
  {
    foreach ($this->dom->getElementsByTagName('style') as $style) {
      $this->dom->removeChild($style);
    }
    if (empty($css)) {
      $css = $this->getCSS();
    }
    elseif (file_exists($css)) {
      $css = file_get_contents($css);
    }
    $style = $this->dom->createElement('style');
    $style->appendChild($this->dom->createCDATASection($css));

    $this->svg->appendChild($style);
  }


  /**
   * Sets physical svg dimensions
   *
   * @param int $width (optional)
   * @param int $height (optional)
   * @return void
   */
  public function setDimensions($width = null, $height = null, $pad = null)
  {
    if ($width && $height) {
      $this->width = intval($width);
      $this->height = intval($height);
    }
    elseif ($width) {
      $this->width = $this->height = intval($width);
    }
    elseif (!empty($this->limits) && empty($this->width)) {
      $this->width = $this->limits['maxx'] - $this->limits['minx'];
      $this->height = $this->limits['maxy'] - $this->limits['miny'];
    }
    if (null !== $pad) {
      $this->pad = $pad;
    }
  }


  /**
   * Sets/updates svg limits
   *
   * @return void
   */
  public function setLimits($minx, $miny, $maxx, $maxy, $locked = true)
  {
    if ($locked) {
      $this->limits = get_defined_vars();
    }
    elseif (!array_get((array) $this->limits, 'locked')) {
      $minx = min(array_get((array) $this->limits, 'minx', $minx), $minx);
      $miny = min(array_get((array) $this->limits, 'miny', $miny), $miny);
      $maxx = max(array_get((array) $this->limits, 'maxx', $maxx), $maxx);
      $maxy = max(array_get((array) $this->limits, 'maxy', $maxy), $maxy);

      $this->limits = get_defined_vars();
    }
  }


  /**
   * Normalizes points to svg dimensions
   *
   * @param array $points
   * @return array
   */
  protected function normalize($points)
  {
    extract($this->limits);
    $width = $this->width;
    $height = $this->height;
    $dpad = $this->pad * 2;
    $pad = $this->pad;

    $normalized = array();
    foreach ($points as $label => $point) {
      if (is_array($point)) {
        $x = array_get($point, 'x', array_get($point, 0, 0));
        $y = array_get($point, 'y', array_get($point, 1, 0));
        $normalized[$label] = array(
          round(($x - $minx) * (($width - $dpad) / ($maxx - $minx)) + $pad),
          round(($height - ($y - $miny) * (($height - $dpad) / ($maxy - $miny))) - $pad)
        );
      }
      else {
        trigger_error('invalid point: '. $point);
      }
    }
    return $normalized;
  }


  /**
   * Configures svg scaling
   *
   * @return void
   */
  protected function updateViewbox()
  {
    $this->resetAttribute('width', $this->width);
    $this->resetAttribute('height', $this->height);
    $this->resetAttribute('viewBox', "0 0 {$this->width} {$this->height}");
    $this->resetAttribute('preserveAspectRatio', 'xMinYMin meet');
  }


  /**
   * Gets default css
   *
   * @return string
   */
  protected function getCSS()
  {
    return;
  }
}
