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

use DCarbone\PHPFHIR\Utilities\DocumentationUtils;
use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Property $property */

$propertyName = $property->getName();
$propertyType = $property->getValueFHIRType();
$propertyTypeClassName = $type->getImports()->getImportByType($propertyType);
$isCollection = $property->isCollection();

$methodName = ($isCollection ? 'add' : 'set') . ucfirst($propertyName);
$documentation = DocumentationUtils::compilePropertyDocumentation($property, 5, true);

ob_start();
?>
    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo TypeHintUtils::propertySetterTypeDoc($config, $property, false); ?> $<?php echo $propertyName; ?>

     * @return static
     */
    public function <?php echo $methodName; ?>(<?php echo TypeHintUtils::typeSetterTypeHint($config, $propertyType, true); ?> $<?php echo $propertyName; ?> = null): self
    {
        if (!($<?php echo $propertyName; ?> instanceof <?php echo $propertyTypeClassName; ?>)) {
            $<?php echo $propertyName; ?> = new <?php echo $propertyTypeClassName; ?>($<?php echo $propertyName; ?>);
        }
        <?php if ($isCollection) : ?>$this->_trackValueAdded(<?php else : ?>$this->_trackValueSet($this-><?php echo $propertyName; ?>, $<?php echo $propertyName; endif; ?>);
        $this-><?php echo $propertyName; ?><?php echo $isCollection ? '[]' : ''; ?> = $<?php echo $propertyName; ?>;
        return $this;
    }
<?php return ob_get_clean();
