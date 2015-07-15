<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\Event;

use DockerToken\Exception\InvalidAccessException;
use DockerToken\Request\Parameters;
use DockerToken\WebToken\ClaimSet;
use Silex\Application;
use Symfony\Component\EventDispatcher\Event;

class TokenRequestEvent extends Event
{
    /** @var Parameters  */
    protected $parameters;
    /** @var Application  */
    protected $app;
    /** @var ClaimSet */
    protected $token;

    /*
     * @inheritdoc
     */
    function __construct(Parameters $parameters, Application $app, ClaimSet $token)
    {
        $this->parameters = $parameters;
        $this->app = $app;
        $this->token = $token;
    }

    /**
     * @return Parameters
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return Application
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @return ClaimSet
     */
    public function getToken()
    {
        return $this->token;
    }


    /**
     * Set if request is failed
     */
    public function authenticationFailed()
    {
        throw new InvalidAccessException();
    }
}