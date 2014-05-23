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

class Query_IndexController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        // check acl
        if (Daiquiri_Auth::getInstance()->checkAcl('Query_Model_Form', 'submit')) {
            $this->view->status = 'ok';
        } else {
            throw new Daiquiri_Exception_Unauthorized();
        }
    }

    public function indexAction() {
        $this->view->status = 'ok';

        // get the query message
        $messagesModel = new Core_Model_Messages();
        try {
            $response = $messagesModel->show('query');
            $this->view->message = $response['row']['value'];
        } catch (Daiquiri_Exception_NotFound $e) {
            $this->view->message = false;
        }

        // get the different download formats
        $formats = array();
        $adapter = Daiquiri_Config::getInstance()->query->download->adapter->toArray();
        foreach ($adapter['enabled'] as $key) {
            $formats[$key] = $adapter['config'][$key]['name'];
        }
        $this->view->downloadForm = new Query_Form_Download(array(
            'formats' => $formats
        ));

        // get the forms to display
        if (Daiquiri_Config::getInstance()->query->forms) {
            $this->view->forms = Daiquiri_Config::getInstance()->query->forms->toArray();
        } else {
            $this->view->forms = array();
        }
    }
}
