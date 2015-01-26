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

class Query_Form_Element_DownloadFormat extends Zend_Form_Element_Radio {

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
        $this->_adapter = $adapter;
    }

    /**
     * Construtor. Adds escape => true to the options array..
     * @param array $options form options for this element
     */
    public function __construct($name, $options = null) {
        if ($options === null) {
            $options = array();
        }

        $options['escape'] = false;

        parent::__construct($name, $options);
    }

    /**
     * Initializes the form element.
     */
    function init() {
        // set class
        $this->setAttrib('class','daiquiri-download-format');

        // set required
        $this->setRequired(true);

        // set multioptions
        $formats = array();
        foreach($this->_adapter as $adapter) {
            $formats[$adapter['format']] = "<div class=\"daiquiri-download-format-name\">{$adapter['name']}</div>";
            $formats[$adapter['format']] .= "<div class=\"daiquiri-download-format-description\">{$adapter['description']}</div>";
        }
        $this->setMultiOptions($formats);
    }
}
