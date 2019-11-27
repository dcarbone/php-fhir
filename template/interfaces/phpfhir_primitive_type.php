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
 * Interface <?php echo PHPFHIR_INTERFACE_PRIMITIVE_TYPE; ?>

 * @package \<?php echo $namespace; ?>
 */
interface <?php echo PHPFHIR_INTERFACE_PRIMITIVE_TYPE; ?> extends <?php echo PHPFHIR_INTERFACE_TYPE; ?> {
    /**
     * Must return true only if defined value is NULL or adheres to whatever limitations this type may have
     * @return bool
     */
    public function _isValid();

    /**
     * If this type has a specific list of allowed values, this must return that list.  In all other cases, it must return null.
     * @return array|null
     */
    public function _getEnumeration();

    /**
     * This method must return the pattern this type's value must adhere to, if it has such a limitation.
     * The returned value must be a valid PHP PCRE pattern string.
     * @return string|null
    public function _getPattern();

    /**
     * If this type has a minimum length requirement, this must return it.  It must return null in all other circumstances
     * @return int|null
     */
    public function _getMinLength();

    /**
     * If this type has a maximum length requirement, this must return it.  It must return -1 in all other circumstances
     * @return int
     */
    public function _getMaxLength();
}
<?php return ob_get_clean();