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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $config->getFullyQualifiedName(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


class <?php echo PHPFHIR_EXCEPTION_API_UNEXPECTED_RESPONSE_CODE; ?> extends <?php echo PHPFHIR_EXCEPTION_ABSTRACT_API_ERROR; ?>

{
    /** @var int */
    private int $_expectedCode;

    public function __construct(<?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?> $rc, int $expectedCode) {
        parent::__construct(sprintf('Response returned code %d, expected %d', $rc->errno, $expectedCode));
        $this->_rc = $rc;
        $this->_expectedCode = $expectedCode;
    }

    public function getExpectedCode(): int
    {
        return $this->_expectedCode;
    }
}
<?php return ob_get_clean();