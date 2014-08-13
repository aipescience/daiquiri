<?php

/*
 *  Copyright (c) 2012-2014 Jochen S. Klar <jklar@aip.de>,
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

class Query_Form_RenameJob extends Daiquiri_Form_Abstract {

    private $_tablename;

    public function setTablename($tablename) {
        $this->_tablename = $tablename;
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement();

        // add fields
        $this->addElement('text', 'tablename', array(
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(1, 128)),
                array('validator' => 'Regex', 'options' => array('pattern' => '/^[^;@%*?()!"`\'&=]+$/'))
            ),
            'label' => 'New name of the table',
            'value' => $this->_tablename,
            'required' => true
        ));

        $this->addPrimaryButtonElement('submit', 'Rename table');
        $this->addButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('tablename'), 'tablerename-group');
        $this->addActionGroup(array('submit', 'cancel'));
    }

}
