<?php
/**
 * GeoMaps Addon for JReviews
 * Copyright (C) 2010-2014 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die;

class AdminJobsalertInstallController extends MyController {

    var $components = array('config', 'admin/admin_packages');

    var $autoLayout = false;

    var $autoRender = false;

    var $addon_name = 'jobsalert';

    var $addon_ref_table;

    function install()
    {
            $Model = new S2Model;
        // Create the marker_icon column in the JReviews categories table
            $query = "CREATE TABLE  `#__jreviews_jobsalert` (
                              `alert_id` int(11) NOT NULL AUTO_INCREMENT,
                              `title` varchar(255) NOT NULL,
                              `user_id` int(11) NOT NULL,
                              `created` datetime NOT NULL,
                              `updated` datetime NOT NULL,
                              `last_alert` datetime NOT NULL,
                              `params` text NOT NULL,
                              `hasnotification` text NOT NULL,
                              `type_alert` tinyint(1) NOT NULL,
                              `type_scheduler` varchar(50) NOT NULL,
                              `status` tinyint(1) NOT NULL DEFAULT '1',
                              PRIMARY KEY (`alert_id`)
                            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
            
            $Model->query($query);

        $response = $this->AdminPackages->upgradeAddon();
        
        return json_encode($response);
    }

    function uninstall()
    {
            $Model = new S2Model;
            $query = "DROP TABLE `#__jreviews_jobsalert`;";
            $Model->query($query);
            $response = AdminPackagesComponent::uninstallPackages(PATH_APP_ADDONS . DS . $this->addon_name . DS . 'cms_compat' . DS . _CMS_NAME);

        return json_encode($response);
    }
}