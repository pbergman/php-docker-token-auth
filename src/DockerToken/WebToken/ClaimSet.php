<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\WebToken;

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
 * @package DockerToken\WebToken
 */
class ClaimSet extends AbstractWebToken
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
            "iss"    => $issuer,
            "sub"    => $subject,
            "aud"    => $audience,
            "exp"    => time() + $this->getExpiresIn(), // Expire in hour
            "nbf"    => time() - (60 * 10),             // Not before 10 minutes ago
            "iat"    => time(),                         // issued time
            "jti"    => strtoupper(bin2hex(openssl_random_pseudo_bytes(16))),
            'access' => [],
        ], self::ARRAY_AS_PROPS);
    }

    /**
     * Get time for token to be valid
     *
     * @return int
     */
    public function getExpiresIn()
    {
        return (60 * 60); // One hour
    }

    /**
     * @return string
     */
    public function getIssuedAt()
    {
        $it = new \DateTime("@" . $this["iat"]);
        $it->setTimezone(new \DateTimeZone("UTC"));
        return $it->format('Y-m-d\TH:i:s\Z');
    }


    /**
     * @param Access $access
     */
    public function addAccess(Access $access)
    {
        $this['access'][] = $access;
    }

    /**
     * @return array|Access[]
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
        $array = parent::getArrayCopy();
        if (!empty($array['access'])) {
            $accesses = [];
            /** @var Access $access */
            foreach($array['access'] as $access) {
                $accesses[] = $access->getArrayCopy();
            }
            $array['access'] = $accesses;
        }
        return $array;
    }


}