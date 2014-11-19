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

class Core_AdminController extends Daiquiri_Controller_Abstract {

    protected $_items;

    public function init() {
        $this->_items = array(
            'admin' => array(
                'text' => 'Admin overview',
                'href' => '/core/admin'),
            'config' => array(
                'text' => 'Configuration',
                'href' => '/core/config',
                'resource' => 'Core_Model_Config',
                'permission' => 'index',
                'icon' => 'fa-wrench'),
            'templates' => array(
                'text' => 'Mail templates',
                'href' => '/core/templates',
                'resource' => 'Core_Model_Templates',
                'permission' => 'index',
                'icon' => 'fa-envelope-o'),
            'messages' => array(
                'text' => 'Status messages',
                'href' => '/core/messages',
                'resource' => 'Core_Model_Messages',
                'permission' => 'index',
                'icon' => 'fa-comment'),
            'user' => array(
                'text' => 'User management',
                'href' => '/auth/user',
                'resource' => 'Auth_Model_User',
                'permission' => 'rows',
                'icon' => 'fa-users'),
            'sessions' => array(
                'text' => 'Sessions management',
                'href' => '/auth/sessions',
                'resource' => 'Auth_Model_Sessions',
                'permission' => 'rows',
                'icon' => 'fa-laptop'),
            'data' => array(
                'text' => 'Database management',
                'href' => '/data',
                'resource' => 'Data_Model_Databases',
                'permission' => 'show',
                'icon' => 'fa-database'),
            'meetings' => array(
                'text' => 'Meetings management',
                'href' => '/meetings/',
                'resource' => 'Meetings_Model_Meetings',
                'permission' => 'index',
                'icon' => 'fa-calendar'),
            'contact' => array(
                'text' => 'Contact messages',
                'href' => '/contact/messages',
                'resource' => 'Contact_Model_Messages',
                'permission' => 'rows',
                'icon' => 'fa-envelope'),
            'examples' => array(
                'text' => 'Query examples',
                'href' => '/query/examples',
                'resource' => 'Query_Model_Examples',
                'permission' => 'index',
                'icon' => 'fa-code'),
            'query' => array(
                'text' => 'Query jobs',
                'href' => '/query/jobs',
                'resource' => 'Query_Model_Jobs',
                'permission' => 'rows',
                'icon' => 'fa-gears')
        );
        if (Daiquiri_Config::getInstance()->core->cms->enabled
            && Daiquiri_Auth::getInstance()->getCurrentRole() === 'admin') {
            $this->_items['cms'] = array(
                'text' => 'CMS Admin',
                'href' => rtrim(Daiquiri_Config::getInstance()->core->cms->url,'/') . '/wp-admin/',
                'icon' => 'fa-pencil'
            );
        }
    }

    public function indexAction() {
        array_shift($this->_items);

        $this->view->links = array();
        foreach ($this->_items as $item) {
            $item['text'] = "<i class=\"fa {$item['icon']}\"></i><span>{$item['text']}</span>";
            unset($item['icon']);

            $link = $this->internalLink($item);

            if (!empty($link)) {
                $this->view->links[] = $this->internalLink($item);
            }
        }

        if (empty($this->view->links)) {
            throw new Daiquiri_Exception_Unauthorized();
        }
    }

    public function menuAction() {

        $this->view->links = array();
        foreach ($this->_items as $item) {
            unset($item['icon']);
            
            $link = $this->internalLink($item);

            if (!empty($link)) {
                $this->view->links[] = $this->internalLink($item);
            }
        }

        // disable layout
        $this->_helper->layout->disableLayout();
    }

}
