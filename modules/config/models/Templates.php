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

class Config_Model_Templates extends Daiquiri_Model_SimpleTable {

    public $templates = array(
        'auth.register' => array('firstname', 'lastname', 'username', 'link'),
        'auth.forgotPassword' => array('firstname', 'lastname', 'username', 'link'),
        'auth.resetPassword' => array('firstname', 'lastname', 'username', 'link'),
        'auth.validate' => array('firstname', 'lastname', 'username', 'link'),
        'auth.confirm' => array('firstname', 'lastname', 'username', 'manager'),
        'auth.reject' => array('firstname', 'lastname', 'username', 'manager'),
        'auth.activate' => array('firstname', 'lastname', 'username'),
        'auth.reenable' => array('firstname', 'lastname', 'username'),
        'contact.submit_user' => array('firstname', 'lastname', 'username'),
        'contact.submit_support' => array('firstname', 'lastname', 'username', 'email',
            'category', 'subject', 'message', 'link'),
        'contact.respond' => array('firstname', 'lastname', 'username', 'subject'),
        'query.plan' => array('firstname', 'lastname', 'email', 'sql', 'plan', 'message')
    );

    public function __construct() {
        $this->setResource('Config_Model_Resource_Templates');
        $this->setValueField('subject');
    }

    public function index() {
        return $this->getResource()->fetchRows();
    }

    public function show($template, array $values = array()) {
        $data = $this->getResource()->fetchRow($template);

        foreach ($this->templates[$template] as $key) {
            if (!empty($values[$key])) {
                $data['subject'] = str_replace('_' . $key . '_', $values[$key], $data['subject']);
                $data['body'] = str_replace('_' . $key . '_', $values[$key], $data['body']);
            }
        }

        return $data;
    }

    public function create(array $formParams = array()) {

        // create the form object
        $form = new Config_Form_CreateTemplates();

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            // get the form values
            $values = $form->getValues();

            $this->getResource()->insertRow($values);
            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function update($template, array $formParams = array()) {
        $resource = $this->getResource();

        // produce variables string
        $tmp = array();
        foreach ($this->templates[$template] as $value) {
            $tmp[] = '_' . $value . '_';
        }
        $variables = implode(' ', $tmp);

        // create the form object
        $form = new Config_Form_EditTemplates(array(
                    'template' => $resource->fetchRow($template)
                ));

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            // get the form values
            $values = $form->getValues();

            $resource->updateRow($template, $values);
            return array('status' => 'ok');
        }

        return array(
            'form' => $form,
            'status' => 'form',
            'variables' => $variables,
            'template' => $template
        );
    }

    public function delete($id, array $formParams = array()) {
        // create the form object
        $form = new Config_Form_DeleteTemplates();

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            $this->getResource()->deleteRow($id);
            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

}
