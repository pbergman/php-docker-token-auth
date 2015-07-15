<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\WebToken;

use DockerToken\Exception\WebTokenException;

abstract class AbstractWebToken extends \ArrayObject
{
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

    public function getArrayCopy()
    {
        return array_filter(parent::getArrayCopy());
    }


}