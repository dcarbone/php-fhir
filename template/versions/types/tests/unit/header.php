<?php declare(strict_types=1);

/*
 * Copyright 2019-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\TestTypeEnum;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$testNS = $type->getFullyQualifiedTestNamespace(TestTypeEnum::UNIT, false);
$testClassname = $type->getTestClassName();
$typeNS = $type->getFullyQualifiedClassName(false);
$typeClassname = $type->getClassName();


ob_start();
echo '<?php'; ?>


namespace <?php echo $testNS; ?>;

<?php $version->getSourceMetadata()->getFullPHPFHIRCopyrightComment(); ?>


use PHPUnit\Framework\TestCase;
use <?php echo $type->getFullyQualifiedClassName(false); ?>;

class <?php echo $testClassname; ?> extends TestCase
{
<?php
return ob_get_clean();