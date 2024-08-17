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

/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Property $property */
/** @var int $propertyIndex */

$typeClassName = $type->getClassName();
$propertyName = $property->getName();
$propertyFieldConst = $property->getFieldConstantName();
$propertyType = $property->getValueFHIRType();
$setter = ($property->isCollection() ? 'add' : 'set') . ucfirst($propertyName);

ob_start(); 
if (0 === $propertyIndex) : ?>
            if <?php else : ?> else if <?php endif; ?>(self::<?php echo $propertyFieldConst; ?> === $field) {
<?php if ($property->isCollection()) : ?>
                if (is_array($value)) {
                    if (is_int(key($value))) {
                        $this->set<?php echo ucfirst($propertyName); ?>($value);
                    } else {
                        $typeClass = PHPFHIRTypeMap::getContainedTypeFromArray($value);
                        if (null === $typeClass) {
                            throw new \InvalidArgumentException(sprintf(
                                '<?php echo $typeClassName; ?> - Unable to determine class for field "<?php echo $propertyName; ?>" from value: %s',
                                json_encode($value)
                            ));
                        }
                        $this-><?php echo $setter; ?>(new $typeClass($value));
                    }
                } elseif ($value instanceof <?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>) {
                    $this-><?php echo $setter; ?>($value);
                }
<?php else : ?>
                if (is_object($value)) {
                    if ($value instanceof <?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>) {
                        $this-><?php echo $setter; ?>($value);
                    } else {
                        throw new \InvalidArgumentException(sprintf(
                            '<?php echo $typeClassName; ?> - Field "<?php echo $propertyName; ?>" must be an object implementing <?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>, object of type %s seen',
                            get_class($value)
                        ));
                    }
                } elseif (is_array($value)) {
                    $typeClass = PHPFHIRTypeMap::getContainedTypeFromArray($value);
                    if (null === $typeClass) {
                        throw new \InvalidArgumentException(sprintf(
                            '<?php echo $typeClassName; ?> - Unable to determine class for field "<?php echo $propertyName; ?>" from value: %s',
                            json_encode($value)
                        ));
                    }
                    $this-><?php echo $setter; ?>(new $typeClass($value));
                } else {
                    throw new \InvalidArgumentException(sprintf(
                        '<?php echo $typeClassName; ?> - Unable to determine class for field "<?php echo $propertyName; ?>" from value: %s',
                        json_encode($value)
                    ));
                }
<?php endif; ?>
            }<?php
return ob_get_clean();
