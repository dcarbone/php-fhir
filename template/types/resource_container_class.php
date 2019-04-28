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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

// define some common things
$fqns = $type->getFullyQualifiedNamespace(true);
$typeClassName = $type->getClassName();
$typeKind = $type->getKind();
$parentType = $type->getParentType();
$fhirName = $type->getFHIRName();
$sortedProperties = $type->getProperties()->getSortedIterator();
$classDocumentation = $type->getDocBlockDocumentationFragment(1, true);

$skipImports = true;

ob_start();

echo require PHPFHIR_TEMPLATE_FILE_DIR . '/header_type.php'; ?>
/**<?php if ('' !== $classDocumentation) : ?>

<?php echo $classDocumentation; ?>
 *<?php endif; ?>

 * Class <?php echo $typeClassName; ?>

 * @package <?php echo $fqns; ?>

 */
abstract class <?php echo $typeClassName; ?><?php echo null !== $parentType ? " extends {$parentType->getClassName()}" : '' ?>
{
    // name of FHIR type this class describes
    const FHIR_TYPE_NAME = '<?php echo $fhirName; ?>';
    const FIELD_RESOURCE_TYPE = 'resourceType';

    // list of all containable resource types and their class
    private static $resourceTypes = [
<?php foreach($sortedProperties as $property) : ?>
        '<?php echo $property->getValueFHIRTypeName(); ?>' => '<?php echo $property->getValueFHIRType()->getFullyQualifiedClassName(true); ?>',
<?php endforeach; ?>    ];

    // prevent direct instantiation of this class
    private function __construct() {}

    /**
     * @param null|array|object $resource
     * @return null|object
     */
    public static function createResource($resource = null)
    {
        $t = gettype($resource);
        if ('NULL' === $t) {
            return null;
        } else if ('object' === $t) {
            if (in_array(get_class($resource), self::$resourceTypes, true)) {
                return $resource;
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'Object of class "%s" cannot be contained by this type',
                    get_class($resource)
                ));
            }
        } else if ('array' !== $t) {
            throw new \InvalidArgumentException(sprintf(
                '$resource must be null, object, or array.  %s seen',
                $t
            ));
        } else if (!isset($resource[self::FIELD_RESOURCE_TYPE])) {
            throw new \InvalidArgumentException(sprintf(
                'Unable to determine type of resource, array is missing field "%s"',
                self::FIELD_RESOURCE_TYPE
            ));
        } else if (!isset(self::$resourceTypes[$resource[self::FIELD_RESOURCE_TYPE]])) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot set contained resource to type "%s"',
                $resource[self::FIELD_RESOURCE_TYPE]
            ));
        } else {
            return self::$resourceTypes[$resource[self::FIELD_RESOURCE_TYPE]];
        }
    }

}<?php return ob_get_clean();