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

class Daiquiri_Form_DisplayGroup_Inline extends Zend_Form_DisplayGroup {

    /**
     * Show labels or not.
     * @var bool
     */
    protected $_label = false;

    /**
     * Sets the label variable.
     * @param bool $label show labels or not
     */
    public function setLabel($label) {
        $this->_label = $label;
    }

    /**
     * Initializes the DisplayGroup
     */
    function init() {
        // set css class for html element
        $this->setAttrib('class', 'daiquiri-form-inline-group');

        // set decorators for DisplayGroup
        $this->setDecorators(array('FormElements','Fieldset'));

        // loop over elements and set decorators
        foreach ($this->getElements() as $element) {
            if (!$this->_label) {
                $element->setDecorators(array(
                    'ViewHelper',
                    'Errors',
                    array(
                        'HtmlTag',
                        array('tag' => 'div',),
                    ),
                    array(
                        'Description',
                        array('tag' => 'div', 'placement' => 'append', 'escape' => true)),
                ));
            } else {
                $element->setDecorators(array(
                    'ViewHelper',
                    'Errors',
                    array(
                        'HtmlTag',
                        array('tag' => 'div')
                    ),
                    array(
                        'Description',
                        array('tag' => 'div', 'placement' => 'append', 'escape' => true)),
                    array(
                        'Label',
                        array('tag' => 'div', 'escape' => false)),
                ));
            }
            // modify Error decorators retroactively
            $element->getDecorator('Errors')->setOptions(array(
                'class' => 'daiquiri-form-error unstyled'
            ));
        }
    }
}
