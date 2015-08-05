<?php namespace PHPFHIR\Template;

use PHPFHIR\Enum\PropertyScopeEnum;
use PHPFHIR\Enum\PropertyTypeEnum;
use PHPFHIR\Utilities\NameUtils;

/**
 * Class PropertyTemplate
 * @package PHPFHIR\Template
 */
class PropertyTemplate extends AbstractTemplate
{
    /** @var string */
    protected $name;
    /** @var PropertyScopeEnum */
    protected $scope;
    /** @var PropertyTypeEnum */
    protected $type;

    /**
     * Constructor
     *
     * @param string $name
     * @param PropertyScopeEnum $scope
     * @param PropertyTypeEnum $type
     */
    public function __construct($name, PropertyScopeEnum $scope, PropertyTypeEnum $type)
    {
        if (NameUtils::isValidPropertyName($name))
            $this->name = $name;
        else
            throw new \InvalidArgumentException('Specified parameter name "'.$name.'" is not valid.');

        $this->scope = $scope;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
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
    public function __toString()
    {
        $output = "    /**\n";

        if ($this->documentation)
            $output = sprintf("%s     * %s\n", $this->documentation);

        return <<<STRING
{$output}
     * @var {$this->type}
     */
    {$this->scope} \${$this->name};
STRING;
    }
}