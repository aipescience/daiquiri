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

abstract class Daiquiri_Form_Element_Abstract {

    /**
     * Constructor. Sets the angular model attribute.
     */
    static public function addAngularOptions($name,$options) {
        if (empty($options['ng-model'])) {
            $options['ng-model'] = "values.{$name}";
        }
        return $options;
    }

    /**
     * Sets the default decorators needed for angular.
     */
    public function addAngularDecorators($element) {
        // the element itself
        $element->addDecorator('ViewHelper');

        // the zend errors
        $element->addDecorator('Errors', array(
            'class' => 'unstyled text-error help-inline',
        ));

        // the angular errors
        $element->addDecorator('Callback', array(
            'callback' => function($content, $element, $options) {
                    $ngErrorModel = 'errors.' . $element->getName();

                    return '<ul class="unstyled text-error help-inline angular" ng-show="' . $ngErrorModel . '"><li ng-repeat="error in ' . $ngErrorModel . '">{{error}}</li></ul>';
                },
            'placement' => 'append'
        ));
        
        // wrap in div.controls
        $element->addDecorator(array('controls' => 'HtmlTag'), array('tag' => 'div', 'class' => 'controls'));

        // the label.control-label
        $element->addDecorator('Label', array('class' => 'control-label'));

        // wrap in div.control-group
        $element->addDecorator(array('control-group' => 'HtmlTag'), array('tag' => 'div', 'class' => 'control-group'));
    }
}