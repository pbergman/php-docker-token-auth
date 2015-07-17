<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\Exception;

use Exception;

class InvalidRequestException extends \Exception implements ExceptionInterface
{
    public function __construct($message = 'Invalid request')
    {
        parent::__construct($message);
    }

}