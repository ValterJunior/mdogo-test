<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage exceptions
 */


/**
 * Exception with http status support
 *
 * @package mdogo
 * @subpackage exceptions
 */
class MdogoException extends RuntimeException
{
  /**
   * @var int
   */
  protected $code = 500;
}



/**
 * Bad request exception
 *
 * @package mdogo
 * @subpackage exceptions
 */
class MdogoBadRequestException extends MdogoException
{
  /**
   * @var int
   */
  protected $code = 400;
}



/**
 * Forbidden exception
 *
 * @package mdogo
 * @subpackage exceptions
 */
class MdogoForbiddenException extends MdogoException
{
  /**
   * @var int
   */
  protected $code = 403;
}



/**
 * Not found exception
 *
 * @package mdogo
 * @subpackage exceptions
 */
class MdogoNotFoundException extends MdogoException
{
  /**
   * @var int
   */
  protected $code = 404;
}



/**
 * Method not allowed exception
 *
 * @package mdogo
 * @subpackage exceptions
 */
class MdogoMethodNotAllowedException extends MdogoException
{
  /**
   * @var int
   */
  protected $code = 405;
}



/**
 * Not acceptable exception
 *
 * @package mdogo
 * @subpackage exceptions
 */
class MdogoNotAcceptableException extends MdogoException
{
  /**
   * @var int
   */
  protected $code = 406;
}



/**
 * Conflict exception
 *
 * @package mdogo
 * @subpackage exceptions
 */
class MdogoConflictException extends MdogoException
{
  /**
   * @var int
   */
  protected $code = 409;
}



/**
 * Unsupported media type exception
 *
 * @package mdogo
 * @subpackage exceptions
 */
class MdogoUnsupportedMediaTypeException extends MdogoException
{
  /**
   * @var int
   */
  protected $code = 415;
}



/**
 * Not implemented exception
 *
 * @package mdogo
 * @subpackage exceptions
 */
class MdogoNotImplementedException extends MdogoException
{
  /**
   * @var int
   */
  protected $code = 501;
}



/**
 * Service unavailable exception
 *
 * @package mdogo
 * @subpackage exceptions
 */
class MdogoServiceUnavailableException extends MdogoException
{
  /**
   * @var int
   */
  protected $code = 503;
}
