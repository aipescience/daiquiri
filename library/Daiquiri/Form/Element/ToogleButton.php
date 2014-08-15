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

class Daiquiri_Form_Element_ToogleButton extends Zend_Form_Element_Button {

    /**
     * An optional tooltip.
     * @var string
     */
    protected $_tooltip = null;

    /**
     * Sets the tooltip.
     * @param string $tooltip tooltip for this button
     */
    public function setTooltip($tooltip) {
        $this->_tooltip = $tooltip;
    }

    /**
     * Initializes the form element
     */
    public function init() {
        // set css class for html element
        $this->setAttrib('class', 'btn');

        // set the data-toggle-value attribute
        $this->setAttrib('data-toggle-value', $this->getName());

        // set decorators
        $this->setDecorators(array(
            array('Description', array('escape'=> false)),
            array('ViewHelper', array('escape'=> false)),
        ));

        // create tooltip
        if (!empty($this->_tooltip)) {
            $this->setOptions(array(
                'label' => '<div data-placement="bottom" rel="tooltip" title="' . $this->_tooltip . '">' . $this->getLabel() . '</div>',
                'escape' => false
            ));
        }
    }
}
