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

class Query_FormController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Query_Model_Form');
    }

    public function indexAction() {
        // get form from request default is sql
        $form = $this->_getParam('form', 'sql');
        $this->getControllerHelper('form')->submit($form);

        // overide form action
        if (isset($this->view->form)) {
           $this->view->form->setAction(Daiquiri_Config::getInstance()->getBaseUrl() . '/query/form/?form=' . $form);
        }
           
        // render a different view script if set
        $viewScript = Daiquiri_Config::getInstance()->query->forms->$form->view;
        if ($viewScript) {
            $this->view->setScriptPath(dirname($viewScript));
            $this->renderScript(basename($viewScript));
        }
    }

    public function planAction() {
        // get form from request default is sql
        $form = $this->_getParam('form', 'sql');
        $mail = $this->_getParam('mail');
        $this->getControllerHelper('form')->plan($mail);
    }

    public function mailAction() {
        $this->getControllerHelper('form')->mail();
    }

}
