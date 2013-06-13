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
 * @class   Daiquiri_Controller_Abstract Abstract.php
 * @brief   Abstract class for daiquiri controllers
 * 
 * Abstract class for daiquiri controllers providing commonly used methods. This
 * class extends the default Zend Controller.
 * 
 */
abstract class Daiquiri_Controller_Abstract extends Zend_Controller_Action {

    /**
     * @brief   parsing request for sql options and generates array
     * @return  array containing 'cols', 'options' and jqGrid keys of an sql 
     *          request
     * 
     * This method parses the HTML request for any sql options needed to handle
     * requests of the table viewer. It parses the columns, options and the 
     * jqGrid parameters and stores them in an array. If a parameter is not
     * found, it is set NULL.
     * 
     * jqGrid parameters that are handled: 
     *  - <b>page</b>         requested page number
     *  - <b>rows</b>         number of rows per page
     *  - <b>sidx</b>         sort id, columns that are sorted
     *  - <b>sord</b>         sort order
     *  - <b>_search</b>      ???
     *  - <b>searchField</b>  list of fields where a search should be performed
     *  - <b>searchOper</b>   search operator for this field (boolean operators,
     *                        arithmetic operators,...???)
     *  - <b>searchString</b> search condition
     * 
     */
    protected function _getTableParams() {

        $params = array();

        // get cols
        $params['cols'] = $this->_getParam('cols');
        if ($params['cols'] !== null) {
            $params['cols'] = explode(',', $params['cols']);
        }

        // get options
        $params['options'] = $this->_getParam('options');

        // get jqGrid parameter
        $keys = array(
            'page', 'rows', 'sidx', 'sord',
            '_search', 'searchField', 'searchOper', 'searchString'
        );
        foreach ($keys as $key) {
            $params[$key] = $this->_getParam($key);
        }

        return $params;
    }

}
