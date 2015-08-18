<?php namespace PHPFHIR\Utilities;

use PHPFHIR\Enum\PHPScopeEnum;
use PHPFHIR\Template\ClassTemplate;
use PHPFHIR\Template\MethodTemplate;
use PHPFHIR\Template\ParameterTemplate;
use PHPFHIR\Template\PropertyTemplate;

/**
 * Class MethodGeneratorUtils
 * @package PHPFHIR\Utilities
 */
abstract class MethodGeneratorUtils
{
    /**
     * @param ClassTemplate $classTemplate
     * @param PropertyTemplate $propTemplate
     */
    public static function implementMethodsForProperty(ClassTemplate $classTemplate, PropertyTemplate $propTemplate)
    {
        $classTemplate->addMethod(self::createGetter($propTemplate));
        $classTemplate->addMethod(self::createSetter($propTemplate));
    }

    /**
     * @param PropertyTemplate $propTemplate
     * @return MethodTemplate
     */
    public static function createGetter(PropertyTemplate $propTemplate)
    {
        $methodTemplate = new MethodTemplate(
            sprintf('get%s', NameUtils::getPropertyMethodName($propTemplate->getName())),
            new PHPScopeEnum('public'),
            array(sprintf('return $this->%s;', $propTemplate->getName()))
        );

        $methodTemplate->setDocumentation($propTemplate->getDocumentation());

        return $methodTemplate;
    }

    /**
     * @param PropertyTemplate $propTemplate
     * @return MethodTemplate
     */
    public static function createSetter(PropertyTemplate $propTemplate)
    {
        $paramTemplate = new ParameterTemplate($propTemplate->getName());

        $methodTemplate = new MethodTemplate(
            sprintf('set%s', NameUtils::getPropertyMethodName($propTemplate->getName())),
            new PHPScopeEnum('public'),
            array(sprintf('$this->%s = %s;', $propTemplate->getName(), NameUtils::getPropertyVariableName($paramTemplate->getName())))
        );

        $methodTemplate->addParameter($paramTemplate);

        return $methodTemplate;
    }
}