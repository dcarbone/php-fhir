<?php declare(strict_types=1);

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\CoreFiles\CoreFile $coreFile */

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

trait <?php echo $coreFile; ?>

{
    /** @var array */
    private array $_fhirComments;

    /**
     * Return any / all comments set on this type.
     *
     * @return string[]
     */
    public function _getFHIRComments(): array
    {
        return $this->_fhirComments ?? [];
    }

    /**
     * Set internal fhir_comments list, overwriting any previous value(s)
     *
     * @param array $fhirComments
     * @return static
     */
    public function _setFHIRComments(iterable $fhirComments): self
    {
        $this->_fhirComments = $fhirComments;
        return $this;
    }

    /**
     * Append comment string to internal fhir_comments list
     *
     * @param string $fhirComment
     * @return static
     */
    public function _addFHIRComment(string $fhirComment): self
    {
        if (!isset($this->_fhirComments)) {
            $this->_fhirComments = [];
        }
        $this->_fhirComments[] = $fhirComment;
        return $this;
    }
}
<?php return ob_get_clean();
