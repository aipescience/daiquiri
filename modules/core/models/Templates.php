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

class Core_Model_Templates extends Daiquiri_Model_Table {

    /**
     * List of available mail templates.
     * @var array $templates
     */
    public $templates;

    /**
     * Constructor. Sets resource object and the database table. Also sets a list of use templates with fields.
     */
    public function __construct() {
        $this->setResource('Daiquiri_Model_Resource_Table');
        $this->getResource()->setTablename('Core_Templates');

        $this->templates = array(
            'auth.register' => array('firstname', 'lastname', 'username', 'link'),
            'auth.forgotPassword' => array('firstname', 'lastname', 'username', 'link'),
            'auth.validate' => array('firstname', 'lastname', 'username', 'link'),
            'auth.confirm' => array('firstname', 'lastname', 'username', 'manager'),
            'auth.reject' => array('firstname', 'lastname', 'username', 'manager'),
            'auth.activate' => array('firstname', 'lastname', 'username'),
            'auth.changePassword' => array('firstname', 'lastname', 'username'),
            'auth.updateUser' => array('firstname', 'lastname', 'username'),
            'contact.submit_user' => array('firstname', 'lastname', 'username'),
            'contact.submit_support' => array('firstname', 'lastname', 'username', 'email',
                'category', 'subject', 'message', 'link'),
            'contact.respond' => array('subject', 'body'),
            'query.plan' => array('firstname', 'lastname', 'email', 'sql', 'plan', 'message'),
            'meetings.validate' => array('meeting', 'firstname', 'lastname', 'link'),
        );
        
        if (in_array('meetings',Daiquiri_Config::getInstance()->getApplication()->resources->modules->toArray())) {
            $participantDetailKeysModel = new Meetings_Model_ParticipantDetailKeys();
            $contributionTypesModel = new Meetings_Model_ContributionTypes();
            $this->templates['meetings.register'] = array_merge(
                array('meeting', 'firstname', 'lastname', 'affiliation','email','arrival','departure'),
                $participantDetailKeysModel->getResource()->fetchValues('key')
            );
            foreach ($contributionTypesModel->getResource()->fetchValues('contribution_type') as $contribution_type) {
                $this->templates['meetings.register'][] = $contribution_type . '_title';
                $this->templates['meetings.register'][] = $contribution_type . '_abstract';
            }
        }
    }

    /**
     * Returns all mail templates.
     * @return array $response
     */
    public function index() {
        return $this->getModelHelper('CRUD')->index();
    }

    /**
     * Returns a mail template given by its template name
     * @param string $template template name
     * @return array $response
     */
    public function show($template,  array $values = array()) {
        // get the template data from the database
        $data = $this->getResource()->fetchRow(array(
            'where' => array('`template` = ?' => $template)
        ));

        // search replace the placeholders
        if (!empty($values)) {
            foreach ($this->templates[$template] as $key) {
                if (!empty($values[$key])) {
                    $data['subject'] = str_replace('_' . $key . '_', $values[$key], $data['subject']);
                    $data['body'] = str_replace('_' . $key . '_', $values[$key], $data['body']);
                }
            }

            // get rid of the remaining placeholders, only if a value array was provided
            foreach ($this->templates[$template] as $key) {
                $data['subject'] = str_replace('_' . $key . '_','', $data['subject']);
                $data['body'] = str_replace('_' . $key . '_','', $data['body']);
            }
        }

        return $data;
    }

    /**
     * Creates a mail template.
     * @param array $formParams
     * @return array $response
     */
    public function create(array $formParams = array()) {
        // create the form object
        $form = new Core_Form_Templates(array(
            'submit' => 'Create new mail template'
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {

                // get the form values
                $values = $form->getValues();

                // check if the entry is already there
                $rows = $this->getResource()->fetchRows(array(
                    'where' => array('`template` = ?' => $values['template'])
                ));

                if (empty($rows)) {
                    // store the details
                    $this->getResource()->insertRow($values);
                    return array('status' => 'ok');
                } else {
                    $form->setDescription('Template already stored');
                    return array(
                        'form' => $form,
                        'status' => 'error',
                        'error' => 'Template already stored'
                    );
                }
            } else {
                return array(
                    'form' => $form,
                    'status' => 'error',
                    'errors' => $form->getMessages()
                );
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

    /**
     * Updates a mail template.
     * @param int $id
     * @param array $formParams
     * @return array $response
     */
    public function update($id, array $formParams = array()) {
        $entry = $this->getResource()->fetchRow($id);
        if (empty($entry)) {
            throw new Daiquiri_Exception_NotFound();
        }

        // produce variables string
        $tmp = array();
        foreach ($this->templates[$entry['template']] as $value) {
            $tmp[] = '_' . $value . '_';
        }
        $variables  = implode(' ', $tmp);

        // create the form object
        $form = new Core_Form_Templates(array(
            'submit'=> 'Update mail template',
            'entry' => $entry
        ));

        // valiadate the form if POST
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get the form values
                $values = $form->getValues();

                // update the row in the database
                $this->getResource()->updateRow($id, $values);
                
                return array('status' => 'ok');
            } else {
                return array(
                    'form' => $form,
                    'status' => 'error',
                    'errors' => $form->getMessages(),
                    'variables' => $variables
                );
            }
        }

        return array(
            'form' => $form,
            'status' => 'form',
            'variables' => $variables
        );
    }

    /**
     * Deletes a mail template.
     * @param int $id
     * @param array $formParams
     * @return array $response
     */
    public function delete($id, array $formParams = array()) {
        return $this->getModelHelper('CRUD')->delete($id, $formParams, 'Delete entry');
    }

    /**
     * Returns all mail templates for export.
     * @return array $response
     */
    public function export() {
        $dbRows = $this->getResource()->fetchRows();

        $data = array();
        foreach ($dbRows as $dbRow) {
            $data[] = array(
                'template' => $dbRow['template'],
                'subject' => $dbRow['subject'],
                'body' => $dbRow['body']
            );
        }

        return array(
            'data' => array('templates' => $data),
            'status' => 'ok'
        );
    }
}
