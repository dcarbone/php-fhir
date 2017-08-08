<?php namespace DCarbone\PHPFHIR\ClassGenerator\Template\PHPFHIR;

use DCarbone\PHPFHIR\ClassGenerator\Config;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\CopyrightUtils;

/**
 * Class HelperTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template\PHPFHIR
 */
class HelperTemplate extends AbstractPHPFHIRClassTemplate {

    /**
     * HelperTemplate constructor.
     * @param \DCarbone\PHPFHIR\ClassGenerator\Config $config
     */
    public function __construct(Config $config) {
        parent::__construct($config, 'PHPFHIRHelper');
    }

    /**
     * @return string
     */
    public function compileTemplate() {
        return sprintf(
            include PHPFHIR_TEMPLATE_DIR . '/helper_template.php',
            $this->outputNamespace,
            CopyrightUtils::getBasePHPFHIRCopyrightComment()
        );
    }
}