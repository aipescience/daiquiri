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

abstract class Daiquiri_Form_Element_Abstract {

    /**
     * Sets the default decorators needed for angular.
     */
    public function loadDaiquiriDecorators($element) {

        if ($element->loadDefaultDecoratorsIsDisabled()) {
            return $element;
        }

        $decorators = $element->getDecorators();
        if (empty($decorators)) {
            // the element itself
            $element->addDecorator('ViewHelper');

            // the hint
            $element->addDecorator('Callback', array(
                'callback' => function($content, $element, $options) {
                    $hint = $element->getHint();
                    if (empty($hint)) {
                        return "";
                    } else {
                        return "<div class=\"form-hint\">{$hint}</div>";
                    }
                },
                'placement' => 'append'
            ));

            // the Zend errors
            $element->addDecorator('Errors', array(
                'class' => 'unstyled text-error',
            ));

            // wrap in div.controls
            $element->addDecorator(array('controls' => 'HtmlTag'), array('tag' => 'div', 'class' => 'controls'));

            // the label.control-label
            $element->addDecorator('Label', array('class' => 'control-label'));

            // wrap in div.control-group
            $element->addDecorator(array('control-group' => 'HtmlTag'), array('tag' => 'div', 'class' => 'control-group'));

            // enable html for label
            $element->getDecorator('Label')->setOption('escape', false);
        }

        return $element;
    }
}