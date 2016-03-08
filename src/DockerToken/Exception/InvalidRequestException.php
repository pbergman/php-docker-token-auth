<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\Exception;

use Exception;

/**
 * Class InvalidRequestException
 *
 * @package DockerToken\Exception
 */
class InvalidRequestException extends \Exception implements ExceptionInterface
{
    public function __construct($message = 'Invalid request')
    {
        parent::__construct($message);
    }

}