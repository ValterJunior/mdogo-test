<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage utilities
 */


/**
 * Exports data and zips results
 *
 * @param string $type (csv, html, xml)
 * @param mixed ... (according to type)
 * @return string
 */
function export_zip($type)
{
  $path = tempnam(sys_get_temp_dir(), uniqid());

  $zip = new ZipArchive();
  if (true !== $zip->open($path, ZipArchive::OVERWRITE)) {
    trigger_error('could not open zip file for writing');
  }
  $zip->addFromString("export.$type", call_user_func_array(
    'export_'. $type,
    array_slice(func_get_args(), 2)
  ));
  $zip->close();

  $result = file_get_contents($path);
  unlink($path);

  return $result;
}



/**
 * Exports rows as csv (comma/character separated values)
 *
 * @param array $items
 * @param bool $header (optional)
 * @param string $delimiter (optional)
 * @param string $enclosure (optional)
 * @return string
 */
function export_csv($items, $header = true, $delimiter = ',', $enclosure = '"')
{
  $handle = tmpfile();
  foreach (export_normalize($items, $header) as $item) {
    array_walk($item, function (&$value) {
      if (is_a($value, 'DateTime')) {
        $value = $value->format('Y-m-d\TH:i');
      }
    });
    fputcsv($handle, $item, $delimiter, $enclosure);
  }
  rewind($handle);
  $csv = stream_get_contents($handle);
  fclose($handle);
  return $csv;
}



/**
 * Exports rows as plain html table
 *
 * @param array $items
 * @param bool $header (optional)
 * @param bool $full (optional)
 * @return string
 */
function export_html($items, $header = true, $full = true)
{
  $dom = new DomDocument();
  $dom->loadHTML(sprintf(
    '<!DOCTYPE html><html>%s%s%s%s</html>',
    '<head><meta charset="utf-8"><title>', date('c') ,'</title></head>',
    '<body><table></table></body>'
  ));
  $table = $dom->getElementsByTagName('table')->item(0);
  if ($header) {
    $head = $table->appendChild($dom->createElement('thead'));
    $body = $table->appendChild($dom->createElement('tbody'));
  }
  else {
    $body = $table;
  }
  foreach (export_normalize($items, $header) as $index => $item) {
    if ($header && !$index) {
      $row = $head->appendChild($dom->createElement('tr')); $tag = 'th';
    }
    else {
      $row = $body->appendChild($dom->createElement('tr')); $tag = 'td';
    }
    foreach ($item as $value) {
      switch (true) {
        case (is_a($value, 'DateTime')):
          $row->appendChild($dom->createElement($tag, $value->format('c')));
          break;
        case (is_bool($value)):
          $row->appendChild($dom->createElement($tag, intval($value)));
          break;
        default:
          $cell = $row->appendChild($dom->createElement($tag));
          $cell->appendChild($dom->createTextNode($value));
      }
    }
  }
  return $dom->saveHTML($full ? $dom : $table);
}



/**
 * Exports rows as xml (SpreadsheetML)
 *
 * @param array $items
 * @param bool $header (optional)
 * @param bool $full (optional)
 * @return string
 */
function export_xml($items, $header = true, $full = true)
{
  $dom = new DOMDocument('1.0', 'UTF-8');
  $dom->appendChild($dom->createProcessingInstruction(
    'mso-application', 'progid="Excel.Sheet"'
  ));
  $xmlns = 'urn:schemas-microsoft-com:office:spreadsheet';
  $workbook = $dom->appendChild($dom->createElement('Workbook'));
  $workbook->setAttribute('xmlns', $xmlns);
  $workbook->setAttribute('xmlns:ss', $xmlns);

  $styles = $workbook->appendChild($dom->createElement('Styles'));
  $style = $styles->appendChild($dom->createElement('Style'));
  $style->setAttribute('ss:ID', 'ssDate');

  $format = $style->appendChild($dom->createElement('NumberFormat'));
  $format->setAttribute('ss:Format', 'yyyy-mm-dd\ hh:mm;@');

  $worksheet = $workbook->appendChild($dom->createElement('Worksheet'));
  $worksheet->setAttribute('ss:Name', 'Sheet1');

  $table = $worksheet->appendChild($dom->createElement('Table'));
  foreach (export_normalize($items, $header) as $item) {
    $row = $table->appendChild($dom->createElement('Row'));
    foreach ($item as $value) {
      $cell = $row->appendChild($dom->createElement('Cell'));
      $data = $cell->appendChild($dom->createElement('Data'));
      switch (true) {
        case (is_a($value, 'DateTime')):
          $data->nodeValue = $value->format('Y-m-d\TH:i');
          $data->setAttribute('ss:Type', 'DateTime');
          $cell->setAttribute('ss:StyleID', 'ssDate');
          break;
        case (is_bool($value)):
          $data->nodeValue = intval($value);
          $data->setAttribute('ss:Type', 'Boolean');
          break;
        case (is_numeric($value)):
          $data->nodeValue = $value;
          $data->setAttribute('ss:Type', 'Number');
          break;
        default:
          $data->appendChild($dom->createTextNode($value));
          $data->setAttribute('ss:Type', 'String');
      }
    }
  }
  return $dom->saveXML($full ? $dom : $workbook);
}



/**
 * Normalizes indexed array (list) of associative arrays (maps)
 *
 * @param array $items
 * @param bool $header (optional)
 * @throws Error
 * @return array
 */
function export_normalize($items, $header = true)
{
  if (!empty($items) && !is_list($items)) {
    trigger_error('invalid list');
  }
  $keys = array();
  foreach ($items as &$item) {
    $item = export_flatten($item);
    $keys = array_unique(array_merge($keys, array_keys($item)));
  }
  $keys = array_values($keys);
  $normalized = array();
  if (!empty($keys)) {
    $defaults = array_combine($keys, array_fill(0, count($keys), null));
    foreach ($items as &$item) {
      $normalized[] = array_values(array_merge($defaults, $item));
    }
    if ($header) {
      array_unshift($normalized, $keys);
    }
  }
  return $normalized;
}



/**
 * Flattens nested arrays for export
 *
 * @param array $array
 * @param string $prefix (internal)
 * @throws Error
 * @return array
 */
function export_flatten($array, $prefix = '')
{
  if (is_object($array) && method_exists($array, 'toArray')) {
    $array = $array->toArray();
  }
  if (!is_array($array)) {
    trigger_error('invalid item');
  }
  $flattened = array();
  foreach ($array as $key => $value) {
    $key = $prefix ? $prefix .'.'. $key : $key;
    if (is_array($value)) {
      $flattened = array_merge(
        $flattened,
        export_flatten($value, $key)
      );
    }
    else {
      $flattened[$key] = $value;
    }
  }
  return $flattened;
}
