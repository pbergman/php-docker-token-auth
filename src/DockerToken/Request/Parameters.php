<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\Request;

use DockerToken\Application;
use DockerToken\Exception\InvalidRequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class Parameters
{
    /** @var string  */
    protected $account;
    /** @var string */
    protected $service;
    /** @var Scope */
    protected $scope;
    /** @var  string */
    protected $authUsername;
    /** @var string */
    protected $authPassword;

    /**
     * @param Application $app
     */
    function __construct(Application $app)
    {
        $this->service = $app['request']->get('service');
        $this->scope = Scope::fromString($app['request']->get('scope'));
        $this->account = $app['request']->get('account');
        $this->parseAuthorization($app['request'], $app['logger']);
    }

    /**
     * get username and password from the headers
     *
     * @param   Request $request
     * @throws  InvalidRequestException
     */
    protected function parseAuthorization(Request $request, LoggerInterface $logger)
    {
        $headers = $request->headers;
        $username = $headers->get('php-auth-user');
        $password = $headers->get('php-auth-pw');

        if (is_null($username) || is_null($password)) {
            if (null === $authorization = $headers->get('authorization')) {
                $logger->error(sprintf('Could not get authorization from header, got: "%s"', implode('", "', $headers->keys())));
                throw new InvalidRequestException();
            } else {

                if (preg_match('#^basic\s([a-z0-9\+/=]+)#i', $authorization, $m)) {
                    list($username, $password) = explode(':', base64_decode($m[1]));
                } else {
                    $logger->error(sprintf('Unsupported authorization header: "%s', $authorization));
                    throw new InvalidRequestException();
                }
            }
        }

        $this->authPassword = $password;
        $this->authUsername = $username;
    }

    /**
     * @return mixed
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @return mixed
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return Scope
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->authPassword;
    }

    /**
     * @return string
     */
    public function getAuthUsername()
    {
        return $this->authUsername;
    }
}