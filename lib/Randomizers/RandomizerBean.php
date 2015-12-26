<?php
/*
 * Copyright (c) 2015 SugarCRM Inc. Licensed by SugarCRM under the Apache 2.0 license.
 */
namespace SugarRestHarness\Randomizers;

class RandomizerBean extends RandomizerAbstract implements RandomizerInterface
{
    public $data = array();
    
    /**
     * getRandomData()
     *
     * Returns one field for a randomly selected sugar bean of a specified module.
     * You can specify which field you want returned, the default is 'id'.
     *
     * NOTE: this method will NOT select from all possible beans! It will select
     * only from the beans the user you're logging in as can access.
     *
     * @param array $params - a hash of parameters. Must include 'module'. May
     *  include 'field'.
     * @return string  - the value of the field specified from a randomly selected
     *  bean of the type specified.
     */
    public function getRandomData($params)
    {
        if (!isset($params['module']) || empty($params['module'])) {
            throw new RandomDataParamMissing(get_class($this), 'module');
        }
        
        $beanList = $this->populate($params['module']);
        
        if (empty($beanList)) {
            return '';
        }
        
        $randomBean = $beanList[rand(0, (count($beanList) - 1))];
        if (isset($params['field'])) {
            $field = $params['field'];
        } else {
            $field = 'id';
        }
        
        $randomData = '';
        if (!isset($randomBean[$field])) {
            return $randomData;
        }
        
        if (is_string($field)) {
            $randomData = $randomBean[$field];
        } elseif (is_array($field)) {
            $randomData = array();
            foreach ($field as $fieldName) {
                $randomData[$fieldName] = $randomBean[$fieldName];
            }
        }
        
        return $randomData;
    }
    
    
    /**
     * populate()
     *
     * Checks to see if we've already retrieved beans for the passed in module
     * name. If we have, we just return that data. If we haven't, retrieve and
     * then store the result, and finally return it.
     *
     * @param string $moduleName - the name of a sugar module.
     * @return array - an array of nested sugar bean data.
     */
    public function populate($moduleName)
    {
        if (empty($this->data[$moduleName])) {
            $this->data[$moduleName] = $this->sendListRequest($moduleName);
        }
        return $this->data[$moduleName];
    }
    
    
    /**
     * sendListRequest()
     *
     * Sends a REST request using the harness itself to a LIST endpoint. This
     * endpoint should return a JSON array with a 'records' field, which will be
     * a nested array of bean data. This method returns that 'records' array.
     *
     * @param string $module - the name of a sugar module.
     * @return array - an array of nested sugar bean data.
     */
    public function sendListRequest($module)
    {
        $config = array(
            'method' => 'GET',
            'module' => $module,
            'routeMap' => 'list',
        );
        $job = new \SugarRestHarness\Jobs\Generic($config);
        $job->rawResults = $job->connector->makeRequest();
        $results = json_decode($job->rawResults, true);
        return $results['records'];
    }
}
