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
use DCarbone\PHPFHIR\Utilities\DocumentationUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$propertyName = $property->getName();
$propertyType = $property->getValueFHIRType();
$propertyTypeKind = $propertyType->getKind();
$propertyTypeClassName = $type->getImports()->getImportByType($propertyType);

$documentation = DocumentationUtils::compilePropertyDocumentation($property, 5, true);

ob_start(); ?>
    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo $propertyType->getFullyQualifiedClassName(true);?>[] $<?php echo $propertyName; ?>

     * @return static
     */
    public function set<?php echo ucfirst($propertyName); ?>(array $<?php echo $propertyName; ?> = []): object
    {
        if ([] !== $this-><?php echo $propertyName; ?>) {
            $this->_trackValuesRemoved(count($this-><?php echo $propertyName; ?>));
            $this-><?php echo $propertyName; ?> = [];
        }
        if ([] === $<?php echo $propertyName; ?>) {
            return $this;
        }
        foreach($<?php echo $propertyName; ?> as $v) {
            if ($v instanceof <?php echo $propertyTypeClassName; ?>) {
                $this->add<?php echo ucfirst($propertyName); ?>($v);
            } else {
                $this->add<?php echo ucfirst($propertyName); ?>(new <?php echo $propertyTypeClassName; ?>($v));
            }
        }
        return $this;
    }
<?php return ob_get_clean();