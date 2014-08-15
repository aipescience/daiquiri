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

class Query_Form_Download extends Daiquiri_Form_Abstract {

    protected $_formats;
    protected $_csrfActive = true;

    public function setFormats(array $formats) {
        $this->_formats = $formats;
    }

    public function getCsrf() {
        return $this->getElement('download_csrf');
    }

    public function init() {
        $this->addElement('select', 'download_format', array(
            'required' => true,
            'label' => 'Select format:',
            'multiOptions' => $this->_formats,
            'decorators' => array('ViewHelper'),
            'class' => 'span4'
        ));
        $this->addElement('button','download_submit',array(
            'label' => 'Download',
            'class' => 'btn btn-primary'
        ));

        $this->addHorizontalGroup(array('download_format'));
        $this->addHorizontalButtonGroup(array('download_submit'));
    }
}
