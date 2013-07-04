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
 * @class   Daiquiri_Form_Abstract Abstract.php
 * @brief   Abstract class for daiquiri form handling.
 * 
 * Abstract class for daiquiri form handling. This function provides a variety
 * of convenience functions when dealing with forms.
 * 
 */
abstract class Daiquiri_Form_Abstract extends Zend_Form {

    /**
     * @brief   Init the form by setting form decorators
     * 
     * Sets the form decorator to what is defined in $_formDecorators.
     * 
     */
    public function init() {
        $this->setDecorators(array(
            'FormElements',
            array('Description', array(
                    'tag' => 'div',
                    'placement' => 'append',
                    'class' => 'text-error align-form-horizontal',
                    'escape' => false)),
            'Form'
        ));

        $this->addCsrfElement();
    }

    /**
     * @breif   Adds a form element for the captcha.
     * @return  string $element identifier for the element
     * 
     * Sets up the default Zend captcha for use with daiquiri and returns
     * the captcha element for further use.
     */
    public function addCaptchaElement() {
        $this->addElement('captcha', 'captcha', array(
            'label' => "Please prove that<br/>you are human",
            'ignore' => true,
            'captcha' => 'image',
            'captchaOptions' => array(
                'captcha' => 'image',
                'font' => Daiquiri_Config::getInstance()->core->captcha->fontpath,
                'imgDir' => Daiquiri_Config::getInstance()->core->captcha->dir,
                'imgUrl' => Zend_Controller_Front::getInstance()->getBaseUrl() . Daiquiri_Config::getInstance()->core->captcha->url,
                'wordLen' => 6,
                'fsize' => 20,
                'height' => 60,
                'width' => 218,
                'gcFreq' => 50,
                'expiration' => 300)
        ));
        return 'captcha';
    }

    /**
     * @brief   Adds a form element for the primary button.
     * @param   string $element identifier for the button
     * @param   string $label label for the button
     * @param   bool $ignore check if this field is ignored on server side
     * @return  string $element identifier for the element
     *
     * Creates the submit button and adds it to the form.
     */
    public function addPrimaryButtonElement($element, $label, $ignore = true) {
        // create form element
        $this->addElement('submit', $element, array(
            'required' => false,
            'ignore' => $ignore,
            'label' => $label,
            'class' => 'btn btn-primary',
            'decorators' => array('ViewHelper'),
        ));
        return $element;
    }

    /**
     * @brief   Adds a form element for a very dangerous button.
     * @param   string $element identifier for the button
     * @param   string $label label for the button
     * @param   bool $ignore check if this field is ignored on server side
     * @return  string $element identifier for the element
     *
     * Creates the submit button and adds it to the form.
     */
    public function addDangerButtonElement($element, $label, $ignore = true) {
        // create form element
        $this->addElement('submit', $element, array(
            'required' => false,
            'ignore' => $ignore,
            'label' => $label,
            'class' => 'btn btn-danger',
            'decorators' => array('ViewHelper'),
        ));
        return $element;
    }

    /**
     * @brief   Adds a form element for a secondary button.
     * @param   string $element identifier for the button
     * @param   string $label label for the button
     * @param   bool $ignore check if this field is ignored on server side
     * @return  string $element identifier for the element
     *
     * Creates the button and adds it to the form.
     */
    public function addButtonElement($element, $label, $ignore = true) {
        // create form element
        $this->addElement('submit', $element, array(
            'required' => false,
            'ignore' => $ignore,
            'label' => $label,
            'class' => 'btn',
            'decorators' => array('ViewHelper'),
        ));
        return $element;
    }

    /**
     * @brief   Adds a form element for a button that looks like a link.
     * @param   string $element identifier for the button
     * @param   string $label label for the button
     * @param   bool $ignore check if this field is ignored on server side
     * @return  string $element identifier for the element
     *
     * Creates the button that looks like a link and adds it to the form.
     */
    public function addLinkButtonElement($element, $label, $ignore = true) {
        // create form element
        $this->addElement('button', $element, array(
            'required' => false,
            'ignore' => $ignore,
            'label' => $label,
            'class' => 'linkbutton',
            'decorators' => array('ViewHelper'),
        ));
        return $element;
    }

    /**
     * @brief   Adds a form element for a dumb button without submit.
     * @param   string $element identifier for the button
     * @param   string $label label for the button
     * @param   bool $ignore check if this field is ignored on server side
     * @return  string $element identifier for the element
     *
     * Creates the dumb button and adds it to the form.
     */
    public function addDumbButtonElement($element, $label, $ignore = true) {
        // create form element
        $this->addElement('button', $element, array(
            'required' => false,
            'ignore' => $ignore,
            'label' => $label,
            'class' => 'btn',
            'decorators' => array('ViewHelper'),
        ));
        return $element;
    }

