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

class Query_Model_Form extends Daiquiri_Model_Abstract {

    public function submit($formstring, array $formParams = array()) {
        // get the formclass
        $formConfig = Daiquiri_Config::getInstance()->query->forms->$formstring;
        if ($formConfig === null || get_Class($formConfig) !== 'Zend_Config') {
            throw new Exception('form options not found');
        } else {
            $formOptions = $formConfig->toArray();
	    $formOptions['name'] = $formstring;
        }

        // get queues
        $resourceString = Daiquiri_Config::getInstance()->query->queue->type;
        $resource = Query_Model_Resource_AbstractQueue::factory($resourceString);
        $queues = array();
        $defaultQueue = false;
        if ($resource::$hasQueues === true) {
            $queues = $resource->fetchQueues();
            $defaultQueue = $resource->fetchDefaultQueue();
            if (empty($defaultQueue[0])) {
                throw new Exception('Queues not setup correctly');
            }
            $defaultQueue = $defaultQueue[0];
            $usrGrp = Daiquiri_Auth::getInstance()->getCurrentRole();

            foreach ($queues as $key => $value) {
                // show only the guest queue for the guest user:
                if ($value['name'] !== "guest" && $usrGrp === "guest") {
                    unset($queues[$key]);
                }

                // remove the guest queue if this is a non guest user
                if ($value['name'] === "guest" && $usrGrp !== "guest") {
                    unset($queues[$key]);
                }
            }
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
            // reinit csrf
            $csrf = $form->getCsrf();
            $csrf->initCsrfToken();

            if ($form->isValid($formParams)) {
                // form is valid, get sql string from functions
                $sql = $form->getQuery();
                $tablename = $form->getTablename();
                $queueId = $form->getQueue();
                //clean from default flag
                $queueId = str_replace("_def", "", $queueId);

                if (empty($tablename)) {
                    $tablename = null;
                }

                $options = array();
                if (!empty($queueId)) {
                    $options['queue'] = $queues[$queueId]['name'];
                }

                //validate query
                $model = new Query_Model_Query();
                if ($model->validate($sql, false, $tablename, $errors) !== true) {
                    $errors = array('form' => $errors);

                    return array(
                        'form' => $form,
                        'csrf' => $csrf->getHash(),
                        'status' => 'error',
                        'errors' => $errors,
                        'formOptions' => $formOptions
                        );
                }

                // take a detour to the query plan
                if ($model->canShowPlan()) {
                    // store query, tablename and queue in session
                    Zend_Session::namespaceUnset('query_plan');
                    $ns = new Zend_Session_Namespace('query_plan');
                    $ns->sql = $sql;
                    $ns->tablename = $tablename;

                    if (isset($options['queue'])) {
                        $ns->queue = $options['queue'];
                    } else {
                        $ns->queue = null;
                    }

                    $ns->plan = $model->plan($sql, $errors);

                    if (!empty($errors)) {
                        $errors = array('form' => $errors);

                        return array(
                            'form' => $form,
                            'csrf' => $csrf->getHash(),
                            'status' => 'error',
                            'errors' => $errors,
                            'formOptions' => $formOptions
                            );
                    }

                    $baseurl = Daiquiri_Config::getInstance()->getSiteUrl();
                    return array(
                        'form' => $form,
                        'csrf' => $csrf->getHash(),
                        'status' => 'plan',
                        'redirect' => $baseurl . '/query/index/plan?form=' . $formstring,
                        'formOptions' => $formOptions
                        );
                } else {
                    // submit query
                    $response = $model->query($sql, false, $tablename, $options);

                    if ($response['status'] === 'ok') {
                        $response['csrf'] = $csrf->getHash();
                        return $response;
                    } else {
                        return array(
                            'form' => $form,
                            'csrf' => $csrf->getHash(),
                            'status' => 'error',
                            'errors' => array('form' => $response['errors']),
                            'formOptions' => $formOptions
                            );
                    }
                }
            } else {
                return array(
                    'form' => $form,
                    'csrf' => $csrf->getHash(),
                    'status' => 'error',
                    'errors' => $form->getMessages(),
                    'formOptions' => $formOptions
                );
            }
        }

        return array(
            'form' => $form,
            'csrf' => $form->getCsrf()->getHash(),
            'status' => 'form',
            'errors' => array(),
            'formOptions' => $formOptions
        );
    }

    public function plan($mail = null, array $formParams = array()) {
        // get query, tablename and queue from session
        $ns = new Zend_Session_Namespace('query_plan');

        // get query model
        $model = new Query_Model_Query();

        // format plan
        $outString = "";
        if (empty($ns->plan)) {
            $outString = "No query plan returned...";
        } else {
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
                // reinit csrf
                $csrf = $form->getElement('plan_csrf');
                $csrf->initCsrfToken();
                
                // get values 
                $values = $form->getValues();

                if ($model->canAlterPlan()) {
                    // get new plan from form
                    $plan = $values['plan_query'];

                    // validate query plus plan
                    if ($model->validate($ns->sql, $plan, $ns->tablename, $errors) !== true) {
                        if (!empty($errors)) {
                            $errors = array('form' => $errors);

                            return array(
                                'form' => $form,
                                'csrf' => $csrf->getHash(),
                                'status' => 'error',
                                'errors' => $errors
                                );
                        }
                    }
                } else {
                    $plan = false;
                }

                if ($mail !== null) {
                    // store plan in session
                    if ($plan !== false) {
                        $ns->planString = $plan;
                    } else {
                        $ns->planString = implode('\n', $ns->plan);
                    }

                    // redirect to mail controller action
                    $baseurl = Daiquiri_Config::getInstance()->getSiteUrl();
                    return array(
                        'form' => $form,
                        'csrf' => $csrf->getHash(),
                        'status' => 'redirect',
                        'redirect' => $baseurl . '/query/index/mail'
                        );
                } else {
                    // submit query
                    $response = $model->query($ns->sql, $plan, $ns->tablename, array("queue" => $ns->queue));

                    if ($response['status'] === 'ok') {
                        $response['csrf'] = $csrf->getHash();
                        return $response;

                    } else {
                        $errors = array('form' => $response['errors']);

                        return array(
                            'form' => $form,
                            'csrf' => $csrf->getHash(),
                            'status' => 'error',
                            'errors' => $errors
                            );
                    }
                }
            } else {
                return array(
                    'form' => $form,
                    'csrf' => $form->getElement('plan_csrf')->getHash(),
                    'status' => 'error',
                    'errors' => $form->getErrors(),
                    'formOptions' => $formOptions
                );
            }
        }

        return array(
            'form' => $form,
            'csrf' => $form->getElement('plan_csrf')->getHash(),
            'status' => 'form'
            );
    }

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
            $user = $userModel->show($userId);
        } else {
            $user = array();
        }

        // get the form for the plan
        $form = new Query_Form_Mail(array(
            'user' => $user,
            'sql' => $ns->sql,
            'plan' => $ns->planString
            ));

        if (!empty($formParams) && $form->isValid($formParams)) {
            // form is valid, get values
            $values = $form->getValues();

            // take the values from the session, NOT from the form
            // DANGER values are not validated in the form and should not be editable
            $values['sql'] = $ns->sql;
            $values['plan'] = $ns->planString;

            // send mail to user who used the contact form
            $mailResource = new Query_Model_Resource_Mail();
            $mailResource->send($values);

            return array('form' => null, 'status' => 'ok');
        }

        return array(
            'form' => $form,
            'status' => 'form',
            'sql' => $ns->sql,
            'plan' => $ns->planString
            );
    }

}
