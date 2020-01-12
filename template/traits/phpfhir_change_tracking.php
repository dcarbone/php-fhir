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
echo "<?php\n\n";

if ('' !== $rootNS) :
    echo "namespace {$rootNS};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Trait <?php echo PHPFHIR_TRAIT_CHANGE_TRACKING; if ('' !== $rootNS) : ?>

 * @package \<?php echo $rootNS; ?>
<?php endif; ?>

 */
trait <?php echo PHPFHIR_TRAIT_CHANGE_TRACKING; ?>

{
    /** @var int */
    private $_setterCalls = 0;
    /** @var int */
    private $_fieldsDefined = 0;
    /** @var int */
    private $_fieldsZeroed = 0;

    /**
     * Tracks field changes on the containing type
     * @param mixed $original
     * @param mixed $new
     * @return void
     */
    protected function _trackChange($original, $new) {
        $this->_setterCalls++;
        if ($original === $new) {
            return;
        }
        if (([] === $original && [] !== $new) || (null === $original && null !== $new)) {
            $this->_fieldsDefined++;
        } else {
            $this->_fieldsZeroed++;
        }
    }

    /**
     * The number of times a setter was called on the containing type
     * @return int
     */
    public function _getSetterCallCount()
    {
        return $this->_setterCalls;
    }

    /**
     * Returns the total number of times any field was set to a non-empty value
     * @return int
     */
    public function _getFieldsDefined()
    {
        return $this->_fieldsDefined;
    }

    /**
     * Returns the number of times any field was zeroed
     * @return int
     */
    public function _getFieldsZeroed()
    {
        return $this->_fieldsZeroed;
    }

    /**
     * Returns true if there are valued fields on the contained type
     * @return bool
     */
    public function _isValued()
    {
        return $this->_fieldsDefined > $this->_fieldZeroed;
    }
}