    /**
     * @brief   Adds select dropdown box.
     * @param   string $element     identifier for the select box
     * @param   string $entries     array of strings with select box entries
     * @param   string $legend      optional legend for the button group
     * @return  string $element identifier for the element
     *
     * Adds select dropdown box.
     */
    public function addDropDownElement($element, $entries, $legend = false) {
        $element = $this->createElement('select', $element, array(
            'required' => false,
            'ignore' => false,
            'label' => $legend,
            'decorators' => array('ViewHelper'),
                ));

        $dropdown->addMultiOptions($entries);

        $this->addElement($dropdown);

        return $element;
    }

    /**
     * @brief   Adds a toggle-button element to the form. Needs additional grouping by addToggleButtonGroup
     *          using addToggleButtonGroup. Toggle-buttons are powered by twitter bootstrap.
     * @param   string $element identifier for the solo toggle-button
     * @param   string $label label for the toggle-button
     * @param   string $tooltip tooltip for the toggle-button (optional)
     *
     * Adds a toggle-button element to the form, that can later be combined to a toggle-button group
     * using addToggleButtonGroup. Toggle-buttons are powered by twitter bootstrap.
     */
    public function addToggleButtonElement($element, $label, $tooltip = false) {
        if ($tooltip) {
            $label = '<div data-placement="bottom" rel="tooltip" title="' . $tooltip . '">' . $label . '</div>';
        }

        $this->addElement('button', $element, array(
            'required' => false,
            'ignore' => false,
            'label' => $label,
            'class' => "btn",
            'data-toggle-value' => $element,
            'decorators' => array('Description', 'ViewHelper'),
            'escape' => false,
        ));

        return $element;
    }

    /**
     * @brief   Adds a toggle-button element from bootstrap twitter. 
     * @param   string $prefix      prefix string for the identifiers of the buttons
     * @param   array  $buttons     array of buttons with key as name and array stating label and tooltip (optional)
     * @param   string $maxVal      number of maximum elements before drop-down list is shown (default: 5)
     *
     * Creates a toggle button bar from given array of toggle-buttons which have been added to the form using
     * addToggleButton or a select box if the number isw to high.
     */
    public function addToggleButtonElements($prefix, array $buttons, $maxVal = 5) {
        // add a hidden field to the button group to get the selected value
        $this->addElement('hidden', $prefix . 'value', array(
            'disableLoadDefaultDecorators' => true,
            'decorators' => array('ViewHelper')
        ));

        if (count($buttons) <= $maxVal) {
            // add buttons for queue
            foreach ($buttons as $b) {
                $this->addToggleButtonElement($b['identifier'], $b['label'], $b['tooltip']);
            }
        } else {
            $entries = array();
            foreach ($buttons as $b) {
                $entries[$b['identifier']] = $b['label'];
            }

            // add select field
            $element = $this->createElement('select', $prefix . 'select', array(
                'required' => false,
                'ignore' => false,
                'decorators' => array('ViewHelper', 'Label'),
                'multiOptions' => $entries
                    ));

            $this->addElement($element);
        }
    }

    /**
     * @brief   Auto-Adds a toggle-button element from bootstrap twitter. 
     * @param   string $prefix      prefix string for the identifiers of the buttons
     * @param   array $buttons      array of buttons with key as name and array stating label and tooltip (optional)
     * @param   string $identifier  name of the group (default: 'span-group')
     * @param   int $maxVal         number of maximum elements before drop-down list is shown (default: 5)
     *
     * Automagically adds a toogle-button group element to the form. Creates a toggle button bar from given 
     * array of elements. If more elements are present than a given value, a drop-down list will be shown instead.
     */
    public function addToggleButtonGroup($prefix, array $buttons, $identifier, $maxVal = 5) {

        if (count($buttons) <= $maxVal) {
            // get group elements
            $elements = array();
            foreach ($buttons as $b) {
                $elements[] = $b['identifier'];
            }

            // add display group
            $this->addDisplayGroup($elements, $identifier, array(
                'class' => 'daiquiri-form-queue-group btn-group pull-right',
                'decorators' => array(
                    'FormElements',
                    array('Fieldset', array(
                            'data-toggle' => 'buttons-radio',
                            'data-toggle-name' => $prefix . 'value'
                    )))));
        } else {
            $element = $prefix . 'select';
            $this->addDisplayGroup(array($element), $identifier, array(
                'class' => 'daiquiri-form-queue-group pull-right',
                'decorators' => array(
                    'FormElements',
                    'Fieldset')));
        }
    }

