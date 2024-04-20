<?php declare(strict_types=1);

/*
 * Copyright 2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
echo "<?php declare(strict_types=1);\n\n";

if ('' !== $rootNS) :
    echo "namespace {$rootNS};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
    /**
    * Trait <?php echo PHPFHIR_TRAIT_XMLNS; if ('' !== $rootNS) : ?>

    * @package \<?php echo $rootNS; ?>
<?php endif; ?>

    */
    trait <?php echo PHPFHIR_TRAIT_XMLNS; ?>

{
    /** @var string */
    protected string $_xmlns = '';

    /**
     * @param null|string $xmlNamespace
     * @return self
     */
    public function _setFHIRXMLNamespace(null|string $xmlNamespace): self
    {
        $this->_xmlns = trim((string)$xmlNamespace);
        return $this;
    }

    /**
     * @return string
     */
    public function _getFHIRXMLNamespace(): string
    {
        return $this->_xmlns;
    }

    /**
     * @return string
     */
    public function _getFHIRXMLElementDefinition(string $elementName): string
    {
        $xmlns = $this->_getFHIRXMLNamespace();
        if ('' !==  $xmlns) {
            $xmlns = sprintf(' xmlns="%s"', $xmlns);
        }
        return sprintf('<%1$s%2$s></%1$s>', $elementName, $xmlns);
    }
}

<?php return ob_get_clean();