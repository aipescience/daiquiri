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

class Query_Form_ConeQuery extends Query_Form_AbstractFormQuery {

    /**
     * Gets the SQL query contructed from the form fields.
     * @return string $sql
     */
    public function getQuery() {
        if (!isset($this->_formOptions['table'])) {
            throw new Exception('no table was specified');
        }
        if (!isset($this->_formOptions['raField'])) {
            throw new Exception('no ra field was specified');
        }
        if (!isset($this->_formOptions['decField'])) {
            throw new Exception('no dec field was specified');
        }

        $ra     = $this->_escape($this->getValue('cone_ra'));
        $dec    = $this->_escape($this->getValue('cone_dec'));
        $radius = $this->_escape($this->getValue('cone_radius'));

        $sql = "SELECT angdist({$ra},{$dec},`{$this->_formOptions['raField']}`,`{$this->_formOptions['decField']}`)";
        $sql .= " * 3600.0 AS distance_arcsec, s.* FROM {$this->_formOptions['table']} AS s";
        $sql .= " WHERE angdist({$ra},{$dec},`{$this->_formOptions['raField']}`,`{$this->_formOptions['decField']}`)";
        $sql .= " < {$radius} / 3600.0;";

        return $sql;
    }

    /**
     * Gets the content of the tablename field.
     * @return string $tablename
     */
    public function getTablename() {
        return $this->getValue('cone_tablename');
    }

    /**
     * Gets the selected queue.
     * @return string $queue
     */
    public function getQueue() {
        return $this->getValue('cone_queue');
    }

    /**
     * Initializes the form.
     */
    public function init() {
        // add form elements
        $this->addCsrfElement('cone_csrf');
        $this->addHeadElement('cone_head');
        $this->addElement('text','cone_ra', array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'RA<sub>deg</sub>'
        ));
        $this->addElement('text','cone_dec', array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'DEC<sub>deg</sub>'
        ));
        $this->addElement('text','cone_radius', array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'Radius<sub>arcsec</sub>'
        ));
        $this->addElement(new Daiquiri_Form_Element_Tablename('cone_tablename', array(
            'label' => 'Name of the new table (optional)',
            'class' => 'span9'
        )));
        $this->addPrimaryButtonElement('cone_submit', 'Submit new cone search');
        $this->addQueuesElement('cone_queues');

        // add display groups
        $this->addParagraphGroup(array('cone_head'),'cone_head-group');
        $this->addHorizontalGroup(array('cone_ra','cone_dec','cone_radius'), 'cone_values-group');
        $this->addParagraphGroup(array('cone_tablename'), 'cone_table-group', false, true);
        $this->addInlineGroup(array('cone_submit','cone_queues'), 'cone_button-group');

        // fill elements with default values
        $this->setDefault('cone_ra', $this->_formOptions['raDefault']);
        $this->setDefault('cone_dec', $this->_formOptions['decDefault']);
        $this->setDefault('cone_radius', $this->_formOptions['radiusDefault']);
    }

}
