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

class Daiquiri_Form_DisplayGroup_HorizontalCaptcha extends Zend_Form_DisplayGroup {

    /**
     * Initializes the DisplayGroup
     */
    function init() {
        // set css class for html element
        $this->setAttrib('class', 'form-horizontal');

        // set decorators for DisplayGroup
        $this->setDecorators(array(
            'FormElements',
            'Fieldset'
        ));

        // set decorators of captcha element
        $elements = $this->getElements();
        $element = $elements['captcha'];
        $element->setDecorators(array(
            'Errors',
            array(
                array('control-group' => 'HtmlTag'),
                array('tag' => 'div', 'class' => 'controls captcha')),
            array(
                'Label',
                array('escape' => false, 'class' => 'control-label')),
            array(
                array('controls' => 'HtmlTag'),
                array('tag' => 'div', 'class' => 'control-group'))
        ));
        $element->getDecorator('Errors')->setOptions(array(
            'tag' => 'span',
            'class' => 'text-error help-inline',
            'style' => 'list-style: none;'
        ));
    }
}
