<?php

/*
 * Copyright 2016-2017 Daniel Carbone (daniel.p.carbone@gmail.com)
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

return <<<STRING
<?php namespace %s;

%s

class PHPFHIRHelper {

    public static function recursiveXMLImport(\\SimpleXMLElement \$sxe, \$data) {
        \$new = simplexml_load_string((string)\$data, '\\SimpleXMLElement', LIBXML_NOEMPTYTAG);

        self::doImport(\$sxe, \$new);
    }
    
    private static function doImport(\\SimpleXMLElement \$sxe, \\SimpleXMLElement \$new) {
        \$namespaces = \$new->getNamespaces(true);
        
        foreach(\$namespaces as \$prefix => \$uri) {
            \$sxe->registerXPathNamespace(\$prefix, \$uri);
        }
                
        if (isset(\$namespaces[''])) {
            \$node = \$sxe->addChild(\$new->getName(), (string)\$new, \$namespaces['']);
        } else {
            \$node = \$sxe->addChild(\$new->getName(), (string)\$new);
        }
                
        foreach(\$new->attributes() as \$attr => \$value) {
            \$node->addAttribute(\$attr, \$value);
        }
                
        foreach(\$namespaces as \$space) {
            foreach (\$new->children(\$space) as \$child) {
                self::doImport(\$node, \$child);
            }
        }
    }
}
STRING;
