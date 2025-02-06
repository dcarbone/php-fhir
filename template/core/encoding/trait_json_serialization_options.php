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

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

trait <?php echo $coreFile; ?>

{
    /** @var array */
    private array $_fieldElideMap = [];

    /**
     * Declare the provided field must be serialized to JSON as an object, rather than an array of objects, when
     * it contains a singular element.
     *
     * @param string $field Name of field on this type.
     */
    public function _setJSONFieldElideSingletonArray(string $field, bool $elideSingleton): void
    {
        $this->_fieldElideMap[$field] = $elideSingleton;
    }

    /**
     * Returns whether the provided field should be JSON serialized as an object, rather than an array of objects, when
     * it contains a singular element.
     *
     * @return true
     */
    public function _getJSONFieldElideSingletonArray(string $field): bool
    {
        return $this->_fieldElideMap[$field] ?? false;
    }
}
<?php return ob_get_clean();
