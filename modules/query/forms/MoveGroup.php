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

class Query_Form_MoveGroup extends Data_Form_Abstract {

    /**
     * The default entry for the prev_id field.
     * @var array
     */
    private $_prevId;

    /**
     * Sets $_prevId.
     * @param int $prevId the default entry for the prev_id field
     */
    public function setPrevId($prevId) {
        $this->_prevId = $prevId;
    }

    /**
     * Initializes the form.
     */
    public function init() {
        $this->addCsrfElement();

        // add elements
        $this->addTextElement('prev_id', array(
            'label' => 'Id of previous group',
            'class' => 'span1 mono',
            'filters' => array('StringTrim'),
            'validators' => array(
                array('validator' => 'int'),
            )
        ));
        $this->addSubmitButtonElement('submit', 'Move query job group');
        $this->addCancelButtonElement('cancel', 'Cancel');

        // add groups
        $this->addHorizontalGroup(array('prev_id'));
        $this->addActionGroup(array('submit', 'cancel'));

        // set fields
        if (isset($this->_prevId)) {
            $this->setDefault('prev_id', $this->_prevId);
        }
    }

}
