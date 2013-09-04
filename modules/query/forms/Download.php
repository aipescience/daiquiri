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

class Query_Form_Download extends Daiquiri_Form_Abstract {

    protected $_formats;

    public function setFormats(array $formats) {
        $this->_formats = $formats;
    }

    public function getCsrf() {
        return $this->getElement('download_csrf');
    }

    public function init() {
        $this->setFormDecorators();
        $this->addCsrfElement('download_csrf');
        
        $this->addElement('select', 'download_format', array(
            'required' => true,
            'label' => 'Select format:',
            'multiOptions' => $this->_formats,
            'decorators' => array('ViewHelper'),
            'class' => 'span4'
        ));
        $this->addPrimaryButtonElement('download_submit', 'Download');

        $this->addHorizontalGroup(array('download_format'));
        $this->addActionGroup(array('download_submit'));
    }

}
