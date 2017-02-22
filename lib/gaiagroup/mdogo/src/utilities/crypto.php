<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage utilities
 */



/**
 * Hashes plaintext using bcrypt via php's crypt function
 * http://codahale.com/how-to-safely-store-a-password
 *
 * @param string $plaintext (<= 72 chars)
 * @param int $cost (4 <=> 31)
 * @throws Error
 * @return string
 */
function bcrypt($plaintext, $cost = 11)
{
  $hash = crypt($plaintext, bcrypt_salt($cost));

  if (!is_bcrypt($hash)) {
    trigger_error('could not hash password');
  }
  return $hash;
}



/**
 * Validates plaintext against bcrypt hash
 *
 * @param string $plaintext (<= 72 chars)
 * @param string $hash
 * @param int $cost (optional)
 * @param bool &$needs_rehash (optional)
 * @throws Error
 * @return bool
 */
function bcheck($plaintext, $hash, $cost = 11, &$needs_rehash = false)
{
  if (!is_bcrypt($hash)) {
    trigger_error('not a valid bcrypt hash');
  }
  $needs_rehash = !strstt($hash, bcrypt_salt($cost, true));

  $rehash = crypt($plaintext, $hash);

  if (!is_bcrypt($rehash)) {
    trigger_error('could not hash password to validate');
  }
  return is_equal($rehash, $hash);
}



/**
 * Generate bcrypt salt (or format prefix)
 * http://www.php.net/security/crypt_blowfish.php
 *
 * @param int $cost (4 <=> 31)
 * @param bool $prefix_only (optional)
 * @return string
 */
function bcrypt_salt($cost, $prefix_only = false)
{
  return sprintf(
    '$2y$%02d$%s',
    min(max(4, $cost), 31),
    $prefix_only ? '' : substr(
      str_replace(
        '+', '.',
        base64_encode(random(16, true))
      ),
      0, 22
    )
  );
}



/**
 * Generates a random base32 (or raw) string
 *
 * @param int $length
 * @param bool $raw
 * @throws Error
 * @return string
 */
function random($length, $raw = false)
{
  $rawlength = $raw ? $length : ceil($length / 1.60);

  $limit = 5;
  do {
    $result = openssl_random_pseudo_bytes($rawlength, $strong);
  }
  while(!$strong && --$limit && !usleep(200000));

  if (!$strong) {
    trigger_error('could not get random bytes');
  }
  elseif (!$raw) {
    $result = str_pad(
      substr(base32_encode($result), 0, $length),
      $length, '0', STR_PAD_LEFT
    );
  }
  return $result;
}



/**
 * Generates a rfc 4122 v.4 compliant uuid
 * From http://stackoverflow.com/a/15875555
 *
 * @return string
 */
function uuid()
{
  $data = random(16, true);
  $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}



/**
 * Compares two strings in length constant time
 *
 * @param string $foo
 * @param string $bar
 * @return bool
 */
function is_equal($foo, $bar)
{
  $diff = strlen($foo) ^ strlen($bar);
  for ($i = 0; $i < strlen($foo) && $i < strlen($bar); $i++) {
    $diff |= (ord($foo[$i]) ^ ord($bar[$i]));
  }
  return (0 === $diff);
}



/**
 * Validates bcrypt hash
 *
 * @param string $string
 * @throws Error
 * @return bool
 */
function is_bcrypt($string)
{
  return (bool) preg_match(
    '/^\$2[yxa]\$((0[4-9])|([12][0-9])|(3[01]))\$[0-9a-zA-Z\/\.]{53}$/',
    $string
  );
}



/**
 * Validates uuid
 *
 * @param string $string
 * @return bool
 */
function is_uuid($string)
{
  return (bool) preg_match(
    '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
    $string
  );
}



/**
 * Encode binary string or int using human readable base32
 * http://www.crockford.com/wrmg/base32.html
 *
 * @param string|int $number
 * @param int $base (optional)
 * @return string
 */
function base32_encode($number, $base = null)
{
  if (empty($base)) {
    $number = bin2hex($number);
    $base = 16;
  }
  return str_replace(
    str_split('abcdefghijklmnopqrstuv'),
    str_split('ABCDEFGHJKMNPQRSTVWXYZ'),
    bc_base_convert($number, $base, 32)
  );
}



/**
 * Decode to binary string or int using human readable base32
 * http://www.crockford.com/wrmg/base32.html
 *
 * @param string $string
 * @param int $base (optional)
 * @return string
 */
function base32_decode($string, $base = null)
{
  $binary = !!(empty($base) && $base = 16);

  $result = bc_base_convert(
    str_replace(
      str_split('ABCDEFGHJKMNPQRSTVWXYZILOU-'),
      str_split('abcdefghijklmnopqrstuv110r'),
      strtoupper($string)
    ),
    32, $base
  );
  if ($binary) {
    if (strlen($result) % 2) {
      $result = "0$result";
    }
    $result = pack('H*', $result);
  }
  return $result;
}



/**
 * Normalizes base32 string
 *
 * @param string $string
 * @return string
 */
function base32_normalize($string)
{
  return str_replace(
    array('I', 'L', 'O', 'U', '-'),
    array('1', '1', '0', 'V'),
    strtoupper($string)
  );
}



/**
 * Converts base of arbitrarily large number
 *
 * @param mixed $number
 * @param int $frombase (2 <=> 36)
 * @param int $tobase (2 <=> 36)
 * @return string
 */
function bc_base_convert($number, $frombase = 10, $tobase = 10)
{
  if (2 > $frombase || 36 < $frombase || 2 > $tobase || 36 < $tobase) {
    trigger_error('only bases 2 <=> 36 supported');
  }
  if (10 === $frombase) {
    $decimal = strval($number);
  }
  else {
    $decimal = 0;
    foreach (str_split($number) as $character) {
      $decimal = bcadd(
        bcmul($decimal, $frombase),
        base_convert($character, $frombase, 10)
      );
    }
  }
  if (10 === $tobase) {
    $result = strval($decimal);
  }
  else {
    $result = '';
    while (bccomp($decimal, '0', 0) > 0) {
      $modulo = bcmod($decimal, $tobase);
      $decimal = bcdiv($decimal, $tobase, 0);
      $result = base_convert($modulo, 10, $tobase) . $result;
    }
  }
  return $result;
}
