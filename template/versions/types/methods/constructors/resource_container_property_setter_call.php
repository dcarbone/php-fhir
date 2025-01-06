<?php declare(strict_types=1);

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Version\Definition\Property $property */

$config = $type->getConfig();
$version = $type->getVersion();
$typeClassName = $type->getClassName();
$propertyName = $property->getName();
$propertyFieldConst = $property->getFieldConstantName();
$propertyType = $property->getValueFHIRType();
$setter = ($property->isCollection() ? 'add' : 'set') . ucfirst($propertyName);

ob_start(); ?>
        if (isset($data[self::<?php echo $propertyFieldConst; ?>])) {
<?php if ($property->isCollection()) : ?>
            if (is_array($data[self::<?php echo $propertyFieldConst; ?>])) {
                if (is_int(key($data[self::<?php echo $propertyFieldConst; ?>]))) {
                    $this->set<?php echo ucfirst($propertyName); ?>($data[self::<?php echo $propertyFieldConst; ?>]);
                } else {
                    $typeClassName = <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassNameFromArray($data[self::<?php echo $propertyFieldConst; ?>]);
                    $d = $data[self::<?php echo $propertyFieldConst; ?>];
                    unset($d[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]);
                    $this-><?php echo $setter; ?>(new $typeClassName($d));
                }
            } elseif (!is_object($data[self::<?php echo $propertyFieldConst; ?>]) || !($data[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE; ?>)) {
                throw new \InvalidArgumentException(sprintf(
                    '<?php echo $typeClassName; ?> - Field "<?php echo $propertyName; ?>" must be an array of objects implementing <?php echo $version->getFullyQualifiedName(true, PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE); ?>, value of type %s seen',
                    is_object($data[self::<?php echo $propertyFieldConst; ?>]) ? get_class($data[self::<?php echo $propertyFieldConst; ?>]) : gettype($data[self::<?php echo $propertyFieldConst; ?>])
                ));
            } else {
                $this-><?php echo $setter; ?>($data[self::<?php echo $propertyFieldConst; ?>]);
            }
<?php else : ?>
            if (is_array($data[self::<?php echo $propertyFieldConst; ?>])) {
                $typeClassName = <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassNameFromArray($data[self::<?php echo $propertyFieldConst; ?>]);
                $d = $data[self::<?php echo $propertyFieldConst; ?>];
                unset($d[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]);
                $typeClass = new $typeClassName($d);
            } else if (!is_object($data[self::<?php echo $propertyFieldConst; ?>]) || !($data[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE; ?>)) {
                throw new \InvalidArgumentException(sprintf(
                    '<?php echo $typeClassName; ?> - Field "<?php echo $propertyName; ?>" must be an array or object implementing <?php echo $version->getFullyQualifiedName(true, PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE); ?>, %s seen',
                    is_object($data[self::<?php echo $propertyFieldConst; ?>]) ? get_class($data[self::<?php echo $propertyFieldConst; ?>]) : gettype($data[self::<?php echo $propertyFieldConst; ?>])
                ));
            } else {
                $typeClass = $data[self::<?php echo $propertyFieldConst; ?>];
            }
            $this-><?php echo $setter; ?>($typeClass);
<?php endif; ?>
        }
<?php
return ob_get_clean();
