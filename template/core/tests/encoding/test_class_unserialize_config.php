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

$imports = $coreFile->getImports();
$imports->addCoreFileImportsByName(
    PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG,
);

$coreFiles = $config->getCoreFiles();

$unserializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo PHPFHIR_TEST_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG; ?> extends TestCase
{
    public function testCanConstructWithoutParams()
    {
        $uc = new <?php echo $unserializeConfigClass->getEntityName(); ?>();
        $this->assertEquals(<?php echo PHPFHIR_DEFAULT_LIBXML_OPTS; ?>, $uc->getLibxmlOpts());
        $this->assertEquals(512, $uc->getJSONDecodeMaxDepth());
    }

    public function testCanConstructWithValidValues()
    {
        $uc = new <?php echo $unserializeConfigClass->getEntityName(); ?>(libxmlOpts: 123, jsonDecodeMaxDepth: 456);
        $this->assertEquals(123, $uc->getLibxmlOpts());
        $this->assertEquals(456, $uc->getJSONDecodeMaxDepth());
    }
}

<?php return ob_get_clean();
