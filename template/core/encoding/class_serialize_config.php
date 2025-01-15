<?php declare(strict_types=1);

/*
 * Copyright 2024-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


class <?php echo PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG; ?>

{
    /** @var bool */
    private bool $_overrideSourceXMLNS = false;
    /** @var string */
    private string $_rootXMLNS;
    /** @var int */
    private int $_xhtmlLibxmlOpts = <?php echo PHPFHIR_DEFAULT_LIBXML_OPT_MASK; ?>;

    public function __construct(null|bool $overrideSourceXMLNS = null,
                                null|string $rootXMLNS = null,
                                null|int $xhtmlLibxmlOpts = null)
    {
        if (null !== $overrideSourceXMLNS) {
            $this->setOverrideSourceXMLNS($overrideSourceXMLNS);
        }
        if (null !== $rootXMLNS) {
            $this->setRootXMLNS($rootXMLNS);
        }
        if (null !== $xhtmlLibxmlOpts) {
            $this->setXHTMLLibxmlOpts($xhtmlLibxmlOpts);
        }
    }

    /**
     * @param null|string $rootXMLNS
     * @return self
     */
    public function setRootXMLNS(null|string $rootXMLNS): self
    {
        if (null === $rootXMLNS) {
            unset($this->_rootXMLNS);
            return $this;
        }
        $this->_rootXMLNS = $rootXMLNS;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getRootXMLNS(): null|string
    {
        return $this->_rootXMLNS ?? null;
    }

    /**
     * If true, overrides the xmlns entry found at the root of a source document, if there was one.
     *
     * @param bool $overrideSourceXMLNS
     * @return self
     */
    public function setOverrideSourceXMLNS(bool $overrideSourceXMLNS): self
    {
        $this->_overrideSourceXMLNS = $overrideSourceXMLNS;
        return $this;
    }

    /**
     * @return bool
     */
    public function getOverrideSourceXMLNS(): bool
    {
        return $this->_overrideSourceXMLNS ?? false;
    }

    /**
     * @param int $xhtmlLibxmlOpts
     * @return self
     */
    public function setXHTMLLibxmlOpts(int $xhtmlLibxmlOpts): self
    {
        $this->_xhtmlLibxmlOpts = $xhtmlLibxmlOpts;
        return $this;
    }

    /**
     * @return int
     */
    public function getXHTMLLibxmlOpts(): int
    {
        return $this->_xhtmlLibxmlOpts ?? 0;
    }
}
<?php return ob_get_clean();
