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

class Daiquiri_Form_Element_Textarea extends Zend_Form_Element_Textarea {

    /**
     * A hint to be displayed with the form element.
     * @var string
     */
    protected $_hint;

    /**
     * Setter for $_hint.
     * @param string $hint a hint for the form element
     */
    public function setHint($hint) {
        $this->_hint = $hint;
    }

    /**
     * Getter for $_hint.
     * @return string $hint a hint for the form element
     */
    public function getHint() {
        return $this->_hint;
    }

    /**
     * Sets the default decorators needed for angular.
     */
    public function loadDefaultDecorators() {
        return Daiquiri_Form_Element_Abstract::loadDaiquiriDecorators($this);
    }
}