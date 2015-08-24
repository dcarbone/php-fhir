<?php namespace PHPFHIR\Template;

use PHPFHIR\Utilities\NameUtils;

/**
 * Class ParameterTemplate
 * @package PHPFHIR\Template
 */
class ParameterTemplate
{
    /** @var string */
    protected $name;

    /** @var array */
    protected $propertyTypes;

    /** @var bool */
    protected $propertyIsCollection;

    /**
     * Constructor
     *
     * @param PropertyTemplate $propertyTemplate
     */
    public function __construct(PropertyTemplate $propertyTemplate)
    {
        $this->name = $propertyTemplate->getName();
        $this->propertyTypes = $propertyTemplate->getTypes();
        $this->propertyIsCollection = $propertyTemplate->isCollection();
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
    public function getPropertyTypes()
    {
        return $this->propertyTypes;
    }

    /**
     * @return boolean
     */
    public function propertyIsCollection()
    {
        return $this->propertyIsCollection;
    }

    /**
     * @return string
     */
    public function getParamDocBlock()
    {
        if ($this->propertyIsCollection)
            return sprintf('@param %s[] %s', implode('[]|', $this->getPropertyTypes()), NameUtils::getPropertyVariableName($this->getName()));

        return sprintf('@param %s %s', implode('|', $this->getPropertyTypes()), NameUtils::getPropertyVariableName($this->getName()));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return NameUtils::getPropertyVariableName($this->getName());
    }
}