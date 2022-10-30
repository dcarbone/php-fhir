<?php declare(strict_types=1);

/*
 * Copyright 2018-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$memberOf = $property->getMemberOf();
$memberOfParent = $memberOf->getParentType();
$memberOfKind = $memberOf->getKind();
$propertyType = $property->getValueFHIRType();

// for children of primitive types, they do not need their own "value" constant as the parent has it
if ($memberOf->hasPrimitiveParent()) :
    return '';
endif;

ob_start(); ?>
    const <?php echo $property->getFieldConstantName(); ?> = '<?php echo $property->getName(); ?>';
<?php if (null !== $propertyType &&
    ($propertyType->getKind()->is(TypeKindEnum::PRIMITIVE_CONTAINER) || $propertyType->isValueContainer())) :
    ?>    const <?php echo $property->getFieldConstantName(); ?>_EXT = '_<?php echo $property->getName(); ?>';
<?php endif;
return ob_get_clean();
