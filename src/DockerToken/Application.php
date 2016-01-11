<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken;

use JWT;
use Base32\Base32;
use DockerToken\Event\TokenRequestEvent;
use DockerToken\Exception\InvalidAccessException;
use DockerToken\Exception\ParameterException;
use DockerToken\Logger\StreamLogger;
use DockerToken\Request\Parameters;
use DockerToken\WebToken\Access;
use DockerToken\WebToken\ClaimSet;
use Silex\Application as BaseApplication;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

class Application extends BaseApplication
{
    const REGISTRY_REQUEST_EVENT = 'registry.request.event';
    const PEM_PREG = '#^-----BEGIN ([A-Z ]+)-----\s*?(?<DATA>[A-Za-z0-9+=/\r\n]+)\s*?-----END \1-----\s*$#D';

    /**
     * @inheritdoc
     */
    public function __construct(array $values = array())
    {
        parent::__construct($values);
        $this->initialize();
    }

    /**
     * Setup application config and validate the config
     */
    protected function initialize()
    {
        $this->validateOptions();
        $this->initializeLogger();
        $this->configureRoute();
    }

    /**
     * add event listener for /v2/token/ url request
     */
    protected function configureRoute()
    {

        $this->get('/v2/token/', function() {

            try {
                $parameters = new Parameters($this);
                $token = new ClaimSet(
                    $this['prop.audience'],
                    $parameters->getAccount(),
                    $this['prop.issuer']
                );
                if (null !== $scope = $parameters->getScope()) {
                    list($type, $name, $actions) = explode(':', $scope, 3);
                    $token->addAccess(new Access($type, $name, explode(',', $actions)));
                }

                /** @var EventDispatcherInterface $dispatcher */
                if (null !== ($dispatcher = $this['dispatcher'])) {
                    if ($dispatcher->hasListeners(self::REGISTRY_REQUEST_EVENT)) {
                        $dispatcher->dispatch(
                            self::REGISTRY_REQUEST_EVENT,
                            new TokenRequestEvent($parameters, $this, $token)
                        );
                    }
                }
                // as described on https://docs.docker.com/registry/spec/auth/token/
                return $this->json(
                    [
                        'token' =>  JWT::encode(
                            $token->getArrayCopy(),
                            $this['prop.private_key'],
                            'RS256',
                            $this->getKid()
                        ),
                        'expires_in' => $token->getExpiresIn(),
                        'issued_at'  => $token->getIssuedAt(),
                    ],
                    Response::HTTP_OK
                );

            } catch (InvalidAccessException $e) {
                return new Response(
                    $e->getMessage(),
                    Response::HTTP_UNAUTHORIZED
                );
            } catch (\Exception $e) {
                $this['logger']->error(
                    sprintf('Exception thrown: %s @ %s(%s),', $e->getMessage(), $e->getFile(), $e->getLine())
                );
                return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        });
    }

    /**
     * validate the given options
     *
     * @throws ParameterException
     */
    protected function validateOptions()
    {
        if (!isset($this['prop.public_key'])) {
            throw new ParameterException('No public key defined');
        }
        if (!isset($this['prop.private_key'])) {
            throw new ParameterException('No private key defined');
        }
        if (!isset($this['prop.audience'])) {
            throw new ParameterException('No audience for claim defined');
        }
        if (!isset($this['prop.issuer'])) {
            throw new ParameterException('No issuer for claim defined');
        }
    }

    /**
     * Setup logger
     */
    protected function initializeLogger()
    {
        $levels = (isset($this['prop.log_level'])) ? (array) $this['prop.log_level'] : null;

        if (isset($this['prop.log_file'])) {

            if (is_file($this['prop.log_file'])) {
                $this['prop.log_file'] = fopen($this['prop.log_file'], 'a+');
            }

            if (!is_resource($this['prop.log_file'])) {
                throw new ParameterException(sprintf('Logger file should be valid file or resource, given "%s"', gettype($this['prop.log_file'])));
            }

            $this['logger'] = new StreamLogger($this['prop.log_file'], $levels);
        } else {
            $this['logger'] = new StreamLogger(fopen('php://stdout', 'w'), $levels);
        }
     }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(){
        return $this['logger'];
    }

    /**
     * Create a kid from the public that the registry will
     * use to verify the public key it got configured.
     * @return string
     * @throws InvalidAccessException
     */
    public function getKid()
    {
        if (false == preg_match(self::PEM_PREG, $this['prop.public_key'], $m)) {
            $this['logger']->error('Invalid PEM format encountered.');
            throw new InvalidAccessException();
        }
        $key = preg_replace('/\n|\r/', '',  $m['DATA']);
        $key = array_slice(str_split(rtrim(Base32::encode(hash('sha256', base64_decode($key), true)), '='), 4), 0, 12);
        return implode(':', $key);
    }

}
