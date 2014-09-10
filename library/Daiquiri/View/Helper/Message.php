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

class Daiquiri_View_Helper_Message extends Zend_View_Helper_Abstract {

    /**
     * The view object.
     * @var Zend_View_Interface
     */
    public $view;


    /**
     * Sets $view.
     * @param Zend_View_Interface $view the view object
     */
    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    /**
     * Returns the message stored for the key as a html paragraph.
     * @param  string $key
     * @return string $message
     */
    public function message($key) {
        $model = new Core_Model_Messages();
        $row = $model->getResource()->fetchRow(array(
            'where' => array('`key` = ?' => $key)
        ));
        if (empty($row)) {
            return '';
        } else {
            return '<p>' . $row['value'] . '</p>';
        }
    }

}
