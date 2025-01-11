<?php declare(strict_types=1);

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type; */

$typeKind = $type->getKind();

ob_start();

foreach($type->getProperties()->getIterator() as $property) :
    $propertyType = $property->getValueFHIRType();

    $setter = $property->getSetterName();

    if (null === $propertyType) : ?>

<?php else:
        $propertyTypeKind = $propertyType->getKind();
        $propertyTypeClassName = $propertyType->getClassName();
        $propertyFieldConst = $property->getFieldConstantName();
        $propertyFieldConstExt = $property->getFieldConstantExtensionName();

        if ($propertyType->getKind()->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST) || $propertyType->hasPrimitiveParent()) :?>
        if (isset($json[self::<?php echo $propertyFieldConst; ?>]) || array_key_exists(self::<?php echo $propertyFieldConst; ?>, $json)) {
<?php       if ($property->isCollection()) : ?>
            if (is_array($json[self::<?php echo $propertyFieldConst; ?>])) {
                foreach($json[self::<?php echo $propertyFieldConst; ?>] as $v) {
                    if (!($v instanceof <?php echo $property->getValueFHIRType()->getClassName(); ?>)) {
                        $v = new <?php echo $property->getValueFHIRType()->getClassName(); ?>($v);
                    }
                    $type-><?php echo $setter; ?>($v);
                }
            } else if ($json[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo $property->getValueFHIRType()->getClassName(); ?>) {
                $type-><?php echo $setter; ?>($json[self::<?php echo $propertyFieldConst; ?>]);
            } else {
                $type-><?php echo $setter; ?>(new <?php echo $property->getValueFHIRType()->getClassName(); ?>($json[self::<?php echo $propertyFieldConst; ?>]));
            }
<?php       else : ?>
            if ($json[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo $property->getvalueFHIRType()->getClassName(); ?>) {
                $type-><?php echo $setter; ?>($json[self::<?php echo $propertyFieldConst; ?>]);
            } else {
                $type-><?php echo $setter; ?>(new <?php echo $property->getValueFHIRType()->getClassName(); ?>($json[self::<?php echo $propertyFieldConst; ?>]));
            }
<?php       endif; ?>
        }
<?php   elseif ($propertyType->getKind() === TypeKindEnum::PRIMITIVE_CONTAINER || $propertyType->hasPrimitiveContainerParent() || $propertyType->isValueContainer()) : ?>
        if (isset($json[self::<?php echo $propertyFieldConst; ?>]) || isset($json[self::<?php echo $propertyFieldConstExt; ?>]) || array_key_exists(self::<?php echo $propertyFieldConst; ?>, $json) || array_key_exists(self::<?php echo $propertyFieldConstExt; ?>, $json)) {
            $value = $json[self::<?php echo $propertyFieldConst; ?>] ?? null;
            $ext = (isset($json[self::<?php echo $propertyFieldConstExt; ?>]) && is_array($json[self::<?php echo $propertyFieldConstExt; ?>])) ? $json[self::<?php echo $propertyFieldConstExt; ?>] : [];
            if (null !== $value) {
                if ($value instanceof <?php echo $propertyTypeClassName; ?>) {
                    $type-><?php echo $setter; ?>($value);
                } else <?php if ($property->isCollection()) : ?>if (is_array($value)) {
                    foreach($value as $i => $v) {
                        if ($v instanceof <?php echo $propertyTypeClassName; ?>) {
                            $type-><?php echo $setter; ?>($v);
                        } else {
                            $iext = (isset($ext[$i]) && is_array($ext[$i])) ? $ext[$i] : [];
                            if (is_array($v)) {
                                $type-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>(array_merge($v, $iext)));
                            } else {
                                $type-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>([<?php echo $propertyTypeClassName; ?>::FIELD_VALUE => $v] + $iext));
                            }
                        }
                    }
                } else<?php endif; ?>if (is_array($value)) {
                    $type-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>(array_merge($ext, $value)));
                } else {
                    $type-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>([<?php echo $propertyTypeClassName; ?>::FIELD_VALUE => $value] + $ext));
                }
            } elseif ([] !== $ext) {
<?php       if ($property->isCollection()) : ?>
                foreach($ext as $iext) {
                    $type-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($iext));
                }
