<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  See the NOTICE file distributed with this work for additional
 *  information regarding copyright ownership. You may obtain a copy
 *  of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

/**
 * Class for the form which is used to update a user by an admin. 
 */
class Auth_Form_Update extends Auth_Form_Abstract {

    /**
     * Initializes the form. 
     */
    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();
        
        $elements = array();

        // add elements
        foreach ($this->getDetails() as $detail) {
            $elements[] = $this->addDetailElement($detail, true);
        }
        if ($this->_changeUsername) {
            $elements[] = $this->addUsernameElement(true, true, $this->_user['id']);
        }
        if ($this->_changeEmail) {
            $elements[] = $this->addEmailElement(true, true, $this->_user['id']);
        }
        $elements[] = $this->addRoleIdElement(true);
        $elements[] = $this->addStatusIdElement(true);
        $this->addPrimaryButtonElement('submit', 'Update user profile');
        $this->addButtonElement('cancel', 'Cancel');

        // set decorators
        $this->addHorizontalGroup($elements);
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        foreach ($elements as $element) {
            $this->setDefault($element, $this->_user[$element]);
        }
    }

}
