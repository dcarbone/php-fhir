<?php
/*
 * Copyright 2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Type $bundleType */

ob_start();

echo "<?php\n\n";

$testNS = $type->getFullyQualifiedTestNamespace(false);
$testClassname = $type->getTestClassName();
$typeNS = $type->getFullyQualifiedClassName(false);
$typeClassname = $type->getClassName();

echo "namespace {$testNS};\n";

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();
echo "\n\n";
echo "use PHPUnit\\Framework\\TestCase;\n";
echo "use PHPUnit\\Runner\\Version;\n";
echo "use {$type->getFullyQualifiedClassName(false)};\n";
if ($type->isDomainResource()) {
    echo "use PHPUnit\\Framework\\AssertionFailedError;\n";
    echo "use {$bundleType->getFullyQualifiedClassName(false)};\n";
}

// TODO: be more efficient about phpunit version determination...
?>

/**
 * Class <?php echo $testClassname; ?>

 * @package \<?php echo $testNS; ?>

 */
if (8 <= intval(strstr(Version::id(), '.', true), 10)) {
    class <?php echo $testClassname; ?> extends TestCase
    {
        protected function tearDown(): void
        {
            gc_collect_cycles();
        }

<?php
return ob_get_clean();