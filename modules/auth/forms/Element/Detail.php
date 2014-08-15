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

class Auth_Form_Element_Detail extends Zend_Form_Element_Text {

    /**
     * Initializes the form element
     */
    function init() {
        // set label
        $this->setLabel(ucfirst($this->getName()));

        // set required
        $this->setRequired(true);

        // set filter
        $this->addFilter('StringTrim');

        // add validator
        $this->addValidator(new Daiquiri_Form_Validator_Text());

        // add validator for max string length
        $this->addValidator('StringLength', false, array(0, 256));
    }
}
