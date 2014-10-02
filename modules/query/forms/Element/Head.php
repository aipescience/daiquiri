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

class Query_Form_Element_Head extends Daiquiri_Form_Element_Note {

    /**
     * The tile to display
     * @var string 
     */
    protected $_title;

    /**
     * Sets $_title
     * @param string $_title the tile to display
     */
    public function setTitle($title) {
        $this->_title = $title;
    }

    /**
     * The help text to display
     * @var string 
     */
    protected $_help;

    /**
     * Sets $_help
     * @param string $_help the help text to display
     */
    public function setHelp($help) {
        $this->_help = $help;
    }

    /**
     * Initializes the form element.
     */
    function init() {
        $this->setValue("<h4>{$this->_title}</h4><p>{$this->_help}</p>");
    }
}
