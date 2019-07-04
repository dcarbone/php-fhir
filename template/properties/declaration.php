<?php

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\DocumentationUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$isCollection = $property->isCollection();
$propType = $property->getValueFHIRType();
$documentation = DocumentationUtils::compilePropertyDocumentation($property, 5, true);

ob_start(); ?>
    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @var null|<?php if ($propType->getKind()->isOneOf([TypeKindEnum::RESOURCE_INLINE, TypeKindEnum::RESOURCE_CONTAINER])) :
    echo $config->getNamespace(true) . '\\' . PHPFHIR_INTERFACE_CONTAINED_TYPE;
else :
    echo $propType->getFullyQualifiedClassName(true);
endif;
echo $isCollection ? '[]' : ''; ?>

     */
    private $<?php echo $property->getName(); ?> = <?php echo $isCollection ? '[]' : 'null'; ?>;
<?php return ob_get_clean();
