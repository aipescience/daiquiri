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

class Daiquiri_Model_Helper_Notification extends Daiquiri_Model_Helper_Abstract {

    public function updateUser($oldUser, $newUser) {
        $config = Daiquiri_Config::getInstance()->core->notification;

        if ($config->updateUser) {
            if ($config->mail) {
                $this->getModel()->getModelHelper('mail')->send('notification.updateUser', array(
                    'to' => $config->mail->toArray(),
                    'id' => $newUser['id'],
                    'username' => $newUser['username'],
                    'firstname' => $newUser['details']['firstname'],
                    'lastname' => $newUser['details']['lastname']
                ));
            }

            if ($config->webhook) {
                $this->getModel()->getModelHelper('webhook')->send($config->webhook, array(
                    'action' => 'updateUser',
                    'old_user' => $oldUser,
                    'new_user' => $newUser
                ));
            }
        }
    }

    public function changePassword($user) {
        $config = Daiquiri_Config::getInstance()->core->notification;

        if ($config->changePassword) {
            if ($config->mail) {
                $this->getModel()->getModelHelper('mail')->send('notification.changePassword', array(
                    'to' => $config->mail->toArray(),
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'firstname' => $user['details']['firstname'],
                    'lastname' => $user['details']['lastname']
                ));
            }

            if ($config->webhook) {
                $this->getModel()->getModelHelper('webhook')->send($config->webhook, array(
                    'action' => 'changePassword',
                    'user' => $user
                ));
            }
        }
    }
}
