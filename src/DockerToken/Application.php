<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken;

use Firebase\JWT\JWT;
use Base32\Base32;
use DockerToken\Event\TokenRequestEvent;
use DockerToken\Exception\InvalidAccessException;
use DockerToken\Exception\ParameterException;
use DockerToken\Logger\StreamLogger;
use DockerToken\Request\Parameters;
use DockerToken\Request\ClaimSet;
use Silex\Application as BaseApplication;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Application
 *
 * @package DockerToken
 */
class Application extends BaseApplication
{
    const REGISTRY_REQUEST_EVENT = 'registry.request.event';
    const PEM_PREG = '#^-----BEGIN ([A-Z ]+)-----\s*?(?<DATA>[A-Za-z0-9+=/\r\n]+)\s*?-----END \1-----\s*$#D';

    /**
     * @inheritdoc
     */
    public function __construct(array $values = array())
    {

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        parent::__construct([
            'options' => $resolver->resolve($values)
        ]);

        $this->initializeLogger();
        $this->configureRoute();
    }


    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired([
                'public_key',
                'private_key',
                'audience',
                'issuer',
            ])
            ->setDefaults([
                'logger_level'      =>  null,
                'logger_file'       =>  null,
                'signing_algorithm' => 'RS256',
                'route_end_point'   => '/v2/token/',

            ])
            ->setAllowedValues('public_key',  function($value){
                return is_file($value) && preg_match(self::PEM_PREG, file_get_contents($value));
            })
            ->setAllowedValues('private_key', function($value){
                return is_file($value) && preg_match(self::PEM_PREG, file_get_contents($value));
            })
            ->setAllowedValues('signing_algorithm', function($value){
                return in_array($value, array_keys(JWT::$supported_algs));
            })
            ->setAllowedValues('logger_file',  function($value){
                return @is_file($value) || @is_null($value) || @is_resource($value);
            })
            ->setAllowedTypes('audience', 'string')
            ->setAllowedTypes('issuer',   'string')
            ->setNormalizer('logger_level', function(Options $options, $value){
                if (!empty($value) && !is_array($value)) {
                    $value = (array) $value;
                }
                return $value;
            })
            ->setNormalizer('logger_file', function(Options $options, $value){
                if (@is_resource($value)) {
                    return $value;
                }

                if (@is_file($value)) {
                    return fopen($value,  'a+');
                }

                return STDOUT;
            })
        ;
    }

    /**
     * add event listener for /v2/token/ url request
     */
    protected function configureRoute()
    {

        $this->get($this['options']['route_end_point'], function() {

            try {
                $parameters = new Parameters($this);
                $token = new ClaimSet(
                    $this['options']['audience'],
                    $parameters->getAccount(),
                    $this['options']['issuer']
                );

                if (null !== ($scope = $parameters->getScope())) {
                    $token->addAccess($scope);
                }

                /** @var EventDispatcherInterface $dispatcher */
                if (null !== ($dispatcher = $this['dispatcher'])) {
                    if ($dispatcher->hasListeners(self::REGISTRY_REQUEST_EVENT)) {
                        /** @var TokenRequestEvent $event */
                        $event = $dispatcher->dispatch(
                            self::REGISTRY_REQUEST_EVENT,
                            new TokenRequestEvent($parameters, $this, $token)
                        );

                        if ($event->getAccess() !== $event::ACCESS_GRANTED) {
                            throw new InvalidAccessException();
                        }
                    }
                }

                // as described on https://docs.docker.com/registry/spec/auth/token/
                return $this->json(
                    [
                        'token' =>  JWT::encode(
                            $token->getArrayCopy(),
                            $this->getSignKey(),
                            $this['options']['signing_algorithm'],
                            $this->getKid()
                        ),
                        'expires_in' => $token->getExpiresTime(),
                        'issued_at'  => $token->getFormattedIssuedAt(),
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
     * get signing key, when using hashmac we need to decode key??
     *
     * @return string
     */
    protected function getSignKey()
    {
        return $this->getPrivateKey(JWT::$supported_algs[$this['options']['signing_algorithm']][0] !== 'openssl');
    }

    /**
     * Setup logger
     */
    protected function initializeLogger()
    {
        $this['logger'] = new StreamLogger($this['options']['logger_file'], $this['options']['logger_level']);
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
        return implode(':', array_slice(str_split(rtrim(Base32::encode(hash('sha256', $this->getPrivateKey(true), true)), '='), 4), 0, 12));
    }

    /**
     * open/decode the private key file
     *
     * @param   bool|false $decode
     * @return  string
     */
    protected function getPrivateKey($decode = false)
    {
        $data = file_get_contents($this['options']['private_key']);
        if ($decode) {
            preg_match(self::PEM_PREG, $data, $m);
            return base64_decode(preg_replace('/\n|\r/', '',  $m['DATA']));
        }  else {
            return $data;
        }
    }

}
