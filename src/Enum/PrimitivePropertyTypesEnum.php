<?php namespace PHPFHIR\Enum;

use MyCLabs\Enum\Enum;

/**
 * Class PrimitivePropertyTypesEnum
 * @package PHPFHIR\Enum
 */
class PrimitivePropertyTypesEnum extends Enum
{
    const BOOLEAN = 'boolean';
    const INTEGER = 'integer';
    const DECIMAL = 'decimal';
    const BASE_64_BINARY = 'base64binary';
    const INSTANT = 'instant';
    const STRING = 'string';
    const URI = 'uri';
    const DATE = 'date';
    const DATETIME = 'datetime';
    const CODE = 'code';
    const XML_ID_REF = 'xmlidref';
    const ID = 'id';
    const OID = 'oid';
    const UUID = 'uuid';
}
