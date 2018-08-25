<?php

namespace DCarbone\PHPFHIR\Utilities;

use DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate;
use DCarbone\PHPFHIR\Config;

/**
 * Class SetterUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class SetterUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $class
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate $property
     */
    public static function createPrimitive(Config $config, ClassTemplate $class, BasePropertyTemplate $property)
    {
        $type = $property->getFHIRElementType();


        $cname = $property->getPHPType();
        var_dump($property->getFHIRElementType(), $property->getXSDMapEntry());exit;
        $bname = substr($cname, strrpos($cname, '\\'));
        $class->addImport($cname);
        $setterTemplate->addBlockToBody(<<<PHP
if (is_scalar({$paramName})) {
    {$paramName} = new {$bname}($paramName);
}
if (!({$paramName} instanceof {$bname})) {
    throw new \\InvalidArgumentException(
        '{$class->getClassName()}::{$setterTemplate->getName()} - Value must be {$property->getPHPType()} or instance of {$cname}, '.gettype({$paramName}).' seen.'
    ); 
}
\$this->{$property->getName()} = {$paramName};
PHP
        );
    }
}