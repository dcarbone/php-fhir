<?php namespace PHPFHIR\Enum;

use MyCLabs\Enum\Enum;

/**
 * Class PropertySourceTypeEnum
 * @package PHPFHIR\Enum
 */
class PropertySourceTypeEnum extends Enum
{
    const SEQUENCE = 'sequence';
    const ATTRIBUTE = 'attribute';
    const CHOICE = 'choice';
    const UNION = 'union';
}