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

class Daiquiri_Form_Element_Password extends Zend_Form_Element_Password {

    /**
     * Contructor.
     * @param string $name    name of the element
     * @param mixed  $options options for the element
     */
    public function __construct($name, $options = array()) {
        if (empty($options['ng-model'])) {
            $options['ng-model'] = "values.{$name}";
        }
        parent::__construct($name,$options);
    }

    /**
     * Load default decorators
     *
     * @return Zend_Form_Element
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            // the element itself
            $this->addDecorator('ViewHelper');

            // the zend errors
            $this->addDecorator('Errors', array(
                'class' => 'unstyled text-error help-inline',
            ));

            // the angular errors
            $this->addDecorator('Callback', array(
                'callback' => function($content, $element, $options) {
                        $ngErrorModel = 'errors.' . $element->getName();

                        return '<ul class="unstyled text-error help-inline angular" ng-show="' . $ngErrorModel . '"><li ng-repeat="error in ' . $ngErrorModel . '">{{error}}</li></ul>';
                    },
                'placement' => 'append'
            ));
            
            // wrap in div.controls
            $this->addDecorator(array('controls' => 'HtmlTag'), array('tag' => 'div', 'class' => 'controls'));

            // the label.control-label
            $this->addDecorator('Label', array('class' => 'control-label'));

            // wrap in div.control-group
            $this->addDecorator(array('control-group' => 'HtmlTag'), array('tag' => 'div', 'class' => 'control-group'));
        }

        return $this;
    }
}