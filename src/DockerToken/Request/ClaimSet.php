<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\Request;

use DockerToken\Exception\WebTokenException;

/**
 * Class ClaimSet
 *
 * @property string $iss
 * @property string $sub
 * @property string $aud
 * @property int    $exp
 * @property int    $nbf
 * @property int    $iat
 * @property string $jti
 *
 * @package DockerToken\Request
 */
class ClaimSet extends \ArrayObject
{
    /**
     * @inheritdoc
     */
    public function __construct($audience = null, $subject = null, $issuer = null)
    {
        if (is_null($issuer)) {
            $issuer = $_SERVER['HTTP_HOST'];
        }

        parent::__construct([
            "iss" => $issuer,
            "sub" => $subject,
            "aud" => $audience,
            "iat" => time(),     // issued time
            "jti" => strtoupper(bin2hex(openssl_random_pseudo_bytes(16))),
            'access' => [],
            'exp' => 0,
            'nbf' => 0,
        ], self::ARRAY_AS_PROPS);

        // Set some times
        $this["exp"] = $this["iat"] + (60 * 60); // Expire in hour
        $this["nbf"] = $this["iat"] - (60 * 10); // Not before 10 minutes ago

    }

    /**
     * Get time for token to be valid
     *
     * @return string
     */
    public function getExpiresTime()
    {
        return $this["exp"] - $this["iat"];
    }

    /**
     * @return string
     */
    public function getFormattedIssuedAt()
    {
        return (new \DateTime("@".$this["iat"], new \DateTimeZone("UTC")))->format('Y-m-d\TH:i:s\Z');
    }


    /**
     * @param Scope $access
     */
    public function addAccess(Scope $scope)
    {
        $this['access'][] = $scope;
    }

    /**
     * @return array|Scope[]
     */
    public function getAccess()
    {
        return $this['access'];
    }

    /**
     * @inheritdoc
     */
    public function getArrayCopy()
    {
        $array = array_filter(parent::getArrayCopy());
        if (!empty($array['access'])) {
            $array['access'] = array_map(function(Scope $s) { return $s->getArrayCopy(); }, $array['access']);
        }
        return $array;
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($index, $newval)
    {
        switch (true) {
            case empty($index):
                throw new WebTokenException('Unsupported to set array with no offset (append)');
                break;
            case $index !== 'access' && !isset($this[$index]):
                throw new WebTokenException(
                    sprintf('Invalid property "%s", available properties: "%s"', $index, implode('", "', array_keys($this->getArrayCopy())))
                );
                break;
            default:
                parent::offsetSet($index, $newval);
        }
    }
}