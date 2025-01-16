<?php declare(strict_types=1);

/*
 * Copyright 2024-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_TYPE);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

interface <?php echo PHPFHIR_INTERFACE_VERSION_TYPE_MAP; ?>

{
    /**
     * Must return the fully qualified class name for FHIR Type name.  Must return null if type not found.
     *
     * @param string $typeName
     * @return string|null
     */
    public static function getTypeClassName(string $typeName): null|string;

    /**
     * Must return the full internal class map
     *
     * @return array
     */
    public static function getMap(): array;

    /**
     * Must return the full list of containable resource types
     *
     * @return array
     */
    public static function getContainableTypes(): array;

    /**
     * @param string $typeName Name of FHIR object reference by a version's container type
     * @return string|null Name of class as string or null if type is not contained in map
     */
    public static function getContainedTypeClassName(string $typeName): null|string;

    /**
     * Must attempt to determine if the provided value is or describes a containable resource type
     *
     * @param string|array|\SimpleXMLElement|<?php echo $typeInterface->getFullyQualifiedName(true); ?> $type
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function isContainableResource(string|array|\SimpleXMLElement|<?php echo PHPFHIR_INTERFACE_TYPE; ?> $type): bool;

    /**
     * @param \SimpleXMLElement $node Parent element containing inline resource
     * @return string Fully qualified class name of contained resource type
     */
    public static function getContainedTypeClassNameFromXML(\SimpleXMLElement $node): string;

    /**
     * @param array $data
     * @return string Fully qualified class name of contained resource type
     */
    public static function getContainedTypeClassNameFromArray(array $data): string;
}
<?php return ob_get_clean();
