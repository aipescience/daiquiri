<?php
/*
 *  Copyright (c) 2012-2015  Jochen S. Klar <jklar@aip.de>,
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

class Query_Form_Download extends Daiquiri_Form_Abstract {

    /**
     * The set of adapter to choose from.
     * @var array
     */
    protected $_adapter;

    /**
     * Sets $_formats.
     * @param array $adapter the set of adapter to choose from
     */
    public function setAdapter(array $adapter) {
        $this->_adapter = array();
        foreach($adapter as $adapter) {
            $this->_adapter[$adapter['format']] = $adapter['name'];
        }
    }

    /**
     * Initializes the form.
     */
    public function init() {
        // add elements
        $this->addCsrfElement('download_csrf');

        $this->addRadioElement('download_format', array(
            'label' => 'Download formats',
            'multiOptions' => $this->_adapter
        ));
        $this->addElement(new Daiquiri_Form_Element_Tablename('download_tablename', array(
            'label' => 'Name of the table',
            'required' => true
        )));
        $this->addSubmitButtonElement('download_submit',array(
            'label' => 'Download table'
        ));

        $this->addHorizontalGroup(array('download_format'),'download-format-group');
        $this->addHorizontalGroup(array('download_tablename'),'download-table-group', false, true);
        $this->addInlineGroup(array('download_submit'));
    }
}
