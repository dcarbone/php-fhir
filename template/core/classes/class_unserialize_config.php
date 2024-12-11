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

$namespace = $config->getFullyQualifiedName(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo $config->getBasePHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Class <?php echo PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
class <?php echo PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG; ?>

{
    /** @var int */
    private int $_libxmlOpts;

    /** @var int */
    private int $_jsonDecodeMaxDepth;

    /**
     * <?php echo PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG; ?> constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        foreach(<?php echo PHPFHIR_ENUM_UNSERIALIZE_CONFIG_KEY; ?>::cases() as $k) {
            if (isset($config[$k->value]) || array_key_exists($k->value, $config)) {
                $this->{"set{$k->value}"}($config[$k->value]);
            }
        }
    }

    /**
     * @param int $libxmlOpts
     * @return self
     */
    public function setLibxmlOpts(int $libxmlOpts): self
    {
        $this->_libxmlOpts = $libxmlOpts;
        return $this;
    }

    /**
     * @return int
     */
    public function getLibxmlOpts(): int
    {
        return $this->_libxmlOpts ?? <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::DEFAULT_LIBXML_OPTS;
    }

    /**
     * @param int $jsonDecodeMaxDepth
     * @return self
     */
    public function setJsonDecodeMaxDepth(int $jsonDecodeMaxDepth): self
    {
        $this->_jsonDecodeMaxDepth = $jsonDecodeMaxDepth;
        return $this;
    }

    /**
     * @return int
     */
    public function getJsonDecodeMaxDepth(): int
    {
        return $this->_jsonDecodeMaxDepth ?? <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::DEFAULT_JSON_DECODE_MAX_DEPTH;
    }
}
<?php return ob_get_clean();