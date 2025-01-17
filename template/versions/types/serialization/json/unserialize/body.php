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
    $pt = $property->getValueFHIRType();
    if (null === $pt) {
        continue;
    }

    $setter = $property->getSetterName();
    $ptk = $pt->getKind();
    $ptClassname = $pt->getClassName();
    $propConst = $property->getFieldConstantName();
    $propConstExt = $property->getFieldConstantExtensionName();

    if ($pt->getKind()->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST) || $pt->hasPrimitiveOrListParent()) :?>
        if (!is_array($json)) {
            $type->setValue($json);
            return $type;
        }
        if ([] === $json) {
            return $type;
        }
        if (isset($json[self::<?php echo $propConst; ?>]) || array_key_exists(self::<?php echo $propConst; ?>, $json)) {
<?php   if ($property->isCollection()) : ?>
            if (is_array($json[self::<?php echo $propConst; ?>])) {
                foreach($json[self::<?php echo $propConst; ?>] as $v) {
                    if (!($v instanceof <?php echo $property->getValueFHIRType()->getClassName(); ?>)) {
                        $v = <?php echo $property->getValueFHIRType()->getClassName(); ?>::jsonUnserialize($v);
                    }
                    $type-><?php echo $setter; ?>($v);
                }
            } else if ($json[self::<?php echo $propConst; ?>] instanceof <?php echo $property->getValueFHIRType()->getClassName(); ?>) {
                $type-><?php echo $setter; ?>($json[self::<?php echo $propConst; ?>]);
            } else {
                $type-><?php echo $setter; ?>(<?php echo $property->getValueFHIRType()->getClassName(); ?>::jsonUnserialize($json[self::<?php echo $propConst; ?>]));
            }
<?php   else : ?>
            if ($json[self::<?php echo $propConst; ?>] instanceof <?php echo $property->getvalueFHIRType()->getClassName(); ?>) {
                $type-><?php echo $setter; ?>($json[self::<?php echo $propConst; ?>]);
            } else {
                $type-><?php echo $setter; ?>(<?php echo $property->getValueFHIRType()->getClassName(); ?>::jsonUnserialize($json[self::<?php echo $propConst; ?>]));
            }
<?php   endif; ?>
        }
<?php elseif ($pt->getKind() === TypeKindEnum::PRIMITIVE_CONTAINER || $pt->hasPrimitiveContainerParent() || $pt->isValueContainer()) : ?>
        if (isset($json[self::<?php echo $propConst; ?>]) || isset($json[self::<?php echo $propConstExt; ?>]) || array_key_exists(self::<?php echo $propConst; ?>, $json) || array_key_exists(self::<?php echo $propConstExt; ?>, $json)) {
            $value = $json[self::<?php echo $propConst; ?>] ?? null;
            $ext = (isset($json[self::<?php echo $propConstExt; ?>]) && is_array($json[self::<?php echo $propConstExt; ?>])) ? $json[self::<?php echo $propConstExt; ?>] : [];
            if (null !== $value) {
                if ($value instanceof <?php echo $ptClassname; ?>) {
                    $type-><?php echo $setter; ?>($value);
                } else <?php if ($property->isCollection()) : ?>if (is_array($value)) {
                    foreach($value as $i => $v) {
                        if ($v instanceof <?php echo $ptClassname; ?>) {
                            $type-><?php echo $setter; ?>($v);
                        } else {
                            $iext = (isset($ext[$i]) && is_array($ext[$i])) ? $ext[$i] : [];
                            if (is_array($v)) {
                                $type-><?php echo $setter; ?>(new <?php echo $ptClassname; ?>(array_merge($v, $iext)));
                            } else {
                                $type-><?php echo $setter; ?>(new <?php echo $ptClassname; ?>([<?php echo $ptClassname; ?>::FIELD_VALUE => $v] + $iext));
                            }
                        }
                    }
                } else<?php endif; ?>if (is_array($value)) {
                    $type-><?php echo $setter; ?>(new <?php echo $ptClassname; ?>(array_merge($ext, $value)));
                } else {
                    $type-><?php echo $setter; ?>(new <?php echo $ptClassname; ?>([<?php echo $ptClassname; ?>::FIELD_VALUE => $value] + $ext));
                }
            } elseif ([] !== $ext) {
<?php   if ($property->isCollection()) : ?>
                foreach($ext as $iext) {
                    $type-><?php echo $setter; ?>(new <?php echo $ptClassname; ?>($iext));
                }
<?php   else : ?>
                $type-><?php echo $setter; ?>(new <?php echo $ptClassname; ?>($ext));
<?php   endif; ?>
            } else {
                $type-><?php echo $setter; ?>(new <?php echo $ptClassname; ?>(null));
            }
        }
