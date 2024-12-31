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
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();

$respClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_CLIENT_RESPONSE);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>

abstract class <?php echo PHPFHIR_EXCEPTION_CLIENT_ABSTRACT_CLIENT; ?> extends \Exception
{
    /** @var <?php echo $respClass->getFullyQualifiedName(true); ?> */
    protected <?php echo PHPFHIR_CLASSNAME_CLIENT_RESPONSE; ?> $_rc;

    /**
     * @return <?php echo $respClass->getFullyQualifiedName(true); ?>

     */
    public function getResponse(): <?php echo PHPFHIR_CLASSNAME_CLIENT_RESPONSE; ?>

    {
        return $this->_rc;
    }
}
<?php return ob_get_clean();
