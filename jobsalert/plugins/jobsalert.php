<?php



defined('MVC_FRAMEWORK') or die;

/**

 * The class name matches the filename with the first letter capitalized  

 */

require_once(JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php');



//require_once(JPATH_BASE.'/components/com_jreviews_addons/jobsalert/models/jobsalert.php');

class JobsalertComponent extends S2Component {



    var $published = false;

    var $name = 'jobsalert';



    /**

     * Limit plugin to run only in specific controller actions

     */

    var $controllerActions = array(

        'categories' => 'search',

        'jobsalert' => array('index', '_save','create_alert'),

        'admin_jobsalert' => '_loadField',

    );



    /**

     * Adds js and css assets to the assets array to be processed later on by the assets helper

     * Need to be set here instead of theme files for pages that can be cached

     *

     */

    function loadAssets($jsGlobals = null) {



        if ($this->c->ajaxRequest)

            return;

        $this->c->assets['head-bottom']['geomaps-plugin'] = $jsGlobals;



        switch ($this->c->name) {

            case 'jobsalert':



                $this->c->assets['css'][] = 'geomaps';



                $this->c->assets['js'][] = 'geomaps';



                break;

        }

    }



    function startup(&$controller) {

        // We only want the plugin to run in the detail page 



        if (!$this->runPlugin($controller)) {

            return false;

        }

        $controller->action = !is_array($this->controllerActions[$controller->name]) ? $this->controllerActions[$controller->name] : $controller->action;

        $ct = JRequest::getString('view') && $controller->action == 'index' ? JRequest::getString('view') : '';

        switch ($ct) {

            case 'create_alert':

                $controller->action = $ct;

                break;

        }
        if ($controller->action == "search" && $controller->name == "categories") {

          
                    $date_notification =Sanitize::getString($controller->params, 'date_notification', false);
                    $alert_scheduler = Sanitize::getString($controller->params, 'alert_scheduler', false);
                    $count_job = Sanitize::getString($controller->params, 'count_job', false);

        if ($date_notification) {

                    $key = 'Listing.created';
                    $controller->Listing->conditions[]  = " $key "  . ' <= ' . $controller->Quote($date_notification);

                    $order == '' and $controller->order = array($key . ' DESC');
                    $controller->Listing->limit =  $count_job? $count_job:10;

            }

        }
        /*if ($controller->action == "search" && $controller->name == "categories") {

          

            $alert_scheduler = Sanitize::getString($controller->params, 'alert_scheduler', false);

        if ($alert_scheduler) {

                $alert_scheduler and $controller->Listing->conditions[] = $this->getScheduler($alert_scheduler, $controller);
                $controller->Listing->limit = 2;

            }

        }*/

        

       

        $this->published = true;

        // Make the controller properties available in other methods inside this class

        $this->c = &$controller;

        if($controller->name=="jobsalert"){

             $js = "jreviews.geomaps = jreviews.geomaps || {};".

                'jreviews.geomaps.google_api_url = "//maps.google.com/maps/api/js?v=3&async=2&sensor=false&key=&language=en&libraries=places";'

                .'jreviews.geomaps.autocomplete = true;'

                .'jreviews.geomaps.autocomplete_country = "'.Sanitize::getString($controller->Config,'geomaps.autocomplete_country').'";'

                .'jreviews.geomaps.mapData = {};

                jreviews.geomaps.fields = {};

                jreviews.geomaps.fields.mapit = "'.Sanitize::getString($controller->Config,'geomaps.mapit_field').'";

                jreviews.geomaps.fields.proximity = "'.Sanitize::getString($controller->Config,'geomaps.advsearch_input').'";

                jreviews.geomaps.fields.lat = "'.Sanitize::getString($controller->Config,'geomaps.latitude').'";

                jreviews.geomaps.fields.lon = "'.Sanitize::getString($controller->Config,'geomaps.longitude').'";

                jreviews.geomaps.fields.default_country = "";

                jreviews.geomaps.fields.address = {};'

                ."jreviews.geomaps.fields.address['address1'] = '".Sanitize::getString($controller->Config,'geomaps.address1')."';

                jreviews.geomaps.fields.address['address2'] = '".Sanitize::getString($controller->Config,'geomaps.address2')."';

                jreviews.geomaps.fields.address['city'] = '".Sanitize::getString($controller->Config,'geomaps.city')."';

                jreviews.geomaps.fields.address['state'] = '".Sanitize::getString($controller->Config,'geomaps.state')."';

                jreviews.geomaps.fields.address['postal_code'] = '".Sanitize::getString($controller->Config,'geomaps.postal_code')."';

                jreviews.geomaps.fields.address['country'] = '".Sanitize::getString($controller->Config,'geomaps.country')."';";

             $this->loadAssets($js);

        }

    }

     

    function getScheduler($startDate, $controller) {

        # Make query filter time

        $timeFilter = false;

        if ($startDate != '') {

            $order = Sanitize::getString($controller->params, 'order');



            $begin_week = date('Y-m-d', strtotime('monday last week'));



            $end_week = date('Y-m-d', strtotime('monday last week +6 days')) . ' 23:59:59';



            $begin_month = date('Y-m-d', mktime(0, 0, 0, date('m'), 1));



            $end_month = date('Y-m-t', strtotime('this month')) . ' 23:59:59';



            $lastseven = date('Y-m-d', strtotime('-1 week'));



            $lasttwelve = date('Y-m-d', strtotime('-2 week'));



            $lastthirty = date('Y-m-d', strtotime('-1 month'));



            $nextseven = date('Y-m-d', strtotime('+1 week')) . ' 23:59:59';



            $nextthirty = date('Y-m-d', strtotime('+1 month')) . ' 23:59:59';



            $key = 'Listing.created';

            $timeFilter = array();



            switch ($startDate) {



                case 'today':

                    $timeFilter = " $key BETWEEN " . $controller->Quote(_TODAY) . ' AND ' . $controller->Quote(_END_OF_TODAY);

                    $order == '' and $controller->order = array($key . ' ASC');

                    break;

                case 'week':

                    $timeFilter = " $key BETWEEN " . $controller->Quote($begin_week) . ' AND ' . $controller->Quote($end_week);

                    $order == '' and $controller->order = array($key . ' ASC');

                    break;

                case 'month':

                    $timeFilter = " $key BETWEEN " . $controller->Quote($begin_month) . ' AND ' . $controller->Quote($end_month);

                    $order == '' and $controller->order = array($key . ' ASC');

                    break;

                case '+7':

                    $timeFilter = " $key BETWEEN " . $controller->Quote(_TODAY) . ' AND ' . $controller->Quote($nextseven);

                    $order == '' and $controller->order = array($key . ' ASC');

                    break;

                case '+30':

                    $timeFilter = " $key BETWEEN " . $controller->Quote(_TODAY) . ' AND ' . $controller->Quote($nextthirty);

                    $order == '' and $controller->order = array($key . ' ASC');

                    break;

                case '-7':

                    $timeFilter = " $key BETWEEN " . $controller->Quote($lastseven) . ' AND ' . $controller->Quote(_END_OF_TODAY);

                    $order == '' and $controller->order = array($key . ' DESC');

                    break;

                case '-14':

                    $timeFilter = " $key BETWEEN " . $controller->Quote($lasttwelve) . ' AND ' . $controller->Quote(_END_OF_TODAY);

                    $order == '' and $controller->order = array($key . ' DESC');

                    break;

                case '-30':

                    $timeFilter = " $key BETWEEN " . $controller->Quote($lastthirty) . ' AND ' . $controller->Quote(_END_OF_TODAY);

                    $order == '' and $controller->order = array($key . ' DESC');

                    break;

            }

        }

        return $timeFilter;

        #end make query filter date 

    }



    function runPlugin(&$controller) {

        

        // Check if running in desired controller/actions

        if (!isset($this->controllerActions[$controller->name])||(!isset($this->controllerActions[$controller->name])&&!Sanitize::getString($controller->params, 'alert_scheduler', false))) {

            return false;

        }

        $actions = !is_array($this->controllerActions[$controller->name]) ?

                array($this->controllerActions[$controller->name]) :

                $this->controllerActions[$controller->name];

        if (!in_array('all', $actions) && !in_array($controller->action, $actions)) {

            return false;

        }

        

        return true;

    }



    /**

     * Event triggered before the Model data is stored to the database

     * @param  Object $model this is the current model used to store the form data

     * @param  Array $data  includes the form posted data

     * @return Array $data  array is returned after being modified

     */

    function plgBeforeSave(&$model, $data) {

        

    }



    /**

     * Event triggered after the Model data is stored to the database

     * Posted data can be found in the $model->data array

     * $mode->data['isNew'] is a boolean allow you to run specific actions for new or edited records

     */

    function plgAfterSave(&$model) {

        

    }



    function plgBeforeRender() {

        

    }

    function plgAfterFind(&$model, $results){

        

        

        return  $results;

    }



}

