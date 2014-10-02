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

class Query_Form_Element_Queues extends Zend_Form_Element_Select {

    /**
     * The set of queues to be used with this query form.
     * @var array
     */
    protected $_queues = null;

    /**
     * Sets $_queues.
     * @param array $queues the set of queues to be used with this query form
     */
    public function setQueues($queues) {
        $this->_queues = $queues;
    }

    /**
     * Initializes the form element.
     */
    function init() {
        // set decorators
        $this->setDecorators(array('ViewHelper', 'Label'));

        // set multioptions and attributes
        $entries = array();
        $attribs = array();
        foreach ($this->_queues as $queue) {
            $entries[$queue['id']] = ucfirst($queue['name']);
            $attribs["data-option-{$queue['id']}-priority"] = $queue['priority'];
            $attribs["data-option-{$queue['id']}-timeout"] = $queue['timeout'];
        }
        $element = $this->addMultiOptions($entries);
        $this->setAttribs($attribs);
    }
}
