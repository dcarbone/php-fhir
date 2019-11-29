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

$setter = 'add'.ucfirst($propertyName);

$documentation = DocumentationUtils::compilePropertyDocumentation($property, 5, true);

ob_start(); ?>
    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo $config->getNamespace(true) . '\\' . PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>[] $<?php echo $propertyName; ?>

     * @return static
     */
    public function set<?php echo ucfirst($propertyName); ?>(array $<?php echo $propertyName; ?> = [])
    {
        $this-><?php echo $propertyName; ?> = [];
        if ([] === $<?php echo $propertyName; ?>) {
            return $this;
        }
        foreach($<?php echo $propertyName; ?> as $v) {
            if (null === $v) {
                continue;
            }
            if (is_object($v)) {
                if ($v instanceof <?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>) {
                    $this-><?php echo $setter; ?>($v);
                } else {
                    throw new \InvalidArgumentException(sprintf(
                        '<?php echo $type->getClassName(); ?> - Field "<?php echo $propertyName; ?>" must be an array of objects implementing <?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>, object of type %s seen',
                        get_class($v)
                    ));
                }
            } else if (is_array($v)) {
                $typeClass = PHPFHIRTypeMap::getContainedTypeFromArray($v);
                if (null === $typeClass) {
                    throw new \InvalidArgumentException(sprintf(
                        '<?php echo $type->getClassName(); ?> - Unable to determine class for field "<?php echo $propertyName; ?>" from value: %s',
                        json_encode($v)
                    ));
                }
                $this-><?php echo $setter; ?>(new $typeClass($v));
            } else {
                throw new \InvalidArgumentException(sprintf(
                    '<?php echo $type->getClassName(); ?> - Unable to determine class for field "<?php echo $propertyName; ?>" from value: %s',
                    json_encode($v)
                ));
            }
        }
        return $this;
    }
<?php return ob_get_clean();