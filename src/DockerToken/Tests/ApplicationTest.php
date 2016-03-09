<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\Tests;

use DockerToken\Event\TokenRequestEventInterface;
use DockerToken\Application;
use DockerToken\Listener\YamlAuthListener;
use Symfony\Component\HttpFoundation\Request;

class ApplicationTest extends  \PHPUnit_Framework_TestCase
{
	/** @var Application */
	protected $app;

	protected function setUp()
	{
		$this->app = new Application([
			'public_key'   => dirname(__FILE__) . '/Utils/test.pub',
			'private_key'  => dirname(__FILE__) . '/Utils/test.key',
			'audience'     => 'registry.docker.com',
			'issuer'       => 'auth.docker.com',
			'logger_file'  => fopen('/dev/null', 'w+')
		]);

	}

	public function testBasicHeader()
	{
		$request = Request::create('/v2/token/');
		$response = $this->app->handle($request);
		$this->assertEquals(500, $response->getStatusCode());

		$request->headers->set('authorization', 'basic aaa:bbb');
		$response = $this->app->handle($request);
		$this->assertEquals(500, $response->getStatusCode());

		$request->headers->set('authorization', sprintf('Basic %s', base64_encode('foo:bar')));
		$response = $this->app->handle($request);
		$this->assertEquals(200, $response->getStatusCode());
	}

	public function testBasicAuthentication()
	{
		$this->app->on(Application::REGISTRY_REQUEST_EVENT, function(TokenRequestEventInterface $event){
			if ($event->getParameters()->getAuthUsername() === 'foo' && $event->getParameters()->getAuthPassword() === 'bar') {
				$event->setAccessGranted();
			} else {
				$event->setAccessDenied();
			}
		});

		$request = Request::create('/v2/token/');
		$request->headers->set('authorization', sprintf('Basic %s', base64_encode('fooooo:baaaaar')));
		$response = $this->app->handle($request);
		$this->assertEquals(401, $response->getStatusCode());

		$request = Request::create('/v2/token/');
		$request->headers->set('authorization', sprintf('Basic %s', base64_encode('foo:bar')));
		$response = $this->app->handle($request);
		$this->assertEquals(200, $response->getStatusCode());
	}

	public function testToken()
	{
		$time = time();
		$this->app->on(Application::REGISTRY_REQUEST_EVENT, function(TokenRequestEventInterface $event) use ($time) {
			$token = $event->getToken();
			$token->iat = $time;
			$token->nbf = $time;
			$token->exp = $time + (3600 * 3);
			$token->jti = 1122334455667788;
			if ($event->getParameters()->getAuthUsername() === 'foo' && $event->getParameters()->getAuthPassword() === 'bar') {
				$event->setAccessGranted();
			} else {
				$event->setAccessDenied();
			}
		});
		$request = Request::create('/v2/token/');
		$request->query->set('scope','repository:php:pull,push');
		$request->headers->set('authorization', sprintf('Basic %s', base64_encode('foo:bar')));
		$response = $this->app->handle($request);
		$this->assertEquals(200, $response->getStatusCode());

		$data = json_decode($response->getContent());
		$this->assertEquals($data->expires_in, (3600 * 3));
		$this->assertEquals($data->issued_at,  (new \DateTime("@".$time, new \DateTimeZone("UTC")))->format('Y-m-d\TH:i:s\Z'));
		// https://docs.docker.com/registry/spec/auth/jwt/
		$token = [
			'iss' => $this->app['options']['issuer'],
            'aud' => $this->app['options']['audience'],
			'iat' => $time,
            'jti' => 1122334455667788,
            'access' => [
	            [
					'type' => 'repository',
					'name' => 'php',
					'actions' => ['pull', 'push'],
				],
            ],
			'exp' => $time + (3600 * 3),
            'nbf' => $time,
		];

		$this->assertSame($token, json_decode(base64_decode(strtr(explode('.', $data->token)[1], '-_,', '+/=')), true));
	}

	public function testListeners()
	{
		$this->app->on(Application::REGISTRY_REQUEST_EVENT, function(TokenRequestEventInterface $event) {
			if ($event->getParameters()->getAuthUsername() === 'foo' && $event->getParameters()->getAuthPassword() === 'bar') {
				$event->setAccessGranted();
			} else {
				$event->setAccessDenied();
			}
		}, 100);
		$this->app->on(Application::REGISTRY_REQUEST_EVENT, new YamlAuthListener(dirname(__FILE__) . '/../../../users.example.yml'));

		$request = Request::create('/v2/token/');
		$request->query->set('scope','repository:php:pull,push');
		$request->headers->set('authorization', sprintf('Basic %s', base64_encode('foo:bar')));
		$response = $this->app->handle($request);
		$this->assertEquals(200, $response->getStatusCode());

		$request->query->set('scope','repository:php:pull,push');
		$request->headers->set('authorization', sprintf('Basic %s', base64_encode('user1:password1')));
		$response = $this->app->handle($request);
		$this->assertEquals(200, $response->getStatusCode());

		$request->query->set('scope','repository:php:pull,delete');
		$request->headers->set('authorization', sprintf('Basic %s', base64_encode('user1:password1')));
		$response = $this->app->handle($request);
		$this->assertEquals(401, $response->getStatusCode());

		$request->query->set('scope','repository:foo:delete');
		$request->headers->set('authorization', sprintf('Basic %s', base64_encode('user1:password1')));
		$response = $this->app->handle($request);
		$this->assertEquals(401, $response->getStatusCode());

		$request->query->set('scope','repository:foo:pull');
		$request->headers->set('authorization', sprintf('Basic %s', base64_encode('user1:password1')));
		$response = $this->app->handle($request);
		$this->assertEquals(200, $response->getStatusCode());

	}

	public function testAccessAbstain()
	{
		$this->app->on(Application::REGISTRY_REQUEST_EVENT, function(TokenRequestEventInterface $event) {
			if ($event->getParameters()->getAuthUsername() !== 'foo' && $event->getParameters()->getAuthPassword() !== 'bar') {
				$event->setAccessDenied();
			}
		}, 100);

		$request = Request::create('/v2/token/');
		$request->headers->set('authorization', sprintf('Basic %s', base64_encode('foo:bar')));
		$response = $this->app->handle($request);
		$this->assertEquals(401, $response->getStatusCode());
	}
}