<?php       else : ?>
                $type-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($ext));
<?php       endif; ?>
            } else {
                $type-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>(null));
            }
        }
<?php   elseif ($propertyTypeKind->isResourceContainer($version)) : ?>
        if (isset($data[self::<?php echo $propertyFieldConst; ?>])) {
<?php       if ($property->isCollection()) : ?>
            if (is_array($data[self::<?php echo $propertyFieldConst; ?>])) {
                if (is_int(key($data[self::<?php echo $propertyFieldConst; ?>]))) {
                    $type-><?php echo $setter; ?>($data[self::<?php echo $propertyFieldConst; ?>]);
                } else {
                    $typeClassName = <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassNameFromArray($data[self::<?php echo $propertyFieldConst; ?>]);
                    $d = $data[self::<?php echo $propertyFieldConst; ?>];
                    unset($d[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]);
                    $type-><?php echo $setter; ?>(new $typeClassName($d));
                }
            } elseif (!is_object($data[self::<?php echo $propertyFieldConst; ?>]) || !($data[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE; ?>)) {
                throw new \InvalidArgumentException(sprintf(
                    '<?php echo $propertyTypeClassName; ?> - Field "<?php echo $property->getName(); ?>" must be an array of objects implementing <?php echo $version->getFullyQualifiedName(true, PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE); ?>, value of type %s seen',
                    is_object($data[self::<?php echo $propertyFieldConst; ?>]) ? get_class($data[self::<?php echo $propertyFieldConst; ?>]) : gettype($data[self::<?php echo $propertyFieldConst; ?>])
                ));
            } else {
                $type-><?php echo $setter; ?>($data[self::<?php echo $propertyFieldConst; ?>]);
            }
<?php       else : ?>
            if (is_array($data[self::<?php echo $propertyFieldConst; ?>])) {
                $typeClassName = <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassNameFromArray($data[self::<?php echo $propertyFieldConst; ?>]);
                $d = $data[self::<?php echo $propertyFieldConst; ?>];
                unset($d[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]);
                $typeClass = new $typeClassName($d);
            } else if (!is_object($data[self::<?php echo $propertyFieldConst; ?>]) || !($data[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE; ?>)) {
                throw new \InvalidArgumentException(sprintf(
                    '<?php echo $propertyTypeClassName; ?> - Field "<?php echo $property->getName(); ?>" must be an array or object implementing <?php echo $version->getFullyQualifiedName(true, PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE); ?>, %s seen',
                    is_object($data[self::<?php echo $propertyFieldConst; ?>]) ? get_class($data[self::<?php echo $propertyFieldConst; ?>]) : gettype($data[self::<?php echo $propertyFieldConst; ?>])
                ));
            } else {
                $typeClass = $data[self::<?php echo $propertyFieldConst; ?>];
            }
            $type-><?php echo $setter; ?>($typeClass);
<?php       endif; ?>
        }
<?php   else : ?>
        if (isset($json[self::<?php echo $propertyFieldConst; ?>]) || array_key_exists(self::<?php echo $propertyFieldConst; ?>, $json)) {
<?php       if ($property->isCollection()) : ?>
            if (is_array($json[self::<?php echo $propertyFieldConst; ?>])) {
                foreach($json[self::<?php echo $propertyFieldConst; ?>] as $v) {
                    if ($v instanceof <?php echo $propertyTypeClassName; ?>) {
                        $type-><?php echo $setter; ?>($v);
                    } else {
                        $type-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($v));
                    }
                }
            } elseif ($json[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo $propertyTypeClassName; ?>) {
                $type-><?php echo $setter; ?>($json[self::<?php echo $propertyFieldConst; ?>]);
            } else {
                $type-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($json[self::<?php echo $propertyFieldConst; ?>]));
            }
<?php       else : ?>
            if ($json[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo $propertyTypeClassName; ?>) {
                $type-><?php echo $setter; ?>($json[self::<?php echo $propertyFieldConst; ?>]);
            } else {
                $type-><?php echo $setter; ?>(new <?php echo $propertyTypeClassName; ?>($json[self::<?php echo $propertyFieldConst; ?>]));
            }
<?php       endif; ?>
        }
<?php
        endif;
    endif;
endforeach; ?>
        return $type;
    }
<?php return ob_get_clean();
