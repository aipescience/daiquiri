<?php
/*
 *  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
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

class Daiquiri_Form_Element_Csrf extends Zend_Form_Element_Xhtml {

    /**
     * Use formHidden view helper by default
     * @var string
     */
    public $helper = 'formHidden';

    /**
     * Initializes the form element
     */
    function init() {
        // get session
        $session = new Zend_Session_Namespace('csrf');

        // this element is required
        $this->setRequired(true);

        // this element will be ignored when getting the values from the form
        $this->setIgnore(true);

        // set the value to the stored hash
        $this->setValue($session->hash);

        // create the corresponding validator
        $validator = new Zend_Validate_Identical($session->hash);
        $validator->setMessage('The CSRF token is not valid. Please refresh the page.');
        $this->addValidator($validator);

        // set decorators
        $this->setDecorators(array(
            'ViewHelper',
            'Errors'
        ));
        $this->getDecorator('Errors')->setOptions(array(
            'class' => 'daiquiri-form-error unstyled text-error',
        ));
    }
}