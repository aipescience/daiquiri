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

/**
 * Model for the currently running query jobs.
 */
class Data_Model_Columns extends Daiquiri_Model_SimpleTable {

    /**
     * Constructor. Sets resource object and primary field.
     */
    public function __construct() {
        $this->setResource('Data_Model_Resource_Columns');
        $this->setValueField('name');
    }

    /**
     * Returns a column entry.
     * @param int $id
     * @return array
     */
    public function show($id) {
        return $this->getResource()->fetchRow($id);
    }

    /**
     * Creates column entry.
     * @param array $formParams
     * @return array
     */
    public function create($tableId = null, array $formParams = array()) {
        // create the form object
        $tablesModel = new Data_Model_Tables();

        $form = new Data_Form_Column(array(
                    'tables' => $tablesModel->getValues(),
                    'tableId' => $tableId,
                    'submit' => 'Create column entry'
                ));

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            $values = $form->getValues();

            if(array_key_exists("ucd_list", $values)) {
                unset($values['ucd_list']);
            }

            if ($this->getResource()->fetchIdWithName($values['table_id'], $values['name']) !== false) {
                throw new Exception("Column entry already exists.");
            }

            $this->commitToDB($values);

            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function update($id, array $formParams = array()) {
        // get the entry
        $tablesModel = new Data_Model_Tables();
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Exception('$id ' . $id . ' not found.');
        }

        $form = new Data_Form_Column(array(
                    'tableId' => $tablesModel->getId($entry['table']),
                    'entry' => $entry,
                    'tables' => $tablesModel->getValues(),
                    'submit' => 'Update column entry'
                ));

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {

            // get the form values
            $values = $form->getValues();

            unset($values['ucd_list']);

            $this->getResource()->updateRow($id, $values);

            if (Daiquiri_Config::getInstance()->data->writeToDB) {
                $this->_writeColumnComment($entry['database'], $entry['table'], $entry['name'], $values);
            }

            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function delete($id, array $formParams = array()) {
        // create the form object
        $form = new Data_Form_Delete(array(
                    'submit' => 'Delete column entry'
                ));

        // valiadate the form if POST
        if (!empty($formParams) && $form->isValid($formParams)) {
            $this->getResource()->deleteRow($id);
            return array('status' => 'ok');
        }

        return array('form' => $form, 'status' => 'form');
    }

    public function commitToDB(array $values = array()) {
        $cache = $values;

        if(array_key_exists("comment", $values)) {
            unset($values['comment']);
        }

        if(array_key_exists("database", $values)) {
            unset($values['database']);
        }

        if(array_key_exists("table", $values)) {
            unset($values['table']);
        }

        // store the values in the database
        $this->getResource()->insertRow($values);

        if (Daiquiri_Config::getInstance()->data->writeToDB) {
            if(array_key_exists("database", $cache)) {
                $tableData = array("database" => $cache['database'],
                                "name" => $cache['table']);
            } else {
                $tablesModel = new Data_Model_Tables();
                $tableData = $tablesModel->show($values['table_id']);
            }

            if(array_key_exists("comment", $cache)) {
                $this->_writeColumnComment($tableData['database'], $tableData['name'], $values['name'], $values, $cache['comment']);
            } else {
                $this->_writeColumnComment($tableData['database'], $tableData['name'], $values['name'], $values);
            }
        }
    }

    private function _writeColumnComment($db, $table, $column, $values, $oldComment = false) {
        //write metadata into comment field of the column (if supported)
        $descResource = new Data_Model_Resource_Description();
        $databasesModel = new Data_Model_Databases();

        if ($oldComment === false) {
            $comment = $descResource->getColumnComment($db, $table, $column);
            $oldComment = $comment;
        } else {
            $comment = $oldComment;
        }

        unset($values['table_id']);
        unset($values['position']);

        $json = Zend_Json::encode($values);

        //check if there is already a comment present with our metadata
        $charPos = strpos($comment, "DQIMETA=");

        if ($charPos !== false) {
            //find end of json
            $endPos = $descResource->findJSONEnd($comment, $charPos);

            if ($endPos === false) {
                throw new Exception("Cannot update MySQL meta data due to corruped column comment.");
            }

            $comment = substr($comment, 0, $charPos) . "DQIMETA=" . $json . substr($comment, $endPos + 1);
        } else {
            if (strlen($comment) > 0) {
                $comment .= ", DQIMETA=" . $json;
            } else {
                $comment = "DQIMETA=" . $json;
            }
        }

        //only do something if there is a change...
        if ($comment !== $oldComment) {
            $descResource->setColumnComment($db, $table, $column, $comment);
        }

        return true;
    }

}
