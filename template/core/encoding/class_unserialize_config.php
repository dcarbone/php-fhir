<?php declare(strict_types=1);

/*
 * Copyright 2024-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$imports = $coreFile->getImports();
$imports->addCoreFileImportsByName(
    PHPFHIR_ENCODING_ENUM_PROFILE_RESOLUTION,
    PHPFHIR_ENCODING_ENUM_ON_UNKNOWN_PROFILE,
    PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE,
    PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH,
    PHPFHIR_ENCODING_ENUM_ON_PROFILE_VIOLATION,
);

$coreFiles = $config->getCoreFiles();
$profileResolutionEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_PROFILE_RESOLUTION);
$onUnknownProfileEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_ON_UNKNOWN_PROFILE);
$onAmbiguousProfileEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE);
$onProfileVersionMismatchEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH);
$onProfileViolationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_ON_PROFILE_VIOLATION);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo \DCarbone\PHPFHIR\Utilities\ImportUtils::compileImportStatements($imports); ?>

class <?php echo PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG; ?>

{
    /** @var int */
    private int $_libxmlOpts = <?php echo PHPFHIR_DEFAULT_LIBXML_OPT_MASK; ?>;
    /** @var int */
    private int $_jsonDecodeMaxDepth = 512;
    /** @var int */
    private int $_jsonDecodeOpts = <?php echo PHPFHIR_DEFAULT_JSON_DECODE_OPT_MASK; ?>;
    /** @var <?php echo $profileResolutionEnum->getFullyQualifiedName(true); ?> */
    private <?php echo PHPFHIR_ENCODING_ENUM_PROFILE_RESOLUTION; ?> $_profileResolution = <?php echo PHPFHIR_ENCODING_ENUM_PROFILE_RESOLUTION; ?>::OFF;
    /** @var <?php echo $onUnknownProfileEnum->getFullyQualifiedName(true); ?> */
    private <?php echo PHPFHIR_ENCODING_ENUM_ON_UNKNOWN_PROFILE; ?> $_onUnknownProfile = <?php echo PHPFHIR_ENCODING_ENUM_ON_UNKNOWN_PROFILE; ?>::FALLBACK;
    /** @var <?php echo $onAmbiguousProfileEnum->getFullyQualifiedName(true); ?> */
    private <?php echo PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE; ?> $_onAmbiguousProfile = <?php echo PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE; ?>::FIRST_DECLARED;
    /** @var <?php echo $onProfileVersionMismatchEnum->getFullyQualifiedName(true); ?> */
    private <?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH; ?> $_onProfileVersionMismatch = <?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH; ?>::IGNORE_VERSION;
    /** @var <?php echo $onProfileViolationEnum->getFullyQualifiedName(true); ?> */
    private <?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VIOLATION; ?> $_onProfileViolation = <?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VIOLATION; ?>::KEEP;

    public function __construct(null|int $libxmlOpts = null,
                                null|int $jsonDecodeMaxDepth = null,
                                null|int $jsonDecodeOpts = null,
                                null|string|<?php echo PHPFHIR_ENCODING_ENUM_PROFILE_RESOLUTION; ?> $profileResolution = null,
                                null|string|<?php echo PHPFHIR_ENCODING_ENUM_ON_UNKNOWN_PROFILE; ?> $onUnknownProfile = null,
                                null|string|<?php echo PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE; ?> $onAmbiguousProfile = null,
                                null|string|<?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH; ?> $onProfileVersionMismatch = null,
                                null|string|<?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VIOLATION; ?> $onProfileViolation = null)
    {
        if (null !== $libxmlOpts) {
            $this->setLibxmlOpts($libxmlOpts);
        }
        if (null !== $jsonDecodeMaxDepth) {
            $this->setJSONDecodeMaxDepth($jsonDecodeMaxDepth);
        }
        if (null !== $jsonDecodeOpts) {
            $this->setJSONDecodeOpts($jsonDecodeOpts);
        }
        if (null !== $profileResolution) {
            $this->setProfileResolution($profileResolution);
        }
        if (null !== $onUnknownProfile) {
            $this->setOnUnknownProfile($onUnknownProfile);
        }
        if (null !== $onAmbiguousProfile) {
            $this->setOnAmbiguousProfile($onAmbiguousProfile);
        }
        if (null !== $onProfileVersionMismatch) {
            $this->setOnProfileVersionMismatch($onProfileVersionMismatch);
        }
        if (null !== $onProfileViolation) {
            $this->setOnProfileViolation($onProfileViolation);
        }
    }

    /**
     * The option mask to use when decoding serialied XML.
     *
     * @see https://www.php.net/manual/en/libxml.constants.php for details.
     *
     * @param int $libxmlOpts
     * @return self
     */
    public function setLibxmlOpts(int $libxmlOpts): self
    {
        $this->_libxmlOpts = $libxmlOpts;
        return $this;
    }

    /**
     * @return int
     */
    public function getLibxmlOpts(): int
    {
        return $this->_libxmlOpts;
    }

    /**
     * Maximum depth of nested
     *
     * @param int $jsonDecodeMaxDepth
     * @return self
     */
    public function setJSONDecodeMaxDepth(int $jsonDecodeMaxDepth): self
    {
        $this->_jsonDecodeMaxDepth = $jsonDecodeMaxDepth;
        return $this;
    }

    /**
     * @return int
     */
    public function getJSONDecodeMaxDepth(): int
    {
        return $this->_jsonDecodeMaxDepth;
    }

    /**
     * The option mask to use when decoding serialized JSON.
     *
     * @see https://www.php.net/manual/en/json.constants.php under the "json_decode" section for details.
     *
     * @param int $jsonDecodeOpts JSON decode options mask
     * @return self
     */
    public function setJSONDecodeOpts(int $jsonDecodeOpts): self
    {
        $this->_jsonDecodeOpts = $jsonDecodeOpts;
        return $this;
    }

    /**
     * Return the current option mask to use when decoding serialized JSON
     *
     * @return int
     */
    public function getJSONDecodeOpts(): int
    {
        return $this->_jsonDecodeOpts;
    }

    /**
     * Whether to unserialize resources into generated profile classes based on their "meta.profile" values.  One of "off" (default) or "auto".
     *
     * @param string|<?php echo $profileResolutionEnum->getFullyQualifiedName(true); ?> $value
     * @return self
     */
    public function setProfileResolution(string|<?php echo PHPFHIR_ENCODING_ENUM_PROFILE_RESOLUTION; ?> $value): self
    {
        if (is_string($value)) {
            $enum = <?php echo PHPFHIR_ENCODING_ENUM_PROFILE_RESOLUTION; ?>::tryFrom($value);
            if (null === $enum) {
                throw new \InvalidArgumentException(sprintf(
                    'profileResolution must be one of ["%s"], "%s" provided',
                    implode('","', array_column(<?php echo PHPFHIR_ENCODING_ENUM_PROFILE_RESOLUTION; ?>::cases(), 'value')),
                    $value
                ));
            }
            $value = $enum;
        }
        $this->_profileResolution = $value;
        return $this;
    }

    /**
     * @return <?php echo $profileResolutionEnum->getFullyQualifiedName(true); ?>

     */
    public function getProfileResolution(): <?php echo PHPFHIR_ENCODING_ENUM_PROFILE_RESOLUTION; ?>
    {
        return $this->_profileResolution;
    }

    /**
     * What to do when a declared profile has no generated class (or constrains a different type).  One of "fallback" (default; ignore it) or "exception".
     *
     * @param string|<?php echo $onUnknownProfileEnum->getFullyQualifiedName(true); ?> $value
     * @return self
     */
    public function setOnUnknownProfile(string|<?php echo PHPFHIR_ENCODING_ENUM_ON_UNKNOWN_PROFILE; ?> $value): self
    {
        if (is_string($value)) {
            $enum = <?php echo PHPFHIR_ENCODING_ENUM_ON_UNKNOWN_PROFILE; ?>::tryFrom($value);
            if (null === $enum) {
                throw new \InvalidArgumentException(sprintf(
                    'onUnknownProfile must be one of ["%s"], "%s" provided',
                    implode('","', array_column(<?php echo PHPFHIR_ENCODING_ENUM_ON_UNKNOWN_PROFILE; ?>::cases(), 'value')),
                    $value
                ));
            }
            $value = $enum;
        }
        $this->_onUnknownProfile = $value;
        return $this;
    }

    /**
     * @return <?php echo $onUnknownProfileEnum->getFullyQualifiedName(true); ?>

     */
    public function getOnUnknownProfile(): <?php echo PHPFHIR_ENCODING_ENUM_ON_UNKNOWN_PROFILE; ?>
    {
        return $this->_onUnknownProfile;
    }

    /**
     * What to do when several declared profiles match and none is a refinement of all the others.  One of "firstDeclared" (default), "fallback" (use the base type) or "exception".
     *
     * @param string|<?php echo $onAmbiguousProfileEnum->getFullyQualifiedName(true); ?> $value
     * @return self
     */
    public function setOnAmbiguousProfile(string|<?php echo PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE; ?> $value): self
    {
        if (is_string($value)) {
            $enum = <?php echo PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE; ?>::tryFrom($value);
            if (null === $enum) {
                throw new \InvalidArgumentException(sprintf(
                    'onAmbiguousProfile must be one of ["%s"], "%s" provided',
                    implode('","', array_column(<?php echo PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE; ?>::cases(), 'value')),
                    $value
                ));
            }
            $value = $enum;
        }
        $this->_onAmbiguousProfile = $value;
        return $this;
    }

    /**
     * @return <?php echo $onAmbiguousProfileEnum->getFullyQualifiedName(true); ?>

     */
    public function getOnAmbiguousProfile(): <?php echo PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE; ?>
    {
        return $this->_onAmbiguousProfile;
    }

    /**
     * What to do when a declared "url|version" does not match the generated profile version.  One of "ignoreVersion" (default), "fallback" (ignore the profile) or "exception".
     *
     * @param string|<?php echo $onProfileVersionMismatchEnum->getFullyQualifiedName(true); ?> $value
     * @return self
     */
    public function setOnProfileVersionMismatch(string|<?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH; ?> $value): self
    {
        if (is_string($value)) {
            $enum = <?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH; ?>::tryFrom($value);
            if (null === $enum) {
                throw new \InvalidArgumentException(sprintf(
                    'onProfileVersionMismatch must be one of ["%s"], "%s" provided',
                    implode('","', array_column(<?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH; ?>::cases(), 'value')),
                    $value
                ));
            }
            $value = $enum;
        }
        $this->_onProfileVersionMismatch = $value;
        return $this;
    }

    /**
     * @return <?php echo $onProfileVersionMismatchEnum->getFullyQualifiedName(true); ?>

     */
    public function getOnProfileVersionMismatch(): <?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH; ?>
    {
        return $this->_onProfileVersionMismatch;
    }

    /**
     * What to do when a resource unserialized into a profile class violates its constraints.  One of "keep" (default; keep the profile type), "fallback" (use the base type) or "exception".
     *
     * @param string|<?php echo $onProfileViolationEnum->getFullyQualifiedName(true); ?> $value
     * @return self
     */
    public function setOnProfileViolation(string|<?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VIOLATION; ?> $value): self
    {
        if (is_string($value)) {
            $enum = <?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VIOLATION; ?>::tryFrom($value);
            if (null === $enum) {
                throw new \InvalidArgumentException(sprintf(
                    'onProfileViolation must be one of ["%s"], "%s" provided',
                    implode('","', array_column(<?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VIOLATION; ?>::cases(), 'value')),
                    $value
                ));
            }
            $value = $enum;
        }
        $this->_onProfileViolation = $value;
        return $this;
    }

    /**
     * @return <?php echo $onProfileViolationEnum->getFullyQualifiedName(true); ?>

     */
    public function getOnProfileViolation(): <?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VIOLATION; ?>
    {
        return $this->_onProfileViolation;
    }
}
<?php return ob_get_clean();
