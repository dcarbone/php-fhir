<?php
/*
 * Copyright 2018-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Type $bundleType */

// TODO: precompile list of ID's to test with?

ob_start(); ?>

    /** @var array */
    private $_fetchedResources = [];

    private static $_ignoreErrs = [
        'Unable to provide support for code system',
        ' minimum required =',
        ' Unable to resolve resource',
    ];

    /**
     * @var string $filename
     * @return array
     */
    protected function _runFHIRValidationJAR($filename)
    {
        $output = [];
        $code = -1;
        $cmd = sprintf(
            'java -jar %s %s -version <?php echo CopyrightUtils::getFHIRVersion(true); ?>',
            PHPFHIR_FHIR_VALIDATION_JAR,
            $filename
        );

        exec($cmd, $output, $code);

        $onlyWarn = false;
        if (0 !== $code) {
            foreach($output as $line) {
                foreach(self::$_ignoreErrs as $ignoreMe) {
                    if (false !== strpos($line, $ignoreMe)) {
                        $onlyWarn = true;
                        break;
                    }
                }
            }
        }

        return [$code, $output, $onlyWarn];
    }

    /**
     * @param string $format Either xml or json
     * @return string
     */
    protected function fetchResource($format)
    {
        if (isset($this->_fetchedResources[$format])) {
            return $this->_fetchedResources[$format];
        }
        $url = sprintf('<?php echo rtrim($config->getTestEndpoint(), '/') . '/' . $type->getFHIRName(); ?>/?_count=1&_format=%s', $format);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $this->assertEmpty($err, sprintf('curl error seen: %s', $err));
        if (method_exists($this, 'assertIsString')) {
            $this->assertIsString($res);
        } else {
            $this->assertInternalType('string', $res);
        }
        $this->_fetchedResources[$format] = $res;
        return $res;
    }

    /**
     * @param string $json
     * @param bool $asArray
     * @return mixed
     */
    protected function decodeJSON($json, $asArray)
    {
        $this->assertJson($json);
        $decoded = json_decode($json, $asArray);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->fail(sprintf(
                'Error decoded JSON: %s; Raw: %s',
                function_exists('json_last_error_msg') ? json_last_error_msg() : ('Code: '.json_last_error()),
                $json
            ));
        }
        return $decoded;
    }

    public function testXML()
    {
        $xml = $this->fetchResource('xml');
        try {
            $bundle = <?php echo $bundleType->getClassName(); ?>::xmlUnserialize($xml);
        } catch(\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    'Error building type "<?php echo $bundleType->getFHIRName(); ?>" from XML: %s; Returned XML: %s',
                    $e->getMessage(),
                    $xml
                ),
                $e->getCode(),
                $e
            );
        }
        $this->assertInstanceOf('<?php echo $bundleType->getFullyQualifiedClassName(true); ?>', $bundle);
        if (0 === count($bundle->getEntry())) {
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "<?php echo $config->getTestEndpoint(); ?>" does not have any "<?php echo $type->getFHIRName(); ?>" entries to test against (returned xml: %s)',
                $xml
            ));
            return;
        }
        $this->assertCount(1, $bundle->getEntry());
        $entry = $bundle->getEntry()[0]->getResource();
        $entryElement = $entry->xmlSerialize();
        $entryXML = $entryElement->ownerDocument->saveXML($entryElement);
        try {
            $type = <?php echo $type->getClassName(); ?>::xmlUnserialize($entryXML);
        } catch (\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    'Error building type "<?php echo $type->getFHIRName(); ?>" from XML: %s; XML: %s',
                    $e->getMessage(),
                    $entryXML
                ),
                $e->getCode(),
                $e
            );
        }
        $this->assertInstanceOf('<?php echo $type->getFullyQualifiedClassName(true); ?>', $type);
        $typeElement = $type->xmlSerialize();
        $this->assertEquals($entryXML, $typeElement->ownerDocument->saveXML($typeElement));
        $bundleElement = $bundle->xmlSerialize();
        $this->assertXmlStringEqualsXmlString($xml, $bundleElement->ownerDocument->saveXML());
    }

    public function testJSON()
    {
        $json = $this->fetchResource('json');
        $decoded = $this->decodeJSON($json, true);
        try {
            $bundle = new <?php echo $bundleType->getClassName(); ?>($decoded);
        } catch(\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    'Error building type "<?php echo $bundleType->getFHIRName(); ?>" from JSON: %s; Returned JSON: %s',
                    $e->getMessage(),
                    $json
                ),
                $e->getCode(),
                $e
            );
        }
        if (0 === count($bundle->getEntry())) {
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "<?php echo $config->getTestEndpoint(); ?>" does not have any <?php echo $type->getFHIRName(); ?>" entries to test against (returned json: %s)',
                $json
            ));
            return;
        }

        $reEncoded = json_encode($bundle);
        try {
            $this->assertEquals($decoded, $this->decodeJSON($reEncoded, true));
        } catch (\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    "json_encode output of \"<?php echo $type->getClassName(); ?>\" does not match input: %s\nSource:\n%s\nRe-encoded:\n%s\n",
                    $e->getMessage(),
                    $json,
                    $reEncoded
                ),
                $e->getCode(),
                $e
            );
        }
    }

    public function testValidationXML()
    {
        $xml = $this->fetchResource('xml');
        try {
            $bundle = <?php echo $bundleType->getClassName(); ?>::xmlUnserialize($xml);
        } catch(\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    'Error building type "<?php echo $bundleType->getFHIRName(); ?>" from XML: %s; Returned XML: %s',
                    $e->getMessage(),
                    $xml
                ),
                $e->getCode(),
                $e
            );
        }
        if (0 === count($bundle->getEntry())) {
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "<?php echo $config->getTestEndpoint(); ?>" does not have any <?php echo $type->getFHIRName(); ?>" entries to test against (returned XML: %s)',
                $xml
            ));
            return;
        }
        $errs = $bundle->_getValidationErrors();
        try {
            $this->assertCount(0, $errs);
        } catch (\Exception $e) {
            $this->markTestSkipped(sprintf('Validation errors seen: %s', json_encode($errs, JSON_PRETTY_PRINT)));
        }
    }

    public function testValidationJSON()
    {
        $json = $this->fetchResource('json');
        $decoded = $this->decodeJSON($json, true);
        try {
            $bundle = new <?php echo $bundleType->getClassName(); ?>($decoded);
        } catch(\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    'Error building type "<?php echo $bundleType->getFHIRName(); ?>" from JSON: %s; Returned JSON: %s',
                    $e->getMessage(),
                    $json
                ),
                $e->getCode(),
                $e
            );
        }
        if (0 === count($bundle->getEntry())) {
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "<?php echo $config->getTestEndpoint(); ?>" does not have any <?php echo $type->getFHIRName(); ?>" entries to test against (returned json: %s)',
                $json
            ));
            return;
        }
        $errs = $bundle->_getValidationErrors();
        try {
            $this->assertCount(0, $errs);
        } catch (\Exception $e) {
            $this->markTestSkipped(sprintf('Validation errors seen: %s', json_encode($errs, JSON_PRETTY_PRINT)));
        }
    }

    public function testFHIRValidationXML()
    {
        $xml = $this->fetchResource('xml');
        try {
            $bundle = <?php echo $bundleType->getClassName(); ?>::xmlUnserialize($xml);
        } catch(\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    'Error building type "<?php echo $bundleType->getFHIRName(); ?>" from XML: %s; Returned XML: %s',
                    $e->getMessage(),
                    $xml
                ),
                $e->getCode(),
                $e
            );
        }
        if (0 === count($bundle->getEntry())) {
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "<?php echo $config->getTestEndpoint(); ?>" does not have any <?php echo $type->getFHIRName(); ?>" entries to test against (returned xml: %s)',
                $xml
            ));
            return;
        }
        $entry = $bundle->getEntry()[0]->getResource();
        $fname = PHPFHIR_OUTPUT_TMP_DIR . '/' . $entry->_getFHIRTypeName() . '-<?php echo CopyrightUtils::getFHIRVersion(false); ?>.xml';
        $sfname = PHPFHIR_OUTPUT_TMP_DIR . '/' . $entry->_getFHIRTypeName() . '-<?php echo CopyrightUtils::getFHIRVersion(false); ?>-source.xml';
        $element = $entry->xmlSerialize();
        file_put_contents($fname, $element->ownerDocument->saveXML($element));
        $this->assertFileExists($fname);
        file_put_contents($sfname, $xml);
        $this->assertFileExists($sfname);

        list($code, $output, $onlyWarn) = $this->_runFHIRValidationJAR($fname);

        if ($onlyWarn) {
            $this->markTestSkipped(sprintf(
                'FHIR validation failed with nonsense code error: %s',
                implode("\n", $output)
            ));
        } else {
            $this->assertEquals(
                0,
                $code,
                sprintf(
                    "Expected exit code 0, saw %d:\n%s",
                    $code,
                    implode("\n", $output)
                )
            );
        }

        unlink($fname);
        if (method_exists($this, 'assertFileDoesNotExist')) {
            $this->assertFileDoesNotExist($fname);
        } else {
            $this->assertFileNotExists($fname);
        }
    }

    public function testFHIRValidationJSON()
    {
        $json = $this->fetchResource('json');
        $decoded = $this->decodeJSON($json, true);
        try {
            $bundle = new <?php echo $bundleType->getClassName(); ?>($decoded);
        } catch(\Exception $e) {
            throw new AssertionFailedError(
                sprintf(
                    'Error building type "<?php echo $bundleType->getFHIRName(); ?>" from JSON: %s; Returned JSON: %s',
                    $e->getMessage(),
                    $json
                ),
                $e->getCode(),
                $e
            );
        }
        if (0 === count($bundle->getEntry())) {
            $this->markTestSkipped(sprintf(
                'Provided test endpoint "<?php echo $config->getTestEndpoint(); ?>" does not have any <?php echo $type->getFHIRName(); ?>" entries to test against (returned json: %s)',
                $json
            ));
            return;
        }
        $entry = $bundle->getEntry()[0]->getResource();
        $fname = PHPFHIR_OUTPUT_TMP_DIR . '/' . $entry->_getFHIRTypeName() . '-<?php echo CopyrightUtils::getFHIRVersion(false); ?>.json';
        file_put_contents($fname, json_encode($entry));
        $this->assertFileExists($fname);

        list($code, $output, $onlyWarn) = $this->_runFHIRValidationJAR($fname);

        if ($onlyWarn) {
            $this->markTestSkipped(sprintf(
                'FHIR validation failed with nonsense code error: %s',
                implode("\n", $output)
            ));
        } else {
            $this->assertEquals(
                0,
                $code,
                sprintf(
                    "Expected exit code 0, saw %d:\n%s",
                    $code,
                    implode("\n", $output)
                )
            );
        }

        unlink($fname);
        if (method_exists($this, 'assertFileDoesNotExist')) {
            $this->assertFileDoesNotExist($fname);
        } else {
            $this->assertFileNotExists($fname);
        }
    }
<?php
return ob_get_clean();