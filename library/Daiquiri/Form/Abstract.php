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
 * @class   Daiquiri_Form_Abstract Abstract.php
 * @brief   Abstract class for daiquiri form handling.
 * 
 * Abstract class for daiquiri form handling. This function provides a variety
 * of convenience functions when dealing with forms.
 * 
 */
abstract class Daiquiri_Form_Abstract extends Zend_Form {

    /**
     * Constructor.
     *
     * @param mixed $options
     * @return void
     */
    public function __construct($options = null)
    {
        if ($options === null) {
            $options = array();
        }
        if (empty($options['name'])) {
            $options['name'] = array_pop(explode('_',get_class($this))) . 'Form';
        }
        if (empty($options['ng-submit'])) {
            $options['ng-submit'] = "submitForm()";
        }
        parent::__construct($options);
    }

    /**
     * Sets the default decorators for the form.
     * @return Daiquiri_Form_Abstract the form object
     */
    public function loadDefaultDecorators() {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('FormElements');
            $this->addDecorator('Description', array(
                'tag' => 'div',
                'placement' => 'append',
                'class' => 'unstyled form-error text-error align-form-horizontal',
                'escape' => false
            ));
            $this->addDecorator('Form');
        }

        return $this;
    }

    /**
     * Adds a form element for a text field.
     * @param  string $name  name of the element
     * @param  array $options options for the element
     * @return string $name  name of the element
     */
    public function addTextElement($name, $options) {
        $this->addElement(new Daiquiri_Form_Element_Text($name, $options));
        return $name;
    }

    /**
     * Adds a form element for a textarea field.
     * @param  string $name  name of the element
     * @param  array $options options for the element
     * @return string $name  name of the element
     */
    public function addTextareaElement($name, $options) {
        $this->addElement(new Daiquiri_Form_Element_Textarea($name, $options));
        return $name;
    }

    /**
     * Adds a form element for a password field.
     * @param  string $name  name of the element
     * @param  array $options options for the element
     * @return string $name  name of the element
     */
    public function addPasswordElement($name, $options) {
        $this->addElement(new Daiquiri_Form_Element_Password($name, $options));
        return $name;
    }

    /**
     * Adds a form element for a select field.
     * @param  string $name  name of the element
     * @param  array $options options for the element
     * @return string $name  name of the element
     */
    public function addSelectElement($name, $options) {
        $this->addElement(new Daiquiri_Form_Element_Select($name, $options));
        return $name;
    }

    /**
     * Adds a form element for a multi select field.
     * @param  string $name  name of the element
     * @param  array $options options for the element
     * @return string $name  name of the element
     */
    public function addMultiselectElement($name, $options) {
        $this->addElement(new Daiquiri_Form_Element_Multiselect($name, $options));
        return $name;
    }

    /**
     * Adds a form element for a checkbox field.
     * @param  string $name  name of the element
     * @param  array $options options for the element
     * @return string $name  name of the element
     */
    public function addCheckboxElement($name, $options) {
        $this->addElement(new Daiquiri_Form_Element_Checkbox($name, $options));
        return $name;
    }

    /**
     * Adds a form element for a multi checkbox field.
     * @param  string $name  name of the element
     * @param  array $options options for the element
     * @return string $name  name of the element
     */
    public function addMultiCheckboxElement($name, $options) {
        $this->addElement(new Daiquiri_Form_Element_MultiCheckbox($name, $options));
        return $name;
    }

    /**
     * Adds a form element for a radio field.
     * @param  string $name  name of the element
     * @param  array $options options for the element
     * @return string $name  name of the element
     */
    public function addRadioElement($name, $options) {
        $this->addElement(new Daiquiri_Form_Element_Radio($name, $options));
        return $name;
    }

    /**
     * Adds a form element for a primary submit button.
     * @param  string $name  name of the element
     * @param  string $label label for the button
     * @return string $name  name of the element
     */
    public function addSubmitButtonElement($name, $label) {
        $this->addElement(new Daiquiri_Form_Element_SubmitButton($name, $label));
        return $name;
    }

    /**
     * Adds a form element for a very dangerous button.
     * @param  string $name  name of the element
     * @param  string $label label for the button
     * @return string $name  name of the element
     */
    public function addDangerButtonElement($name, $label) {
        $this->addElement(new Daiquiri_Form_Element_DangerButton($name, $label));
        return $name;
    }

    /**
     * Adds a form element for a cancel button (for angular).
     * @param  string $name  name of the element
     * @param  string $label label for the button
     * @return string $name  name of the element
     */
    public function addCancelButtonElement($name, $label) {
        $this->addElement(new Daiquiri_Form_Element_CancelButton($name, $label));
        return $name;
    }

    /**
     * Adds a form element for a secondary button.
     * @param  string $name  name of the element
     * @param  string $label label for the button
     * @return string $name  name of the element
     */
    public function addButtonElement($name, $label) {
        $this->addElement(new Daiquiri_Form_Element_Button($name, $label));
        return $name;
    }

    /**
     * Adds a form element for a button that looks like a link.
     * @param  string $name  name of the element
     * @param  string $label label for the button
     * @return string $name  name of the element
     */
    public function addLinkButtonElement($name, $label) {
        $this->addElement(new Daiquiri_Form_Element_LinkButton($name, $label));
        return $name;
    }

    /**
     * Adds a form element for a dumb button without submit.
     * @param  string $name  name of the element
     * @param  string $label label for the button
     * @return string $name  name of the element
     */
    public function addDumbButtonElement($name, $label) {
        $this->addElement(new Daiquiri_Form_Element_DumbButton($name, $label));
        return $name;
    }

    /**
     * Adds a singe toggle-button element. Needs additional grouping by 
     * Daiquiri_Form_DisplayGroup_ToggleButtons using addToggleButtonsGroup. 
     * @param string $name    name of the element
     * @param string $label   label for the element
     * @param string $tooltip tooltip for the toggle-button
     */
    public function addToggleButtonElement($name, $label, $tooltip = null) {
        $this->addElement(new Daiquiri_Form_Element_ToogleButton($name, array(
            'label' => $label,
            'tooltip' => $tooltip
        )));
        return $name;
    }

    /**
     * Add a hash element for security against CSRF attacks.
     * @param  string $name name of the element
     * @return mixed  $name name of the element or 'false'
     */
    public function addCsrfElement($name = 'csrf') {
        if (php_sapi_name() !== 'cli' && Daiquiri_Auth::getInstance()->useCsrf()) {
            $this->addElement(new Daiquiri_Form_Element_Csrf($name));
            return $name;
        } else {
            return false;
        }
    }

    /**
     * Adds a form element for the captcha.
     * @return  string $name name of the element
     */
    public function addCaptchaElement() {
        $this->addElement(new Daiquiri_Form_Element_Captcha('captcha'));
        return 'captcha';
    }

    /**
     * Adds a form group using an inlined div for every element and adiv for the group.
     * @param array $elements array of form element names
     * @param string $name    name of the group
     * @param string $legend  legend for the formgroup
     * @param bool $label     show labels of the form elements 
     */
    public function addInlineGroup(array $elements, $name = 'inline-group', $legend = Null, $label = False) {
        $this->addDisplayGroup($elements, $name, array(
            'displayGroupClass' => 'Daiquiri_Form_DisplayGroup_Inline',
            'legend' => $legend,
            'label' => $label
        ));
    }

    /**
     * Adds a form group using an inlined div for every element and right-floating div for the group.
     * @param array $elements array of form element names
     * @param string $name    name of the group
     * @param string $legend  legend for the formgroup
     * @param bool $label     show labels of the form elements 
     */
    public function addInlineRightGroup(array $elements, $name = 'inline-group', $legend = Null, $label = False) {
        $this->addDisplayGroup($elements, $name, array(
            'displayGroupClass' => 'Daiquiri_Form_DisplayGroup_InlineRight',
            'legend' => $legend,
            'label' => $label
        ));
    }

    /**
     * Adds a form group using a div for every element.
     * @param array $elements array of form element names
     * @param string $name    name of the group
     * @param string $legend  legend for the formgroup
     * @param bool $label     show labels of the form elements 
     */
    public function addDivGroup(array $elements, $name = 'div-group', $legend = Null, $label = False) {
        $this->addDisplayGroup($elements, $name, array(
            'displayGroupClass' => 'Daiquiri_Form_DisplayGroup_Div',
            'legend' => $legend,
            'label' => $label
        ));
    }

    /**
     * Adds a form group using a p for every element.
     * @param array $elements array of form element names
     * @param string $name    name of the group
     * @param string $legend  legend for the formgroup
     * @param bool $label     show labels of the form elements 
     */
    public function addParagraphGroup(array $elements, $name = 'paragraph-group', $legend = Null, $label = False) {
        $this->addDisplayGroup($elements, $name, array(
            'displayGroupClass' => 'Daiquiri_Form_DisplayGroup_Paragraph',
            'legend' => $legend,
            'label' => $label
        ));
    }

    /**
     * Adds a form group, using the form-horizontal of bootstrap.
     * @param array  $elements array of form element names
     * @param string $name     name of the group
     * @param string $legend   legend for the fieldset
     */
    public function addHorizontalGroup(array $elements, $name = 'horizontal-group', $legend = Null) {
        $this->addDisplayGroup($elements, $name, array(
            'displayGroupClass' => 'Daiquiri_Form_DisplayGroup_Horizontal',
            'legend' => $legend
        ));
    }

    /**
     * Adds a form group, putting every element next to each other in a button or action group.
     * @param array  $elements array of form element names
     * @param string $name     name of the group
     * @param string $legend   legend for the fieldset
     */
    public function addHorizontalButtonGroup(array $elements, $name = 'action-group', $legend = Null) {
        $this->addDisplayGroup($elements, $name, array(
            'displayGroupClass' => 'Daiquiri_Form_DisplayGroup_HorizontalButton',
            'legend' => $legend
        ));
    }

    /**
     * Adds a form group, putting the captcha in a div.
     * @param string $element captcha element name
     * @param string $name    name of the group
     * @param string $legend  legend for the fieldset
     */
    public function addHorizontalCaptchaGroup($element, $name = 'captcha-group', $legend = Null) {
        $this->addDisplayGroup(array($element), $name, array(
            'displayGroupClass' => 'Daiquiri_Form_DisplayGroup_HorizontalCaptcha',
            'legend' => $legend
        ));
    }

    /**
     * Adds a form group using a view script.
     * @param array  $elements   array of form element names
     * @param string $viewscript viewscrit to use with this group
     * @param string $name       name of the group
     * @param string $legend     legend for the fieldset
     */
    public function addViewScriptGroup(array $elements, $viewscript, $name = 'view-script-group', $legend = Null) {
        $this->addDisplayGroup($elements, $name, array(
            'displayGroupClass' => 'Daiquiri_Form_DisplayGroup_ViewScript',
            'viewScript' => $viewscript,
            'legend' => $legend
        ));
    }

    /**
     * Adds a form group for a set of toogle buttons.
     * @param array  $elements   array of form element names
     * @param string $name       name of the group
     */
    public function addToggleButtonsGroup(array $elements, $name = 'toogle-button-group', $prefix = 'toogle_buttons_') {
        $this->addDisplayGroup($elements, $name, array(
            'displayGroupClass' => 'Daiquiri_Form_DisplayGroup_ToggleButtons',
            'prefix' => $prefix
        ));
    }

    /**
     * Adds a toggle-button bar. Automagically decides whether to use a drop down instead.
     * @param string $prefix  prefix string for the identifiers of the buttons
     * @param array  $buttons array of buttons with key as name and array stating label and tooltip
     */
    public function addToggleButtonElements(array $buttons, $prefix) {
        // add a hidden field to the button group to get the selected value
        $this->addElement('hidden', $prefix . 'value', array(
            'decorators' => array('ViewHelper')
        ));

        // add buttons
        foreach ($buttons as $button) {
            $this->addToggleButtonElement($button['name'], $button['label'], $button['tooltip']);
        }
    }

    /**
     * Disables a given form field. Disabling form fields also results in their values not being 
     * submitted at all! We therefore set the field readonly.
     * @param string $name name of the form field
     */
    public function setFieldReadonly($name) {
        $element = $this->getElement($name);
        $element->setAttrib('readonly', true);
        $class = $element->getAttrib('class');
        $element->setAttrib('class', $class . ' readonly');
    }

}
