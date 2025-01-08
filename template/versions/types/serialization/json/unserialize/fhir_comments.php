        if (isset($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS])) {
            if (is_array($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS])) {
                $this->_setFHIRComments($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS]);
            } elseif (is_string($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS])) {
                $this->_addFHIRComment($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS]);
            }
        }