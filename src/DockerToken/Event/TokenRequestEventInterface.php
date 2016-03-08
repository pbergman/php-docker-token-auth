<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\Event;

use DockerToken\Request\Parameters;
use DockerToken\Request\ClaimSet;
use Silex\Application;

interface TokenRequestEventInterface
{
    const ACCESS_ABSTAIN = 'access.abstain';
    const ACCESS_DENIED = 'access.denied';
    const ACCESS_GRANTED = 'access.granted';

    /**
     * @param Parameters    $parameters     parameters holding credentials, scope etc.
     * @param Application   $app            the main app
     * @param ClaimSet      $token          the token that is going to be send back
     */
    function __construct(Parameters $parameters, Application $app, ClaimSet $token);

    /**
     * @return Parameters
     */
    public function getParameters();

    /**
     * @return Application
     */
    public function getApp();

    /**
     * @return ClaimSet
     */
    public function getToken();

    /**
     * will mark event as successfull authentication
     */
    public function setAccessGranted();

    /**
     * will mark event as failed authentication, will have no effection on propagation
     */
    public function setAccessDenied();

    /**
     * @return bool
     */
    public function isAccessDenied();

    /**
     * @return bool
     */
    public function isAccessGranted();

    /**
     * returns the current access bit
     *
     * @return string
     */
    public function getAccess();
}