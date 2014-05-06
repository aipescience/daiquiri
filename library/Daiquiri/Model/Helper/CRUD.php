<?php

/*  
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
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

class Daiquiri_Model_Helper_CRUD extends Daiquiri_Model_Helper_Abstract {

    public function index() {
        return array(
            'status' => 'ok',
            'rows' => $this->getResource()->fetchRows()
        );
    }

    public function show($id) {
        $row = $this->getResource()->fetchRow($id);
        if (empty($row)) {
            throw new Daiquiri_Exception_NotFound();
        }

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
                return $this->validationErrorResponse($form);
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

        // get entry from teh database
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Daiquiri_Exception_NotFound();
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
                return $this->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function delete($id, array $formParams = array(), $submit = null, $formclass = null) {
        if ($submit === null) {
            $submit = $this->_getSubject('Delete');
        }
        if ($formclass === null) {
            $formclass = 'Daiquiri_Form_Danger';
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
                return $this->validationErrorResponse($form);
            } 
        }

        return array('form' => $form, 'status' => 'form');
    }
 
    public function validationErrorResponse($form, $extraErrors = false) {
        // get validation errors from form
        $errors = $form->getMessages();

        // add description for extra error
        if ($extraErrors !== false) {
            if (!is_array($extraErrors)) {
                $extraErrors = array($extraErrors);
            }

            $form->setDescription(implode('; ',$extraErrors));
            $errors['form'] = $extraErrors;
        }

        // construct response array
        $response = array(
            'form' => $form,
            'status' => 'error',
            'errors' => $errors
        );

        // add and re-init csrf
        $csrf = $form->getCsrf();
        if (!empty($csrf)) {
            $csrf->initCsrfToken();
            $response['csrf'] = $csrf->getHash();
        }

        return $response;
    }

    private function _getSubject($action) {
        $class = get_class($this->getModel());
        $words = preg_split('/(?=[A-Z])/',lcfirst(substr($class, strrpos($class,'_') + 1,-1)));
        return ucfirst($action) . ' ' . strtolower(implode(' ',$words));
    }

    private function _getFormclass($formname = null) {
        $class = get_class($this->getModel());

        if ($formname === null) {
            $formname = substr($class, strrpos($class,'_') + 1);
        }

        $module = substr($class, 0, strpos($class,'_'));
        return $module . '_Form_' . ucfirst($formname);
    }
}