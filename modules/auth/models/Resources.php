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

class Auth_Model_Resources extends Daiquiri_Model_Abstract {

    /**
     * Constructor. Sets resource object and tablename.
     */
    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Table');
        $this->getResource()->setTablename('Auth_Resources');
    }

    /**
     * Creates resource entry.
     * @param array $formParams
     * @return array $response
     */
    public function create(array $formParams = array()) {
        // create the form object
        $form = new Auth_Form_Resources();

        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // check if the entry is already there
                $rows = $this->getResource()->fetchRows(array(
                    'where' => array('`resource` = ?' => $values['resource'])
                ));

                if (empty($rows)) {
                    // insert the row
                    $id = $this->getResource()->insertRow($values);
                    return array('status' => 'ok');
                } else {
                    $form->setDescription('Key already stored');
                    return $this->getModelHelper('CRUD')->validationErrorResponse($form);
                }

                $rows = $this->getResource()->fetchRows(array(
                    'where' => array('`key` = ?' => $values['key'])
                ));

            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }
        return array('form' => $form, 'status' => 'form');
    }

}
