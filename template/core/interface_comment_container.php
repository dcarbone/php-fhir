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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


interface <?php echo PHPFHIR_INTERFACE_COMMENT_CONTAINER; ?>

{
    /**
     * Arbitrary comments of a hopefully useful nature
     * @return array
     */
    public function _getFHIRComments(): array;

    /**
     * Set internal fhir_comments list, overwriting any previous value(s)
     * @param array $fhirComments
     * @return static
     */
    public function _setFHIRComments(array $fhirComments): self;

    /**
     * Append comment string to internal fhir_comments list
     * @param string $fhirComment
     * @return static
     */
    public function _addFHIRComment(string $fhirComment): self;
}
<?php return ob_get_clean();