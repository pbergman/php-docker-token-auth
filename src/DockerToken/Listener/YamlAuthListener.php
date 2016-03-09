<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\Listener;

use DockerToken\Application;
use DockerToken\Event\TokenRequestEventInterface;
use DockerToken\Exception\ListenerAccessException;
use Symfony\Component\Yaml\Yaml;

class YamlAuthListener
{
    /** @var array  */
    protected $users;

    function __construct($file)
    {
        if (!class_exists('Symfony\Component\Yaml\Yaml')) {
            throw new \RuntimeException('Install symfony/yaml to use this listener');
        }

        if (!is_file($file)) {
            throw new ListenerAccessException(sprintf('Could not find file: %s', $file));
        }

        $this->users = Yaml::parse($file);
    }

    /**
     * @inheritdoc
     */
    function __invoke(TokenRequestEventInterface $event)
    {

        if (false === $event->isAccessGranted()) {
            /** @var Application $app */
            $app = $event->getApp();
            $parameters = $event->getParameters();

            $app->getLogger()->info(sprintf(
                'Checking YAML authentication, user: "%s", scope: "%s"',
                $parameters->getAuthUsername(),
                $parameters->getScope()
            ));

            foreach($this->users as $user) {
                if ($user['username'] === $parameters->getAuthUsername() && $user['password'] === $parameters->getAuthPassword()) {

                    $app->getLogger()->debug(sprintf('User found: %s', isset($user['access']) ? json_encode($user['access']) : "[ALL RIGHTS]" ));

                    if (null === ($scope = $event->getParameters()->getScope())) {
                        // no scope to validate
                        $event->setAccessGranted();
                        return;
                    } else {
                        if (isset($user['access'])) {
                            foreach ($user['access'] as $access) {
                                $type = isset($access['type']) ? $access['type'] : null;
                                $name = isset($access['name']) ? $access['name'] : null;
                                $actions = isset($access['actions']) ? $access['actions'] : [];
                                if ($scope->isValid($type, $name, $actions)) {
                                    $event->setAccessGranted();
                                    return;
                                }
                            }
                        } else {
                            // no limits on account
                            $event->setAccessGranted();
                            return;
                        }
                    }
                    $event->setAccessDenied();
                }
            }
        }
    }
}