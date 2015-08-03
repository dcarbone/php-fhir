<?php namespace PHPFHIR\Utilities;

use PHPFHIR\Enum\SimpleClassTypesEnum;
use PHPFHIR\Template\ClassTemplate;

/**
 * Class SimpleTypeClassGenerator
 * @package PHPFHIR\Utilities
 */
abstract class SimpleTypeClassGenerator
{
    public static function generate(\SimpleXMLElement $element,
                                    $name,
                                    $outputNS)
    {
        $type = new SimpleClassTypesEnum(ltrim(strrchr($name, '-'), "-"));
        $rootNS = NSUtils::generateRootNamespace(
            $outputNS,
            NSUtils::getSimpleTypeNamespace($type)
        );

        $className = NameUtils::getSimpleTypeClassName($name);
        $documentation = XMLUtils::getDocumentation($element);

        $class = new ClassTemplate($rootNS, $className, $documentation);

        var_dump($className);

//        $namespace = NSUtils::getSimpleTypeNamespace($name);
//
//        $documentation = XMLUtils::getDocumentation($element);
//
//        var_dump($name);
    }
}