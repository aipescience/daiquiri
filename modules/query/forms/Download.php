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

class Query_Form_Download extends Daiquiri_Form_Abstract {

    /**
     * The set of adapter to choose from.
     * @var array
     */
    protected $_adapter;

    /**
     * Sets $_formats.
     * @param array $adapter the set of adapter to choose from
     */
    public function setAdapter(array $adapter) {
        $this->_adapter = $adapter;
    }

    /**
     * Initializes the form.
     */
    public function init() {
        // add elements
        $this->addCsrfElement('download_csrf');
        $this->addElement(new Daiquiri_Form_Element_Tablename('download_tablename', array(
            'label' => 'Name of the new table (optional)',
            'class' => 'span9',
            'required' => true
        )));
        $this->addElement(new Query_Form_Element_DownloadFormat('download_format', array(
            'adapter' => $this->_adapter
        )));
        $this->addSubmitButtonElement('download_submit',array(
            'label' => 'Download table'
        ));

        $this->addDisplayGroup(array('download_tablename'),'download-table-group', false, true);
        $this->addDisplayGroup(array('download_format'),'download-format-group');
        $this->addInlineGroup(array('download_submit'));

        // add angular directives
        $this->setAttrib('name',"download");
        $this->setAttrib('ng-submit',"downloadTable()");
        $this->setDecorators(array(
            'FormElements',
            array('Callback', array(
                'callback' => function($content, $element, $options) {
                    $ngErrorModel = 'errors.form';

                    return '<ul class="unstyled text-error angular-hidden" ng-show="' . $ngErrorModel . '"><li ng-repeat="error in ' . $ngErrorModel . '">Error: {{error}}</li></ul>';
                },
                'placement' => 'append'
            )),
            'Form'
        ));
        foreach (array('download_csrf','download_tablename','download_format') as $name) {
            $element = $this->getElement($name);
            $element->setAttrib('ng-model','values.' . $name);
            $element->addDecorators(array(array('Callback', array(
                'callback' => function($content, $element, $options) {
                    $ngErrorModel = 'errors.' . $element->getName();

                    return '<ul class="unstyled text-error angular-hidden" ng-show="' . $ngErrorModel . '"><li ng-repeat="error in ' . $ngErrorModel . '">Error: {{error}}</li></ul>';
                },
                'placement' => 'append'
            ))));
        }
    }
}
