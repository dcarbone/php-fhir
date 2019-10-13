
<?php

/*
 * Copyright 2018-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var \DCarbone\PHPFHIR\Definition\Type $type */

$xmlName = NameUtils::getTypeXMLElementName($type);

ob_start();

echo require_with(
    PHPFHIR_TEMPLATE_METHODS_DIR . '/common.php',
    [
        'type' => $type,
    ]
);
?>
    /**
     * @return array
     */
    public function getFHIRComments()
    {
        return $this->_fhirComments;
    }

    /**
     * @param array $fhirComments
     * @return <?php echo $type->getFullyQualifiedClassName(true); ?>

     */
    public function setFHIRComments(array $fhirComments)
    {
        $this->_fhirComments = $fhirComments;
        return $this;
    }

    /**
     * @param string $fhirComment
     * @return <?php echo $type->getFullyQualifiedClassName(true); ?>

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

<?php return ob_get_clean();