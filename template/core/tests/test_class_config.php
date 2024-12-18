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

use DCarbone\PHPFHIR\Enum\TestType;

/** @var \DCarbone\PHPFHIR\Config $config */

ob_start();

echo "<?php\n\n";

echo "namespace {$config->getFullyQualifiedTestsName(TestType::BASE, false)};\n\n";

echo $config->getBasePHPFHIRCopyrightComment(false);

echo "\n\n";
echo "use PHPUnit\\Framework\\TestCase;\n";
echo "use {$config->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_FACTORY_CONFIG)};\n";
echo "use {$config->getFullyQualifiedName(false, PHPFHIR_ENUM_FACTORY_CONFIG_KEY)};\n";
echo "use {$config->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_CONSTANTS)};\n";

?>

class <?php echo PHPFHIR_TEST_CLASSNAME_FACTORY_CONFIG; ?> extends TestCase
{
    public function testConfigDefaults(): void
    {
        $config = new <?php echo PHPFHIR_CLASSNAME_FACTORY_CONFIG; ?>();
        $this->assertFalse($config->getRegisterAutoloader());
        $this->assertEquals(<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::DEFAULT_LIBXML_OPTS, $config->getLibxmlOpts());
        $this->assertNull($config->getRootXMLNS());
        $this->assertFalse($config->getOverrideSourceXMLNS());
    }

    public function testConfigWithkeys(): void
    {
        $data = [
            <?php echo PHPFHIR_ENUM_FACTORY_CONFIG_KEY; ?>::REGISTER_AUTOLOADER->value => true,
            <?php echo PHPFHIR_ENUM_FACTORY_CONFIG_KEY; ?>::LIBXML_OPTS->value => 12345,
            <?php echo PHPFHIR_ENUM_FACTORY_CONFIG_KEY; ?>::ROOT_XMLNS->value => 'xmlns://example.org',
            <?php echo PHPFHIR_ENUM_FACTORY_CONFIG_KEY; ?>::OVERRIDE_SOURCE_XMLNS->value => true,
        ];
        $config = new <?php echo PHPFHIR_CLASSNAME_FACTORY_CONFIG; ?>($data);
        foreach($data as $k => $v) {
            $this->assertEquals($v, $config->{"get{$k}"}());
        }
    }
}
<?php
return ob_get_clean();
