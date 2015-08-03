<?php namespace PHPFHIR\Enum;

use MyCLabs\Enum\Enum;

/**
 * Class MethodScopeEnum
 * @package PHPFHIR\Enum
 */
class MethodScopeEnum extends Enum
{
    const _PRIVATE = 'private';
    const _PROTECTED = 'protected';
    const _PUBLIC = 'public';
}