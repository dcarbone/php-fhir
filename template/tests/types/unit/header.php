<?php declare(strict_types=1);

/*
 * Copyright 2019-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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

ob_start();

echo "<?php\n\n";

$testNS = $type->getFullyQualifiedTestNamespace(PHPFHIR_TEST_TYPE_UNIT, false);
$testClassname = $type->getTestClassName();
$typeNS = $type->getFullyQualifiedClassName(false);
$typeClassname = $type->getClassName();

echo "namespace {$testNS};\n\n";

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();
echo "\n\n";
echo "use PHPUnit\\Framework\\TestCase;\n";
echo "use {$type->getFullyQualifiedClassName(false)};\n";
?>

/**
 * Class <?php echo $testClassname; ?>

 * @package \<?php echo $testNS; ?>

 */
class <?php echo $testClassname; ?> extends TestCase
{
<?php
return ob_get_clean();