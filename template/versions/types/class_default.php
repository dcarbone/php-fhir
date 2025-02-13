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

use DCarbone\PHPFHIR\Utilities\XMLValueLocationUtils;
use DCarbone\PHPFHIR\Utilities\DocumentationUtils;
use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

if ($type->isPrimitiveType() && !$type->hasPrimitiveTypeParent()) {
    throw new \LogicException(sprintf('Cannot use template %s for Type "%s"', __FILE__, $type->getFHIRName()));
}

$sourceMeta = $version->getSourceMetadata();

$coreFiles = $version->getConfig()->getCoreFiles();
$versionCoreFiles = $version->getCoreFiles();

$versionEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENUM_VERSION);
$xmlLocationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION);

// define some common things
$typeKind = $type->getKind();

ob_start();

// build file header
echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . '/header.php',
    [
        'version' => $version,
        'type' => $type,
    ]
);

// -- property field name constants
if ($type->hasLocalProperties()) : ?>
    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
<?php
    foreach ($type->getProperties()->getIterator() as $property) :
        if ($property->getOverloadedProperty() || $property->getMemberOf()->hasPrimitiveTypeParent()) {
            continue;
        }

        $propertyType = $property->getValueFHIRType(); ?>
    public const <?php echo $property->getFieldConstantName(); ?> = '<?php echo $property->getName(); ?>';
<?php   if (null !== $propertyType && ($propertyType->isPrimitiveContainer() || $propertyType->hasPrimitiveContainerParent())) :
    ?>    public const <?php echo $property->getFieldConstantName(); ?>_EXT = '<?php echo $property->getExtName(); ?>';
<?php   endif;
    endforeach;
// -- end property field name constants
endif;

// -- property validation rules
?>

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
    // The default validation rules for this type as defined in the FHIR schema used to generate this code.
    private const _FHIR_VALIDATION_RULES = [
<?php foreach ($type->getAllPropertiesIndexedIterator() as $property) :
        $validationMap = $property->buildValidationMap($type);
        if ([] !== $validationMap) : ?>
        self::<?php echo $property->getFieldConstantName(); ?> => [
<?php       foreach($validationMap as $k => $v) : ?>
            <?php echo $k; ?>::NAME => <?php echo pretty_var_export($v, 3); ?>,
<?php       endforeach; ?>
        ],
<?php
        endif;
endforeach; ?>
    ];
<?php
// -- end property validation rules

if (!$type->hasPrimitiveTypeParent() && $type->hasNonOverloadedProperties()) :
    // -- start xml location array definition
?>

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
    private array $_valueXMLLocations = [
<?php foreach ($type->getProperties()->getIterator() as $property) :
        if (!$property->isSerializableAsXMLAttribute() || null !== $property->getOverloadedProperty()) {
            continue;
        } ?>
        self::<?php echo $property->getFieldConstantName(); ?> => <?php echo XMLValueLocationUtils::determineDefaultLocation($type, $property, true); ?>,
<?php endforeach; ?>
    ];
<?php
    // -- end xml location array definition
endif;

// -- directly implemented properties
if ($type->hasLocalProperties()) : ?>

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
<?php    foreach ($type->getProperties()->getIterator() as $property) :
        if ($property->getOverloadedProperty()) {
            continue;
        }
        $documentation = DocumentationUtils::compilePropertyDocumentation($property, 5, true); ?>
    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *
     *<?php endif; ?> @var <?php echo TypeHintUtils::propertyGetterDocHint($version, $property, false); ?> <?php
        if ('' !== $documentation) : ?>

     <?php endif; ?>*/
    protected <?php echo TypeHintUtils::propertyDeclarationHint($version, $property, false); ?> $<?php echo $property->getName(); ?>;
<?php
// -- end directly implemented properties
    endforeach;
endif;

// -- end field properties

if (!$type->hasPrimitiveTypeParent()) :
    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_METHODS_DIR . '/constructor.php',
        [
            'version' => $version,
            'type' => $type,
        ]
    );
endif; ?>

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
    public function _getFHIRTypeName(): string
    {
        return self::FHIR_TYPE_NAME;
    }
<?php

if (!$type->hasConcreteParent() && ($sourceMeta->isDSTU1() || $type->isResourceType())) : ?>

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
    public function _getFHIRVersion(): <?php echo $versionEnum; ?>
    {
        return <?php echo $versionEnum; ?>::<?php echo $version->getConstName(); ?>;
    }
<?php endif;
if ($type->isContainedType()) : ?>

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
    public function _getResourceType(): string
    {
        return static::FHIR_TYPE_NAME;
    }
<?php
endif;

if (!$type->hasPrimitiveTypeParent() && $type->hasNonOverloadedProperties()) :
    // --- property methods ?>

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
<?php
    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_PROPERTIES_DIR . '/methods/default.php',
        [
            'version' => $version,
            'type' => $type,
        ]
    );
    // --- end property methods
endif;

if (!$type->isAbstract()) :
    
    if ($type->isPrimitiveContainer() || $type->hasPrimitiveContainerParent()) : ?>

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
    public function _nonValueFieldDefined(): bool
    {
    return <?php foreach($type->getAllPropertiesIndexedIterator() as $i => $property) :
        if ($property->isValueProperty()) { continue; }
        if ($i > 0) : ?>

            || <?php endif; ?>isset($this-><?php echo $property->getName(); ?>)<?php endforeach; ?>;
    }
<?php endif; ?>

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
<?php

    if (!$type->hasPrimitiveTypeParent()) :
        echo require_with(
            PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR . '/xml.php',
            [
                'version' => $version,
                'type'     => $type,
            ]
        );

        echo "\n";

        echo require_with(
            PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR . '/json.php',
            [
                'version' => $version,
                'type' => $type,
            ]
        );
    endif;
endif;

if (null === $type->getParentType() || ($type->isPrimitiveContainer() && !$type->hasPrimitiveContainerParent())) : ?>

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
    public function __toString(): string
    {
<?php
    if ($type->isPrimitiveContainer() || $type->hasPrimitiveContainerParent()) : ?>
        return $this->_getValueAsString();
<?php
    else : ?>
        return self::FHIR_TYPE_NAME;
<?php
    endif; ?>
    }
<?php
endif; ?>}<?php return ob_get_clean();
