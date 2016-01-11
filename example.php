<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
require_once __DIR__.'/vendor/autoload.php';

$app = new \DockerToken\Application(array(
    'prop.public_key'   => file_get_contents(dirname(__FILE__) . '/public.key'),
    'prop.private_key'  => file_get_contents(dirname(__FILE__) . '/private.key'),
    'prop.audience'     => 'registry.docker.com',
    'prop.issuer'       => 'auth.docker.com',
));
$app->on($app::REGISTRY_REQUEST_EVENT, function(DockerToken\Event\TokenRequestEvent $event){
    if ($event->getParameters()->getAuthUsername() !== 'foo' || $event->getParameters()->getAuthUsername() !== 'bar') {
        throw new \DockerToken\Exception\InvalidAccessException();
    }
});
$app->run();