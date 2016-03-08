<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\Event;

use DockerToken\Exception\InvalidAccessException;
use DockerToken\Request\Parameters;
use DockerToken\Request\ClaimSet;
use Silex\Application;
use Symfony\Component\EventDispatcher\Event;

class TokenRequestEvent extends Event implements TokenRequestEventInterface
{
    /** @var Parameters  */
    protected $parameters;
    /** @var Application  */
    protected $app;
    /** @var ClaimSet */
    protected $token;
    /** @var int */
    protected $access;

    /*
     * @inheritdoc
     */
    function __construct(Parameters $parameters, Application $app, ClaimSet $token)
    {
        $this->parameters = $parameters;
        $this->app = $app;
        $this->token = $token;
        $this->access = self::ACCESS_ABSTAIN;
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

    /**
     * @param int $access
     */
    public function setAccess($access)
    {
        $this->access = $access;
    }

    /**
     * @inheritdoc
     */
    public function setAccessGranted()
    {
        $this->setAccess(self::ACCESS_GRANTED);
    }

    /**
     * @inheritdoc
     */
    public function setAccessDenied()
    {
        $this->setAccess(self::ACCESS_DENIED);
    }

    /**
     * @return int
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * @return bool
     */
    public function isAccessDenied()
    {
        return $this->access === self::ACCESS_DENIED;
    }

    /**
     * @return bool
     */
    public function isAccessGranted()
    {
        return $this->access === self::ACCESS_GRANTED;
    }
}