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

/** @var \DCarbone\PHPFHIR\Config $config */

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $config->getFullyQualifiedName(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


class <?php echo PHPFHIR_CLASSNAME_SERIALIZE_CONFIG; ?>

{
    /** @var bool */
    private bool $_overrideSourceXMLNS;
    /** @var string */
    private string $_rootXMLNS;
    /** @var int */
    private int $_xhtmlLibxmlOpts;

    /**
     * <?php echo PHPFHIR_CLASSNAME_SERIALIZE_CONFIG; ?> constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        foreach(<?php echo PHPFHIR_ENUM_SERIALIZE_CONFIG_KEY; ?>::cases() as $k) {
            if (isset($config[$k->value]) || array_key_exists($k->value, $config)) {
                $this->{"set{$k->value}"}($config[$k->value]);
            }
        }
    }

    /**
     * @param string $rootXMLNS
     * @return self
     */
    public function setRootXMLNS(string $rootXMLNS): self
    {
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
