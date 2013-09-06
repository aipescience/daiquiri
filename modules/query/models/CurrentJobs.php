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

class Query_Model_CurrentJobs extends Daiquiri_Model_Abstract {

    protected $_user;

    /**
     * Constructor. Sets resource object and primary field.
     */
    public function __construct() {
        $this->_userId = Daiquiri_Auth::getInstance()->getCurrentId();
        $this->_username = Daiquiri_Auth::getInstance()->getCurrentUsername();
        $resource = Query_Model_Resource_AbstractQueue::factory(Daiquiri_Config::getInstance()->query->queue->type);
        $this->setResource(get_class($resource));
    }

    public function index() {
        $sqloptions = $this->getResource()->getSqloptionsForIndex($this->_userId);
        $rows = $this->getResource()->fetchRows($sqloptions);
        return $rows;
    }

    public function show($id) {
        $row = $this->getResource()->fetchRow($id);

        if (empty($row) || $row['user_id'] !== $this->_userId) {
            throw new Daiquiri_Exception_AuthError();
        }

        // fetch table statistics
        $stat = $this->getResource()->fetchTableStats($id);

        // create return array
        $data = array();
        $translations = $this->getResource()->getTranslations();
        foreach (array_merge($row, $stat) as $key => $value) {
            $data[$key] = array(
                'key' => $key,
                'name' => $translations[$key],
                'value' => $value
            );
        }

        // add username
        $data['username'] = array(
            'key' => 'username',
            'name' => 'Username',
            'value' => $this->_username
        );

        return $data;
    }

    public function kill($id, array $formParams = array()) {
        // create the form object
        $form = new Query_Form_KillJob();

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            $row = $this->getResource()->fetchRow($id);

            if ($row['user_id'] !== $this->_userId) {
                throw new Daiquiri_Exception_AuthError();
            } else {
                $this->getResource()->killJob($id);
                return array('status' => 'ok');
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function remove($id, array $formParams = array()) {
        // create the form object
        $form = new Query_Form_RemoveJob();

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            $row = $this->getResource()->fetchRow($id);
            if ($row['user_id'] !== $this->_userId) {
                throw new Daiquiri_Exception_AuthError();
            } else {
                $this->getResource()->removeJob($id);
                return array('status' => 'ok');
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function rename($id, array $formParams = array()) {
        // get the job
        $row = $this->getResource()->fetchRow($id);

        // create the form object
        $form = new Query_Form_RenameJob(array(
            'tablename' => $row['table']
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // reinit csrf
                $csrf = $form->getElement('csrf');
                $csrf->initCsrfToken();

                $row = $this->getResource()->fetchRow($id);
                if ($row['user_id'] !== $this->_userId) {
                    throw new Daiquiri_Exception_AuthError();
                } else {
                    $data = $form->getValues();

                    // check if table was not renamed at all
                    if($row['table'] !== $data['tablename']) {
                        $error = $this->getResource()->renameJob($id, $data['tablename']);

                        //adding error to form error stack
                        $form->getElement('tablename')->addError($error);
                        if ($error !== 'ok') {
                            return array(
                                'status' => 'error',
                                'errors' => $form->getMessages(),
                                'form' => $form,
                                'csrf' => $csrf->getHash()
                                );
                        }
                    }

                    return array('status' => 'ok');
                }
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages(),
                    'form' => $form,
                    'csrf' => $form->getElement('csrf')->getHash()
                    );
            }
        }

        return array(
            'form' => $form,
            'status' => 'form'
            );
    }

}
