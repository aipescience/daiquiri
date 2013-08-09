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

class Query_Form_Plan extends Daiquiri_Form_Abstract {

    protected $_query;
    protected $_editable;
    protected $_mail;

    public function setQuery($query) {
        $this->_query = $query;
    }

    public function setEditable($editable) {
        $this->_editable = $editable;
    }

    public function setMail($mail) {
        $this->_mail = $mail;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement('plan_csrf');

        $this->addElement('textarea', 'plan_query', array(
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(3, 32768))
            ),
            'label' => 'Query:',
            'rows' => '15',
            'class' => 'span9 mono',
        ));

        if ($this->_mail) {
            $this->addLinkButtonElement('plan_mail', 'Send this plan as a bug report to the Daiquiri developers (opens a new window/tab).', false);
        }

        if (!$this->_editable) {
            $this->getElement('plan_query')->setAttrib('readonly', 'readonly');
        }

        $this->addPrimaryButtonElement('plan_submit', 'Submit this plan');
        $this->addButtonElement('plan_cancel', 'Cancel');

        $this->addParagraphGroup(array('plan_query'), 'input-group');
        $this->addInlineGroup(array('plan_submit', 'plan_cancel'), 'button-group');
        if ($this->_mail) {
            $this->addInlineGroup(array('plan_mail'), 'mail-group');
        }
        if (isset($this->_query)) {
            $this->setDefault('plan_query', $this->_query);
        }
    }

}
