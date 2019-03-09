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

use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var \DCarbone\PHPFHIR\Definition\Type $type */

$xmlName = NameUtils::getTypeXMLElementName($type);

ob_start();
echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/unserialize_header.php';
?>
        if (isset($attributes->value)) {
            return $type->setValue((string)$attributes->value);
        }
        if (isset($children->value)) {
            return $type->setValue((string)$children->value);
        }
        if ('' !== ($v = (string)$sxe)) {
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
<?php echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml/serialize_header.php'; ?>
        $sxe->addAttribute('value', (string)$this);
        return $returnSXE ? $sxe : $sxe->saveXML();
    }
<?php return ob_get_clean();