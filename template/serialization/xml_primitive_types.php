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
/** @var \DCarbone\PHPFHIR\Enum\TypeKindEnum $typeKind */
/** @var string $fhirName */
/** @var string $typeClassName */

ob_start();
echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR.'/xml_unserialize_header.php';
?>
        if (null !== ($v = $sxe->attributes()->value)) {
            return $type->setValue((string)\$v);
        }
        if ('' !== ($v = (string)$sxe->children()->value)) {
            return $type->setValue($v);
        }
        return $type;
    }

    /**
     * @param bool \$returnSXE
     * @param null|\SimpleXMLElement \$sxe
     * @return string|\SimpleXMLElement
     */
    public function xmlSerialize($returnSXE = false, \SimpleXMLElement $sxe = null)
    {
        if (null === $sxe) {
            $sxe = new \SimpleXMLElement('<<?php echo $xmlName; ?> xmlns="<?php echo PHPFHIR_FHIR_XMLNS; ?>"></<?php echo $xmlName; ?>>');
        }
        $sxe->addAttribute('value', (string)$this);
        return $returnSXE ? $sxe : $sxe->saveXML();
    }

<?php return ob_get_clean();