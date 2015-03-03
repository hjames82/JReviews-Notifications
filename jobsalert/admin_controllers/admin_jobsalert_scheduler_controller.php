<?php 
defined( 'MVC_FRAMEWORK') or die; 
 
/**
 * The class name is the CamelCased filename 
*/ 
 
class AdminJobsAlertSchedulerController extends MyController 
{ 
   /**
    * Autoloads Models in the array
    * @var array
    */
   var $uses = array('Criteria');

   var $helpers = array('admin/admin_settings'); 
 
   var $components = array('config'); 
 
   function beforeFilter()
   {
      parent::beforeFilter();
   } 
 
   function index()
   {
      // We use an existing method in the Criteria model to get the Listing Types
 
      $listingTypes = $this->Criteria->getSelectList();
      
      // Send the $listingTypes variable to the View
 
      $this->set('listingTypes',$listingTypes);
 
      // Render the View
 
      return $this->render('scheduler','index');
   }
}