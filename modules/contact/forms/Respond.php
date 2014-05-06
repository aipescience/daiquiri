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

class Contact_Form_Respond extends Daiquiri_Form_Abstract {

    protected $_subject;
    protected $_body;

    public function setSubject($subject) {
        $this->_subject = $subject;
    }

    public function setBody($body) {
        $this->_body = $body;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();

        // add elements
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
            'label' => 'Message',
            'class' => 'input-xxlarge mono',
            'rows' => '10',
            'required' => true,
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => new Daiquiri_Form_Validator_Textarea())
            )
        ));

        $this->addPrimaryButtonElement('submit', 'Respond to contact message');
        $this->addButtonElement('cancel', 'Cancel');

        // create groups
        $this->addHorizontalGroup(array('subject', 'body'));
        $this->addActionGroup(array('submit', 'cancel'));

        // set default values for fields.
        $this->setDefault('subject', $this->_subject);
        $this->setDefault('body', $this->_body);
    }

}
