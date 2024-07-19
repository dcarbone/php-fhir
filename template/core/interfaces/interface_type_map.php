<?php declare(strict_types=1);

/*
 * Copyright 2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Config $config */

$namespace = $config->getFullyQualifiedName(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo $config->getBasePHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Interface <?php echo PHPFHIR_INTERFACE_TYPE_MAP; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
interface <?php echo PHPFHIR_INTERFACE_TYPE_MAP; ?>

{
    /**
     * Must return the fully qualified class name for FHIR Type name.  Must return null if type not found
     * @param string $typeName
     * @return string|null
     */
    public function getTypeClass(string $typeName): null|string;

    /**
     * Must return the full internal class map
     * @return array
     */
    public function getMap(): array;

    /**
     * Must return the full list of containable resource types
     * @return array
     */
    public function getContainableTypes(): array;

    /**
     * @param string $typeName Name of FHIR object reference by a version's container type
     * @return string|null Name of class as string or null if type is not contained in map
     */
    public function getContainedTypeClassName(string $typeName): null|string;

    /**
     * Must attempt to determine if the provided value is or describes a containable resource type
     * @param string|array|\SimpleXMLElement|<?php echo PHPFHIR_INTERFACE_TYPE; ?> $type
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isContainableResource(string|array|\SimpleXMLElement|<?php echo PHPFHIR_INTERFACE_TYPE; ?> $type): bool;

    /**
     * @param \SimpleXMLElement $node Parent element containing inline resource
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_CONFIG); ?> $config
     * @return null|<?php echo $config->getfullyQualifiedName(true, PHPFHIR_INTERFACE_CONTAINED_TYPE); ?>

     */
    public function getContainedTypeFromXML(\SimpleXMLElement $node, <?php echo PHPFHIR_CLASSNAME_CONFIG; ?> $config): null|<?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>;

    /**
     * @param array|null $data
     * @return null|<?php echo $config->getfullyQualifiedName(true, PHPFHIR_INTERFACE_CONTAINED_TYPE); ?>

     */
    public function getContainedTypeFromArray(null|array $data): null|<?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>;
}
<?php return ob_get_clean();
