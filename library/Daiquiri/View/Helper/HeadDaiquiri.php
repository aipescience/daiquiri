<?php

/*
 *  Copyright (c) 2012, 2013 Jochen S. Klar <jklar@aip.de>,
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
    public static $files = array(
        // jquery and jquery ui
        'lib/jquery-2.1.0.js',
        // bootstrap
        'lib/bootstrap/css/bootstrap.css',
        'lib/bootstrap/js/bootstrap.js',
        // flot
        'lib/jquery.flot.js',
        // code mirror
        'lib/codemirror/css/codemirror.css',
        'lib/codemirror/js/codemirror.js',
        'lib/codemirror/js/runmode.js',
        'lib/codemirror/js/sql.js',
        // bootstrap-datepicker
        'lib/bootstrap-datepicker/css/datepicker.css',
        'lib/bootstrap-datepicker/js/bootstrap-datepicker.js',
        // other libs
        'lib/insert_at_caret.js',
        'lib/samp.js',
        //daiquiri common
        'css/daiquiri_common.css',
        'js/daiquiri_common.js',
        // daiquiri browser
        'css/daiquiri_browser.css',
        'js/daiquiri_browser.js',
        // daiquiri table
        'css/daiquiri_table.css',
        'js/daiquiri_table.js',
        // daiquiri table
        'css/daiquiri_imageview.css',
        'js/daiquiri_imageview.js',
        // daiquiri query
        'css/daiquiri_query.css',
        'js/daiquiri_query.js',
        // daiquiri data management
        'css/daiquiri_data.css',
        'js/daiquiri_data.js',
        // daiquiri head
        'css/daiquiri_modal.css',
        'js/daiquiri_modal.js',
        // daiquiri wordpress stylesheet
        'css/daiquiri_wp.css',
        // daiquiri misc
        'js/daiquiri_plot.js',
        'js/daiquiri_query_buttons.js',
        'js/daiquiri_codemirror.js',
        'js/daiquiri_samp.js'
    );

    // image files, which need to be taken car of when minifying
    public static $img = array(
        'lib/bootstrap/img/glyphicons-halflings.png',
        'lib/bootstrap/img/glyphicons-halflings-white.png'
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
        $hl = $this->view->headLink();
        $hs = $this->view->headScript();

        $js = array();
        $css = array();
        if (Daiquiri_Config::getInstance()->core->minify->enabled == true) {
            $js[] = 'min/daiquiri.js';
            $css[] =  'min/daiquiri.css';
        } else {
            foreach (Daiquiri_View_Helper_HeadDaiquiri::$files as $file) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($ext === 'js') {
                    $js[] = 'daiquiri/' . $file;
                } else if ($ext === 'css') {
                    $css[] = 'daiquiri/' . $file;
                }
            }
        }
        foreach ($inputfiles as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext === 'js') {
                $js[] = $file;
            } else if ($ext === 'css') {
                $css[] = $file;
            }
        }

        // append css files
        foreach ($css as $file) {
            $hl->appendStylesheet($this->view->baseUrl($file));
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
