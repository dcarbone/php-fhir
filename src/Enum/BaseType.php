<?php

namespace DCarbone\PHPFHIR\Enum;

use MyCLabs\Enum\Enum;

/**
 * Class BaseType
 * @package DCarbone\PHPFHIR\Enum
 */
class BaseType extends Enum
{
    const ELEMENT          = 'Element';
    const BACKBONE_ELEMENT = 'BackboneElement';
    const RESOURCE         = 'Resource';
    const DOMAIN_RESOURCE  = 'DomainResource';
    const QUANTITY         = 'Quantity';
}