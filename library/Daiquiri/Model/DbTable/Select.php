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
 * @class   Daiquiri_Model_DbTable_Select Select.php
 * @brief   Class extending Zend_Db_Table_Select to alter the table names PROPERLY!
 * 
 * Class extending Zend_Db_Table_Select to alter the column names PROPERLY!
 * This is mainly used to set column names to values that include dots (i.e. if
 * using MySQL, the column names include the aliased table name they come from)
 */
class Daiquiri_Model_DbTable_Select extends Zend_Db_Table_Select {

    // public function setColumns($table, array $columnArray) {
    //     //this function sets the column names to what is provided
    //     //nothing more - no parsing no nothing. 

    //     $newColumns = array();

    //     $this->from($table);

    //     foreach($columnArray as $column) {
    //         $column = trim($column, "`");

    //         $newColumns[] = array($table, $column, NULL);
    //     }

    //     $this->_parts['columns'] = $newColumns;
    // }

}
