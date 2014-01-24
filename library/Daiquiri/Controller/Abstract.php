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
 * @class   Daiquiri_Controller_Abstract Abstract.php
 * @brief   Abstract class for daiquiri controllers
 * 
 * Abstract class for daiquiri controllers providing commonly used methods. This
 * class extends the default Zend Controller.
 * 
 */
abstract class Daiquiri_Controller_Abstract extends Zend_Controller_Action {

    public function setViewElements($response, $redirect = null) {
        if (!empty($redirect)) {
            $this->view->redirect = $redirect;
        }
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function setFormAction($response, $url) {
        if (array_key_exists('form', $response)) {
            $form = $response['form'];
            $action = Daiquiri_Config::getInstance()->getBaseUrl() . $url;
            $form->setAction($action);
        }
    }
}
