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

use DCarbone\PHPFHIR\Utilities\DocumentationUtils;
use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$propertyName = $property->getName();
$propertyType = $property->getValueFHIRType();
$propertyTypeKind = $propertyType->getKind();
$propertyTypeClassName = $type->getImports()->getImportByType($propertyType);
$isCollection = $property->isCollection();

$documentation = DocumentationUtils::compilePropertyDocumentation($property, 5, true);

ob_start(); ?>
    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo TypeHintUtils::typeTypeDoc($config, $propertyType, true, false); ?> $<?php echo $propertyName; ?>

     * @return static
     */
    public function <?php echo $isCollection ? 'add' : 'set'; ?><?php echo ucfirst($propertyName); ?>(<?php echo TypeHintUtils::typeTypeHint($config, $propertyType, true); ?> $<?php echo $propertyName; ?> = null): object
    {
        <?php if ($isCollection) : ?>$this->_trackValueAdded(<?php else : ?>$this->_trackValueSet($this-><?php echo $propertyName; ?>, $<?php echo $propertyName; endif; ?>);
        $this-><?php echo $propertyName; ?><?php echo $isCollection ? '[]' : ''; ?> = $<?php echo $propertyName; ?>;
        return $this;
    }
<?php return ob_get_clean();
