<?php namespace PHPFHIR\Template;

use PHPFHIR\Enum\PHPScopeEnum;
use PHPFHIR\Utilities\NameUtils;

/**
 * Class SetterMethodTemplate
 * @package PHPFHIR\Template
 */
class SetterMethodTemplate extends AbstractMethodTemplate
{
    /** @var ParameterTemplate[] */
    protected $parameters = array();

    /**
     * Constructor
     *
     * @param PropertyTemplate $propertyTemplate
     */
    public function __construct(PropertyTemplate $propertyTemplate)
    {
        if ($propertyTemplate->isCollection())
            $name = sprintf('add%s', NameUtils::getPropertyMethodName($propertyTemplate->getName()));
        else
            $name = sprintf('set%s', NameUtils::getPropertyMethodName($propertyTemplate->getName()));

        parent::__construct($name, new PHPScopeEnum(PHPScopeEnum::_PUBLIC));

        $this->setDocumentation($propertyTemplate->getDocumentation());
    }

    /**
     * @return ParameterTemplate[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param ParameterTemplate $parameterTemplate
     */
    public function addParameter(ParameterTemplate $parameterTemplate)
    {
        $this->parameters[] = $parameterTemplate;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $output = sprintf("    /**\n%s", self::_getDocumentationOutput());

        foreach($this->getParameters() as $param)
        {
            $output = sprintf(
                "%s     * %s\n",
                $output,
                $param->getParamDocBlock(true)
            );
        }

        $output = sprintf("%s     */\n    %s function %s(", $output, (string)$this->getScope(), $this->getName());

        $params = array();
        foreach($this->getParameters() as $param)
        {
            $params[] = NameUtils::getPropertyVariableName($param->getName());
        }
        $output = sprintf("%s%s)\n    {\n", $output, implode(', ', $params));

        foreach($this->getBody() as $line)
        {
            $output = sprintf("%s        %s\n", $output, $line);
        }

        return sprintf("%s    }\n\n", $output);
    }
}