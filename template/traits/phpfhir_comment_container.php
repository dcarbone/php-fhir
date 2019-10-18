<?php
/*
 * Copyright 2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */

$rootNS = $config->getNamespace(false);

ob_start();
echo "<?php\n\n"; ?>
namespace <?php echo $rootNS; ?>;

<?php echo CopyrightUtils::getFullPHPFHIRCopyrightComment();
?>

/**
 * Trait <?php echo PHPFHIR_TRAIT_COMMENT_CONTAINER; ?>

 * @package \<?php echo $rootNS; ?>

 */
trait <?php echo PHPFHIR_TRAIT_COMMENT_CONTAINER; ?>

{
    /** @var array */
    private $_fhirComments = [];

    /**
     * Arbitrary comments of a hopefully useful nature
     * @return array
     */
    public function getFHIRComments()
    {
        return $this->_fhirComments;
    }

    /**
     * Set internal fhir_comments list, overwriting any previous value(s)
     * @param array $fhirComments
     * @return static
     */
    public function setFHIRComments(array $fhirComments)
    {
        $this->_fhirComments = $fhirComments;
        return $this;
    }

    /**
     * Append comment string to internal fhir_comments list
     * @param string $fhirComment
     * @return static
     */
    public function addFHIRComment($fhirComment)
    {
        if (is_string($fhirComment)) {
            $this->_fhirComments[] = $fhirComment;
            return $this;
        }
        throw new \InvalidArgumentException(sprintf(
            '$fhirComment must be a string, %s seen.',
            gettype($fhirComment)
        ));
    }
}
<?php
return ob_get_clean();