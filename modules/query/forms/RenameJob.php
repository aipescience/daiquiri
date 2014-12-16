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

    /**
     * The default entry for the tablename field.
     * @var array
     */
    private $_tablename;

    /**
     * Sets $_tablename.
     * @param array $tablename the default entry for the tablename field
     */
    public function setTablename($tablename) {
        $this->_tablename = $tablename;
    }

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();

        // add fields
        $this->addElement(new Daiquiri_Form_Element_Tablename('tablename', array(
            'label' => 'New name of the table',
            'value' => $this->_tablename,
            'required' => true
        )));

        $this->addSubmitButtonElement('submit', 'Rename table');
        $this->addCancelButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('tablename'), 'tablerename-group');
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));
    }

}
