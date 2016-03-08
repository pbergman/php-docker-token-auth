<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\Request;

class Scope
{
    /** @var string  */
    protected $type;
    /** @var string */
    protected $name;
    /** @var array */
    protected $actions;

    /**
     * @inheritdoc
     */
    function __construct($type, $name, $actions)
    {
        $this->type = $type;
        $this->name = $name;
        $this->actions = $actions;
    }

    /**
     * will create a new scope object from a string for exmaple:
     *
     *   repository:samalba/my-app:push
     *
     * @param   string  $scope
     * @return  Scope|null
     */
    public static function fromString($scope)
    {
        if (!empty($scope)) {
            list($type, $name, $actions) = explode(':', $scope);
            return new self($type, $name, array_map('strtolower', array_map('trim', explode(',', $actions))));
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * will validate given token based on given values
     *
     * @param   null    $type
     * @param   null    $name
     * @param   array   $actions
     * @return  bool
     */
    public function isValid($type = null, $name = null, array $actions = [])
    {
        if (!is_null($type) && $this->type !== $type) {
            return false;
        }

        if (!is_null($name) && $this->name !== $name) {
            return false;
        }

        if (!empty($actions)) {

            if (count($actions) !== count($this->actions)) {
                return false;
            }

            foreach ($actions as $action) {
                if (!in_array(strtolower($action), $this->actions)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    function __toString()
    {
        return sprintf('%s:%s:%s', $this->type, $this->name, implode(',', $this->actions));
    }


    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return [
            'type'    => $this->type,
            'name'    => $this->name,
            'actions' => $this->actions,
        ];
    }
}