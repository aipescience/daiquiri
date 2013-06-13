<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  See the NOTICE file distributed with this work for additional
 *  information regarding copyright ownership. You may obtain a copy
 *  of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
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