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
$coreTestFiles = $config->getCoreTestFiles();
$imports = $coreFile->getImports();

$resourcepParserClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_RESOURCE_PARSER);

$mockResourceClass = $coreTestFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_RESOURCE_TYPE);

$imports->addCoreFileImports(
    $resourcepParserClass,
    $mockResourceClass,
);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>
<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo $coreFile; ?> extends TestCase
{
    // base64 encoded gzip'd CarePlan resource JSON.
    private const _CAREPLAN_JSON = <<<EOD
H4sIAAAAAAAAA61TW0/bMBR+36+I8sQkjJMmTpOqq4SAaUiThqDaHgYPjn3cekriyHa5CPW/7zil
MJC4jC0Pufn4O+e7+Da24MzKCpjf9BBP4gNu4aThXbwba4nfXNaM5awgucoYqVXGCS/ymgjBZA5J
nlQ8xdoWPI8nt3FvjdINAv2Ml973E0qXzXjP2AVVS23pylFhLNAzb1fCrywcgtKd9tp0uEbCGhE4
QR8muFjvxh6ufcB1nvuVw3kW0IHlHiQ2lfoS/0zxEV23Tec+nW+bXl1d7V1lQ9+0qip6vfRtcx7P
Arso0IuUsdEpuF4jmLE3kV8ibH8T7SADARJH+7g3rS2d7QuvL3FCcJNoumpm00bPXtk4pVjz5joa
QCmSmMVI+J6oMG3fwIao7jx0qENsrASLPwQqsEBU1PkWK6XuFsOru3EeWix8SfwDI+FsKKRbrck9
4G6AC0HgzoFzZHBijVa82Md1pgW5pztlqBP+AYVlFUvwGtxyCIYjx6/Igu22xr+p1K3qXyCGmFhQ
YKETofUJR9M6T1UiWZnxknBVVqSumSA5DwmGUo5qMYKyrIPyuM2sUGj7FOhou0C5KJgoioKwZFSS
tKgE4UkJRGYsqfJRXiR1GqB6sNrIu9zawGOUjBKSMJKm85RNRuMJnqiETQZhoJObipQkGRnl87Sa
YFFW3lWsg98W5sDbQfw/Zzu4W6AJ5Gla5WOi+FgRpXK8VYUiWcGF4uiBqrNBLb7J8yY6Eo+tbsKg
G7/eZ3KW5CxFk9kTlzHCLZLj4XhH3kT80mgZwTVYoR08b/nf7HvmxDRGDNsDpYeRPu8fn34/PvoR
ffl2dnI83/8ar9ch2f9JhnFaJKh0+UiGQ4A+qi1wv0TEiHcywjgtho8tJRftWFjoFuhdxh8J8l6E
f5fmYv3hN1dgy4chBgAA
EOD;

    private function _getCarePlanJSON(): string
    {
        return gzdecode(base64_decode(self::_CAREPLAN_JSON, strict: true));
    }

    public function testCanJSONUnserializeResource()
    {
        $json = $this->_getCarePlanJSON();
        $decoded = json_decode($json);
        $rsc = <?php echo $mockResourceClass; ?>::jsonUnserialize($decoded);
        $encoded = json_encode($decoded);
        $this->assertJsonStringEqualsJsonString($encoded, $json);
    }
}
<?php return ob_get_clean();
