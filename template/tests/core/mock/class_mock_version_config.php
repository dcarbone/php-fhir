<?php declare(strict_types=1);

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();
$testCoreFiles = $config->getCoreTestFiles();
$imports = $coreFile->getImports();

$versionConfigInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION_CONFIG);

$unserializeConfig = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG);
$serializeConfig = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);

$imports->addCoreFileImports(
    $versionConfigInterface,

    $unserializeConfig,
    $serializeConfig,
);

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> implements <?php echo $versionConfigInterface; ?>

{
    private <?php echo $unserializeConfig; ?> $_unserializeConfig;
    private <?php echo $serializeConfig; ?> $_serializeConfig;

    public function __construct(null|<?php echo $unserializeConfig; ?> $unserializeConfig = null,
                                null|<?php echo $serializeConfig; ?> $serializeConfig = null)
    {
        $this->_unserializeConfig = $unserializeConfig ?? new <?php echo $unserializeConfig; ?>();
        $this->_serializeConfig = $serializeConfig ?? new <?php echo $serializeConfig; ?>();
    }

    public function getUnserializeConfig(): <?php echo $unserializeConfig; ?>

    {
        return $this->_unserializeConfig;
    }

    public function getSerializeConfig(): <?php echo $serializeConfig; ?>

    {
        return $this->_serializeConfig;
    }
}
<?php return ob_get_clean();
