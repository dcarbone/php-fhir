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

    protected $isCollection = false;

    protected $types = array();

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     */
    public function setName($name)
    {
        if (NameUtils::isValidPropertyName($name))
            $this->name = $name;
        else
            throw new \InvalidArgumentException('Specified property name "'.$name.'" is not valid.');

    }

    /**
     * @return PHPScopeEnum
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param PHPScopeEnum $scope
     */
    public function setScope(PHPScopeEnum $scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return boolean
     */
    public function isCollection()
    {
        return $this->isCollection;
    }

    /**
     * @param boolean $isCollection
     */
    public function setIsCollection($isCollection)
    {
        $this->isCollection = $isCollection;
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
        $output = "    /**\n";

        if (isset($this->documentation))
        {
            foreach($this->documentation as $doc)
            {
                $output = sprintf("%s     * %s\n", $output, $doc);
            }
        }

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