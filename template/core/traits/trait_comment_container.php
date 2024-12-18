<?php declare(strict_types=1);

/*
 * Copyright 2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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


trait <?php echo PHPFHIR_TRAIT_COMMENT_CONTAINER; ?>

{
    /** @var array */
    private array $_fhirComments = [];

    /**
     * Arbitrary comments of a hopefully useful nature
     * @return array
     */
    public function _getFHIRComments(): array
    {
        return $this->_fhirComments;
    }

    /**
     * Set internal fhir_comments list, overwriting any previous value(s)
     * @param array $fhirComments
     * @return self
     */
    public function _setFHIRComments(array $fhirComments): self
    {
        $this->_fhirComments = $fhirComments;
        return $this;
    }

    /**
     * Append comment string to internal fhir_comments list
     * @param string $fhirComment
     * @return self
     */
    public function _addFHIRComment(string $fhirComment): self
    {
        $this->_fhirComments[] = $fhirComment;
        return $this;
    }
}
<?php
return ob_get_clean();