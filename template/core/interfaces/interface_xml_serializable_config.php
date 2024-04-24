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

$namespace = $config->getNamespace(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Interface <?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
interface <?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?>

{
    public const DEFAULT_LIBXML_OPTS = LIBXML_NONET | LIBXML_PARSEHUGE | LIBXML_COMPACT;

    /**
     * Sets the option flags to provide to libxml when serializing and unserializing XML
     *
     * @param int $libxmlOpts
     * @return static
     */
    public function setLibxmlOpts(int $libxmlOpts): self;

    /**
     * Must return the set libxml option flags
     *
     * @return int
     */
    public function getLibxmlOpts(): int;
}
<?php return ob_get_clean();