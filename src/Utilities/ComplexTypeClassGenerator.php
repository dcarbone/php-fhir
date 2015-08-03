<?php namespace PHPFHIR\Utilities;

/**
 * Class ComplexTypeClassGenerator
 * @package PHPFHIR\Utilities
 */
abstract class ComplexTypeClassGenerator
{
    public static function generate(\SimpleXMLElement $element,
                                    $name,
                                    $outputNS)
    {
        $documentation = XMLUtils::getDocumentation($element);
    }
}