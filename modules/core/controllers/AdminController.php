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

    protected $_links = array();

    public function init() {
        $items = array(
            'config' => array(
                'text' => 'Configuration',
                'href' => '/core/config',
                'resource' => 'Core_Model_Config',
                'permission' => 'index'),
            'templates' => array(
                'text' => 'Mail templates',
                'href' => '/core/templates',
                'resource' => 'Core_Model_Templates',
                'permission' => 'index'),
            'messages' => array(
                'text' => 'Status messages',
                'href' => '/core/messages',
                'resource' => 'Core_Model_Messages',
                'permission' => 'index'),
            'user' => array(
                'text' => 'User management',
                'href' => '/auth/user',
                'resource' => 'Auth_Model_User',
                'permission' => 'rows'),
            'sessions' => array(
                'text' => 'Sessions management',
                'href' => '/auth/sessions',
                'resource' => 'Auth_Model_Sessions',
                'permission' => 'rows'),
            'data' => array(
                'text' => 'Database management',
                'href' => '/data',
                'resource' => 'Data_Model_Databases',
                'permission' => 'show'),
            'meetings' => array(
                'text' => 'Meetings management',
                'href' => '/meetings/',
                'resource' => 'Meetings_Model_Meetings',
                'permission' => 'index'),
            'contact' => array(
                'text' => 'Contact messages',
                'href' => '/contact/messages',
                'resource' => 'Contact_Model_Messages',
                'permission' => 'rows'),
            'examples' => array(
                'text' => 'Examples',
                'href' => '/query/examples',
                'resource' => 'Query_Model_Examples',
                'permission' => 'index'),
            'query' => array(
                'text' => 'Query jobs',
                'href' => '/query/jobs',
                'resource' => 'Query_Model_Jobs',
                'permission' => 'rows')
        );
        if (Daiquiri_Config::getInstance()->core->cms->enabled
            && Daiquiri_Auth::getInstance()->getCurrentRole() === 'admin') {
            $items['cms'] = array(
                'text' => 'CMS Admin',
                'href' => Daiquiri_Config::getInstance()->core->cms->url + '/wp-admin/'
            );
        }

        foreach ($items as $item) {
            $link = $this->internalLink($item);

            if(!empty($link)) {
                $this->_links[] = $link;
            }
        }
    }

    public function indexAction() {
        if (empty($this->_links)) {
            throw new Daiquiri_Exception_Unauthorized();
        }

        $this->view->links = $this->_links;
    }

    public function menuAction() {
        $this->view->links = $this->_links;

        // disable layout
        $this->_helper->layout->disableLayout();
    }

}
