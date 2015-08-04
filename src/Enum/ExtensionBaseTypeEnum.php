<?php namespace PHPFHIR\Enum;

use MyCLabs\Enum\Enum;

/**
 * Class ExtensionBaseTypeEnum
 * @package PHPFHIR\Enum
 */
class ExtensionBaseTypeEnum extends Enum
{
    const ELEMENT = 'Element';
    const BACKBONE_ELEMENT = 'BackboneElement';
    const RESOURCE = 'Resource';
}