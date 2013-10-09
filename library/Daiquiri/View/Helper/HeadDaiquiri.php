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
 * @class   Daiquiri_View_Helper_HeadDaiquiri HeadDaiquiri.php
 * @brief   Daiquiri View helper for displaying HTML header with all JS and CSS information
 * 
 * Zend view helper for adding the Daiquiri header to the page. All the JavaScript and CSS
 * files defined in the $_files array are added and if wished, minified. 
 * 
 */
class Daiquiri_View_Helper_HeadDaiquiri extends Zend_View_Helper_Abstract {

    // files to be included in this order, but css and js seperately
    private $_files = array(
        // jquery and jquery ui
        'daiquiri/lib/jquery-1.8.2.min.js',
        // bootstrap
        'daiquiri/lib/bootstrap/css/bootstrap.css',
        'daiquiri/lib/bootstrap/js/bootstrap.js',
        // flot
        'daiquiri/lib/jquery.flot.js',
        // code mirror
        'daiquiri/lib/codemirror/lib/codemirror.js',
        'daiquiri/lib/codemirror/lib/codemirror.css',
        'daiquiri/lib/codemirror/addon/runmode/runmode.js',
        'daiquiri/lib/codemirror/mode/sql/sql.js',
        // other libs
        'daiquiri/lib/insert_at_caret.js',
        // daiquiri common
        'daiquiri/css/daiquiri_common.css',
        'daiquiri/js/daiquiri_common.js',
        // daiquiri browser
        'daiquiri/css/daiquiri_browser.css',
        'daiquiri/js/daiquiri_browser.js',
        // daiquiri table
        'daiquiri/css/daiquiri_table.css',
        'daiquiri/js/daiquiri_table.js',
        // daiquiri query
        'daiquiri/css/daiquiri_query.css',
        'daiquiri/js/daiquiri_query.js',
        // daiquiri user table
        'daiquiri/js/daiquiri_admin_table.js',
        // daiquiri cms
        'daiquiri/css/daiquiri_cms.css',
        // daiquiri misc
        'daiquiri/js/daiquiri_plot.js',
        'daiquiri/js/daiquiri_query_buttons.js',
        'daiquiri/js/daiquiri_codemirror.js',
        'daiquiri/js/daiquiri_wp_menu.js',
        'daiquiri/js/daiquiri_modal.js',
        'daiquiri/lib/sampjs/samp.js',
        'daiquiri/js/daiquiri_samp.js',
    );
    public $view;

    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    /**
     * @brief   headDaiquiri method - prints the Daiquiri header
     * @param   array $inputfiles: array with any additional files that should be added
     *                             to the header.
     * @return  HTML header
     * 
     * Produces the HTML header by adding the required JS and CSS script to the view. 
     * These are the files necessary for Daiquiri to work as defined in $_files and any
     * additional file given in $inputfiles. 
     *
     * If minify is enabled in the configuration file, the JS and CSS files are minified.
     * 
     */
    public function headDaiquiri(array $inputfiles) {
        // merge input files with the ones defined above
        $files = array_merge($this->_files, $inputfiles);

        // get minified view helpers or not
        if (Daiquiri_Config::getInstance()->core &&
                Daiquiri_Config::getInstance()->core->minify &&
                Daiquiri_Config::getInstance()->core->minify->enabled) {
            $hl = $this->view->minifyHeadLink();
            $hs = $this->view->minifyHeadScript();
        } else {
            $hl = $this->view->headLink();
            $hs = $this->view->headScript();
        }

        $js = array();
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext === 'js') {
                $js[] = $file;
            } else if ($ext === 'css') {
                $hl->appendStylesheet($this->view->baseUrl($file));
            }
        }

        // prepend js files in reverse order
        foreach (array_reverse($js) as $file) {
            $hs->prependFile($this->view->baseUrl($file));
        }

        // echo the view helpers
        echo $hl;
        echo $hs;
    }

}
