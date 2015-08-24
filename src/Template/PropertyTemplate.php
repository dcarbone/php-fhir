<?php namespace PHPFHIR\Template;

use PHPFHIR\Enum\PHPScopeEnum;
use PHPFHIR\Utilities\NameUtils;

/**
 * Class PropertyTemplate
 * @package PHPFHIR\Template
 */
class PropertyTemplate extends AbstractTemplate
{
    /** @var string */
    protected $name;

    /** @var PHPScopeEnum */
    protected $scope;

    /** @var bool */
    protected $isCollection = false;

    /** @var array  */
    protected $types = array();

    /**
     * @param string $name
     * @param PHPScopeEnum $scope
     * @param bool $isCollection
     */
    public function __construct($name, PHPScopeEnum $scope, $isCollection)
    {
        if (NameUtils::isValidPropertyName($name))
            $this->name = $name;
        else
            throw new \InvalidArgumentException(sprintf('Specified property name "%s" is not valid.', $name));

        $this->scope = $scope;
        $this->isCollection = $isCollection;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return PHPScopeEnum
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return boolean
     */
    public function isCollection()
    {
        return $this->isCollection;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param string $type
     */
    public function addType($type)
    {
        $this->types[] = $type;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $output = sprintf("    /**\n%s", self::_getDocumentationOutput());

        if ($this->isCollection())
        {
            return sprintf("%s     * @var %s[]\n     */\n    %s %s = array();\n\n",
                $output,
                implode('|', $this->types),
                (string)$this->getScope(),
                NameUtils::getPropertyVariableName($this->getName())
            );
        }
        else
        {
            return sprintf("%s     * @var %s\n     */\n    %s %s;\n\n",
                $output,
                implode('|', $this->types),
                (string)$this->getScope(),
                NameUtils::getPropertyVariableName($this->getName())
            );
        }
    }
}