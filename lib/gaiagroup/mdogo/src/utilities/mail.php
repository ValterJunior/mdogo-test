<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage utilities
 */


/**
 * Send utf-8 (html) email with optional file attachments
 *
 * @param string $sender
 * @param string $recipient
 * @param string $subject
 * @param string $message
 * @param array $attachments (optional)
 * @return bool
 */
function sendmail($sender, $recipient, $subject, $message, $attachments = array())
{
  return MdogoMail::send($sender, $recipient, $subject, $message, $attachments);
}



/**
 * Mailer utility class supporting utf8, html and attachments
 *
 * @package mdogo
 * @subpackage utilities
 */
/* static */ class MdogoMail
{
  /**
   * Prevent instantiation
   */
  protected function __construct() {}
  protected function __clone() {}


  /**
   * Send utf-8 (html) email with optional file attachments
   *
   * @param string $sender
   * @param string $recipient
   * @param string $subject
   * @param string $message
   * @param array $attachments (optional)
   * @param array $options (optional)
   * @return bool
   */
  public static function send($sender, $recipient, $subject, $message, $attachments = array(), $options = array())
  {
    $boundary = uniqid();

    return mail(
      static::encodeEmail($recipient),
      static::encodeString($subject),
      static::getBody($message, $attachments, $boundary),
      static::getHeaders(array_override(
        array(
          'From'          => static::encodeEmail($sender),
          'Content-Type'  => "multipart/mixed; boundary=\"MIX-$boundary\""
        ),
        $options
      ))
    );
  }


  /**
   * Base64-encodes utf8 string
   *
   * @param string $string
   * @return string
   */
  public static function encodeString($string)
  {
    return "=?UTF-8?B?". base64_encode($string) ."?=";
  }


  /**
   * Base64-encodes utf8 email address
   *
   * @param string $email
   * @return string
   */
  public static function encodeEmail($email)
  {
    if (preg_match('/^(.+?)\s<(.+@.+)>/', $email, $matches)) {
      $email = static::encodeString($matches[1]) ." <{$matches[2]}>";
    }
    return $email;
  }


  /**
   * Base64-encodes utf8 message
   *
   * @param string $message
   * @return string
   */
  public static function encodeMessage($message)
  {
    return chunk_split(base64_encode($message), 70, "\n");
  }


  /**
   * Base64-encodes file contents
   *
   * @param string $file
   * @return string
   */
  public static function encodeFile($file)
  {
    return static::encodeMessage(file_get_contents($file));
  }


  /**
   * Removes html from string
   *
   * @param string $hypertext
   * @param array $filters (optional)
   * @return string
   */
  public static function stripTags($hypertext, $filters = array('style'))
  {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML(str_replace(
      '&nbsp;', ' ', mb_convert_encoding($hypertext, 'HTML-ENTITIES', 'UTF-8')
    ));
    $xpath = new DOMXpath($dom);
    $query = array_reduce(
      $filters,
      function ($result, $filter) {
        return sprintf('%s[not(parent::%s)]', $result, $filter);
      },
      '//body//text()'
    );
    $lines = array_filter(array_map(
      function ($node) { return trim($node->nodeValue); },
      iterator_to_array($xpath->query($query))
    ));
    libxml_clear_errors();

    return implode("\n\n", $lines) ."\n";
  }


  /**
   * Gets email headers
   *
   * @param array $options (optional)
   * @return string
   */
  protected static function getHeaders($options)
  {
    $headers = "MIME-Version: 1.0\r\n";
    foreach ($options as $key => $value) {
      $headers .= "$key: $value\r\n";
    }
    return $headers;
  }


  /**
   * Gets email body
   *
   * @param string $message
   * @param array $attachments
   * @param string $boundary
   * @return string
   */
  protected static function getBody($message, $attachments, $boundary)
  {
    if ($message !== strip_tags($message)) {
      $body = static::getBodyHTML($message, $boundary);
    }
    else {
      $body = static::getBodyText($message, $boundary);
    }
    foreach ($attachments as $file) {
      $body .= static::getBodyFile($file, $boundary) ?: '';
    }
    $body .= "\n--MIX-$boundary--\n";

    return $body;
  }


  /**
   * Gets html body partial
   *
   * @param string $message
   * @param string $boundary
   * @return string
   */
  protected static function getBodyHTML($message, $boundary)
  {
    return replace_tokens(
      static::getBodyHTMLFormat(),
      array(
        'plaintext' => static::encodeMessage(static::stripTags($message)),
        'hypertext' => static::encodeMessage($message),
        'boundary'  => $boundary
      )
    );
  }


  /**
   * Gets text body partial
   *
   * @param string $message
   * @param string $boundary
   * @return string
   */
  protected static function getBodyText($message, $boundary)
  {
    return replace_tokens(
      static::getBodyTextFormat(),
      array(
        'plaintext' => static::encodeMessage($message),
        'boundary'  => $boundary
      )
    );
  }


  /**
   * Gets file body partial
   *
   * @param string $file
   * @param string $boundary
   * @return string
   */
  protected static function getBodyFile($file, $boundary)
  {
    if (file_exists($file)) {
      return replace_tokens(
        static::getBodyFileFormat(),
        array(
          'content'   => static::encodeFile($file),
          'name'      => static::encodeString(get_filename($file)),
          'mime'      => static::getType($file),
          'boundary'  => $boundary
        )
      );
    }
  }


  /**
   * Gets file mime type by extension
   *
   * @param string $file
   * @return string
   */
  protected static function getType($file)
  {
    static $types;
    if (!isset($types)) {
      $types = Mdogo::get('environment')->get('mime');
      array_walk(
        $types,
        function (&$type) {
          if (strstt($type, 'text/')) {
            $type .= '; charset=utf-8';
          }
        }
      );
    }
    return array_get($types, get_extension($file));
  }


  /**
   * Gets html body format string
   *
   * @return string
   */
  protected static function getBodyHTMLFormat()
  {
    return <<<'EOT'
--MIX-@boundary
Content-Type: multipart/alternative; boundary=ALT-@boundary

--ALT-@boundary
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: base64

@plaintext

--ALT-@boundary
Content-Type: text/html; charset=utf-8
Content-Transfer-Encoding: base64

@hypertext

--ALT-@boundary--

EOT;
  }


  /**
   * Gets text body format string
   *
   * @return string
   */
  protected static function getBodyTextFormat()
  {
    return <<<'EOT'
--MIX-@boundary
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: base64

@plaintext

EOT;
  }


  /**
   * Gets file body format string
   *
   * @return string
   */
  protected static function getBodyFileFormat()
  {
    return <<<'EOT'
--MIX-@boundary
Content-Type: @mime; name="@name"
Content-Disposition: attachment; filename="@name"
Content-Transfer-Encoding: base64

@content

EOT;
  }
}
