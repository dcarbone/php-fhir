<?php namespace PHPFHIR\Enum;

use MyCLabs\Enum\Enum;

/**
 * Class ParameterScopeEnum
 * @package PHPFHIR\Enum
 */
class ParameterScopeEnum extends Enum
{
    const _PRIVATE = 'private';
    const _PROTECTED = 'protected';
    const _PUBLIC = 'public';
}