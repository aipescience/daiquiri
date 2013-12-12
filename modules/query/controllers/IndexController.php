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

class Query_IndexController extends Daiquiri_Controller_Abstract {

    public function init() {
        // check acl
        if (Daiquiri_Auth::getInstance()->checkAcl('Query_Model_Form', 'submit')) {
            $this->view->status = 'ok';
        } else {
            throw new Daiquiri_Exception_AuthError();
        }
    }

    public function indexAction() {
        $this->view->status = 'ok';

        $messagesModel = new Config_Model_Messages();
        $this->view->message = $messagesModel->show('query');

        // get the forms to display
        if (Daiquiri_Config::getInstance()->query->forms) {
            $this->view->forms = Daiquiri_Config::getInstance()->query->forms->toArray();
        } else {
            $this->view->forms = array();
        }
    }

    public function formAction() {
        // get form from request default is sql
        $form = $this->_getParam('form', 'sql');

        // get the model
        $model = Daiquiri_Proxy::factory('Query_Model_Form');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $response = array('form' => null, 'status' => 'cancel');
            } else {
                // run the query
                $response = $model->submit($form, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $model->submit($form);
        }

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }

        // set the action of the form
        if (isset($this->view->form)) {
            $baseurl = $this->getRequest()->getBaseUrl();
            $this->view->form->setAction($baseurl . '/query/index/form?form=' . $form);
        }

        // render a different view script if set
        if (isset($response['formOptions']['view'])) {
            $this->view->setScriptPath(dirname($response['formOptions']['view']));
            $this->renderScript(basename($response['formOptions']['view']));
        }
    }

    public function planAction() {
        // get form from request default is sql
        $form = $this->_getParam('form', 'sql');

        // get the model
        $model = Daiquiri_Proxy::factory('Query_Model_Form');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('plan_cancel')) {
                $baseurl = $this->getRequest()->getBaseUrl();
                // user clicked cancel
                $this->_redirect($baseurl . '/query/index/form?form=' . $form);
            } else {
                // call the plan
                $response = $model->plan($this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $model->plan();
        }

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }

        // set the action of the form
        if (isset($this->view->form)) {
            $baseurl = $this->getRequest()->getBaseUrl();
            $this->view->form->setAction($baseurl . '/query/index/plan');
        }
    }

    public function mailAction() {
        // get the model
        $model = Daiquiri_Proxy::factory('Query_Model_Form');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('mail_cancel')) {
                $baseurl = $this->getRequest()->getBaseUrl();
                $this->_redirect($baseurl . '/query/index/form?form=' . $form);
            } else {
                $response = $model->mail($this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $model->mail();
        }

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }

        // set the action of the form
        if (isset($this->view->form)) {
            $baseurl = $this->getRequest()->getBaseUrl();
            $this->view->form->setAction($baseurl . '/query/index/mail');
        }
    }

    public function downloadAction() {
        // get the model
        $model = Daiquiri_Proxy::factory('Query_Model_Database');

        // get parameters from request
        $table = $this->_getParam('table');

        // check if POST or GET
        if ($this->_request->isPost()) {
            // validate form and create download option selector
            $response = $model->download($table, $this->_request->getPost());
        } else {
            // just display the form
            $response = $model->download($table);
        }

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function streamAction() {
        // get the model
        $model = Daiquiri_Proxy::factory('Query_Model_Database');

        // get parameters from request
        $table = $this->_getParam('table');
        $format = $this->_getParam('format');

        //Zend stuff is killed inside $model->stream so that errors can still
        //be shown
        //stream
        $response = $model->stream($table, $format);

        //end here before zend does something else...
        if ($response['status'] === "ok") {
            exit();
        } else {
            $this->view->status = $response['status'];
            $this->view->error = $response['error'];
        }
    }

    public function fileAction() {
        // get the model
        $model = Daiquiri_Proxy::factory('Query_Model_Database');

        // get parameters from request
        $table = $this->_getParam('table');
        $format = $this->_getParam('format');

        $response = $model->file($table, $format);

        //getting rid of all the output buffering in Zend
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $controller = Zend_Controller_Front::getInstance();
        $controller->getDispatcher()->setParam('disableOutputBuffering', true);

        ob_end_clean();

        http_send_content_disposition($response['filename']);
        http_send_content_type($response['mime']);
        http_send_file($response['file']);
    }

    public function regenAction() {
        // get the model
        $model = Daiquiri_Proxy::factory('Query_Model_Database');

        // get parameters from request
        $table = $this->_getParam('table');
        $format = $this->_getParam('format');

        $response = $model->regen($table, $format);

        // assign to view
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function listJobsAction() {
        // get the model
        $model = Daiquiri_Proxy::factory('Query_Model_CurrentJobs');
        
        $this->view->data = $model->index();
        $this->view->status = 'ok';
    }

    public function showJobAction() {
        // get the model
        $model = Daiquiri_Proxy::factory('Query_Model_CurrentJobs');

        // get parameters from request
        $id = $this->_getParam('id');
        $redirect = $this->_getParam('redirect', '/query/');

        $this->view->redirect = $redirect;
        $this->view->data = $model->show($id);
        $this->view->status = 'ok';
    }

    public function renameJobAction() {
        // get the model
        $model = Daiquiri_Proxy::factory('Query_Model_CurrentJobs');

        // get parameters from request
        $id = $this->_getParam('id');
        $redirect = $this->_getParam('redirect', '/query/');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and rename table
                $response = $model->rename($id, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $model->rename($id);
        }

        // set action for form
        if (array_key_exists('form',$response)) {
            $form = $response['form'];
            $form->setAction(Daiquiri_Config::getInstance()->getBaseUrl() . '/query/index/rename-job?id=' . $id);
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function removeJobAction() {
        // get the model
        $model = Daiquiri_Proxy::factory('Query_Model_CurrentJobs');

        // get parameters from request
        $id = $this->_getParam('id');
        $redirect = $this->_getParam('redirect', '/query/');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and delete user
                $response = $model->remove($id, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $model->remove($id);
        }

        // set action for form
        if (array_key_exists('form',$response)) {
            $form = $response['form'];
            $form->setAction(Daiquiri_Config::getInstance()->getBaseUrl() . '/query/index/remove-job?id=' . $id);
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function killJobAction() {
        // get the model
        $model = Daiquiri_Proxy::factory('Query_Model_CurrentJobs');

        // get parameters from request
        $id = $this->_getParam('id');
        $redirect = $this->_getParam('redirect', '/query/');

        // check if POST or GET
        if ($this->_request->isPost()) {
            if ($this->_getParam('cancel')) {
                // user clicked cancel
                $this->_redirect($redirect);
            } else {
                // validate form and delete user
                $response = $model->kill($id, $this->_request->getPost());
            }
        } else {
            // just display the form
            $response = $model->kill($id);
        }

        // set action for form
        if (array_key_exists('form',$response)) {
            $form = $response['form'];
            $form->setAction(Daiquiri_Config::getInstance()->getBaseUrl() . '/query/index/kill-job?id=' . $id);
        }

        // assign to view
        $this->view->redirect = $redirect;
        foreach ($response as $key => $value) {
            $this->view->$key = $value;
        }
    }

    public function databaseAction() {
        $model = Daiquiri_Proxy::factory('Query_Model_Database');
        $databases = $model->index();

        $this->view->databases = $databases;
        $this->view->status    = 'ok';
    }

    public function exampleQueriesAction() {
        $model = Daiquiri_Proxy::factory('Query_Model_Examples');
        $this->view->examples = $model->index();
        $this->view->status = 'ok';
    }

}
