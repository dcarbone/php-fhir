<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
                    $typeClass = PHPFHIRTypeMap::getContainedTypeFromArray($data[self::<?php echo $propertyFieldConst; ?>]);
                    if (null === $typeClass) {
                        throw new \InvalidArgumentException(sprintf(
                            '<?php echo $typeClassName; ?> - Unable to determine class for field "<?php echo $propertyName; ?>" from value: %s',
                            json_encode($data[self::<?php echo $propertyFieldConst; ?>])
                        ));
                    }
                    $this-><?php echo $setter; ?>(new $typeClass($data[self::<?php echo $propertyFieldConst; ?>]));
                }
            } elseif ($data[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>) {
                $this-><?php echo $setter; ?>($data[self::<?php echo $propertyFieldConst; ?>]);
            }
<?php else : ?>
            if (is_object($data[self::<?php echo $propertyFieldConst; ?>])) {
                if ($data[self::<?php echo $propertyFieldConst; ?>] instanceof <?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>) {
                    $this-><?php echo $setter; ?>($data[self::<?php echo $propertyFieldConst; ?>]);
                } else {
                    throw new \InvalidArgumentException(sprintf(
                        '<?php echo $typeClassName; ?> - Field "<?php echo $propertyName; ?>" must be an object implementing <?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>, object of type %s seen',
                        get_class($data[self::<?php echo $propertyFieldConst; ?>])
                    ));
                }
            } elseif (is_array($data[self::<?php echo $propertyFieldConst; ?>])) {
                $typeClass = PHPFHIRTypeMap::getContainedTypeFromArray($data[self::<?php echo $propertyFieldConst; ?>]);
                if (null === $typeClass) {
                    throw new \InvalidArgumentException(sprintf(
                        '<?php echo $typeClassName; ?> - Unable to determine class for field "<?php echo $propertyName; ?>" from value: %s',
                        json_encode($data[self::<?php echo $propertyFieldConst; ?>])
                    ));
                }
                $this-><?php echo $setter; ?>(new $typeClass($data[self::<?php echo $propertyFieldConst; ?>]));
            } else {
                throw new \InvalidArgumentException(sprintf(
                    '<?php echo $typeClassName; ?> - Unable to determine class for field "<?php echo $propertyName; ?>" from value: %s',
                    json_encode($data[self::<?php echo $propertyFieldConst; ?>])
                ));
            }
<?php endif; ?>
        }
<?php
return ob_get_clean();
