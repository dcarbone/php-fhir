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

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

class <?php echo PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG; ?>

{
    public const PROFILE_RESOLUTION_OFF = 'off';
    public const PROFILE_RESOLUTION_AUTO = 'auto';
    public const PROFILE_RESOLUTIONS = [self::PROFILE_RESOLUTION_OFF, self::PROFILE_RESOLUTION_AUTO];

    public const ON_UNKNOWN_PROFILE_FALLBACK = 'fallback';
    public const ON_UNKNOWN_PROFILE_EXCEPTION = 'exception';
    public const ON_UNKNOWN_PROFILE_ACTIONS = [self::ON_UNKNOWN_PROFILE_FALLBACK, self::ON_UNKNOWN_PROFILE_EXCEPTION];

    public const ON_AMBIGUOUS_PROFILE_FIRST_DECLARED = 'firstDeclared';
    public const ON_AMBIGUOUS_PROFILE_FALLBACK = 'fallback';
    public const ON_AMBIGUOUS_PROFILE_EXCEPTION = 'exception';
    public const ON_AMBIGUOUS_PROFILE_ACTIONS = [
        self::ON_AMBIGUOUS_PROFILE_FIRST_DECLARED,
        self::ON_AMBIGUOUS_PROFILE_FALLBACK,
        self::ON_AMBIGUOUS_PROFILE_EXCEPTION,
    ];

    public const ON_PROFILE_VERSION_MISMATCH_IGNORE_VERSION = 'ignoreVersion';
    public const ON_PROFILE_VERSION_MISMATCH_FALLBACK = 'fallback';
    public const ON_PROFILE_VERSION_MISMATCH_EXCEPTION = 'exception';
    public const ON_PROFILE_VERSION_MISMATCH_ACTIONS = [
        self::ON_PROFILE_VERSION_MISMATCH_IGNORE_VERSION,
        self::ON_PROFILE_VERSION_MISMATCH_FALLBACK,
        self::ON_PROFILE_VERSION_MISMATCH_EXCEPTION,
    ];

    public const ON_PROFILE_VIOLATION_KEEP = 'keep';
    public const ON_PROFILE_VIOLATION_FALLBACK = 'fallback';
    public const ON_PROFILE_VIOLATION_EXCEPTION = 'exception';
    public const ON_PROFILE_VIOLATION_ACTIONS = [
        self::ON_PROFILE_VIOLATION_KEEP,
        self::ON_PROFILE_VIOLATION_FALLBACK,
        self::ON_PROFILE_VIOLATION_EXCEPTION,
    ];

    /** @var int */
    private int $_libxmlOpts = <?php echo PHPFHIR_DEFAULT_LIBXML_OPT_MASK; ?>;
    /** @var int */
    private int $_jsonDecodeMaxDepth = 512;
    /** @var int */
    private int $_jsonDecodeOpts = <?php echo PHPFHIR_DEFAULT_JSON_DECODE_OPT_MASK; ?>;
    /** @var string */
    private string $_profileResolution = self::PROFILE_RESOLUTION_OFF;
    /** @var string */
    private string $_onUnknownProfile = self::ON_UNKNOWN_PROFILE_FALLBACK;
    /** @var string */
    private string $_onAmbiguousProfile = self::ON_AMBIGUOUS_PROFILE_EXCEPTION;
    /** @var string */
    private string $_onProfileVersionMismatch = self::ON_PROFILE_VERSION_MISMATCH_IGNORE_VERSION;
    /** @var string */
    private string $_onProfileViolation = self::ON_PROFILE_VIOLATION_KEEP;

    public function __construct(null|int $libxmlOpts = null,
                                null|int $jsonDecodeMaxDepth = null,
                                null|int $jsonDecodeOpts = null,
                                null|string $profileResolution = null,
                                null|string $onUnknownProfile = null,
                                null|string $onAmbiguousProfile = null,
                                null|string $onProfileVersionMismatch = null,
                                null|string $onProfileViolation = null)
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
     * @param string $value
     * @return self
     */
    public function setProfileResolution(string $value): self
    {
        if (!in_array($value, self::PROFILE_RESOLUTIONS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'profileResolution must be one of ["%s"], "%s" provided',
                implode('","', self::PROFILE_RESOLUTIONS),
                $value
            ));
        }
        $this->_profileResolution = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getProfileResolution(): string
    {
        return $this->_profileResolution;
    }

    /**
     * What to do when a declared profile has no generated class (or constrains a different type).  One of "fallback" (default; ignore it) or "exception".
     *
     * @param string $value
     * @return self
     */
    public function setOnUnknownProfile(string $value): self
    {
        if (!in_array($value, self::ON_UNKNOWN_PROFILE_ACTIONS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'onUnknownProfile must be one of ["%s"], "%s" provided',
                implode('","', self::ON_UNKNOWN_PROFILE_ACTIONS),
                $value
            ));
        }
        $this->_onUnknownProfile = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getOnUnknownProfile(): string
    {
        return $this->_onUnknownProfile;
    }

    /**
     * What to do when several declared profiles match and none is a refinement of all the others.  One of "exception" (default), "firstDeclared" or "fallback" (use the base type).
     *
     * @param string $value
     * @return self
     */
    public function setOnAmbiguousProfile(string $value): self
    {
        if (!in_array($value, self::ON_AMBIGUOUS_PROFILE_ACTIONS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'onAmbiguousProfile must be one of ["%s"], "%s" provided',
                implode('","', self::ON_AMBIGUOUS_PROFILE_ACTIONS),
                $value
            ));
        }
        $this->_onAmbiguousProfile = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getOnAmbiguousProfile(): string
    {
        return $this->_onAmbiguousProfile;
    }

    /**
     * What to do when a declared "url|version" does not match the generated profile version.  One of "ignoreVersion" (default), "fallback" (ignore the profile) or "exception".
     *
     * @param string $value
     * @return self
     */
    public function setOnProfileVersionMismatch(string $value): self
    {
        if (!in_array($value, self::ON_PROFILE_VERSION_MISMATCH_ACTIONS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'onProfileVersionMismatch must be one of ["%s"], "%s" provided',
                implode('","', self::ON_PROFILE_VERSION_MISMATCH_ACTIONS),
                $value
            ));
        }
        $this->_onProfileVersionMismatch = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getOnProfileVersionMismatch(): string
    {
        return $this->_onProfileVersionMismatch;
    }

    /**
     * What to do when a resource unserialized into a profile class violates its constraints.  One of "keep" (default; keep the profile type), "fallback" (use the base type) or "exception".
     *
     * @param string $value
     * @return self
     */
    public function setOnProfileViolation(string $value): self
    {
        if (!in_array($value, self::ON_PROFILE_VIOLATION_ACTIONS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'onProfileViolation must be one of ["%s"], "%s" provided',
                implode('","', self::ON_PROFILE_VIOLATION_ACTIONS),
                $value
            ));
        }
        $this->_onProfileViolation = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getOnProfileViolation(): string
    {
        return $this->_onProfileViolation;
    }
}
<?php return ob_get_clean();
