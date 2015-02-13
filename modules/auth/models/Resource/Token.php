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

class Auth_Model_Resource_Token extends Daiquiri_Model_Resource_Table {

    /**
     * Constructor. Sets tablename.
     */
    public function __construct() {
        $this->setTablename('Auth_Token');
    }

    /**
     * Creates inserts and returns a new token.
     * @return  string  $token  the new token
     */
    public function insertRow($path) {
        // randomly create the new token
        $token = md5(mt_rand(1,1000000));

        // set expiration date to tomorrow
        $expires = date("Y-m-d\TH:i:s", time() + 60);

        // insert into database credentials
        $this->getAdapter()->insert('Auth_Token', array(
            'username' => Daiquiri_Auth::getInstance()->getCurrentUsername(),
            'token' => $token,
            'path' => $path,
            'expires' => $expires
        ));

        // return the id of the newly created user
        return $token;
    }

    /**
     * Removes all tokens which are expired.
     */
    public function cleanup() {
        $this->getAdapter()->delete('Auth_Token', array('expires < NOW()'));
    }

    /**
     * Checks if a token is valid.
     * @return bool $isValid
     */
    public function check($username, $token, $path) {
        $select = $this->select();
        $select->from('Auth_Token', array('id'));
        $select->where('username = ?', $username);
        $select->where('token = ?', $token);
        $select->where('path = ?', $path);
        $select->where('expires >= NOW()');

        $row = $this->fetchOne($select);

        if ($row === false) {
            return false;
        } else {
            return true;
        }
    }

}