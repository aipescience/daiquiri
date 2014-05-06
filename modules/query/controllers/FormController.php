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

class Query_FormController extends Daiquiri_Controller_Abstract {

    protected $_model;

    public function init() {
        $this->_model = Daiquiri_Proxy::factory('Query_Model_Form');
    }

    public function indexAction() {
        // get form from request default is sql
        $form = $this->_getParam('form', 'sql');
        $this->getControllerHelper('form')->submit($form);

        // overide form action
        if (isset($this->view->form)) {
           $this->view->form->setAction(Daiquiri_Config::getInstance()->getBaseUrl() . '/query/form/?form=' . $form);
        }
           
        // render a different view script if set
        $viewScript = Daiquiri_Config::getInstance()->query->forms->$form->view;
        if ($viewScript) {
            $this->view->setScriptPath(dirname($viewScript));
            $this->renderScript(basename($viewScript));
        }
    }

    public function planAction() {
        // get form from request default is sql
        $form = $this->_getParam('form', 'sql');
        $mail = $this->_getParam('mail');
        $this->getControllerHelper('form')->plan($mail);
    }

    public function mailAction() {
        $this->getControllerHelper('form')->mail();
    }

}
