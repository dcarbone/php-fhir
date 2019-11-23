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
if (null === $restrictionBaseType) {
    // TODO: do better here...
    $restrictionBaseType = $types->getTypeByName('string-primitive');
}

ob_start();

// build file header
echo require_with(
        PHPFHIR_TEMPLATE_FILE_DIR . '/header_type.php',
        [
                'fqns' => $fqns,
                'skipImports' => false,
                'type' => $type,
                'types' => $types,
                'config' => $config,
                'sortedProperties' => $sortedProperties,
        ]
);
// build class header ?>
/**<?php if ('' !== $classDocumentation) : ?>

<?php echo $classDocumentation; ?>
 *<?php endif; ?>

 * Class <?php echo $typeClassName; ?>

 * @package <?php echo $fqns; ?>

 */
<?php echo require_with(PHPFHIR_TEMPLATE_TYPES_DIR . '/definition.php', ['type' => $type, 'parentType' => $parentType]); ?>

    // name of FHIR type this class describes
    const FHIR_TYPE_NAME = <?php echo $type->getTypeNameConst(true); ?>;

    /**
     * The list of values allowed by <?php echo $fhirName; ?>

     * @var array
     */
    private static $valueList = [
<?php foreach($type->getEnumeration() as $enum) : ?>
        '<?php echo $enum->getValue(); ?>',
<?php endforeach; ?>
    ];

<?php
echo require_with(
    PHPFHIR_TEMPLATE_METHODS_DIR . '/common.php',
    [
        'type' => $type,
        'parentType' => $type->getParentType(),
    ]
);
?>

    /**
     * Returns the list of allowed values for this type
     * @return string[]
     */
    public function _getAllowedValueList()
    {
        return self::$valueList;
    }

    /**
     * @return bool
     */
    public function _isValid()
    {
        $v = $this->getValue();
        return null === $v || in_array((string)$v, self::$valueList, true);
    }
}<?php return ob_get_clean();