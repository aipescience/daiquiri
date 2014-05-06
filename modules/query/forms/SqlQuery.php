<?php

/*
 *  Copyright (c) 2012-2014 Jochen S. Klar <jklar@aip.de>,
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
        $head = new Daiquiri_Form_Element_Note('head', array(
            'value' => "<h2>{$this->_formOptions['title']}</h2><p>{$this->_formOptions['help']}</p>"
        ));

        $this->addElement($head);
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

        $this->addParagraphGroup(array('head'), 'sql-head-group');
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
