<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
 *                           Adrian M. Partl <apartl@aip.de>, 
 *                           AIP E-Science (www.aip.de)
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  See the NOTICE file distributed with this work for additional
 *  information regarding copyright ownership. You may obtain a copy
 *  of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

class Daiquiri_View_Helper_AccountMenu extends Zend_View_Helper_Abstract {

    public $view;

    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    public function accountMenu($listOnly = true) {
        $html = '';
        if (Daiquiri_Auth::getInstance()->checkAcl('Auth_Model_User', 'edit')
                || Daiquiri_Auth::getInstance()->checkAcl('Auth_Model_Password', 'change')) {
            if ($listOnly === true) {
                $html .= '<li class="dropdown">';
                $html .= '<a class="dropdown-toggle" data-toggle="dropdown" href="#">My Account</a>';
                $html .= '<ul class = "dropdown-menu">';
            }
            $html .= $this->view->internalLink(array(
                'href' => '/auth/account/update',
                'text' => 'Update User',
                'resource' => 'Auth_Model_Account',
                'permission' => 'update',
                'prepend' => '<li class="nav-item">',
                'append' => '</li>'));
            $html .= $this->view->internalLink(array(
                'href' => '/auth/password/change',
                'text' => 'Change Password',
                'resource' => 'Auth_Model_Password',
                'permission' => 'change',
                'prepend' => '<li class="nav-item">',
                'append' => '</li>'));
            if ($listOnly === true) {
                $html .= '</ul></li>';
            }
        }
            
        return $html;
    }

}
