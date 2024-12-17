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

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $config->getFullyQualifiedName(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


trait <?php echo PHPFHIR_TRAIT_SOURCE_XMLNS; ?>

{
    /** @var string */
    private string $_sourceXmlns;

    /**
     * @param string $xmlns
     */
    protected function _setSourceXmlns(string $xmlns): void
    {
        $this->_sourceXmlns = $xmlns;
    }

    /**
     * @return null|string
     */
    public function _getSourceXmlns(): null|string
    {
        return $this->_sourceXmlns ?? null;
    }
}
<?php return ob_get_clean();
