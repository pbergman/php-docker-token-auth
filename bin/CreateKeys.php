<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */

class CreateKeysCommand
{
    protected $input;

    function __construct()
    {
        $this->input = array_merge(
            $this->defaults(),
            $this->processOptions()
        );
        $this->validateSetOptions();
        $this->validateNotSetOptions();
        if (isset($this->input['help'])) {
            echo $this->getHelper();
            exit();
        }

    }

    public function run()
    {
        if (strrev($this->input['folder']) !== DIRECTORY_SEPARATOR) {
            $this->input['folder'] .= DIRECTORY_SEPARATOR;
        }
        $files = [];
        foreach (['pub', 'key', 'crt', 'csr'] as $extension) {
            $files[$extension] = sprintf(
                '%s%s%s.%s',
                $this->input['folder'],
                $this->input['prefix'],
                $this->input['hostname'],
                $extension
            );
        }
        foreach ($files as $file) {
            if (file_exists($file)) {
                throw new RuntimeException(sprintf('File exist: %s', $file));
            }
        }
        $dn = array(
            "countryName"            => $this->input['country'],
            "stateOrProvinceName"    => $this->input['state-or-province-name'],
            "localityName"           => $this->input['locality-name'],
            "organizationName"       => $this->input['organization-name'],
            "organizationalUnitName" => $this->input['organizational-unit-name'],
            "commonName"             => $this->input['common-name'],
            "emailAddress"           => $this->input['email-address'],
        );
        // Create the private and public key
        $res = openssl_pkey_new([
            'digest_alg'        => $this->input['alg'],
            'private_key_bits'  => $this->input['bits'],
            'private_key_type'  => OPENSSL_KEYTYPE_RSA,
        ]);
        // Generate a certificate signing request
        $csr = openssl_csr_new(array_filter($dn), $res);
        // Creates a self-signed cert
        $sscert = openssl_csr_sign($csr, null, $res, $this->input['days']);
        openssl_csr_export($csr, $out);
        file_put_contents($files['csr'], $out);
        // Export certfile
        openssl_x509_export($sscert, $out);
        file_put_contents($files['crt'], $out);
        // Extract the private key from $res to $privKey
        openssl_pkey_export($res, $out);
        file_put_contents($files['key'], $out);
        // Extract the public key from $res to $pubKey
        $out = openssl_pkey_get_details($res);
        file_put_contents($files['pub'], $out["key"]);

    }

    protected function validateSetOptions()
    {
        foreach($this->input as $name => $value) {
            switch ($name) {
                case 'folder':
                    if (!is_dir($value) && false === mkdir($value)) {
                        throw new InvalidArgumentException(sprintf('Could not create folder: "%s"', $value));
                    }
                    break;
            }
        }
    }

    protected function validateNotSetOptions()
    {
        $options = array_values(array_map(
            function($v) {
                return $this->getRawName($v);
            },
            $this->getOptions()
        ));

        $options = array_filter(
            $options,
            function($v) {
                return !array_key_exists($v, $this->input);
            }
        );

        foreach ($options as $option) {
            switch($option) {
                case 'country':
                case 'state-or-province-name':
                case 'locality-name':
                case 'organization-name':
                case 'organizational-unit-name':
                case 'common-name':
                case 'email-address':
                    $name = strtr(sprintf('%s: ', $option), '-', ' ');
                    $this->input[$option] = $this->readline($name);
            }
        }
    }

    protected function readline($text)
    {
        if (function_exists('readline')) {
            return readline($text);
        } else {
            fwrite(STDOUT, $text);
            return trim(fgets(STDIN));
        }
    }

    protected function defaults()
    {
        return [
            'bits' => 2048,
            'alg' => 'sha512',
            'folder' => getcwd(),
            'days' => 356,
            'prefix' => null,
            'hostname' => gethostname(),
        ];
    }

    public function processOptions()
    {
        $options = $this->getOptions();
        $return = [];
        $input = getopt(implode('', array_keys($options)), array_values($options));

        foreach ($options as $short => $long) {
            if (isset($input[$this->getRawName($short)])) {
                $return[$this->getRawName($long)] = $input[$this->getRawName($short)];
            }
            if (isset($input[$this->getRawName($long)])) {
                $return[$this->getRawName($long)] = $input[$this->getRawName($long)];
            }
        }
        return $return;
    }

    protected function getRawName($name)
    {
        while (strrev($name)[0] === ':') {
            $name = substr($name, 0, -1);
        }

        return $name;
    }

    public function getOptions()
    {
        return [
            'f:' => 'folder:',
            'p:' => 'prefix:',
            'c:' => 'country:',
            's:' => 'state-or-province-name:',
            'l:' => 'locality-name:',
            'o:' => 'organization-name:',
            'u:' => 'organizational-unit-name:',
            'n:' => 'common-name:',
            'e:' => 'email-address:',
            'h:' => 'hostname:',

        ];
    }

    public function getHelper()
    {
        $cwd = getcwd();

        return <<<EOH

        Available options:

        -b, --bits                      Private key bits (default 2048)
        -a, --alg                       Digest alg (sha512)
        -f, --folder                    The folder where to save the keys (default: "$cwd")
        -p, --prefix                    Prefix for the keys
        -d, --days                      Days csr valid (365)
        -c, --country                   Country used for csr
        -s, --state-or-province-name    State or province name used for csr
        -l, --locality-name             Locality name used for csr
        -o, --organization-name         Organization name used for csr
        -u, --organizational-unit-name  Organizational unit name used for csr
        -n, --common-name               Common name used for csr
        -e, --email-address             Email address used for csr

EOH;

    }

}
(new CreateKeysCommand())->run();exit;
