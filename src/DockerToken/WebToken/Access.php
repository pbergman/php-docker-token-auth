<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\WebToken;

use DockerToken\Exception\ParameterException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ClaimSet
 *
 * @property string $type
 * @property string $name
 * @property array  actions
 *
 * @package DockerToken\Access
 */
class Access extends AbstractWebToken
{
    const ACTION_DENY_EVERYTHING = '';
    const ACTION_ALLOW_EVERYTHING = '*';

    /**
     * @param string    $type
     * @param string    $name
     * @param array     $actions
     */
    public function __construct($type, $name, array $actions = [self::ACTION_ALLOW_EVERYTHING])
    {
        parent::__construct([
            'type'    => $type,
            'name'    => $name,
            'actions' => $actions,
        ], self::ARRAY_AS_PROPS);
    }

    /**
     * @param   Request $request
     * @return  Access
     * @throws  ParameterException
     */
    public static function newAccessFromRequest(Request $request)
    {
        if (null !== $scope = $request->get('scope')) {
            list($type, $name, $actions) = explode(':', $scope, 3);
            return new self($type, $name, explode(',', $actions));
        } else {
            throw new ParameterException('No scope found in request');
        }
    }


}
