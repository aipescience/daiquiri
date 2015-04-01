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

        // get the csrf token
        $session = new Zend_Session_Namespace('csrf');
        $csrf = $session->hash;

        // get the forms to display
        $options = array(
            'defaultForm' => Null,
            'polling' => Daiquiri_Config::getInstance()->query->polling->toArray(),
            'forms' => array(),
            'csrf' => $csrf
        );

        foreach(Daiquiri_Config::getInstance()->query->forms as $key => $form) {
            if ($form->default) $options['defaultForm'] = $key;
            $options['forms'][] = array(
                'key' => $key,
                'title' => $form->title
            );
        }

        $this->view->options = $options;

        // get options for the query plan
        $queryModel = new Query_Model_Query();
        $this->view->plan = array(
            'enabled' => $queryModel->canShowPlan(),
            'editable' => $queryModel->canAlterPlan(),
            'mail' => Daiquiri_Config::getInstance()->query->processor->mail->enabled,
        );

        // check if imageviewer is enabled
        if (Daiquiri_Config::getInstance()->query->images->enabled) {
            $this->view->images = true;
        } else {
            $this->view->images = false;
        }

        // check if the table cols/rows should be selectable
        if (Daiquiri_Config::getInstance()->query->results->select) {
            $this->view->select = true;
        } else {
            $this->view->select = false;
        }

        // check if samp is enabled
        if (Daiquiri_Config::getInstance()->query->samp->enabled && Daiquiri_Auth::getInstance()->getCurrentUsername() !== 'guest') {
            $this->view->samp = true;
        } else {
            $this->view->samp = false;
        }

        // check if plot is enabled
        if (Daiquiri_Config::getInstance()->query->plot->enabled) {
            $this->view->plot = true;
        } else {
            $this->view->plot = false;
        }


    }
}
