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

class Query_Model_Form extends Daiquiri_Model_Abstract {

    /**
     * Submits a new query to the database.
     * @param string $formstring name of the form to use
     * @param array $formParams
     * @return array $response
     */
    public function submit($formstring, array $formParams = array()) {
        // get the formclass
        $formConfig = Daiquiri_Config::getInstance()->query->forms->$formstring;
        if ($formConfig === null || get_Class($formConfig) !== 'Zend_Config') {
            throw new Exception('form options not found');
        } else {
            $formOptions = $formConfig->toArray();
            $formOptions['name'] = $formstring;
        }

        // get current role
        $role = Daiquiri_Auth::getInstance()->getCurrentRole();

        // get queues
        $resource = Query_Model_Resource_AbstractQuery::factory();
        if ($resource->hasQueues() && $role !== 'guest') {
            $config = $resource->fetchConfig();
            $queues = $config['userQueues'];
            $defaultQueue = $config['defaultQueue'];
        } else {
            $queues = false;
            $defaultQueue = false;
        }

        // get the form
        $form = new $formConfig->class(array(
            'formOptions' => $formOptions,
            'queues' => $queues,
            'defaultQueue' => $defaultQueue
        ));

        // init errors array
        $errors = array();

        // validate form
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // form is valid, get sql string from functions
                $sql = $form->getQuery();

                // get tablename
                $tablename = $form->getTablename();
                if (empty($tablename)) {
                    $tablename = null;
                }

                // get queue
                $queue = $form->getQueue();

                // prepare sources array
                $sources = array();

                // validate query
                $model = new Query_Model_Query();
                if ($model->validate($sql, false, $tablename, $errors, $sources) !== true) {
                    // set description for form
                    $form->setDescription(implode('; ',$errors));

                    // construct response array
                    return array(
                        'form' => $form,
                        'formOptions' => $formOptions,
                        'status' => 'error',
                        'errors' => array(
                            'form' => $errors
                        )
                    );
                }

                // take a detour to the query plan
                if ($model->canShowPlan()) {
                    // store query, tablename and queue in session
                    Zend_Session::namespaceUnset('query_plan');
                    $ns = new Zend_Session_Namespace('query_plan');
                    $ns->sql = $sql;
                    $ns->tablename = $tablename;

                    if (isset($queue)) {
                        $ns->queue = $queue;
                    } else {
                        $ns->queue = $queue;
                    }

                    $ns->plan = $model->plan($sql, $errors);

                    if (!empty($errors)) {
                        return $this->getModelHelper('CRUD')->validationErrorResponse($form,$errors);
                    }

                    // construct response with redirect to plan
                    $baseurl = Daiquiri_Config::getInstance()->getSiteUrl();
                    return array(
                        'status' => 'plan',
                        'redirect' => $baseurl . '/query/form/plan?form=' . $formstring,
                    );
                } else {
                    // set queue in options array
                    $options = array();
                    if (isset($queue)) {
                        $options['queue'] = $queue;
                    }

                    // submit query
                    $response = $model->query($sql, false, $tablename, $options);

                    if ($response['status'] === 'ok') {
                        // submitting the query was successful
                        return $response;
                    } else {
                        // set description for form
                        $form->setDescription(implode('; ',$response['errors']));

                        // construct response array
                        return array(
                            'form' => $form,
                            'formOptions' => $formOptions,
                            'status' => 'error',
                            'errors' => array(
                                'form' => $response['errors']
                            )
                        );
                    }
                }
            } else {
                return array(
                    'form' => $form,
                    'formOptions' => $formOptions,
                    'status' => 'error',
                    'errors' => $form->getMessages() // the validation errors
                );
            }
        }

        return array(
            'form' => $form,
            'formOptions' => $formOptions,
            'status' => 'form'
        );
    }

    /**
     * Submits a new query query plan to the database.
     * @param string $mail
     * @param array $formParams
     * @return array $response
     */
    public function plan($mail = null, array $formParams = array()) {
        // get query, tablename and queue from session
        $ns = new Zend_Session_Namespace('query_plan');

        // get query model
        $model = new Query_Model_Query();

        // format plan
        if (empty($ns->plan)) {
            $outString = "No query plan returned...";
        } else {
            $outString = "";
            foreach ($ns->plan as $line) {
                $outString .= $line . "\n";
            }
        }

        // get the form for the plan
        $form = new Query_Form_Plan(array(
            'query' => $outString,
            'editable' => $model->canAlterPlan(),
            'mail' => Daiquiri_Config::getInstance()->query->processor->mail->enabled
        ));

        // init errors array
        $errors = array();

        // validate form
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // get values
                $values = $form->getValues();

                if ($model->canAlterPlan()) {
                    // get new plan from form
                    $plan = $values['plan_query'];

                    // validate query plus plan
                    if ($model->validate($ns->sql, $plan, $ns->tablename, $errors) !== true) {
                        if (!empty($errors)) {
                            return $this->getModelHelper('CRUD')->validationErrorResponse($form,$errors);
                        }
                    }
                } else {
                    $plan = false;
                }

                if (empty($mail)) {
                    // submit query
                    $response = $model->query($ns->sql, $plan, $ns->tablename, array("queue" => $ns->queue));
                    if ($response['status'] === 'ok') {
                        return $response;
                    } else {
                        return $this->getModelHelper('CRUD')->validationErrorResponse($form,$response['errors']);
                    }
                } else {
                    // store plan in session
                    if ($plan !== false) {
                        $ns->planString = $plan;
                    } else {
                        $ns->planString = implode('\n', $ns->plan);
                    }

                    // redirect to mail controller action and return
                    $baseurl = Daiquiri_Config::getInstance()->getSiteUrl();
                    return array(
                        'status' => 'redirect',
                        'redirect' => $baseurl . '/query/form/mail'
                    );
                }
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form,$errors);
            }
        }

        return array(
            'form' => $form,
            'status' => 'form',
            'query' => $outString
        );
    }

    /**
     * Submits a new query query plan to the database.
     * @param array $formParams
     * @return array $response
     */
    public function mail(array $formParams = array()) {
        if (Daiquiri_Config::getInstance()->query->processor->mail->enabled != true) {
            throw new Exception('Processor mail is disabled in config.');
        }

        // get query, plan, tablename and queue from session
        $ns = new Zend_Session_Namespace('query_plan');

        // get the current user
        $userModel = new Auth_Model_User();
        $userId = Daiquiri_Auth::getInstance()->getCurrentId();
        if ($userId > 0) {
            // get the user model for getting user details
            $user = $userModel->getResource()->fetchRow($userId);
        } else {
            $user = array();
        }

        // get the form for the plan
        $form = new Query_Form_Mail(array(
            'user' => $user,
            'sql' => $ns->sql,
            'plan' => $ns->planString
        ));

        // validate form
        if (!empty($formParams)) {
            if ($form->isValid($formParams)) {
                // form is valid, get values
                $values = $form->getValues();

                // take the values from the session, NOT from the form
                // DANGER values are not validated in the form and should not be editable
                $sql = $ns->sql;
                $planString = $ns->planString;

                if (empty(Daiquiri_Config::getInstance()->query->processor->mail->admin)) {
                    throw new Exception('No admin email addresses configured');
                } else {
                    $this->getModelHelper('mail')->send('query.plan', array(
                        'to' => Daiquiri_Config::getInstance()->query->processor->mail->admin->toArray(),
                        'sql' => $sql,
                        'plan' => $planString,
                        'firstname' => $values['firstname'],
                        'lastname' => $values['lastname'],
                        'email' => $values['email'],
                        'message' => $values['message']
                    ));
                }

                return array('status' => 'ok');
            } else {
                return $this->getModelHelper('CRUD')->validationErrorResponse($form);
            }
        }

        return array('form' => $form, 'status' => 'form');
    }

}
