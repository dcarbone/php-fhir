<?php namespace PHPFHIR\Template;

use PHPFHIR\Enum\PHPScopeEnum;
use PHPFHIR\Utilities\NameUtils;

/**
 * Class GetterMethodTemplate
 * @package PHPFHIR\Template
 */
class GetterMethodTemplate extends AbstractMethodTemplate
{
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
        $name = sprintf('get%s', NameUtils::getPropertyMethodName($propertyTemplate->getName()));

        parent::__construct($name, new PHPScopeEnum(PHPScopeEnum::_PUBLIC));

        $this->setDocumentation($propertyTemplate->getDocumentation());
        $this->propertyTypes = $propertyTemplate->getTypes();
        $this->propertyIsCollection = $propertyTemplate->isCollection();
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
    public function __toString()
    {
        $output = sprintf("    /**\n%s", self::_getDocumentationOutput());

        if ($this->propertyIsCollection())
            $output = sprintf("%s     * @return %s[]\n", $output, implode('[]|', $this->getPropertyTypes()));
        else
            $output = sprintf("%s     * @return %s\n", $output, implode('[]', $this->getPropertyTypes()));

        $output = sprintf("%s     */\n    %s function %s()\n    {\n", $output, (string)$this->getScope(), $this->getName());

        foreach($this->getBody() as $line)
        {
            $output = sprintf("%s        %s\n", $output, $line);
        }

        return sprintf("%s    }\n\n", $output);
    }
}