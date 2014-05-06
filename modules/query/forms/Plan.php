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
