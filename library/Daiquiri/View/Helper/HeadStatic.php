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

class Daiquiri_View_Helper_HeadStatic extends Zend_View_Helper_Abstract {

    /**
     * Default static files to be included in this order, but css and js seperately
     * @var array
     */
    public static $files = array(
        // jquery
        'jquery.js' => 'daiquiri/lib/jquery-2.1.1.min.js',
        // bootstrap
        'bootstrap.css' => 'daiquiri/lib/bootstrap/css/bootstrap.css',
        'bootstrap.js' => 'daiquiri/lib/bootstrap/js/bootstrap.js',
        // angular
        'angular.js' => 'daiquiri/lib/angular/angular.min.js',
        'angular-sanitize.js' => 'daiquiri/lib/angular/angular-sanitize.min.js',
        'angular-cookies.js' => 'daiquiri/lib/angular/angular-cookies.min.js',
        // font-awesome
        'font-awesome.css' => 'daiquiri/lib/font-awesome/css/font-awesome.min.css',
        // flot
        'jquery.flot.js' => 'daiquiri/lib/jquery.flot.min.js',
        // code mirror
        'codemirror.css' => 'daiquiri/lib/codemirror/css/codemirror.css',
        'codemirror.js' => 'daiquiri/lib/codemirror/js/codemirror.js',
        'codemirror.runmode.js' => 'daiquiri/lib/codemirror/js/runmode.js',
        'codemirror.sql.js' => 'daiquiri/lib/codemirror/js/sql.js',
        // bootstrap-datepicker
        'bootstrap-datepicker.css' => 'daiquiri/lib/bootstrap-datepicker/css/datepicker.css',
        'bootstrap-datepicker.js' => 'daiquiri/lib/bootstrap-datepicker/js/bootstrap-datepicker.js',
        // other libs
        'samp.js' => 'daiquiri/lib/sampjs/samp.js',
        //daiquiri common
        // 'daiquiri_common.js' => 'daiquiri/js/daiquiri_common.js',
        // daiquiri browser
        // 'daiquiri_browser.css' => 'daiquiri/css/daiquiri_browser.css',
        // 'daiquiri_browser.js' => 'daiquiri/js/daiquiri_browser.js',
        // daiquiri table
        // 'daiquiri_table.css' => 'daiquiri/css/daiquiri_table.css',
        // 'daiquiri_table.js' => 'daiquiri/js/daiquiri_table.js',
        // daiquiri table
        // 'daiquiri_imageview.css' => 'daiquiri/css/daiquiri_imageview.css',
        // 'daiquiri_imageview.js' => 'daiquiri/js/daiquiri_imageview.js',
        // daiquiri query
        // 'daiquiri_query.css' => 'daiquiri/css/daiquiri_query.css',
        // 'daiquiri_query.js' => 'daiquiri/js/daiquiri_query.js',
        // 'query.css' => 'daiquiri/css/query.css',
        // 'query.js' => 'daiquiri/js/query.js',
        // daiquiri data management
        // 'daiquiri_data.css' => 'daiquiri/css/daiquiri_data.css',
        // 'daiquiri_data.js' => 'daiquiri/js/daiquiri_data.js',
        // daiquiri modal
        // 'daiquiri_modal.css' => 'daiquiri/css/daiquiri_modal.css',
        // 'daiquiri_modal.js' => 'daiquiri/js/daiquiri_modal.js',
        // daiquiri codemirror
        // 'daiquiri_codemirror.css' => 'daiquiri/css/daiquiri_codemirror.css',
        // 'daiquiri_codemirror.js' => 'daiquiri/js/daiquiri_codemirror.js',
        // daiquiri wordpress stylesheet
        // 'daiquiri_wp.css' => 'daiquiri/css/daiquiri_wp.css',
        // daiquiri misc
        // 'daiquiri_plot.js' => 'daiquiri/js/daiquiri_plot.js',
        // 'daiquiri_query_buttons.js' => 'daiquiri/js/daiquiri_query_buttons.js',
        // 'daiquiri_samp.js' => 'daiquiri/js/daiquiri_samp.js'
        //
        'daiquiri_common.css' => 'daiquiri/css/common.css',
        'daiquiri_form.css' => 'daiquiri/css/form.css',
        'daiquiri_wordpress.css' => 'daiquiri/css/wordpress.css',
        'daiquiri_table.css' => '/daiquiri/css/table.css',
        'daiquiri_table.js' => '/daiquiri/js/table.js',
        'daiquiri_modal.css' => '/daiquiri/css/modal.css',
        'daiquiri_modal.js' => '/daiquiri/js/modal.js',
        'daiquiri_browser.css' => '/daiquiri/css/browser.css',
        'daiquiri_browser.js' => '/daiquiri/js/browser.js',
        'daiquiri_samp.css' => '/daiquiri/css/samp.css',
        'daiquiri_samp.js' => '/daiquiri/js/samp.js',
        'daiquiri_codemirror.css' => '/daiquiri/css/codemirror.css',
        'daiquiri_codemirror.js' => '/daiquiri/js/codemirror.js',
        'daiquiri_admin.js' => '/daiquiri/js/admin.js'
    );

    /**
     * Files, which need to be linked when minifying
     * @var array
     */
    public static $links = array(
        'img/glyphicons-halflings.png' => 'daiquiri/lib/bootstrap/img/glyphicons-halflings.png',
        'img/glyphicons-halflings-white.png' => 'daiquiri/lib/bootstrap/img/glyphicons-halflings-white.png',
        'img/glyphicons-halflings.png' => 'daiquiri/lib/bootstrap/img/glyphicons-halflings.png',
        'fonts/fontawesome-webfont.woff' => 'daiquiri/lib/font-awesome/fonts/fontawesome-webfont.woff',
        'fonts/fontawesome-webfont.ttf' => 'daiquiri/lib/font-awesome/fonts/fontawesome-webfont.ttf',
        'fonts/fontawesome-webfont.svg' => 'daiquiri/lib/font-awesome/fonts/fontawesome-webfont.svg',
        'fonts/fontawesome-webfont.eot' => 'daiquiri/lib/font-awesome/fonts/fontawesome-webfont.eot'
    );

    /**
     * The Zend view object
     * @var Zend_View_Interface
     */
    public $view;

    /**
     * Setter for the view object
     * @param Zend_View_Interface $view [description]
     */
    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    /**
     * Produces the HTML header by adding the required JS and CSS script to the view. 
     * These are the files necessary for Daiquiri to work as defined in $_files and any
     * additional file given in $inputfiles. If minify is enabled in the configuration 
     * file, the JS and CSS files are minified.
     * @param  array  $customFiles   additional static files
     * @param  array  $overrideFiles files that override the default files
     */
    public function headStatic(array $customFiles, array $overrideFiles = array()) {
        $hl = $this->view->headLink();
        $hs = $this->view->headScript();

        $js = array();
        $css = array();
        if (Daiquiri_Config::getInstance()->core->minify->enabled == true) {
            $js[] = 'min/js/daiquiri.js';
            $css[] =  'min/css/daiquiri.css';
        } else {
            foreach (Daiquiri_View_Helper_HeadStatic::$files as $key => $file) {
                if (array_key_exists($key, $overrideFiles)) {
                    $file = $overrideFiles[$key];
                }

                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($ext === 'js') {
                    $js[] = $file;
                } else if ($ext === 'css') {
                    $css[] = $file;
                }
            }
        }

        foreach ($customFiles as $file) {
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
        echo PHP_EOL . PHP_EOL . $hl . PHP_EOL . PHP_EOL . $hs . PHP_EOL . PHP_EOL;
    }

}