    /**
     * @brief   Add a hash element for security against CSRF attacks.
     * @param   string $salt salt value for the hash
     * @return  Zend_Form_Element_Hash
     * 
     * Adds a hidden tag with a salt for CSRF attack protection.
     */
    public function addCsrfElement() {
        $field = new Zend_Form_Element_Hash('csrf_hash');
        $field->setOptions(array(
            'ignore' => true,
            'salt' => Daiquiri_Config::getInstance()->core->csrfSalt,
            'decorators' => array('ViewHelper')
        ));
        $this->addElement($field);
        return 'csrf_hash';
    }

    /**
     * @brief   Adds a form group to the form object, using a span for every element.
     * @param   array $elements     array of form element names
     * @param   string $identifier  name of the group (default: 'span-group')
     * @param   string $legend      optional legend for the formgroup
     * @param   bool $label         show labels of form elements (default: False)
     * 
     * This function generates a form group, which uses a span for every element.
     * 
     * The group will be added to the form object!
     */
    public function addInlineGroup(array $elements, $identifier = 'span-group', $legend = Null, $label = False) {
        foreach ($elements as $element) {
            // get form element
            $e = $this->getElement($element);
            // set decorators
            if (!$label) {
                $e->setDecorators(array(
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
                $e->setDecorators(array(
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
            $e->getDecorator('Errors')->setOptions(array(
                'class' => 'daiquiri-form-error unstyled'
            ));
        }
        $this->addDisplayGroup($elements, $identifier, array(
            'class' => 'daiquiri-form-inline-group',
            'decorators' => array(
                'FormElements',
                'Fieldset'
            )
        ));
        if ($legend) {
            $group = $this->getDisplayGroup($identifier);
            $group->setOptions(array('legend' => $legend));
        }
    }

    /**
     * @brief   Adds a form group to the form object, using a span for every element.
     * @param   array $elements     array of form element names
     * @param   string $identifier  name of the group (default: 'span-group')
     * @param   string $legend      optional legend for the formgroup
     * @param   bool $label         show labels of form elements (default: False)
     * 
     * This function generates a form group, which uses a span for every element.
     * 
     * The group will be added to the form object!
     */
    public function addInlineRightGroup(array $elements, $identifier = 'span-group', $legend = Null, $label = False) {
        foreach ($elements as $element) {
            // get form element
            $e = $this->getElement($element);
            // set decorators
            if (!$label) {
                $e->setDecorators(array(
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
                $e->setDecorators(array(
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
            $e->getDecorator('Errors')->setOptions(array(
                'class' => 'daiquiri-form-error unstyled'
            ));
        }
        $this->addDisplayGroup($elements, $identifier, array(
            'class' => 'daiquiri-form-inline-right-group',
            'decorators' => array(
                'FormElements',
                'Fieldset'
            )
        ));
        if ($legend) {
            $group = $this->getDisplayGroup($identifier);
            $group->setOptions(array('legend' => $legend));
        }
    }

    /**
     * @brief   Adds a form group to the form object, using a p for every element.
     * @param   array $elements     array of form element names
     * @param   string $identifier  name of the group (default: 'paragraph-group')
     * @param   string $legend      optional legend for the formgroup
     * @param   bool $label         show labels of form elements (default: False)
     * 
     * This function generates a form group, which uses a p for every element.
     * 
     * The group will be added to the form object!
     */
    public function addParagraphGroup(array $elements, $identifier = 'paragraph-group', $legend = Null, $label = False) {
        foreach ($elements as $element) {
            // get form element
            $e = $this->getElement($element);
            // set decorators
            if (!$label) {
                $e->setDecorators(array(
                    'ViewHelper',
                    'Errors',
                    array(
                        'HtmlTag',
                        array('tag' => 'p'),
                    ),
                    array(
                        'Description',
                        array('tag' => 'p', 'placement' => 'append', 'escape' => true)),
                ));
            } else {
                $e->setDecorators(array(
                    'ViewHelper',
                    'Errors',
                    array(
                        'HtmlTag',
                        array('tag' => 'p')
                    ),
                    array(
                        'Description',
                        array('tag' => 'p', 'placement' => 'append', 'escape' => true)),
                    array(
                        'Label',
                        array('tag' => 'p', 'escape' => false)),
                ));
            }
            // modify Error decorators retroactively
            $e->getDecorator('Errors')->setOptions(array(
                'class' => 'daiquiri-form-error unstyled'
            ));
        }
        $this->addDisplayGroup($elements, $identifier, array(
            'class' => 'daiquiri-form-paragraph-group',
            'decorators' => array(
                'FormElements',
                'Fieldset'
            )
        ));
        if ($legend) {
            $group = $this->getDisplayGroup($identifier);
            $group->setOptions(array('legend' => $legend));
        }
    }

    /**
     * @brief   Adds a form group to the form object, using the form-horizontal 
     *          of the bootstrap framework.
     * @param   array $elements     array of form element names
     * @param   string $identifier  name of the group (default: 'horizontal-group')
     * 
     * This function generates a form group, which utilizes the form-horizontal 
     * class of the bootstrap javascript framework.
     * 
     * The group will be added to the form object!
     */
    public function addHorizontalGroup(array $elements, $identifier = 'horizontal-group', $legend = Null) {
        foreach ($elements as $element) {
            // get form element
            $e = $this->getElement($element);

            // set decorators
            $e->setDecorators(array(
                'ViewHelper',
                'Errors',
                array(
                    'Description',
                    array('tag' => 'div', 'placement' => 'append', 'escape' => true)),
                array(
                    array('control-group' => 'HtmlTag'),
                    array('tag' => 'div', 'class' => 'controls')),
                array(
                    'Label',
                    array('escape' => false, 'class' => 'control-label')),
                array(
                    array('controls' => 'HtmlTag'),
                    array('tag' => 'div', 'class' => 'control-group'))
            ));
            // modify Error decorators retroactively
            $e->getDecorator('Errors')->setOptions(array(
                'class' => 'help-inline daiquiri-form-error unstyled',
            ));
        }
        $this->addDisplayGroup($elements, $identifier, array(
            'class' => 'daiquiri-form-horizontal-group form-horizontal',
            'decorators' => array(
                'FormElements',
                'Fieldset'
            )
        ));
        if ($legend) {
            $group = $this->getDisplayGroup($identifier);
            $group->setOptions(array('legend' => $legend));
        }
    }

    /**
     * @brief   Adds a form group to the form object, putting every element
     *          next to each other in a button or action group.
     * @param   array $elements     array of form element names
     * @param   string $identifier  name of the group (default: 'action-group')
     * @param   string $legend      legend for the fieldset
     * 
     * This function generates a form group putting every element next to each 
     * other in a button or action group, aligned with any horizontal groups.
     * 
     * The group will be added to the form object!
     */
    public function addActionGroup(array $elements, $identifier = 'action-group', $legend = Null) {
        foreach ($elements as $element) {
            $this->getElement($element)->setDecorators(array(
                'ViewHelper'
            ));
        }
        $this->addDisplayGroup($elements, $identifier, array(
            'class' => 'form-horizontal',
            'decorators' => array(
                'FormElements',
                array(
                    array('control-group' => 'HtmlTag'),
                    array('tag' => 'div', 'class' => 'controls')),
                array(
                    array('controls' => 'HtmlTag'),
                    array('tag' => 'div', 'class' => 'control-group')),
                'Fieldset'
            )
        ));
        if ($legend) {
            $group = $this->getDisplayGroup($identifier);
            $group->setOptions(array('legend' => $legend));
        }
    }

    /**
     * @brief   Adds a form group to the form object, putting the captcha in a div.
     * @param   string $element     captcha element name
     * @param   string $identifier  name of the group (default: 'captcha-group')
     * 
     * This function generates a form group which adds a captcha as a div.
     * 
     * The group will be added to the form object!
     */
    public function addCaptchaGroup($element, $identifier = 'captcha-group', $legend = Null) {
        $e = $this->getElement($element);
        $e->setDecorators(array(
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
        // modify Error decorators retroactively
        $e->getDecorator('Errors')->setOptions(array(
            'tag' => 'span',
            'class' => 'help-inline',
            'style' => 'list-style: none;'
        ));
        $this->addDisplayGroup(array($element), $identifier, array(
            'class' => 'form-horizontal',
            'decorators' => array(
                'FormElements',
                'Fieldset'
            )
        ));
        if ($legend) {
            $group = $this->getDisplayGroup($identifier);
            $group->setOptions(array('legend' => $legend));
        }
    }

    public function addViewScriptGroup(array $elements, $viewscript, $identifier = 'view-script-group', $legend = Null) {
        foreach ($elements as $element) {
            $this->getElement($element)->setDecorators(array(
                'ViewHelper'
            ));
        }
        $this->addDisplayGroup($elements, $identifier);

        $group = $this->getDisplayGroup($identifier);
        $group->setOptions(array(
            'decorators' => array(
                array('ViewScript', array(
                        'viewScript' => $viewscript,
                        'group' => $group
                )))
                )
        );

        if ($legend) {
            $group->setOptions(array('legend' => $legend));
        }
    }

    /**
     * @brief   Disables a given form field.
     * @param   string $field       name of the form field
     * 
     * Disables a given form field.
     * 
     * WARNING! Disabling form fields also results in their values not being 
     * submitted at all! We therefore set the field readonly.
     */
    public function setFieldReadonly($field) {
        $element = $this->getElement($field);
        $element->setAttrib('readonly', true);
        $class = $element->getAttrib('class');
        $element->setAttrib('class', $class . ' readonly');
    }

}
