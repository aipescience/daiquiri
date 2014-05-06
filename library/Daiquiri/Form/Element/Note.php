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

/**
 * This form element renders a plain html string, but has
 * no input field. It can be used to display further information or 
 * images in a form.
 */

/**
 * @class   Daiquiri_Form_Element_Note Note.php
 * @brief   Class of a label/note/text form element.
 * 
 * This form element renders a plain html string, but has no input field.
 * It can be used to display further information or images in a form.
 * 
 * This is a more or less pure inheritance of Zend_Form_Element_Xhtml.
 * 
 */
class Daiquiri_Form_Element_Note extends Zend_Form_Element_Xhtml {

    /**
     * @var string $helper
     * Default form view helper to use for rendering
     */
    public $helper = 'formNote';

}