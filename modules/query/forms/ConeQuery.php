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
	if (!isset($this->_formOptions['ra'])) {
            throw new Exception('no ra field was specified');
        }
	if (!isset($this->_formOptions['dec'])) {
            throw new Exception('no dec field was specified');
        }

	$ra     = $this->_escape($this->getValue($this->getFieldId('ra')));
	$dec    = $this->_escape($this->getValue($this->getFieldId('dec')));
	$radius = $this->_escape($this->getValue($this->getFieldId('radius')));

	$sql = "SELECT angdist({$ra},{$dec},`{$this->_formOptions['ra']}`,`{$this->_formOptions['dec']}`)";
	$sql .= " * 3600.0 AS distance_arcsec, s.* FROM {$this->_formOptions['table']} AS s";
	$sql .= " WHERE angdist({$ra},{$dec},`{$this->_formOptions['ra']}`,`{$this->_formOptions['dec']}`)";
	$sql .= " < {$radius} / 3600.0;";
        return $sql;
    }

    public function getTablename() {
        return $this->getValue($this->getFieldId('tablename'));
    }

    public function getQueue() {
        return $this->getValue($this->getFieldId('queue'));
    }

    public function getCsrf() {
        return $this->getElement($this->getFieldId('csrf'));
    }

    public function init() {
        $this->setAttrib('id', 'daiquiri-form-query-cone');
        $this->setFormDecorators();
        $this->addCsrfElement($this->getFieldId('csrf'));

        // add fields
        $this->addElement('text', $this->getFieldId('ra'), array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'RA'
        ));
        $this->addElement('text', $this->getFieldId('dec'), array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'DEC'
        ));
        $this->addElement('text', $this->getFieldId('radius'), array(
            'filters' => array('StringTrim'),
            'required' => true,
            'validators' => array(
                array('validator' => 'float')
            ),
            'label' => 'Radius'
        ));
        $this->addElement('text', $this->getFieldId('tablename'), array(
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
        $this->addPrimaryButtonElement($this->getFieldId('submit'), 'Submit new cone search');

        // add groups
        $this->addHorizontalGroup(array($this->getFieldId('ra'), $this->getFieldId('dec'), $this->getFieldId('radius')));
        $this->addParagraphGroup(array($this->getFieldId('tablename')), 'table-group', false, true);
        $this->addInlineGroup(array($this->getFieldId('submit')), 'button-group');

        if (isset($this->_tablename)) {
            $this->setDefault($this->getFieldId('tablename'), $this->_tablename);
        }

	$this->setDefault($this->getFieldId('ra'), '320.0');
        $this->setDefault($this->getFieldId('dec'), '16.0');
        $this->setDefault($this->getFieldId('radius'), '2.0');
    }

}
