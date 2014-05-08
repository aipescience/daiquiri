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

        $ra     = $this->_escape($this->getValue($this->getFieldId('ra')));
        $dec    = $this->_escape($this->getValue($this->getFieldId('dec')));
        $radius = $this->_escape($this->getValue($this->getFieldId('radius')));

        $sql = "SELECT angdist({$ra},{$dec},`{$this->_formOptions['raField']}`,`{$this->_formOptions['decField']}`)";
        $sql .= " * 3600.0 AS distance_arcsec, s.* FROM {$this->_formOptions['table']} AS s";
        $sql .= " WHERE angdist({$ra},{$dec},`{$this->_formOptions['raField']}`,`{$this->_formOptions['decField']}`)";
        $sql .= " < {$radius} / 3600.0;";

    //Zend_Debug::dump($sql); die(0);
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
        $head = new Daiquiri_Form_Element_Note('head', array(
            'value' => "<h2>{$this->_formOptions['title']}</h2><p>{$this->_formOptions['help']}</p>"
        ));
        $this->addElement($head);

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
        $this->addParagraphGroup(array('head'), 'sql-head-group');
        $this->addHorizontalGroup(array($this->getFieldId('ra'), $this->getFieldId('dec'), $this->getFieldId('radius')));
        $this->addParagraphGroup(array($this->getFieldId('tablename')), 'table-group', false, true);
        $this->addInlineGroup(array($this->getFieldId('submit')), 'button-group');

        if (isset($this->_tablename)) {
            $this->setDefault($this->getFieldId('tablename'), $this->_tablename);
        }

        $this->setDefault($this->getFieldId('ra'), $this->_formOptions['raDefault']);
        $this->setDefault($this->getFieldId('dec'), $this->_formOptions['decDefault']);
        $this->setDefault($this->getFieldId('radius'), $this->_formOptions['radiusDefault']);
    }

}
