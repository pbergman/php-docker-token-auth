<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\Exception;

use Exception;

/**
 * Class InvalidAccessException
 *
 * @package DockerToken\Exception
 */
class InvalidAccessException extends \Exception implements ExceptionInterface
{
    public function __construct($message = 'Invalid credentials')
    {
        parent::__construct($message);
    }

}