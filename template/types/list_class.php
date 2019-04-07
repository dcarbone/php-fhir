<?php

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\Definition\Types $types */

// define some common things
$fqns = $type->getFullyQualifiedNamespace(true);
$typeClassName = $type->getClassName();
$typeKind = $type->getKind();
$parentType = $type->getParentType();
$fhirName = $type->getFHIRName();
$sortedProperties = $type->getProperties()->getSortedIterator();
$classDocumentation = $type->getDocBlockDocumentationFragment(1, true);
$restrictionBase = $type->getRestrictionBaseFHIRName();
$restrictionBaseType = $types->getTypeByName($restrictionBase);

ob_start();

// build file header
echo require PHPFHIR_TEMPLATE_FILE_DIR . '/header.php';

// build class header ?>
/**<?php if ('' !== $classDocumentation) : ?>

<?php echo $classDocumentation; ?>
 *<?php endif; ?>

 * Class <?php echo $typeClassName; ?>

 * @package <?php echo $fqns; ?>

 */
class <?php echo $typeClassName; ?><?php echo null !== $parentType ? " extends {$parentType->getClassName()}" : '' ?> implements \JsonSerializable
{
    // name of FHIR type this class describes
    const FHIR_TYPE_NAME = '<?php echo $fhirName; ?>';
<?php if (null === $parentType) : ?>    const FIELD_RESOURCE_TYPE = 'resourceType';
<?php endif; ?>

    const FIELD_VALUE = 'value';

    /** @var null|<?php echo $restrictionBaseType->getFullyQualifiedClassName(true); ?> */
    private $value = null;

    /**
     * The list of values allowed by <?php echo $fhirName; ?>

     * @var array
     */
    private static $valueList = [
<?php foreach($type->getEnumeration() as $enum) : ?>
        '<?php echo $enum->getValue(); ?>',
<?php endforeach; ?>
    ];

    /**
     * <?php echo $typeClassName; ?> Constructor
     * @param null|string $value;
     */
    public function __construct($value = null)
    {
        $this->setValue($value);
    }

    /**
     * @param null|string $value;
     * @return <?php echo $type->getFullyQualifiedClassName(true); ?>

     */
    public function setValue($value = null)
    {
        if (null === $value) {
            $this->value = null;
            return $this;
        }
        if ($value instanceof <?php echo $restrictionBaseType->getClassName(); ?>) {
            $this->value = $value;
            return $this;
        }
        $this->value = new <?php echo $restrictionBaseType->getClassName(); ?>($value);
        return $this;
    }

    /**
     * @return null|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the list of allowed values for this type
     * @return string[]
     */
    public function getValueList()
    {
        return self::$valueList;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $v = $this->getValue();
        return null === $v || in_array($v, self::$valueList, true);
    }

<?php echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml.php'; ?>

<?php echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/json.php'; ?>

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getValue();
    }
}<?php return ob_get_clean();