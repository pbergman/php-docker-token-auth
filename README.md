##PHP docker registry 2 token authentication

This is a light weight docker token authentication build in php to the specs of the docs.

https://docs.docker.com/registry/spec/auth/token

It can be used to validate push/pull and registration us users for you private registry.

###Configuring


property |  required | type | description
---------|-----------|------|------
public_key   | yes |string |  the private key, should be the content not file location
private_key  | yes |string |  the public key, should be the content not file location
audience     | yes |string |  audience that is registered in the registry (server name of registry)
issuer       | yes |string |  issuer that is registered in the registry (server name of this server)
logger_level    | no | string\|array |  log level[s] to display (example: ['error', 'info'])
logger_file     | no |string |  log file or fd for logging , if non is given it will use stdout
route_end_point | no |string | The route endpoint to listen to, if non given it will default to /v2/token/
signing_algorithm   | no |string| The algoritm used for signing the token, supported: HS256, HS512, HS384, RS256 (but only RS256 tested :D)

the config should be given as argument with the constructor:

```
$app = new DockerToken\Application([
    'public_key'   => dirname(__FILE__) . '/public.key',
    'private_key'  => dirname(__FILE__) . '/private.key',
    'audience'     => 'registry.docker.com',
    'issuer'       => 'auth.docker.com',
])
```

###Validating

There are some listeners defined in src\DockerToken\Listener that can be used for validation (see) example.php or the tests.

To communicate between handlers you can use the (is|set)Access(Granted|Denied) methods (see LdapAuthListener or YamlAuthListener).

By default the flag is set to abstain en when finished when the flag is not granted it will see it as the authentiaction is not 
succesfull and throws a InvalidAccessException.

If you want to stop on success you can use the stopPropagation method because from the event because the set methods won`t 
do that. And on failure you can just call  the InvalidAccessException that will resolve in a 401 status.

When using DockerToken\Listener\YamlAuthListener you also need symfony/yaml.

###Running

php -S 127.0.0.1:9999 example.php

###Keys

to generate keys, see:

```
php bin/CreateKeys.php 
```
