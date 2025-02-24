<?php declare(strict_types=1);

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\CoreFiles\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();

$serializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

/**
 * PHP FHIR XMLWriter Class.
 *
 * This class is intended specifically for internal use within the PHPFHIR library.  Use outside this scope is not
 * promoted or supported.
 */
final class <?php echo PHPFHIR_ENCODING_CLASSNAME_XML_WRITER; ?> extends \XMLWriter
{
    private const _MEM = 'memory';

    private <?php echo $serializeConfigClass; ?> $_config;
    private bool $_docStarted = false;
    private bool $_rootOpen = false;
    private null|string $_open = null;

    /**
     * <?php echo $coreFile; ?> constructor.
     *
     * @param <?php echo $serializeConfigClass->getFullyQualifiedName(true); ?> $config
     */
    public function __construct(<?php echo $serializeConfigClass; ?> $config)
    {
        $this->_config = $config;
    }

    /**
     * @see https://www.php.net/manual/en/xmlwriter.openmemory.php
     *
     * @return bool
     */
    public function openMemory(): bool
    {
        if (null !== $this->_open) {
            throw new \LogicException('This XMLWriter instance is already open');
        }
        $this->_open = self::_MEM;
        return parent::openMemory();
    }

    /**
     * @see https://www.php.net/manual/en/xmlwriter.openuri.php
     *
     * @param string $uri
     * @return bool
     */
    public function openUri(string $uri): bool
    {
        if (null !== $this->_open) {
            throw new \LogicException('This XMLWriter instance is already open');
        }
        $this->_open = $uri;
        return parent::openUri($uri);
    }

    /**
     * @return bool
     */
    public function isOpen(): bool
    {
        return null !== $this->_open;
    }

    /**
     * Returns the destination of writes made by this class.  Value will be "null" if not opened, "memory" if writing
     * opened with "openMemory()", or the $uri provided to "openUri()"
     *
     * @return null|string
     */
    public function getWriteDestination(): null|string
    {
        return $this->_open;
    }

    /**
     * Used to track whether the document has been started
     *
     * @return bool
     */
    public function isDocStarted(): bool
    {
        return $this->_docStarted;
    }

    /**
     * @see https://www.php.net/manual/en/xmlwriter.startdocument.php
     *
     * @param null|string $version
     * @param null|string $encoding
     * @param null|string $standalone
     * @return bool
     */
    public function startDocument(null|string $version = '1.0', null|string $encoding = 'UTF-8', null|string $standalone = 'yes'): bool
    {
        if ($this->_docStarted) {
            throw new \LogicException('Document has already been started');
        }
        $this->_docStarted = true;
        return parent::startDocument($version, $encoding, $standalone);
    }

    /**
     * @return bool
     */
    public function isRootOpen(): bool
    {
        return $this->_rootOpen;
    }

    /**
     * @param string $name
     * @param string|null $sourceXMLNS
     * @return bool
     */
    public function openRootNode(string $name, null|string $sourceXMLNS): bool
    {
        if (null === $this->_open) {
            throw new \LogicException('Must open write destination before writing root node');
        } else if (!$this->_docStarted) {
            throw new \LogicException('Document must be started before writing root node');
        } else if ($this->_rootOpen) {
            throw new \LogicException('Root node is already open');
        }
        if (!$this->startElement($name)) {
            return false;
        }
        if ($this->_config->getOverrideSourceXMLNS() || null === $sourceXMLNS) {
            $ns = (string)$this->_config->getRootXMLNS();
        } else {
            $ns = $sourceXMLNS;
        }
        if ('' !== $ns) {
            if (!$this->writeAttribute('xmlns', $ns)) {
                return false;
            }
        }
        $this->_rootOpen = true;
        return true;
    }
}
<?php return ob_get_clean();