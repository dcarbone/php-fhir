<?php

/*
 * Copyright 2018-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$propertyName = $property->getName();
$propertyType = $property->getValueFHIRType();
$propertyTypeKind = $propertyType->getKind();
$propertyTypeClassName = $propertyType->getClassName();
$isCollection = $property->isCollection();

$documentation = DocumentationUtils::compilePropertyDocumentation($property, 5, true);

ob_start(); ?>
    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param null|<?php echo $config->getNamespace(true) . '\\' . PHPFHIR_INTERFACE_CONTAINED_TYPE; ?> $<?php echo $propertyName; ?>

     * @return static
     */
    public function <?php echo $isCollection ? 'add' : 'set'; ?><?php echo ucfirst($propertyName); ?>(<?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?> $<?php echo $propertyName; ?> = null)
    {
        $this-><?php echo $propertyName; ?><?php echo $isCollection ? '[]' : ''; ?> = $<?php echo $propertyName; ?>;
        return $this;
    }
<?php return ob_get_clean();
