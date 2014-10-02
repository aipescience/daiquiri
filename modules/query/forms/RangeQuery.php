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

class Query_Form_RangeQuery extends Query_Form_AbstractFormQuery {

    /**
     * Gets the SQL query contructed from the form fields.
     * @return string $sql
     */
    public function getQuery() {
        if (!isset($this->_formOptions['table'])) {
            throw new Exception('no table was specified');
        }

        $sql = "SELECT * FROM {$this->_formOptions['table']}";
        $sql .= $this->_quoteInto(" WHERE `x` >= ?", $this->getValue('range_xmin'));
        $sql .= $this->_quoteInto(" AND `x` <= ?", $this->getValue('range_xmax'));
        $sql .= $this->_quoteInto(" AND `y` >= ?", $this->getValue('range_ymin'));
        $sql .= $this->_quoteInto(" AND `y` <= ?", $this->getValue('range_ymax'));
        $sql .= $this->_quoteInto(" AND `z` >= ?", $this->getValue('range_zmin'));
        $sql .= $this->_quoteInto(" AND `z` <= ?", $this->getValue('range_zmax'));

        return $sql;
    }

    /**
     * Gets the content of the tablename field.
     * @return string $tablename
     */
    public function getTablename() {
        return $this->getValue('range_tablename');
    }

    /**
     * Gets the selected queue.
     * @return string $queue
     */
    public function getQueue() {
        return $this->getValue('range_queue');
    }

    /**
     * Initializes the form.
     */
    public function init() {
        // add form elements
        $this->addCsrfElement('range_csrf');
        $this->addHeadElement('range_head');
        $this->addElement('text', 'range_xmin', array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'x<sub>min</sub>',
            'class' => 'span2'
        ));
        $this->addElement('text', 'range_xmax', array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'x<sub>max</sub>',
            'class' => 'span2'
        ));
        $this->addElement('text', 'range_ymin', array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'y<sub>min</sub>',
            'class' => 'span2'
        ));
        $this->addElement('text', 'range_ymax', array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'y<sub>max</sub>',
            'class' => 'span2'
        ));
        $this->addElement('text', 'range_zmin', array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'z<sub>min</sub>',
            'class' => 'span2'
        ));
        $this->addElement('text', 'range_zmax', array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'z<sub>max</sub>',
            'class' => 'span2'
        ));
        $this->addElement(new Daiquiri_Form_Element_Tablename('range_tablename', array(
            'label' => 'Name of the new table (optional)',
            'class' => 'span9'
        )));
        $this->addPrimaryButtonElement('range_submit', 'Submit new Range Query');
        $this->addQueueElement('range_queue');

        // add display groups
        $this->addViewScriptGroup(array('range_xmin', 'range_xmax', 'range_ymin', 'range_ymax', 'range_zmin', 'range_zmax'), '_forms/range.phtml');
        $this->addParagraphGroup(array('range_tablename'), 'table-group', false, true);
        $this->addInlineGroup(array('range_submit','range_queue'), 'button-group');

        // fill elements with default values
        // todo
    }

}
