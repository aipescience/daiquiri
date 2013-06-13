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

class Query_Form_ConeQuery extends Query_Form_AbstractFormQuery {

    public function getQuery() {
        if (!isset($this->_formOptions['table'])) {
            throw new Exception('no table was specified');
        }

        $sql = "SELECT * FROM {$this->_formOptions['table']}";
        $sql .= $this->_quoteInto(" WHERE `ra` >= ?", $this->getValue('cone_ramin'));
        $sql .= $this->_quoteInto(" AND `ra` <= ?", $this->getValue('cone_ramax'));
        $sql .= $this->_quoteInto(" AND `de` >= ?", $this->getValue('cone_demin'));
        $sql .= $this->_quoteInto(" AND `de` <= ?", $this->getValue('cone_demax'));

        return $sql;
    }

    public function getTablename() {
        return $this->getValue('cone_tablename');
    }

    public function getQueue() {
        return $this->getValue('cone_queue');
    }

    public function init() {

        $this->setAttrib('id', 'daiquiri-form-cone-query');

        $this->addElement('text', 'cone_ramin', array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'RA<sub>min</sub>',
            'class' => 'span2'
        ));
        $this->addElement('text', 'cone_ramax', array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'RA<sub>max</sub>',
            'class' => 'span2'
        ));
        $this->addElement('text', 'cone_demin', array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'DEC<sub>min</sub>',
            'class' => 'span2'
        ));
        $this->addElement('text', 'cone_demax', array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'DEC<sub>max</sub>',
            'class' => 'span2'
        ));
        $this->addElement('text', 'cone_tablename', array(
            'filters' => array(
                'StringTrim',
                array('PregReplace', array('match' => '/ /', 'replace' => '_'))
            ),
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(0, 128)),
                array('validator' => 'Regex', 'options' => array('pattern' => '/^[^;@%*?()!"`\'&]+$/'))
            ),
            'label' => 'Name of the new table',
            'class' => 'span9'
        ));

        // add fields
        $this->addPrimaryButtonElement('cone_submit', 'Submit new cone search');

        // add groups
        $this->addViewScriptGroup(array('cone_ramin', 'cone_ramax', 'cone_demin', 'cone_demax'), '_forms/cone.phtml');
        $this->addParagraphGroup(array('cone_tablename'), 'table-group', false, true);
        $this->addInlineGroup(array('cone_submit'), 'button-group');

        if (isset($this->_tablename)) {
            $this->setDefault('cone_tablename', $this->_tablename);
        }

        $this->setDefault('cone_ramin', '0');
        $this->setDefault('cone_ramax', '360');
        $this->setDefault('cone_demin', '-90');
        $this->setDefault('cone_demax', '90');
    }

}
