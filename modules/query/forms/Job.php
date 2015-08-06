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

class Query_Form_Job extends Data_Form_Abstract {

    /**
     * List of query job groups
     * @var array
     */
    protected $_groups = array();

    /**
     * Setter for $_groups
     * @param array $groups list of query job groups
     */
    protected function setGroups($groups) {
        $this->_groups[0] = 'unassigned';
        foreach ($groups as $group) {
            $this->_groups[$group['id']] = $group['name'];
        }
    }

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();

        // add elements
        $this->addSelectElement('group_id', array(
            'label' => 'Group',
            'multiOptions' => $this->_groups
        ));
        $this->addSubmitButtonElement('submit', $this->_submit);
        $this->addCancelButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('group_id'));
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        foreach (array('group_id') as $element) {
            if (isset($this->_entry[$element])) {
                $this->setDefault($element, $this->_entry[$element]);
            }
        }
    }

}
