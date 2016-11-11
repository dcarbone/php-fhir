<?php

namespace DCarbone\PHPFHIR\Test\GeneratorTest;

use DCarbone\PHPFHIR\ClassGenerator\Generator;
use DCarbone\PHPFHIR\ClassGenerator\Generator\MethodGenerator;

class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Expect that the generator can generate FHIR models from an xsd and place them in the default output directory.
     */
    public function testGenerateFHIRModelFromXSD()
    {
        $rootDirectory = realpath(sprintf('%s/../../', __DIR__));
        $xsdPath = sprintf('%s/tests/integration/xsd', $rootDirectory);

        $generator = new Generator($xsdPath);

        $generator->generate();

        $generatedFHIRMoneyClassPath = sprintf('%s/output/PHPFHIRGenerated/FHIRMoney.php', $rootDirectory);
        $expectedFHIRMoneyClassPath = sprintf('%s/tests/integration/expected/FHIRMoney.php', $rootDirectory);

        $this->assertFileExists($generatedFHIRMoneyClassPath);

        $generatedFHIRMoneyClassContents = $this->stripDynamicCode(file_get_contents($generatedFHIRMoneyClassPath));
        $expectedFHIRMoneyClassContents = trim(file_get_contents($expectedFHIRMoneyClassPath));

        $this->assertSame($expectedFHIRMoneyClassContents, $generatedFHIRMoneyClassContents);
    }

    /**
     * Expect that the generator can be configured to alter the behaviour of the xmlSerialize method.
     */
    public function testXmlSerializationAttributeOverrides()
    {
        $rootDirectory = realpath(sprintf('%s/../../', __DIR__));
        $xsdPath = sprintf('%s/tests/integration/xsd', $rootDirectory);

        MethodGenerator::addXmlSerializationAttributeOverride('Money', 'id');

        $generator = new Generator($xsdPath);

        $generator->generate();

        $generatedFHIRMoneyClassPath = sprintf('%s/output/PHPFHIRGenerated/FHIRMoney.php', $rootDirectory);
        $expectedFHIRMoneyClassPath = sprintf('%s/tests/integration/expected/FHIRMoneyIdAsAttribute.php', $rootDirectory);

        $this->assertFileExists($generatedFHIRMoneyClassPath);

        $generatedFHIRMoneyClassContents = $this->stripDynamicCode(file_get_contents($generatedFHIRMoneyClassPath));
        $expectedFHIRMoneyClassContents = trim(file_get_contents($expectedFHIRMoneyClassPath));

        $this->assertSame($expectedFHIRMoneyClassContents, $generatedFHIRMoneyClassContents);

        // reset generator behaviour
        MethodGenerator::setXmlSerializationAttributeOverride(array());
    }

    /**
     * Strip out comments that contain dynamic text
     *
     * @param string $contents
     * @return string
     */
    private function stripDynamicCode($contents)
    {
        $contents = trim($contents);

        $beforeComments = substr($contents, 0, strpos($contents, '/*!'));

        $afterComments = substr($contents, strpos($contents, '*/') + 4);

        return $beforeComments . $afterComments;
    }
}
