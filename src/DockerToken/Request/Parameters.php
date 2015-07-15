<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\Request;

use DockerToken\Application;
use DockerToken\Exception\InvalidAccessException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class Parameters
{
    /** @var string  */
    protected $account;
    /** @var string */
    protected $service;
    /** @var string */
    protected $scope;
    /** @var  string */
    protected $authUsername;
    /** @var string */
    protected $authPassword;
    /** @var LoggerInterface  */
    protected $logger;

    /**
     * @param Application $app
     */
    function __construct(Application $app)
    {
        $this->service = $app['request']->get('service');
        $this->scope = $app['request']->get('scope');
        $this->account = $app['request']->get('account');
        $this->logger = $app['logger'];
        $this->parseAuthorization($app['request']);
    }

    /**
     * get username and password from the headers
     *
     * @param   Request $request
     * @throws  InvalidAccessException
     */
    protected function parseAuthorization(Request $request)
    {
        $headers = $request->headers;
        $username = $headers->get('php-auth-user');
        $password = $headers->get('php-auth-pw');

        if (is_null($username) || is_null($password)) {
            if (null === $authorization = $headers->get('authorization')) {
                $this->logger->critical('Could not get authorization header');
                throw new InvalidAccessException('Could not get authorization header');
            } else {
                if (preg_match('/^(.*):(.*)$/', base64_decode($authorization), $m)) {
                    list(, $username, $password) = $m;
                } else {
                    $this->logger->error('Authorization header is invalid');
                    throw new InvalidAccessException('Authorization header is invalid');
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
     * @return mixed
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