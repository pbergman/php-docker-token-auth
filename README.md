##PHP docker registry 2 token authentication

This is a light weight docker token authentication build in php to the specs of the docs.

https://docs.docker.com/registry/spec/auth/token

It can be used to validate push/pull and registration us users for you private registry.

###Configuring


property | type | description
---------|-------------
prop.public_key   | string |  the private key, should be the content not file location
prop.private_key  | string |  the public key, should be the content not file location
prop.audience     | string |  audience that is registered in the registry (server name of registry)
prop.issuer       | string |  issuer that is registered in the registry (server name of this server)
prop.log_level    | string:array |  log level[s] to display (example: ['error', 'info'])
prop.log_file     |string |  log file for logging , if non is given it will use stdout


the config should be given as argument with the constructor:

```
$app = new DockerToken\Application([
    'prop.public_key'   => file_get_contents(dirname(__FILE__) . '/public.key'),
    'prop.private_key'  => file_get_contents(dirname(__FILE__) . '/private.key'),
    'prop.audience'     => 'registry.docker.com',
    'prop.issuer'       => 'auth.docker.com',
])
```

###Validating

This application has on it self no validation, this can be added by adding a listener.(see example.php)

If credentials are invalid you can throw a InvalidAccessException this will result in a 401 response.

###Running

php -S 127.0.0.1:9999 example.php

###Keys

to generate keys, see:

```
php bin/CreateKeys.php 
```
