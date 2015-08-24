<?php namespace PHPFHIR\Generator;

use PHPFHIR\Enum\PHPScopeEnum;
use PHPFHIR\Template\ClassTemplate;
use PHPFHIR\Template\GetterMethodTemplate;
use PHPFHIR\Template\MethodTemplate;
use PHPFHIR\Template\ParameterTemplate;
use PHPFHIR\Template\PropertyTemplate;
use PHPFHIR\Template\SetterMethodTemplate;
use PHPFHIR\Utilities\NameUtils;

/**
 * Class MethodGenerator
 * @package PHPFHIR\Generator
 */
abstract class MethodGenerator
{
    /**
     * @param ClassTemplate $classTemplate
     * @param PropertyTemplate $propertyTemplate
     */
    public static function implementMethodsForProperty(ClassTemplate $classTemplate, PropertyTemplate $propertyTemplate)
    {
        $classTemplate->addMethod(self::createGetter($propertyTemplate));
        $classTemplate->addMethod(self::createSetter($propertyTemplate));
    }

    /**
     * @param PropertyTemplate $propertyTemplate
     * @return GetterMethodTemplate
     */
    public static function createGetter(PropertyTemplate $propertyTemplate)
    {
        $getterTemplate = new GetterMethodTemplate($propertyTemplate);
        $getterTemplate->addLineToBody(sprintf('return $this->%s;', $propertyTemplate->getName()));
        return $getterTemplate;
    }

    /**
     * @param PropertyTemplate $propertyTemplate
     * @return SetterMethodTemplate
     */
    public static function createSetter(PropertyTemplate $propertyTemplate)
    {
        $paramTemplate = new ParameterTemplate($propertyTemplate);

        if ($propertyTemplate->isCollection())
        {
            $methodBody = sprintf(
                '$this->%s[] = %s;',
                $propertyTemplate->getName(),
                NameUtils::getPropertyVariableName($paramTemplate->getName())
            );
        }
        else
        {
            $methodBody = sprintf(
                '$this->%s = %s;',
                $propertyTemplate->getName(),
                NameUtils::getPropertyVariableName($paramTemplate->getName())
            );
        }

        $setterTemplate = new SetterMethodTemplate($propertyTemplate);
        $setterTemplate->addParameter($paramTemplate);
        $setterTemplate->addLineToBody($methodBody);

        return $setterTemplate;
    }
}