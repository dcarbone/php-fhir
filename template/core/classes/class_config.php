<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */

$namespace = $config->getFullyQualifiedName(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo $config->getBasePHPFHIRCopyrightComment();

echo "\n\n"; ?>
/**
 * Class <?php echo PHPFHIR_CLASSNAME_CONFIG; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
class <?php echo PHPFHIR_CLASSNAME_CONFIG; ?> implements \JsonSerializable
{
    /** @var bool */
    private bool $_registerAutoloader = false;

    /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION); ?>[] */
    private array $versions = [];

    /**
     * <?php echo PHPFHIR_CLASSNAME_CONFIG; ?> Constructor
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach(<?php echo PHPFHIR_ENUM_CONFIG_KEY; ?>::cases() as $k) {
            if (isset($config[$k->value]) || array_key_exists($k->value, $config)) {
                $this->{"set{$k->value}"}($config[$k->value]);
            }
        }
    }

    /**
     * @var bool $registerAutoloader
     * @return self
     */
    public function setRegisterAutoloader(bool $registerAutoloader): self
    {
        $this->_registerAutoloader = $registerAutoloader;
        return $this;
    }

    /**
     * @return bool
     */
    public function getRegisterAutoloader(): bool
    {
        return $this->_registerAutoloader;
    }

    public function addVersion(string $name, array $config): self
    {
        if (isset($this->_versions[$name])) {
            throw new \InvalidArgumentException(sprintf('Version "%s" already defined.', $name));
        }



        $this->versions[] = ;
        return $this;
    }

    <?php // TODO: add version initialization; ?>

    /**
     * @return \stdClass
     */
    public function jsonSerialize(): \stdClass
    {
        $out = new \stdClass();
        foreach(<?php echo PHPFHIR_ENUM_CONFIG_KEY; ?>::cases() as $key) {
            $out->{$key->value} = $this->{"get$key->value"}();
        }
        return $out;
    }
}
<?php return ob_get_clean();