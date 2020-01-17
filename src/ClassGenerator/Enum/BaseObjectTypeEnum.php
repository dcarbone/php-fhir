<?php namespace DCarbone\PHPFHIR\ClassGenerator\Enum;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use MyCLabs\Enum\Enum;

/**
 * Class BaseObjectTypeEnum
 * @package DCarbone\PHPFHIR\ClassGenerator\Enum
 */
class BaseObjectTypeEnum extends Enum
{
    const BACKBONE_ELEMENT = 'BackboneElement';
    const BACKBONE_TYPE = 'BackboneType';
    const BASE = 'Base';
    const CANONICAL_RESOURCE = 'CanonicalResource';
    const DATA_TYPE = 'DataType';
    const DOMAIN_RESOURCE = 'DomainResource';
    const ELEMENT = 'Element';
    const METADATA_RESOURCE = 'MetadataResource';
    const QUANTITY = 'Quantity';
    const RESOURCE = 'Resource';
}
