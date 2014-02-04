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

class Meetings_Form_Mails extends Daiquiri_Form_Abstract {

    private $_accepted = array();
    private $_rejected = array();
    private $_acceptTemplate;
    private $_rejectTemplate;

    public function setAccepted($accepted) {
        foreach ($accepted as $row) {
            $this->_accepted[$row['id']] = $row['firstname'] . ' ' . $row['lastname'] . ' <' . $row['email'] . '>';
        }
    }

    public function setRejected($rejected) {
        foreach ($rejected as $row) {
            $this->_rejected[$row['id']] = $row['firstname'] . ' ' . $row['lastname'] . ' <' . $row['email'] . '>';
        }
    }

    public function setAcceptTemplate($acceptTemplate) {
        $this->_acceptTemplate = $acceptTemplate;
    }

    public function setRejectTemplate($rejectTemplate) {
        $this->_rejectTemplate = $rejectTemplate;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();

        $element = new Daiquiri_Form_Element_Note('accepted_note', array(
            'value' => '<p>The folowing mail will be send to all <b>accepted</b> participants as selected below.</p>'
        ));
        $this->addElement($element);
        $element = new Daiquiri_Form_Element_Note('accepted_mail', array(
            'label' => 'Acceptance mail',
            'value' => '<pre>' . $this->_acceptTemplate['body'] . '</pre>'
        ));
        $this->addElement($element);
        $this->addElement('multiCheckbox', 'accepted_id', array(
            'label' => 'Accepted Users',
            'multiOptions' => $this->_accepted
        ));

        $element = new Daiquiri_Form_Element_Note('rejected_note', array(
            'value' => '<p>The folowing mail will be send to all <b>rejected</b> participants as selected below.</p>'
        ));
        $this->addElement($element);
        $element = new Daiquiri_Form_Element_Note('rejected_mail', array(
            'label' => 'Rejection mail',
            'value' => '<pre>' . $this->_rejectTemplate['body'] . '</pre>'
        ));
        $this->addElement($element);
        $this->addElement('multiCheckbox', 'rejected_id', array(
            'label' => 'Rejected users',
            'multiOptions' => $this->_rejected
        ));

        $this->addDangerButtonElement('submit', 'Send Mails');
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addInlineGroup(array('accepted_note'), 'accepted-note');
        $this->addHorizontalGroup(array('accepted_mail','accepted_id'), 'accepted');
        $this->addInlineGroup(array('rejected_note'), 'rejected-note');
        $this->addHorizontalGroup(array('rejected_mail','rejected_id'), 'rejected-id');
        $this->addActionGroup(array('submit', 'cancel'));

        // tick all fields
        $this->getElement('accepted_id')->setValue(array_keys($this->_accepted));   
        $this->getElement('rejected_id')->setValue(array_keys($this->_rejected));   
    }

}
