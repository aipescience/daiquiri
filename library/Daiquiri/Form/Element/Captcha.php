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

class Daiquiri_Form_Element_Captcha extends Zend_Form_Element_Captcha {

    /**
     * @var string $helper
     * Default form view helper to use for rendering
     */
    public $helper = 'formNote';

    /**
     * Sets the default decorators needed for angular.
     */
    public function loadDefaultDecorators() {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            // the zend errors
            $this->addDecorator('Errors', array(
                'class' => 'unstyled text-error',
            ));

            // wrap in div.controls
            $this->addDecorator(array('controls' => 'HtmlTag'), array('tag' => 'div', 'class' => 'controls captcha'));

            // the label.control-label
            $this->addDecorator('Label', array('class' => 'control-label', 'escape' => false));

            // wrap in div.control-group
            $this->addDecorator(array('control-group' => 'HtmlTag'), array('tag' => 'div', 'class' => 'control-group'));
        }

        return $this;
    }

    function init() {
        $this->setOptions(array(
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
    }
}