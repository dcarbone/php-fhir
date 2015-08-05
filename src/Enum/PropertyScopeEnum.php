<?php namespace PHPFHIR\Enum;

use MyCLabs\Enum\Enum;

/**
 * Class PropertyScopeEnum
 * @package PHPFHIR\Enum
 */
class PropertyScopeEnum extends Enum
{
    const _PRIVATE = 'private';
    const _PROTECTED = 'protected';
    const _PUBLIC = 'public';
}