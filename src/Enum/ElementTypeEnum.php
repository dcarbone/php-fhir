<?php namespace PHPFHIR\Enum;

use MyCLabs\Enum\Enum;

/**
 * Class ElementTypeEnum
 * @package PHPFHIR\Enum
 */
class ElementTypeEnum extends Enum
{
    const COMPLEX_TYPE = 'complextype';
    const COMPLEX_CONTENT = 'complexcontent';
    const SIMPLE_TYPE = 'simpletype';

    const ANNOTATION = 'annotation';
    const DOCUMENTATION = 'documentation';
    const RESTRICTION = 'restriction';
    const EXTENSION = 'extension';

    const ATTRIBUTE = 'attribute';
    const SEQUENCE = 'sequence';
    const UNION = 'union';
    const ELEMENT = 'element';
    const CHOICE = 'choice';
}