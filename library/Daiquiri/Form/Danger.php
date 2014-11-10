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

class Daiquiri_Form_Danger extends Daiquiri_Form_Abstract {

    /**
     * Label for the submit button
     * @var string
     */
    protected $_submit;

    /**
     * Sets $_submit
     * @param string $submit label for the submit button
     */
    public function setSubmit($submit) {
        $this->_submit = $submit;
    }

    public function init() {
        $this->addCsrfElement();
        
        // add class
        $this->setAttrib('class','daiquiri-form-danger');

        // add fields
        $this->addDangerButtonElement('submit', $this->_submit);
        $this->addCancelButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalButtonGroup(array('submit', 'cancel'));
    }

}
