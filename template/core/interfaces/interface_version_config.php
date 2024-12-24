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

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $config->getFullyQualifiedName(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


interface <?php echo PHPFHIR_INTERFACE_VERSION_CONFIG; ?>

{
    /**
     * Must return the unserialization config to use for this version
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG); ?>

     */
    public function getUnserializeConfig(): <?php echo PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG; ?>;

    /**
     * Must return the serialization config to use for this version
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_SERIALIZE_CONFIG); ?>
     */
    public function getSerializeConfig(): <?php echo PHPFHIR_CLASSNAME_SERIALIZE_CONFIG; ?>;
}
<?php return ob_get_clean();