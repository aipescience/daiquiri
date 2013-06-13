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
 * Model for the user input into the query system.
 */
class Query_Model_Query extends Daiquiri_Model_Abstract {

    /**
     * Constructor. 
     */
    public function __construct() {
        parent::__construct();

        $this->queue = Query_Model_Resource_AbstractQueue::factory(Daiquiri_Config::getInstance()->query->queue->type);
        $this->processor = Query_Model_Resource_AbstractProcessor::factory(Daiquiri_Config::getInstance()->query->processor);
    }

    public function canShowPlan() {
        $planType = Daiquiri_Config::getInstance()->query->processor->type;
        $planType = "QPROC_" . strtoupper($planType);

        if ($this->processor->supportsPlanType($planType) and
                ($planType === "QPROC_INFOPLAN" or $planType === "QPROC_ALTERPLAN")) {
            return true;
        } else {
            return false;
        }
    }

    public function canAlterPlan() {
        $planType = Daiquiri_Config::getInstance()->query->processor->type;
        $planType = "QPROC_" . strtoupper($planType);

        if ($this->processor->supportsPlanType($planType) and
                ($planType === "QPROC_ALTERPLAN")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validates a plain text query.
     * @param string $sql
     * @param string $plan
     * @param string $table
     * @param array &$errors
     * @return TRUE if valid, FALSE if not. 
     */
    public function validate($sql, $plan = false, $table, array &$errors) {
        // init error array
        $errors = array();

        // check if there is any input
        if (empty($sql)) {
            $errors['sqlError'] = 'No SQL input given.';
            return false;
        }

        // process sql string
        if ($plan === false) {
            if ($this->processor->validateQuery($sql, $table, $errors) !== true) {
                return false;
            }
        }

        if ($plan !== false and $this->processor->supportsPlanType("QPROC_ALTERPLAN") === true) {
            if ($this->processor->validatePlan($plan, $table, $errors) !== true) {
                return false;
            }
        }

        return true;
    }

    /**
     * Querys the system with a plain text query.
     * @param string $sql
     * @param string $plan
     * @param string $table
     * @param array $options for further options that are handeled by the queue
     * @return object 
     */
    public function query($sql, $plan = false, $table, $options = array()) {
        // init error array
        $errors = array();

        // check if there is a name for the new table
        if (empty($table)) {
            $tablename = false;
        } else {
            $tablename = $table;
        }

        // get group of the user
        $usrGrp = Daiquiri_Auth::getInstance()->getCurrentRole();
        if ($usrGrp !== null) {
            $options['usrGrp'] = $usrGrp;
        } else {
            $options['usrGrp'] = "guest";
        }

        //if plan type direct, obtain query plan
        if ($this->processor->supportsPlanType("QPROC_SIMPLE") === true) {
            $plan = $this->processor->getPlan($sql, $errors);

            if (!empty($errors)) {
                return array('status' => 'error', 'errors' => $errors);
            }
        } else {
            //if plan type is AlterPlan and no plan is available, throw error
            if ($this->processor->supportsPlanType("QPROC_ALTERPLAN") === true and $plan === false) {
                $errors['planError'] = 'Query plan required. If you end up here, something went badly wrong';
                return array('status' => 'error', 'errors' => $errors);
            }
        }

        // process sql string
        $job = $this->processor->query($sql, $errors, $plan, $tablename);
        if (!empty($errors)) {
            return array('status' => 'error', 'errors' => $errors);
        }

        // before submission, see if user has enough quota
        if ($this->_checkQuota($this->queue, $usrGrp)) {
            $errors['quotaError'] = 'Your quota has been reached. Drop some tables to free space or contact the administrators';
            return array('status' => 'error', 'errors' => $errors);
        }

        // submit job
        $statusId = $this->queue->submitJob($job, $errors, $options);
        if (!empty($errors)) {
            return array('status' => 'error', 'errors' => $errors);
        }

        // return with success
        return array(
            'status' => 'ok',
            'job' => $job
        );
    }

    /**
     * Obtrains the query plan
     * @param string $sql
     * @return object 
     */
    public function plan($sql, array &$errors) {
        // init error array
        $errors = array();

        $plan = $this->processor->getPlan($sql, $errors);

        return $plan;
    }

    //returns true if quota is reached
    private function _checkQuota($resource, $usrGrp) {
        $dbStatData = $resource->fetchDatabaseStats();

        $usedSpace = $dbStatData['db_size'] * 1024 * 1024;

        $quotaStr = Daiquiri_Config::getInstance()->query->quota->$usrGrp;

        //if no quota given, let them fill the disks!
        if (empty($quotaStr)) {
            return false;
        }

        //parse the quota to resolve KB, MB, GB, TB, PB, EB...
        preg_match("/([0-9.]+)\s*([KMGTPEBkmgtpeb]*)/", $quotaStr, $parse);
        $quota = (float) $parse[1];
        $unit = $parse[2];

        switch (strtoupper($unit)) {
            case 'EB':
                $quota *= 1024;
            case 'PB':
                $quota *= 1024;
            case 'TB':
                $quota *= 1024;
            case 'GB':
                $quota *= 1024;
            case 'MB':
                $quota *= 1024;
            case 'KB':
                $quota *= 1024;
            default:
                break;
        }

        if ($usedSpace > $quota) {
            return true;
        } else {
            return false;
        }
    }

}
