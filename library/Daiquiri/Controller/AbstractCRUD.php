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

abstract class Daiquiri_Controller_AbstractCRUD extends Daiquiri_Controller_Abstract {

    protected $_options = null;

    public function init() {
        $request = $this->getRequest();
        $module  = $request->module;
        $controller = $request->controller; 
        $name    = substr(str_replace('-', ' ', $controller), 0, -1);;

        $this->_options = array(
            'index' => array(
                'title' => ucfirst($controller),
                'url' => '/' . $module . '/' . $controller . '/'
            ),
            'show' => array(
                'title' => 'Show ' . $name,
                'url' => '/' . $module . '/' . $controller . '/show/'
            ),
            'create' => array(
                'title' => 'Create ' . $name,
                'url' => '/' . $module . '/' . $controller . '/create/'
            ),
            'update' => array(
                'title' => 'Update ' . $name,
                'url' => '/' . $module . '/' . $controller . '/update/'
            ),
            'delete' => array(
                'title' => 'Delete ' . $name,
                'url' => '/' . $module . '/' . $controller . '/delete/'
            )
        );
    }

    public function indexAction() {
        $response = $this->_model->index();
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }

        $this->view->options = $this->_options;
        $this->view->model = $this->_model->getClass();
    }

    public function showAction() {
        // get params
        $id = $this->_getParam('id'); 
        $redirect = $this->_getParam('redirect', $this->_options['index']['url']);

        // get the data from the model
        $response = $this->_model->show($id);

        // assign to view
        $this->view->redirect = $redirect;
        $this->view->title = $this->_options['show']['title'];
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function createAction() {
        // get params
        $redirect = $this->_getParam('redirect', $this->_options['index']['url']);

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and do stuff
                $response = $this->_model->create($this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->create();
        }

        // set action for form
        if ($this->_options['create']['url'] !== null && array_key_exists('form', $response)) {
            $form = $response['form'];
            $action = Daiquiri_Config::getInstance()->getBaseUrl() . $this->_options['create']['url'];
            $form->setAction($action);
        }

        // assign to view
        $this->view->redirect = $redirect;
        $this->view->title = $this->_options['create']['title'];
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function updateAction() {
        // get params
        $id = $this->_getParam('id'); 
        $redirect = $this->_getParam('redirect', $this->_options['index']['url']);

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and do stuff
                $response = $this->_model->update($id, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->update($id);
        }

        // set action for form
        if ($this->_options['update']['url'] !== null && array_key_exists('form',$response)) {
            $form = $response['form'];
            $action = Daiquiri_Config::getInstance()->getBaseUrl() . $this->_options['update']['url'] . '?id=' . $id;
            $form->setAction($action);
        }

        // assign to view
        $this->view->redirect = $redirect;
        $this->view->title = $this->_options['update']['title'];
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function deleteAction() {
        // get params
        $id = $this->_getParam('id');
        $redirect = $this->_getParam('redirect', $this->_options['index']['url']);

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and do stuff
                $response = $this->_model->delete($id, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $this->_model->delete($id);
        }

        // set action for form
        if ($this->_options['delete']['url'] !== null && array_key_exists('form',$response)) {
            $form = $response['form'];
            $action = Daiquiri_Config::getInstance()->getBaseUrl() . $this->_options['delete']['url'] . '?id=' . $id;
            $form->setAction($action);
        }

        // assign to view
        $this->view->redirect = $redirect;
        $this->view->title = $this->_options['delete']['title'];
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

}
