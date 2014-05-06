<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Config_Form_Templates extends Daiquiri_Form_Abstract {

    private $_submit = null;
    private $_entry = null;

    public function setSubmit($submit) {
        $this->_submit = $submit;
    }
    public function setEntry($entry) {
        $this->_entry = $entry;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();

        // add elements
        $this->addElement('text', 'template', array(
            'label' => 'Template',
            'class' => 'input-xxlarge mono',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addElement('text', 'subject', array(
            'label' => 'Subject',
            'class' => 'input-xxlarge mono',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Text()),
            )
        ));
        $this->addElement('textarea', 'body', array(
            'label' => 'Body',
            'class' => 'input-xxlarge mono',
            'rows' => '20',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Textarea()),
            )
        ));
        $this->addPrimaryButtonElement('submit', $this->_submit);
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('template','subject', 'body'));
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        if (isset($this->_entry['template'])) {
            $this->setDefault('template', $this->_entry['template']);
        }
        if (isset($this->_entry['subject'])) {
            $this->setDefault('subject', $this->_entry['subject']);
        }
        if (isset($this->_entry['body'])) {
            $this->setDefault('body', $this->_entry['body']);
        }
    }

}
