<?php namespace PHPFHIR\Enum;

use MyCLabs\Enum\Enum;

/**
 * Class BaseObjectTypeEnum
 * @package PHPFHIR\Enum
 */
class BaseObjectTypeEnum extends Enum
{
    const ELEMENT = 'Element';
    const BACKBONE_ELEMENT = 'BackboneElement';
    const RESOURCE = 'Resource';
}