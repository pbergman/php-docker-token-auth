<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
require_once __DIR__.'/vendor/autoload.php';

$app = new \DockerToken\Application(array(
    'public_key'   => dirname(__FILE__) . '/test.pub',
    'private_key'  => dirname(__FILE__) . '/test.key',
    'audience'     => 'registry.docker.com',
    'issuer'       => 'auth.docker.com',
));

// listener for defined users
// $app->on($app::REGISTRY_REQUEST_EVENT, new \DockerToken\Listener\YamlAuthListener('users.example.yml'));
// custom listener with hardcoded user/password
$app->on($app::REGISTRY_REQUEST_EVENT, function(DockerToken\Event\TokenRequestEventInterface $event){
    if ($event->isAccessGranted() || $event->isAccessDenied()) {
	    if ($event->getParameters()->getAuthUsername() !== 'foo' || $event->getParameters()->getAuthPassword() !== 'bar') {
		    // could throw InvalidAccessException but then no other event will be called
		    // throw new \DockerToken\Exception\InvalidAccessException();
		    $event->setAccessDenied();
	    }
	    $event->setAccessGranted();
    }
});
// listener for LDAP
// $app->on($app::REGISTRY_REQUEST_EVENT, new \DockerToken\Listener\LdapAuthListener('uid={username},dc=example,dc=com', '127.0.0.1'));
$app->run();