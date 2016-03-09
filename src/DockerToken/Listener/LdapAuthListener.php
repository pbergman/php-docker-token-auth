<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\Listener;

use DockerToken\Application;
use DockerToken\Event\TokenRequestEventInterface;
use DockerToken\Exception\ListenerAccessException;

/**
 * Class LdapAuthListener
 *
 * @package DockerToken\Listener
 */
class LdapAuthListener
{
    /** @var string  */
    protected $rdn;
    /** @var string */
    protected $host;
    /** @var string  */
    protected $filter;

    /**
     * @param string    $rdn        rdn format, for example: uid={username},ou=users,dc={host}, will resolve vars with {}
     * @param string    $host       host to connect with
     * @param string    $filter     filter to search for
     */
    function __construct($rdn, $host, $filter = '(uid={username})')
    {
        $this->rdn = $rdn;
        $this->host = $host;
        $this->filter = $filter;
    }

    /**
     * @inheritdoc
     */
    function __invoke(TokenRequestEventInterface $event)
    {
        // If other listener has granted access, we don`t need to authenticate
        if (false === $event->isAccessGranted()) {
            /** @var Application $app */
            $app = $event->getApp();
            $parameters = $event->getParameters();
            $vars = ['{username}' => $parameters->getAuthUsername(), '{host}' => $this->host];
            $rdn = $this->format($this->rdn, $vars);
            $filter = $this->format($this->filter, $vars);

            $app->getLogger()->info(sprintf(
                'Checking LDAP authentication, user: "%s", scope: "%s", rdn: "%s"',
                $parameters->getAuthUsername(),
                $parameters->getScope(),
                $rdn
            ));

            if (false === ($connection = @ldap_connect($this->host))) {
                $message = 'Could not connect to LDAP server';
                $app->getLogger()->error($message );
                throw new ListenerAccessException($message);
            }

            ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);

            if (false === @ldap_bind($connection, $rdn, $parameters->getAuthPassword())) {
                $app->getLogger()->error(sprintf("Authentication failed for user '%s'", $parameters->getAuthUsername()));
                $app->getLogger()->error(ldap_error($connection));
                $event->setAccessDenied();
                ldap_close($connection);
            } else {
                if (false === ($result = @ldap_search($connection, $rdn, $filter, ['uid']))) {
                    $app->getLogger()->error(ldap_error($connection));
                    $event->setAccessDenied();
                } else {
                    $entries = ldap_get_entries($connection, $result);
                    ldap_unbind($connection);
                    ldap_close($connection);
                    if (isset($entries['count']) && $entries['count'] > 0) {
                        $app->getLogger()->info(sprintf("Authentication success for user '%s'", $parameters->getAuthUsername()));
                        $event->setAccessGranted();
                    } else {
                        $app->getLogger()->error(sprintf("Authentication failed for user '%s'", $parameters->getAuthUsername()));
                        $event->setAccessDenied();
                    }
                }
            }
        }
    }

    /**
     * @param   string  $string
     * @param   array   $vars
     * @return  string
     */
    protected function format($string, array $vars)
    {
        return str_replace(array_keys($vars), array_values($vars), $string);
    }
}