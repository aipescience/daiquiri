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

class Daiquiri_Form_Element_Captcha extends Zend_Form_Element_Captcha {

    /**
     * @var string $helper
     * Default form view helper to use for rendering
     */
    public $helper = 'formNote';

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