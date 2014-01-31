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

class Daiquiri_Model_Helper_CRUD extends Daiquiri_Model_Helper_Abstract {

    public function index() {
        return array(
            'status' => 'ok',
            'rows' => $this->getResource()->fetchRows()
        );
    }

    public function show($id) {
        return array(
            'status' => 'ok',
            'row' => $this->getResource()->fetchRow($id)
        );
    }

    public function create(array $formParams = array(), $submit = null, $formclass = null) {
        if ($submit === null) {
            $submit = $this->_getSubject('Create');
        }
        if ($formclass === null) {
            $formclass = $this->_getFormclass();
        }

        // create the form object
        $form = new $formclass(array(
            'submit'=> $submit
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {

                // get the form values
                $values = $form->getValues();

                // store the values in the database
                $this->getResource()->insertRow($values);

                return array('status' => 'ok');
            } else {
                return array(
                    'status' => 'error',
                    'form' => $form,
                    'errors' => $form->getMessages()
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function update($id, array $formParams = array(), $submit = null, $formclass = null) {
        if ($submit === null) {
            $submit = $this->_getSubject('Update');
        }
        if ($formclass === null) {
            $formclass = $this->_getFormclass();
        }

        // get meeting from teh database
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Exception('$id ' . $id . ' not found.');
        }

        // create the form object
        $form = new $formclass(array(
            'submit'=> $submit,
            'entry' => $entry
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // store the values in the database
                $this->getResource()->updateRow($id, $values);

                return array('status' => 'ok');
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages(),
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function delete($id, array $formParams = array(), $submit = null, $formclass = null) {
        if ($submit === null) {
            $submit = $this->_getSubject('Delete');
        }
        if ($formclass === null) {
            $formclass = $this->_getFormclass('Delete');
        }

        // get meeting from the database
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Exception('$id ' . $id . ' not found.');
        }

        // create the form object
        $form = new $formclass(array(
            'submit'=> $submit
        ));

        // valiadate the form if POST
        if (!empty($formParams)){

            if ($form->isValid($formParams)) {
                $this->getResource()->deleteRow($id);
                return array('status' => 'ok');
            } else {
                return array(
                    'status' => 'error',
                    'errors' => $form->getMessages(),
                );
            } 
        }

        return array('form' => $form, 'status' => 'form');
    }
 
    private function _getSubject($action) {
        $class = get_class($this->getModel());
        $words = preg_split('/(?=[A-Z])/',lcfirst(substr($class, strrpos($class,'_') + 1,-1)));
        return ucfirst($action) . ' ' . strtolower(implode(' ',$words));
    }

    private function _getFormclass($formname = null) {
        $class = get_class($this->getModel());

        if ($formname === null) {
            $formname = substr($class, strrpos($class,'_') + 1,-1);
        }

        $module = substr($class, 0, strpos($class,'_'));
        return $module . '_Form_' . ucfirst($formname);
    }
}