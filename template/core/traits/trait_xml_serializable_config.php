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
    * Trait <?php echo PHPFHIR_TRAIT_XML_SERIALIZABLE_CONFIG; if ('' !== $rootNS) : ?>

    * @package \<?php echo $rootNS; ?>
<?php endif; ?>

    */
trait <?php echo PHPFHIR_TRAIT_XML_SERIALIZABLE_CONFIG; ?>

{
    /** @var int */
    private int $libxmlOpts;
    /** @var string */
    private string $domVersion;
    /** @var string */
    private string $encoding;
    /** @var bool */
    private bool $preserveWhitespace;
    /** @var bool */
    private bool $formatOutput;

    /**
     * @return \DOMDocument;
     */
    public function newDOMDocument(): \DOMDocument
    {
        $dom = new \DOMDocument($this->getDOMVersion(), $this->getEncoding());
        $dom->preserveWhiteSpace = $this->getPreserveWhitespace();
        $dom->formatOutput = $this->getFormatOutput();
        $dom->substituteEntities = false;
        $dom->strictErrorChecking = false;
        $dom->validateOnParse = false;
        return $dom;
    }

    /**
     * Sets the option flags to provide to libxml when serializing and unserializing XML
     *
     * @param int $libxmlOpts
     * @return static
     */
    public function setLibxmlOpts(int $libxmlOpts): self
    {
        $this->libxmlOpts = $libxmlOpts;
        return $this;
    }

    /**
     * Returns set libxml option flags
     *
     * @return int
     */
    public function getLibxmlOpts(): int
    {
        return $this->libxmlOpts ?? <?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?>::DEFAULT_LIBXML_OPTS;
    }

    /**
     * @param string $domVersion
     * @return static
     */
    public function setDOMVersion(string $domVersion): self
    {
        $this->domVersion = $domVersion;
        return $this;
    }

    /**
     * @return string
     */
    public function getDOMVersion(): string
    {
        return $this->domVersion ?? <?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?>::DEFAULT_DOM_VERSION;
    }

    /**
     * @param string $encoding
     * @return static
     */
    public function setEncoding(string $encoding): self
    {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * @return string
     */
    public function getEncoding(): string
    {
        return $this->encoding ?? <?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?>::DEFAULT_ENCODING;
    }

    /**
     * Sets whether or not to preserve whitespace when rendering XML
     *
     * @param bool $preserveWhitespace
     * @return static
     */
    public function setPreserveWhitespace(bool $preserveWhitespace): self
    {
        $this->preserveWhitespace = $preserveWhitespace;
        return $this;
    }

    /**
     * @return bool
     */
    public function getPreserveWhitespace(): bool
    {
        return $this->preserveWhitespace ?? <?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?>::DEFAULT_PRESERVE_WHITESPACE;
    }

    /**
     * @param bool $formatOutput
     * @return static
     */
    public function setFormatOutput(bool $formatOutput): self
    {
        $this->formatOutput = $formatOutput;
        return $this;
    }

    /**
     * @return bool
     */
    public function getFormatOutput(): bool
    {
        return $this->formatOutput ?? <?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?>::DEFAULT_FORMAT_OUTPUT;
    }
}
<?php return ob_get_clean();