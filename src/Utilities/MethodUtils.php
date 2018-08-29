<?php

namespace DCarbone\PHPFHIR\Utilities;

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Type\Property;

/**
 * Class SetterUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class MethodUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     */
    public static function createPrimitiveSetter(Config $config, Type $type, Property $property)
    {
        return '';
//        $propName = $property->getName();
//        $paramName = NameUtils::getPropertyVariableName($propName);
//        $phpType = $property->getPHPTypeName();
//        $out = "    /**\n";
//        $out .= $property->getDocBlockDocumentationFragment();
//        $out .= "     * @var null|"
//        $out .= "     */\n";
//
//        if ($property->isCollection()) {
//            $out =
//        }
//
//        $cname = $property->getPHPType();
//        var_dump($property->getFHIRElementType(), $property->getXSDMapEntry());exit;
//        $bname = substr($cname, strrpos($cname, '\\'));
//        $class->addImport($cname);
//        $setterTemplate->addBlockToBody(<<<PHP
//if (is_scalar({$paramName})) {
//    {$paramName} = new {$bname}($paramName);
//}
//if (!({$paramName} instanceof {$bname})) {
//    throw new \\InvalidArgumentException(
//        '{$class->getClassName()}::{$setterTemplate->getName()} - Value must be {$property->getPHPType()} or instance of {$cname}, '.gettype({$paramName}).' seen.'
//    );
//}
//\$this->{$property->getName()} = {$paramName};
//PHP
//        );
    }
}