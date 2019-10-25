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

/** @var \DCarbone\PHPFHIR\Definition\Type $type */

ob_start();

echo "<?php\n\n";

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>

if (!class_exists('<?php echo $type->getFullyQualifiedTestClassName(true); ?>', true)) {
    if (class_exists('\\PHPUnit\\Runner\\Version', true) && version_compare(\PHPUnit\Runner\Version::id(),  '8.0', '>=')) {
        require __DIR__ . '/<?php echo $type->getTestClassName() . '_phpunit_gte_8.php'; ?>';
    } else {
        require __DIR__ . '/<?php echo $type->getTestClassName() . '_phpunit_lt_8.php'; ?>';
    }
}
<?php return ob_get_clean();