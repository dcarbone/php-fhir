<?php namespace PHPFHIR\Utilities;

use PHPFHIR\Template\ClassTemplate;

/**
 * Class GeneratorUtils
 * @package PHPFHIR\Utilities
 */
abstract class GeneratorUtils
{
    /**
     * @param array $data
     * @return ClassTemplate
     */
    public static function buildClassTemplate(array $data)
    {
        return new ClassTemplate(
            $data['rootNS'],
            $data['className'],
            XMLUtils::getDocumentation($data['sxe'])
        );
    }

}