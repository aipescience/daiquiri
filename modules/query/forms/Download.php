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

    public function init() {
        parent::init();

        $this->addElement('select', 'format', array(
            'required' => true,
            'label' => 'Select format:',
            'multiOptions' => $this->_formats,
            'decorators' => array('ViewHelper'),
            'class' => 'span4'
        ));
        $this->addPrimaryButtonElement('submit-download', 'Download');

        $this->addHorizontalGroup(array('format'));
        $this->addActionGroup(array('submit-download'));
    }

}
