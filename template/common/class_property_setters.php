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

/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $sortedProperties */

ob_start(); ?>
<?php foreach($sortedProperties as $property) :
    $propertyType = $property->getValueFHIRType();
    $propertyTypeKind = $propertyType->getKind();
    ?>
    <?php if ($property->isCollection()) : ?>
        <?php if ($propertyTypeKind->isPrimitiveContainer()) : ?>
            <?php echo require PHPFHIR_TEMPLATE_SETTERS_DIR . '/collection_primitive_container.php'; ?>
        <?php else : ?>
            <?php echo require PHPFHIR_TEMPLATE_SETTERS_DIR . '/collection.php'; ?>
        <?php endif; ?>
    <?php else : ?>
        <?php if ($propertyTypeKind->isPrimitiveContainer()) : ?>
            <?php echo require PHPFHIR_TEMPLATE_SETTERS_DIR . '/single_primitive_container.php'; ?>
        <?php else : ?>
            <?php echo require PHPFHIR_TEMPLATE_SETTERS_DIR . '/single.php'; ?>
        <?php endif; ?>
    <?php endif; ?>

<?php endforeach; ?>
<?php return ob_get_clean();
