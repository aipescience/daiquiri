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

class Query_Form_SqlQuery extends Query_Form_AbstractFormQuery {

    protected $_queue;
    public function getQuery() {
        return $this->getValue('sql_query');
    }

    public function setQuery($query) {
        $this->_query = $query;
    }

    public function getTablename() {
        return $this->getValue('sql_tablename');
    }

    public function getQueue() {
        $value = str_replace('sql_queue_', '', $this->getValue('sql_queue_value'));
        return $value;
    }

    public function getCsrf() {
        return $this->getElement('sql_csrf');
    }

    public function init() {
        $this->setAttrib('id', 'daiquiri-form-query-sql');
        $this->setFormDecorators();
        $this->addCsrfElement('sql_csrf');

        // add fields
        $this->addElement('textarea', 'sql_query', array(
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(3, 1024))
            ),
            'required' => true,
            'label' => 'Query:',
            'class' => 'span9 mono',
            'style' => "resize: none;"
        ));
        $this->addElement('text', 'sql_tablename', array(
            'filters' => array(
                'StringTrim',
                array('PregReplace', array('match' => '/ /', 'replace' => '_'))
            ),
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(0, 128)),
                array('validator' => 'Regex', 'options' => array('pattern' => '/^[^;@%*?()!"`\'&]+$/'))
            ),
            'label' => 'Name of the new table (optional):',
            'class' => 'span9'
        ));

        $this->addQueueElements('sql_queue_');
        $this->addPrimaryButtonElement('sql_submit', 'Submit new SQL Query');
        $this->addDumbButtonElement('sql_clear', 'Clear input window');

        $this->addParagraphGroup(array('sql_query'), 'sql-input-group');
        $this->addParagraphGroup(array('sql_tablename'), 'sql-table-group', false, true);
        $this->addQueueGroup('sql_queue_', 'sql-queue-group');
        $this->addInlineGroup(array('sql_submit', 'sql_clear'), 'sql-button-group');

        if (isset($this->_tablename)) {
            $this->setDefault('sql_tablename', $this->_tablename);
        }

        if (isset($this->_query)) {
            $this->setDefault('sql_query', $this->_query);
        }
    }

}