<?php elseif ($ptk->isResourceContainer($version)) : ?>
        if (isset($data[self::<?php echo $propConst; ?>])) {
<?php   if ($property->isCollection()) : ?>
            if (is_array($data[self::<?php echo $propConst; ?>])) {
                if (is_int(key($data[self::<?php echo $propConst; ?>]))) {
                    $type-><?php echo $setter; ?>($data[self::<?php echo $propConst; ?>]);
                } else {
                    $typeClassName = <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassNameFromArray($data[self::<?php echo $propConst; ?>]);
                    $d = $data[self::<?php echo $propConst; ?>];
                    unset($d[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]);
                    $type-><?php echo $setter; ?>(new $typeClassName($d));
                }
            } elseif (!is_object($data[self::<?php echo $propConst; ?>]) || !($data[self::<?php echo $propConst; ?>] instanceof <?php echo PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE; ?>)) {
                throw new \InvalidArgumentException(sprintf(
                    '<?php echo $ptClassname; ?> - Field "<?php echo $property->getName(); ?>" must be an array of objects implementing <?php echo $version->getFullyQualifiedName(true, PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE); ?>, value of type %s seen',
                    is_object($data[self::<?php echo $propConst; ?>]) ? get_class($data[self::<?php echo $propConst; ?>]) : gettype($data[self::<?php echo $propConst; ?>])
                ));
            } else {
                $type-><?php echo $setter; ?>($data[self::<?php echo $propConst; ?>]);
            }
<?php   else : ?>
            if (is_array($data[self::<?php echo $propConst; ?>])) {
                $typeClassName = <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassNameFromArray($data[self::<?php echo $propConst; ?>]);
                $d = $data[self::<?php echo $propConst; ?>];
                unset($d[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]);
                $typeClass = new $typeClassName($d);
            } else if (!is_object($data[self::<?php echo $propConst; ?>]) || !($data[self::<?php echo $propConst; ?>] instanceof <?php echo PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE; ?>)) {
                throw new \InvalidArgumentException(sprintf(
                    '<?php echo $ptClassname; ?> - Field "<?php echo $property->getName(); ?>" must be an array or object implementing <?php echo $version->getFullyQualifiedName(true, PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE); ?>, %s seen',
                    is_object($data[self::<?php echo $propConst; ?>]) ? get_class($data[self::<?php echo $propConst; ?>]) : gettype($data[self::<?php echo $propConst; ?>])
                ));
            } else {
                $typeClass = $data[self::<?php echo $propConst; ?>];
            }
            $type-><?php echo $setter; ?>($typeClass);
<?php   endif; ?>
        }
<?php else : ?>
        if (isset($json[self::<?php echo $propConst; ?>]) || array_key_exists(self::<?php echo $propConst; ?>, $json)) {
<?php   if ($property->isCollection()) : ?>
            if (is_array($json[self::<?php echo $propConst; ?>])) {
                foreach($json[self::<?php echo $propConst; ?>] as $v) {
                    if ($v instanceof <?php echo $ptClassname; ?>) {
                        $type-><?php echo $setter; ?>($v);
                    } else {
                        $type-><?php echo $setter; ?>(new <?php echo $ptClassname; ?>($v));
                    }
                }
            } elseif ($json[self::<?php echo $propConst; ?>] instanceof <?php echo $ptClassname; ?>) {
                $type-><?php echo $setter; ?>($json[self::<?php echo $propConst; ?>]);
            } else {
                $type-><?php echo $setter; ?>(new <?php echo $ptClassname; ?>($json[self::<?php echo $propConst; ?>]));
            }
<?php       else : ?>
            if ($json[self::<?php echo $propConst; ?>] instanceof <?php echo $ptClassname; ?>) {
                $type-><?php echo $setter; ?>($json[self::<?php echo $propConst; ?>]);
            } else {
                $type-><?php echo $setter; ?>(new <?php echo $ptClassname; ?>($json[self::<?php echo $propConst; ?>]));
            }
<?php   endif; ?>
        }
<?php endif;
endforeach; ?>
        return $type;
    }
<?php return ob_get_clean();
