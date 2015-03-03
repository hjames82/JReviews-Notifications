<?php
/**
 * PaidListings Addon for JReviews
 * Copyright (C) 2010-2014 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class JobsAlertModel extends MyModel  {

	var $name = 'JobsAlert';

	var $useTable = '#__jreviews_jobsalert AS `JobsAlert`';

	var $primaryKey = 'JobsAlert.alert_id';

	var $realKey = 'alert_id';

	var $fields = array('JobsAlert.*');

	 function afterFind($results)
		{

		}
}