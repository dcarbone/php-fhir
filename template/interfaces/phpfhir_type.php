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

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */

$namespace = $config->getNamespace(false);

ob_start();

echo "<?php\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Interface <?php echo PHPFHIR_INTERFACE_TYPE; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
interface <?php echo PHPFHIR_INTERFACE_TYPE; ?> extends \JsonSerializable {
    /**
     * @param array|null $data
     */
    public function __construct($data = null);

    /**
     * Returns the FHIR name represented by this Type
     * @return string
     */
    public function getFHIRTypeName();

    /**
     * @param \SimpleXMLElement|string|null \$sxe
     * @param null|\<?php echo $namespace . '\\' . PHPFHIR_INTERFACE_TYPE; ?> $type
     * @return null|static
     */
    public static function xmlUnserialize($sxe = null, <?php echo PHPFHIR_INTERFACE_TYPE; ?> $type = null);

    /**
     * @param null|\SimpleXMLElement \$sxe
     * @return string|\SimpleXMLElement
     */
    public function xmlSerialize(\SimpleXMLElement $sxe = null);

    /**
     * @return string
     */
    public function __toString();
}
<?php return ob_get_clean();