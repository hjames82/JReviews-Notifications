<?php







defined('MVC_FRAMEWORK') or die;







/**



 * The class name matches the file name with the first letter capitalized



 */

 JTable::addIncludePath(JPATH_SITE.DS.'components'.DS.'com_community'.DS.'tables');

require_once(JPATH_SITE.DS.'components'.DS.'com_community'.DS.'libraries'.DS.'parameter.php');

class JobsAlertController extends MyController {







    var $uses = array('paid_coupon', 'jobsalert', 'article', 'user', 'menu', 'claim', 'category', 'jreviews_category', 'review', 'favorite', 'field', 'field_option', 'criteria');



    var $helpers = array('routes', 'libraries', 'html', 'text', 'assets', 'form', 'jreviews', 'custom_fields', 'paginator');



    var $components = array('config', 'access', 'everywhere');



    var $autoRender = false;



    var $autoLayout = false;



    var $radius_field = 'jr_radius';



    var $max_radius = 20;







    // Need to return object by reference for PHP4



    function &getObserverModel() {



        return $this->Listing;



    }







    function listings($Listings) {







        if (Sanitize::getString($this->params, 'action') == 'xml') {



            $access = $this->Access->getAccessLevels();







            $feed_filename = S2_CACHE . 'views' . DS . 'jreviewsfeed_' . md5($access . $this->here) . '.xml';







            $this->Feeds->useCached($feed_filename, 'listings');



        }







        $this->name = 'categories';   // Required for assets helper







        $this->autoRender = false;







        $action = Sanitize::paranoid($this->action);







        $dir_id = str_replace(array('_', ' '), array(',', ''), Sanitize::getString($this->params, 'dir'));







        $cat_id = Sanitize::getString($this->params, 'cat');







        $criteria_id = Sanitize::getString($this->params, 'criteria');







        $user_id = Sanitize::getInt($this->params, 'user', $this->_user->id);







        $index = Sanitize::getString($this->params, 'index');







        $sort = Sanitize::getString($this->params, 'order');







        $listview = Sanitize::getString($this->params, 'listview');







        $tmpl_suffix = Sanitize::getString($this->params, 'tmpl_suffix');







        $order_field = Sanitize::getString($this->Config, 'list_order_field');







        $order_default = Sanitize::getString($this->Config, 'list_order_default');











        if ($sort == '' && $order_field != '' && in_array($this->action, array('category', 'alphaindex', 'search', 'custom'))) {







            $sort = $order_field;



        } elseif ($sort == '') {







            $sort = $order_default;



        }







        $this->params['default_order'] = $order_default;







        $menu_id = Sanitize::getInt($this->params, 'menu', Sanitize::getString($this->params, 'Itemid'));







        $query_listings = true;







        // Check if it can be disabled for parent category pages when listings are disabled







        $total_special = Sanitize::getInt($this->data, 'total_special', 0);







        $listings = array();







        $parent_categories = array();







        $count = 0;







        $conditions = array();







        $joins = array();







        if ($action == 'category' || ($action == 'search' && is_numeric($cat_id) && $cat_id > 0)) {



            $parent_categories = $this->Category->findParents($cat_id);







            if ($action == 'category' && !$parent_categories) {



                return cmsFramework::raiseError(404, JText::_('JGLOBAL_CATEGORY_NOT_FOUND'));



            }







            if ($parent_categories) {



                $category = end($parent_categories); // This is the current category







                if (!$category['Category']['published'] || !$this->Access->isAuthorized($category['Category']['access'])) {



                    return $this->render('elements', 'login');



                }







                $dir_id = $this->params['dir'] = $category['Directory']['dir_id'];







                $categories = $this->Category->findChildren($cat_id, $category['Category']['level']);







                # Check the listing type of all subcategories and if it's the same one apply the overrides to the parent category as well



                $overrides = array();







                if (count($categories) > 1 && $category['Category']['criteria_id'] == 0 && !empty($categories)) {







                    foreach ($categories AS $tmp_cat) {







                        if ($tmp_cat['Category']['criteria_id'] > 0 && !empty($tmp_cat['ListingType']['config'])) {







                            $overrides[$tmp_cat['Category']['criteria_id']] = $tmp_cat['ListingType']['config'];



                        }



                    }







                    if (count($overrides) == 1) {







                        $category['ListingType']['config'] = array_shift($overrides);



                    }



                }



            }







            # Override global configuration







            $order_field_override = '';







            $order_default_override = -1;







            if (isset($category)) {



                isset($category['ListingType']) and $this->Config->override($category['ListingType']['config']);







                if (!is_array($category['ListingType']['config'])) {







                    $category['ListingType']['config'] = json_decode($category['ListingType']['config'], true);



                }







                $order_field_override = Sanitize::getString($category['ListingType']['config'], 'list_order_field');







                $order_default_override = Sanitize::getString($category['ListingType']['config'], 'list_order_default');



            }







            if ($order_field_override != '') {







                $sort_default = $order_field_override;



            } elseif ($order_default_override != -1) {







                $sort_default = $order_default_override;



            } elseif ($order_field != '') {







                $sort_default = $order_field;



            } else {







                $sort_default = $order_default;



            }







            $this->params['default_order'] = $sort_default;







            $sort = Sanitize::getString($this->params, 'order', $sort_default);







            // Set default order for pagination



            $sort == '' and $sort = $order_default;



        }







        # Set the theme layout and suffix







        $this->Theming->setSuffix(array('categories' => $parent_categories));







        $this->Theming->setLayout(array('categories' => $parent_categories));







        if ($this->action == 'category' && isset($category) && !empty($category) && (!$this->Access->isAuthorized($category['Category']['access']) || !$category['Category']['published'])



            ) {



            return $this->render('elements', 'login');



    }







        # Get listings



        # Modify and perform database query based on lisPage type







    switch ($action) {



        case 'alphaindex':



//                    $index = isset($index{0}) ? $index{0} : '';



        $conditions[] = ($index == '0' ? 'Listing.' . EverywhereComContentModel::_LISTING_TITLE . ' REGEXP "^[0-9]"' : 'Listing.' . EverywhereComContentModel::_LISTING_TITLE . ' LIKE ' . $this->Quote($index . '%'));







        break;



    }







    $children = $this->action == 'category' ? $this->Config->list_show_child_listings : true;







    $Listings->addCategoryFiltering($conditions, $this->Access, compact('children', 'cat_id', 'dir_id', 'criteria_id'));







    $Listings->addListingFiltering($conditions, $this->Access, compact('user_id'));



    $queryData = array(



        /* 'fields' they are set in the model */



        'joins' => $joins,



        'conditions' => $conditions,



        'limit' => $this->limit,



        'offset' => $this->offset



        );











        # Modify query for correct ordering. Change FIELDS, ORDER BY and HAVING BY directly in Listing Model variables



    if ($this->action != 'custom' || ($this->action == 'custom' && empty($Listing->order))) {



        $Listings->processSorting($action, $sort);



    }







        // This is used in Listings model to know whether this is a list page to remove the plugin tags



    $Listings->controller = 'categories';







        // Check if review scope checked in advancd search



    $scope = explode('_', Sanitize::getString($this->params, 'scope'));







    if ($this->action == 'search' && in_array('reviews', $scope)) {



        $queryData['joins'][] = "LEFT JOIN #__jreviews_comments AS Review ON Listing.id = Review.pid AND Review.published = 1 AND Review.mode = 'com_content'";







            $queryData['group'][] = "Listing.id"; // Group By required due to one to many relationship between listings => reviews table



        }



        $query_listings and $listings = $Listings->findAll($queryData);







        # If only one result then redirect to it



        if ($this->Config->search_one_result && count($listings) == 1 && $this->action == 'search' && $this->page == 1) {



            $listing = array_shift($listings);







            $url = cmsFramework::makeAbsUrl($listing['Listing']['url'], array('sef' => true));







            cmsFramework::redirect($url);



        }







        # Prepare Listing count query







        if (in_array($action, array('category')) || $action != 'favorites') {



            unset($queryData['joins']['User'], $queryData['joins']['Claim']);







            if ($this->action == 'search' && in_array('reviews', $scope)) {



                $queryData['joins']['Review'] = "LEFT JOIN #__jreviews_comments AS Review ON Listing.id = Review.pid AND Review.published = 1 AND Review.mode = 'com_content'";



            }



        }







        // Need to add user table join for author searches







        if (isset($this->params['author'])) {



            $queryData['joins'][] = "LEFT JOIN #__users AS User ON User." . UserModel::_USER_ID . " = Listing." . EverywhereComContentModel::_LISTING_USER_ID;



        }







        if ($query_listings && !isset($Listing->count)) {



            if (in_array($this->action, array('favorites', 'mylistings'))) {







                $queryData['session_cache'] = false;



            }







            $count = $Listings->findCount($queryData, ($this->action == 'search' && in_array('reviews', $scope)) ? 'DISTINCT Listing.id' : '*');



        } elseif (isset($Listings->count)) {



            $count = $Listings->count;



        }







        if ($total_special > 0 && $total_special < $count) {



            $count = Sanitize::getInt($this->data, 'total_special');



        }







        # Get directory info for breadcrumb if dir id is a url parameter



        $directory = array();







        if (is_numeric($dir_id)) {



            $directory = $this->Directory->findRow(array(



                'fields' => array(



                    'Directory.id AS `Directory.dir_id`',



                    'Directory.title AS `Directory.slug`',



                    'Directory.desc AS `Directory.title`'



                    ),



                'conditions' => array('Directory.id = ' . $dir_id)



                ));



        }







        /*         * ****************************************************************



         * Process page title and description



         * ***************************************************************** */







        $name_choice = constant('UserModel::_USER_' . strtoupper($this->Config->name_choice));







        $page = $this->createPageArray($menu_id);







        switch ($action) {



            case 'alphaindex':







            $title = isset($directory['Directory']) ? Sanitize::getString($directory['Directory'], 'title', '') : '';







            $page['title'] = ($title != '' ? $title . ' - ' . ($index == '0' ? '0-9' : $index) : ($index == '0' ? '0-9' : $index));







            break;







            case 'list':



            case 'search':







            $this->__seo_fields($page, $cat_id);







            break;







            case 'featured':



            case 'latest':



            case 'mostreviews':



            case 'popular':



            case 'toprated':



            case 'topratededitor':







            break;







            default:







            $page['title'] = "alert";







            break;



        }







        # Category ids to be used for ordering list



        $cat_ids = array();







        if (in_array($action, array('search', 'category'))) {



            $cat_ids = $cat_id;



        } elseif (!empty($categories)) {



            $cat_ids = implode(',', array_keys($categories));



        }







        $this->set(



            array(



                'Config' => $this->Config,



                'User' => $this->_user,



                'subclass' => 'listing',



                'page' => $page,



                'directory' => $directory,



                    'category' => isset($category) ? $category : array(), // Category list



                    'categories' => isset($categories) ? $categories : array(),



                    'parent_categories' => $parent_categories, // Used for breadcrumb



                    'cat_id' => $cat_id,



                    'listings' => $listings,



                    'pagination' => array('total' => $count))



            );



        //return $listing;

        if (empty($listings)) {



            return false;



        }



        return $listings;



    }







    function cronAlerts() {



       // Need to check the secret matches, otherwise don' do anything

      

        $secret = Sanitize::getString($this->Config,'cron_secret');



        if(strcmp(Sanitize::getString($this->params,'secret'), $secret) !== 0) {



            die(JreviewsLocale::getPHP('ACCESS_DENIED'));



        }



        $alerts = $this->getAlerts(true);

        if (empty($alerts))



            return false;



        require_once(JPATH_BASE.'/components/com_easydiscuss/helpers/helper.php');



        $this->JobsAlert = ClassRegistry::getClass('JobsAlertModel');

       

        foreach ($alerts as $key => $alert) {



            unset($listings);



            $tmpParams = $this->params;



            $alert = $alert['JobsAlert'];



            $params = json_decode($alert['params']);



            $JobsAlertModel = ClassRegistry::getClass('JobsAlertModel');



            $url = array();



            foreach ($params->filter as $k => $filter) {



                //$k= ($k=='search_query_type')?'query':$k;

                if (is_array($filter)) {



                    $filter = implode('_', $filter);



                }



                $this->params[$k] = $filter;



                $url[] = ($k=='search_query_type')?"query=" . $filter:"$k=" . $filter;



            }



            



            if (isset($params->criteria)) {



                $this->params['criteria'] = Sanitize::getString($params, 'criteria');



            }



            isset($alert['type_scheduler']) and $url['alert_scheduler'] = 'alert_scheduler=' . $alert['type_scheduler'];



            $this->params['typescheduler'] = $alert['type_scheduler'];



            $listings = $this->search($alert);
            if ($listings) {

              

                $this->addFilterListing($alert,$listings);



                $url = implode('&', $url);



                $criteria_id = $params->criteria;



                $url = cmsFramework::route("option=com_jreviews&url=search-results&criteria=$criteria_id&$url");



                $alert['url'] = $url;



                $this->sendNotification($alert, $listings); 



            } else {



        



            }



            $this->params = $tmpParams;



        }



        return true;



    }



    function addFilterListing($alert,$listings){



     $jsonFilter = json_decode($alert['hasnotification']);



     !is_array($jsonFilter) and $jsonFilter= array();



     foreach ($listings as $key =>$listing){



      (!in_array($key,$jsonFilter)) and $jsonFilter[] = $key;



  }



  $this->data['JobsAlert']['alert_id'] = $alert['alert_id'];



  $this->data['JobsAlert']['hasnotification'] = json_encode($jsonFilter);



  $this->JobsAlert->store($this->data);



}



function search($alert) {



    $Listings = $this->Listing;



    $Listings->conditions = array();







    $lat = $lon = 0;



    (isset($this->params['jr_latitude'])&&$this->params['jr_latitude'])and $lat = $this->params['jr_latitude'];



    (isset($this->params['jr_longitude'])&&$this->params['jr_longitude'])and $lon = $this->params['jr_longitude'];



    if($lon!=0 && $lat!=0){



        $search_address_field = Sanitize::getString($this->Config,'geomaps.advsearch_input');



        $this->jr_lat = Sanitize::getString($this->Config,'geomaps.latitude');



        $this->jr_lon = Sanitize::getString($this->Config,'geomaps.longitude');



        unset(



            $this->params[$search_address_field],



            $this->params[$this->jr_lat],



            $this->params[$this->jr_lon]



            );







        // Create a square around the center to limite the number of rows processed in the zip code table



        // http://www.free-zipcodes.com/



        // http://www.mysqlconf.com/mysql2008/public/schedule/detail/347



        $this->distance_in = 'mi';



        $default_radius = Sanitize::getString($this->Config,'geomaps.radius');



        $center = array('lat'=>$lat,'lon'=>$lon);



        $radius = min(Sanitize::getFloat($this->params,$this->radius_field,$default_radius),$this->max_radius);



        $degreeDistance = $this->distance_in == 'mi' ? 69.172 : 40076/360;







        $lat_range = $radius/$degreeDistance;







        $lon_range = $radius/abs(cos($center['lat']*pi()/180)*$degreeDistance);







        $min_lat = $center['lat'] - $lat_range;







        $max_lat = $center['lat'] + $lat_range;







        $min_lon = $center['lon'] - $lon_range;







        $max_lon = $center['lon'] + $lon_range;







        $squareArea = "`Field`.{$this->jr_lat} BETWEEN $min_lat AND $max_lat AND `Field`.{$this->jr_lon} BETWEEN $min_lon AND $max_lon";



        $Listings->conditions[] = $squareArea;



    }







        $urlSeparator = "_"; //Used for url parameters that pass something more than just a value







        $simplesearch_custom_fields = 1; // Search custom fields in simple search







        $simplesearch_query_type = Sanitize::getString($this->Config, 'search_simple_query_type', 'all'); // any|all







        $min_word_chars = 3; // Only words with min_word_chars or higher will be used in any|all query types







        $category_ids = '';







        $criteria_ids = Sanitize::getString($this->params, 'criteria');







        $dir_id = Sanitize::getString($this->params, 'dir', '');







        $accepted_query_types = array('any', 'all', 'exact');



        



        $query_type = Sanitize::getString($this->params, 'search_query_type');



        



        $keywords = urldecode(Sanitize::getString($this->params, 'keywords'));







        $scope = Sanitize::getString($this->params, 'scope');







        $author = urldecode(Sanitize::getString($this->params, 'author'));







        $ignored_search_words = $keywords != '' ? cmsFramework::getIgnoredSearchWords() : array();







        if (!in_array($query_type, $accepted_query_types)) {



            $query_type = 'all'; // default value if value used is not recognized



        }







        // Build search where statement for standard fields



        $wheres = array();







        // Transform scope into DB table columns





        $scope = array_filter(explode($urlSeparator, $scope));







        foreach ($scope AS $key => $term) {



            switch ($term) {







                case 'title':



                case 'introtext':



                case 'fulltext':







                $scope[$key] = $Listings->_SIMPLE_SEARCH_FIELDS[$term];







                break;







                default:







                unset($scope[$term]);







                break;



            }



        }







        # SIMPLE SEARCH



        if ($keywords != '' && empty($scope)) {



            $scope = $Listings->_SIMPLE_SEARCH_FIELDS;







            $words = array_unique(explode(' ', $keywords));







            // Include custom fields







            if ($simplesearch_custom_fields == 1) {



                $fields = $this->Field->getTextBasedFieldNames();



                // TODO: find out which fields have predefined selection values to get the searchable values instead of reference



                // Merge standard fields with custom fields



                $scope = array_merge($scope, $fields);



            }







            $whereFields = array();







            foreach ($words as $word) {



                $whereContentFields = array();







                if (strlen($word) >= $min_word_chars && !in_array($word, $ignored_search_words)) {



                    $word = urldecode(trim($word));







                    foreach ($scope as $contentfield) {



                        $whereContentFields[] = " $contentfield LIKE " . $this->QuoteLike($word);



                    }







                    if (!empty($whereContentFields)) {







                        $whereFields[] = " (" . implode(') OR (', $whereContentFields) . ')';



                    }



                }



            }







            if (!empty($whereFields)) {



                $wheres[] = " (" . implode(($simplesearch_query_type == 'all' ? ') AND (' : ') OR ('), $whereFields) . ')';



            }



        } else {







            # ADVANCED SEARCH



            // Process core content fields and reviews



            if ($keywords != '' && !empty($scope)) {



                $allowedContentFields = array('title', 'introtext', 'fulltext', 'reviews', 'metakey');







                // Only add meta keywords if the db column exists







                if (EverywhereComContentModel::_LISTING_METAKEY != '') {



                    $scope['metakey'] = EverywhereComContentModel::_LISTING_METAKEY;



                }







                switch ($query_type) {



                    case 'exact':







                    foreach ($scope as $scope_key => $contentfield) {



                        if (in_array($scope_key, $allowedContentFields)) {



                            $w = array();







                            if ($contentfield == 'reviews') {



                                $w[] = " Review.comments LIKE " . $this->QuoteLike($keywords);







                                $w[] = " Review.title LIKE " . $this->QuoteLike($keywords);



                            } else {







                                $w[] = " $contentfield LIKE " . $this->QuoteLike($keywords);



                            }







                            $whereContentOptions[] = "\n" . implode(' OR ', $w);



                        }



                    }







                    $wheres[] = implode(' OR ', $whereContentOptions);







                    break;







                    case 'any':



                    case 'all':



                    default:







                    $words = array_unique(explode(' ', $keywords));







                    $whereFields = array();







                    foreach ($scope as $scope_key => $contentfield) {



                        if (in_array($scope_key, $allowedContentFields)) { {



                            $whereContentFields = array();







                            $whereReviewComment = array();







                            $whereReviewTitle = array();







                            foreach ($words as $word) {



                                if (strlen($word) >= $min_word_chars && !in_array($word, $ignored_search_words)) {



                                    if ($contentfield == 'reviews') {



                                        $whereReviewComment[] = "Review.comments LIKE " . $this->QuoteLike($word);







                                        $whereReviewTitle[] = "Review.title LIKE " . $this->QuoteLike($word);



                                    } else {







                                        $whereContentFields[] = "$contentfield LIKE " . $this->QuoteLike($word);



                                    }



                                }



                            }



                            if ($contentfield == 'reviews') {



                                if (!empty($whereReviewTitle)) {



                                    $whereFields[] = "\n(" . implode(($query_type == 'all' ? ') AND (' : ') OR ('), $whereReviewTitle) . ")";



                                }







                                if (!empty($whereReviewComment)) {



                                    $whereFields[] = "\n(" . implode(($query_type == 'all' ? ') AND (' : ') OR ('), $whereReviewComment) . ")";



                                }



                            } elseif (!empty($whereContentFields)) {



                                $whereFields[] = "\n(" . implode(($query_type == 'all' ? ') AND (' : ') OR ('), $whereContentFields) . ")";



                            }



                        }



                    }







                    if (!empty($whereFields)) {



                        $wheres[] = '(' . implode(') OR (', $whereFields) . ')';



                    }



                }







                break;



            }



        } else {







            $scope = array();



        }







            // Process author field



        if ($author && $this->Config->search_item_author) {



            $wheres[] = "



            (



                User." . UserModel::_USER_REALNAME . " LIKE " . $this->QuoteLike($author) . " OR



                User." . UserModel::_USER_ALIAS . " LIKE " . $this->QuoteLike($author) . " OR



                Listing." . EverywhereComContentModel::_LISTING_AUTHOR_ALIAS . " LIKE " . $this->QuoteLike($author) .



                ")";



}







            // Process custom fields



$query_string = Sanitize::getString($this->passedArgs, 'url');







$customFields = $this->Field->getFieldNames();







if ($tag = Sanitize::getVar($this->params, 'tag')) {



    $this->click2search = true;







    $click2search_field = 'jr_' . $tag['field'];







    if (!in_array($click2search_field, $customFields)) {



        return cmsFramework::raiseError(404, s2Messages::submitErrorGeneric());



    }







    if ($menu_id = Sanitize::getInt($this->params, 'Itemid')) {



        $menuParams = $this->Menu->getMenuParams($menu_id);







        $action = Sanitize::getString($menuParams, 'action');







                    // If it's an adv. search menu and click2search url, use the menu criteria id



        switch ($action) {



            case '2':



            !isset($this->params['cat']) && $this->params['cat'] = $menuParams['catid'];







            break;



            case '11':







            $this->params['criteria'] = $menuParams['criteriaid'];







            break;







            default:







            break;



        }



    }







                // Field value underscore fix: remove extra menu parameter not removed in routes regex



    $tag['value'] = preg_replace(array('/_m[0-9]+$/', '/_m$/', '/_$/'), '', $tag['value']);







                // Below is included fix for dash to colon change in J1.5



    $query_string = 'jr_' . $tag['field'] . _PARAM_CHAR . str_replace(':', '-', $tag['value']) . '/' . $query_string;



}







$url_array = explode("/", $query_string);







            // Include external parameters for custom fields - this is required for components such as sh404sef





foreach ($this->params AS $varName => $varValue) {



    if (substr($varName, 0, 3) == "jr_" && false === array_search($varName . _PARAM_CHAR . $varValue, $url_array)) {



        $url_array[] = $varName . _PARAM_CHAR . $varValue;



    }



}







            /*             * **************************************************************************



             * First pass of url params to get all field names and then find their types



             * ************************************************************************** */







            $fieldNameArray = array();







            foreach ($url_array as $url_param) {



                // Fixes issue where colon separating field name from value gets converted to a dash by Joomla!



                if (preg_match('/^(jr_[a-z0-9]+)-([\S\s]*)/', $url_param, $matches)) {



                    $key = $matches[1];



                    $value = $matches[2];



                } else {



                    $param = explode(":", $url_param);



                    $key = $param[0];



                    $value = Sanitize::getVar($param, '1', null); // '1' is the key where the value is stored in $param



                }







                if (substr($key, 0, 3) == "jr_" && in_array($key, $customFields) && !is_null($value) && $value != '') {



                    $fieldNameArray[$key] = $value;



                }



            }



            // Find out the field type to determine whether it's an AND or OR search







            if (!empty($fieldNameArray)) {



                $query = '



                SELECT



                name, type



                FROM



                        #__jreviews_fields



                WHERE



                name IN (' . $this->Quote(array_keys($fieldNameArray)) . ')'



                ;







                $fieldTypesArray = $this->Field->query($query, 'loadAssocList', 'name');



            }

            

            $OR_fields = array("select", "radiobuttons"); // Single option







            $AND_fields = array("selectmultiple", "checkboxes", "relatedlisting"); // Multiple option



            foreach ($fieldNameArray AS $key => $value) {



                $searchValues = explode($urlSeparator, $value);







                $fieldType = $fieldTypesArray[$key]['type'];







                // Process values with separator for multiple values or operators. The default separator is an underscore



                if (substr_count($value, $urlSeparator)) {







                    // Check if it is a numeric or date value



                    $allowedOperators = array("equal" => '=', "higher" => '>=', "lower" => '<=', "between" => 'between');



                    $operator = $searchValues[0];







                    $isDate = false;



                    if ($searchValues[1] == "date") {



                        $isDate = true;



                    }







                    if (in_array($operator, array_keys($allowedOperators)) && (is_numeric($searchValues[1]) || $isDate)) {



                        if ($operator == "between") {



                            if ($isDate) {



                                @$searchValues[1] = low($searchValues[2]) == 'today' ? _TODAY : $searchValues[2];



                                @$searchValues[2] = low($searchValues[3]) == 'today' ? _TODAY : $searchValues[3];



                            }







                            $low = is_numeric($searchValues[1]) ? $searchValues[1] : $this->Quote($searchValues[1]);



                            $high = is_numeric($searchValues[2]) ? $searchValues[2] : $this->Quote($searchValues[2]);



                            $wheres[] = "\n" . $key . " BETWEEN " . $low . ' AND ' . $high;



                        } else {



                            if ($searchValues[1] == "date") {



                                $searchValues[1] = low($searchValues[2]) == 'today' ? _TODAY : $searchValues[2];



                            }



                            $value = is_numeric($searchValues[1]) ? $searchValues[1] : $this->Quote($searchValues[1]);



                            $wheres[] = "\n" . $key . $allowedOperators[$operator] . $value;



                        }



                    } else {



                        // This is a field with pre-defined options



                        $whereFields = array();







                        if (isset($tag) && $key = 'jr_' . $tag['field']) {



                            // Field value underscore fix



                            if (in_array($fieldType, $OR_fields)) {



                                $whereFields[] = " $key = '*" . $this->Quote('*' . urldecode($value) . '*');



                            } else {



                                $whereFields[] = " $key LIKE " . $this->Quote('%*' . urldecode($value) . '*%');



                            }



                        } elseif (!empty($searchValues)) {



                            foreach ($searchValues as $value) {



                                $searchValue = urldecode($value);



                                if (in_array($fieldType, $OR_fields)) {



                                    $whereFields[] = " $key = " . $this->Quote('*' . $value . '*');



                                } else {



                                    $whereFields[] = " $key LIKE " . $this->Quote('%*' . $value . '*%');



                                }



                            }



                        }







                        if (in_array($fieldType, $OR_fields)) { // Single option field



                            $wheres[] = '(' . implode(') OR (', $whereFields) . ')';



                        } else { // Multiple option field



                            $wheres[] = '(' . implode(') AND (', $whereFields) . ')';



                        }



                    }



                } else {







                    $value = urldecode($value);







                    $whereFields = array();







                    switch ($fieldType) {







                        case in_array($fieldType, $OR_fields):







                        $whereFields[] = " $key = " . $this->Quote('*' . $value . '*');







                        break;







                        case in_array($fieldType, $AND_fields):







                        $whereFields[] = " $key LIKE " . $this->Quote('%*' . $value . '*%');







                        break;







                        case 'decimal':







                        $whereFields[] = " $key = " . (float) $value;







                        break;







                        case 'integer':







                        $whereFields[] = " $key = " . (int) $value;







                        break;







                        case 'date':







                        $order = Sanitize::getString($this->params, 'order');







                        $begin_week = date('Y-m-d', strtotime('monday last week'));







                        $end_week = date('Y-m-d', strtotime('monday last week +6 days')) . ' 23:59:99';







                        $begin_month = date('Y-m-d', mktime(0, 0, 0, date('m'), 1));







                        $end_month = date('Y-m-t', strtotime('this month')) . ' 23:59:99';







                        $lastseven = date('Y-m-d', strtotime('-1 week'));







                        $lastthirty = date('Y-m-d', strtotime('-1 month'));







                        $nextseven = date('Y-m-d', strtotime('+1 week')) . ' 23:59:99';







                        $nextthirty = date('Y-m-d', strtotime('+1 month')) . ' 23:59:99';







                        switch ($value) {







                            case 'future':



                            $whereFields[] = " $key >= " . $this->Quote(_TODAY);



                            $order == '' and $Listings->order = array($key . ' ASC');



                            break;



                            case 'today':



                            $whereFields[] = " $key BETWEEN " . $this->Quote(_TODAY) . ' AND ' . $this->Quote(_END_OF_TODAY);



                            $order == '' and $Listings->order = array($key . ' ASC');



                            break;



                            case 'week':



                            $whereFields[] = " $key BETWEEN " . $this->Quote($begin_week) . ' AND ' . $this->Quote($end_week);



                            $order == '' and $Listings->order = array($key . ' ASC');



                            break;



                            case 'month':



                            $whereFields[] = " $key BETWEEN " . $this->Quote($begin_month) . ' AND ' . $this->Quote($end_month);



                            $order == '' and $Listings->order = array($key . ' ASC');



                            break;



                            case '+7':



                            $whereFields[] = " $key BETWEEN " . $this->Quote(_TODAY) . ' AND ' . $this->Quote($nextseven);



                            $order == '' and $Listings->order = array($key . ' ASC');



                            break;



                            case '+30':



                            $whereFields[] = " $key BETWEEN " . $this->Quote(_TODAY) . ' AND ' . $this->Quote($nextthirty);



                            $order == '' and $Listings->order = array($key . ' ASC');



                            break;



                            case '-7':



                            $whereFields[] = " $key BETWEEN " . $this->Quote($lastseven) . ' AND ' . $this->Quote(_END_OF_TODAY);



                            $order == '' and $Listings->order = array($key . ' DESC');



                            break;



                            case '-30':



                            $whereFields[] = " $key BETWEEN " . $this->Quote($lastthirty) . ' AND ' . $this->Quote(_END_OF_TODAY);



                            $order == '' and $Listings->order = array($key . ' DESC');



                            break;



                            default:



                            $whereFields[] = " $key = " . $this->Quote($value);



                            break;



                        }







                        break;







                        default:







                        if (isset($tag) && $key == 'jr_' . $tag['field'] && $fieldType == 'text') {



                            $whereFields[] = " $key = " . $this->Quote($value);



                        } else {







                            $whereFields[] = " $key LIKE " . $this->QuoteLike($value);



                        }







                        break;



                    }







                    $wheres[] = " (" . implode(') AND (', $whereFields) . ")";



                }



            } // endforeach



        }







        $where = !empty($wheres) ? "\n (" . implode(") AND (", $wheres) . ")" : '';

        





        // Determine which categories to include in the queries



        if ($cat_id = Sanitize::getString($this->params, 'cat')) {



            $category_ids = explode($urlSeparator, $this->params['cat']);







            // Remove empty or nonpositive values from array



            if (!empty($category_ids)) {



                foreach ($category_ids as $index => $value) {



                    if (empty($value) || $value < 1 || !is_numeric($value)) {



                        unset($category_ids[$index]);



                    }



                }



            }







            $category_ids = is_array($category_ids) ? implode(',', $category_ids) : $category_ids;







            $category_ids != '' and $this->params['cat'] = $category_ids;



        } elseif (isset($criteria_ids) && trim($criteria_ids) != '') {



            $criteria_ids = str_replace($urlSeparator, ',', $criteria_ids);







            $criteria_ids != '' and $this->params['criteria'] = $criteria_ids;



        } elseif (isset($dir_id) && trim($dir_id) != '') {



            $dir_id = str_replace($urlSeparator, ',', $dir_id);







            $dir_id != '' and $this->params['dir'] = $dir_id;



        }



        #



        # Make query filter time



        $startDate = Sanitize::getString($this->params, 'typescheduler', '');



        if ($startDate != '') {



            $order = Sanitize::getString($this->params, 'order');







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



                $timeFilter = " $key BETWEEN " . $this->Quote(_TODAY) . ' AND ' . $this->Quote(_END_OF_TODAY);



                $order == '' and $Listings->order = array($key . ' ASC');



                break;



                case 'week':



                $timeFilter = " $key BETWEEN " . $this->Quote($begin_week) . ' AND ' . $this->Quote($end_week);



                $order == '' and $Listings->order = array($key . ' ASC');



                break;



                case 'month':



                $timeFilter = " $key BETWEEN " . $this->Quote($begin_month) . ' AND ' . $this->Quote($end_month);



                $order == '' and $Listings->order = array($key . ' ASC');



                break;



                case '+7':



                $timeFilter = " $key BETWEEN " . $this->Quote(_TODAY) . ' AND ' . $this->Quote($nextseven);



                $order == '' and $Listings->order = array($key . ' ASC');



                break;



                case '+30':



                $timeFilter = " $key BETWEEN " . $this->Quote(_TODAY) . ' AND ' . $this->Quote($nextthirty);



                $order == '' and $Listings->order = array($key . ' ASC');



                break;



                case '-7':



                $timeFilter = " $key BETWEEN " . $this->Quote($lastseven) . ' AND ' . $this->Quote(_END_OF_TODAY);



                $order == '' and $Listings->order = array($key . ' DESC');



                break;



                case '-14':



                $timeFilter = " $key BETWEEN " . $this->Quote($lasttwelve) . ' AND ' . $this->Quote(_END_OF_TODAY);



                $order == '' and $Listings->order = array($key . ' DESC');



                break;



                case '-30':



                $timeFilter = " $key BETWEEN " . $this->Quote($lastthirty) . ' AND ' . $this->Quote(_END_OF_TODAY);



                $order == '' and $Listings->order = array($key . ' DESC');



                break;



                default:



                $timeFilter = " $key = " . $this->Quote($value);



                break;



            }



            //$Listings->conditions[] = $timeFilter;



        }



        #end make query filter date   



        #filter Criteria



           $cri_id = Sanitize::getInt($this->params, 'criteria', 0);



           if ($cri_id) {



               $this->conditions[]="(Criteria.criterida_id=$cri_id)";



           }



        #filter old listing has notifications



        $alert['params'] = json_decode($alert['params']);



        if($alert['params']->filter_old){



           $old = json_decode($alert['hasnotification']);



           if(count($old)){



            $cd = '('.implode(',',$old).')';



            $Listings->conditions[]="(Listing.id NOT IN $cd )";



        }







    }



        # Add search conditions to Listing model



    if ($where != '') {

        $Listings->conditions[] = $where;



    } elseif ((



        count($Listings->conditions) == 0 &&



        $dir_id == '' &&



        $category_ids == '' &&



        $criteria_ids == ''



        ) &&



    !Sanitize::getBool($this->Config, 'search_return_all', false)) {



        return false;



    }

    $listings =$this->listings($Listings);



    return $listings;



}







function beforeFilter() {



    parent::beforeFilter();



}







function create_alert() {



    if (!$this->_user->id) {



        return $this->render('elements', 'login');



    }



    $menu_id = Sanitize::getInt($this->params, 'Itemid');



    $page = $this->createPageArray($menu_id);



    $alerts_limit = Sanitize::getVar($this->Config, 'alerts_limit');



    $alerts = $this->resetAlerts();



    $limited = ($alerts_limit<=count($alerts))?true:false;



    $criteria_id = Sanitize::getVar($this->Config, 'criteria_id');



    $alert_id = Sanitize::getInt($this->data, 'alert_id',false);



    $alert = $alert_id ? $this->getAlert( $alert_id):false;



    $this->set(array('page' => $page, 'criteria_id' => $criteria_id,'limited'=>$limited,'alerts_limit'=>$alerts_limit,'alert'=>$alert));



    $this->renderLayout('jobsalert', 'create_alert');



}



function getAlert($id) {







    if(!$id)



        return ;



    $this->JobsAlert = ClassRegistry::getClass('JobsAlertModel');



    $conditions[] = "JobsAlert.alert_id = '$id'";



    $alert = $this->JobsAlert->findRow(array(



        'fields' => array('JobsAlert.*'),



        'conditions' => $conditions



        ), array());







    return $alert;



}







function getFields(&$params, &$criteria_id) {



    $params = Sanitize::getVar($this->Config, 'showFields');



    $geomapInput = Sanitize::getVar($this->Config, 'geomaps.advsearch_input');



    $params = $params ? json_decode($params) : false;



    foreach ($params as $key => $criteria) {







        $criteria_id = $key;



        break;



    }



    if ($criteria_id) {



            # Process custom fields



        $search = 1;



        $searchFields = $this->Field->getFieldsArrayNew($criteria_id, 'listing', null, $search);



    }



    foreach ($searchFields as $key => $group) {







        foreach ($group['Fields'] as $k => $field) {



            if($field['name']==$geomapInput&&!isset($this->Config->{'jobsalert.geomapInGroup'})){



             $this->geomap_group = $group['group_name'];



         }



         if (!isset($params->{$criteria_id})) {



            unset($searchFields);



        } elseif (!isset($params->{$criteria_id}->{$field['group_id']})) {



            unset($searchFields[$key]);



        } elseif (!isset($params->{$criteria_id}->{$field['group_id']}->{$field['field_id']}->status) || $params->{$criteria_id}->{$field['group_id']}->{$field['field_id']}->status != 'on') {



            unset($searchFields[$key]['Fields'][$k]);



        } elseif (isset($params->{$criteria_id}->{$field['group_id']}->{$field['field_id']}->status) && isset($params->{$criteria_id}->{$field['group_id']}->{$field['field_id']}->mutil)) {



            $searchFields[$key]['Fields'][$k]['multi'] = 'selectmultiple';







        }



    }



}







return isset($searchFields) ? $searchFields : false;



}







function alert_form() {



        //$params = false;



    $criteria_id = Sanitize::getVar($this->Config, 'criteria_id');



    $searchFields = $this->getFields($params, $criteria_id);



    $radius = Sanitize::getVar($this->Config, 'geomaps.radius');



    $radius_metric = Sanitize::getVar($this->Config, 'geomaps.radius_metric');



    $radius_metric =  $radius_metric=='mi'?'miles':$radius_metric;



    $this->set(array(



        'params' => $params,



        'criteria_id' => $criteria_id,



        'searchFields' => $searchFields,



        'geomapInGroup'=>'group_'.$this->geomap_group,



        'radius_metric' => $radius_metric,



        'radius' => $radius



        ));



    $this->renderLayout('jobsalert', 'alert_form');



}







function _loadValues($field_id, $value) {



    $vl = is_array($value) ? implode("','", $value) : $value;



    $db = JFactory::getDbo();



    $query = $db->getQuery(true);



    $query->select('text')



    ->from('#__jreviews_fieldoptions')



    ->where("`fieldid`='$field_id' and `value` in ('$vl')");



    $db->setQuery($query);



    $rows = $db->loadAssocList();



    $rt = is_array($value) ? implode(",", $value) : $value;



    if (count($rows) == 1) {



        $rt = $rows[0]['text'];



    } elseif (count($rows) > 1) {



        $rt = '';



        foreach ($rows as $key => $row) {







            $rt[] = $row['text'];



        }



        $rt = implode(',', $rt);



    }



    return $rt;



}







function getAlerts($all = false) {



    $this->JobsAlert = ClassRegistry::getClass('JobsAlertModel');



    $type = Sanitize::getString($this->params, 'tscld');



    if (!$all){



       $conditions[] = 'JobsAlert.user_id = ' . (int) $this->_user->id;



       $conditions[] = 'JobsAlert.type_alert >0 ' ;



   } 



   if($type){



       $conditions[] = "JobsAlert.type_scheduler = '$type'";



   }



   $joins[] = 'LEFT JOIN #__users AS AlertUser ON JobsAlert.user_id = AlertUser.id';



   $conditions[] = 'JobsAlert.status >0';



   $alerts = $this->JobsAlert->findAll(array(



    'conditions' => $conditions,



    'fields' => array('AlertUser.name as username', 'AlertUser.email as useremail', 'JobsAlert.*'),



    'offset' => $this->offset,



    'joins' => $joins,



    'order' => array('JobsAlert.created DESC')



    ), array() /* no callbacks */);







   return $alerts;



}







function index() {



        //Check login



 if (!$this->_user->id) {







    return $this->render('elements', 'login');



}

$alerts = $this->resetAlerts();



$fieldsArray = $this->getFIeldsArray();



$menu_id = Sanitize::getInt($this->params, 'Itemid');



$page = $this->createPageArray($menu_id);



$this->set(array('page' => $page, 'alerts' => $alerts, 'fieldsArray' => $fieldsArray,'Field'=>$this->Field));



return $this->renderLayout('jobsalert', 'index');



}







function getFIeldsArray() {



    $tmp = array();







    $searchFields = $this->getFields($params, $criteria_id);







    foreach ($searchFields as $key => $group) {



        foreach ($group['Fields'] as $k => $field) {



            $tmp[$k]['title'] = $field['title'];



            $tmp[$k]['field_id'] = $field['field_id'];



        }



    }



    return $tmp;



}







function resetAlerts() {



    $alerts = $this->getAlerts();



    $tmp = false;







    $fieldsArray = $this->getFIeldsArray();







    if (count($alerts) > 0) {



        foreach ($alerts as $key => $alert) {



            $tmp[$key] = $alert['JobsAlert'];



            $tmp[$key] ['params'] = json_decode($alert['JobsAlert']['params']);



            $filters = (array) $tmp[$key] ['params']->filter;



            $t = array();



            $url = array();







            foreach ($filters as $k => $ft) {



                if (isset($fieldsArray[$k])) {



                    $t[$k]['title'] = $fieldsArray [$k]['title'];



                    $t[$k]['field_id'] = $fieldsArray [$k]['field_id'];



                    $t[$k]['value'] = $ft;



                    $t[$k]['text'] = $this->_loadValues($t[$k]['field_id'], $t[$k]['value']);



                    $url[] = is_array($ft) ? "$k=" . implode('_', $ft) : "$k=$ft";



                } else {







                    if ($k == 'keywords') {



                        $t['keyworks']['title'] = __t('Keywords',true);



                        $t['keyworks']['text'] = $ft;



                        $url['keyworks'] = 'keywords=' . $ft;



                    }



                    if ($k == 'scope') {







                        $t['score']['title'] = __t('Score',true);



                        $pr = is_array($ft) ? implode('_', $ft) : "$ft";



                        $t['score']['text'] = is_array($ft) ? implode(',', $ft) : "$ft";



                        $t['score']['text'] = str_replace("introtext", __t('Listing summary',true),$t['score']['text']);



                        $t['score']['text'] = str_replace("title", __t('Listing Title',true),$t['score']['text']);



                            //$t['score']['customs']=1;



                        $url['scope'] = "scope=$pr";



                    }



                    if ($k == 'search_query_type') {



                        $t['search_query_type']['title'] = __t('Search query',true);



                        $t['search_query_type']['text'] = $ft;



                        $url['query'] = 'query=' . $ft;



                    }





                    if ($k == 'jr_radius') {



                       $radius_metric = Sanitize::getVar($this->Config, 'geomaps.radius_metric');



                       $radius_metric =  $radius_metric=='mi'?'miles':$radius_metric;



                       $t['jr_radius']['title'] = __t('Radius',true);



                       $t['jr_radius']['text'] = $ft." $radius_metric";



                       $url['jr_radius'] = 'jr_radius=' . $ft;



                   }



               }



           }



           if (!isset($url['score']) && isset($url['keywords'])) {



            unset($url['keywords']);



        }



        if (isset($tmp[$key]['type_scheduler'])) {



            $url[] = 'alert_scheduler=' . $tmp[$key]['type_scheduler'];



        }



        if(!isset($t['jr_longitude'])||!isset($t['jr_latitude'])){



           if(isset($t['jr_radius']))



           {



            unset($t['jr_radius']);



        }



        if(isset($url['jr_radius']))



        {



            unset($url['jr_radius']);



        }







    }



    $url[]="alert_id=".$tmp[$key]['alert_id'];







    $url = implode('&', $url);



    $criteria_id = Sanitize::getInt($tmp[$key] ['params'], 'criteria');



    $url = cmsFramework::route("option=com_jreviews&url=search-results&criteria=$criteria_id&$url");



    $arrayAlertScheduler = array('today' => 'Daily', 'week' => 'Weekly', '-14' => 'Bi-Weekly', 'month' => 'Monthly');



    $arrayAlertValue = array(false, 'Notification', 'Email', 'Notification,Email');







    $AlertScheduler = explode('_', $tmp[$key]['type_scheduler']);



    $schedulerText = array();



    foreach ($AlertScheduler as $scheduler) {



        isset($arrayAlertScheduler[$scheduler]) and $schedulerText[] = $arrayAlertScheduler[$scheduler];



    }







    $schedulerText = count($schedulerText) ? implode(',', $schedulerText) : false;



    if (isset($arrayAlertValue[$alert['JobsAlert']['type_alert']]) && $arrayAlertValue[$alert['JobsAlert']['type_alert']]) {



        $t['jr_alerttype'] = array('title' =>__t("Notification type",true), 'text' => $arrayAlertValue[$alert['JobsAlert']['type_alert']]);



    }



    if ($schedulerText) {



        $t['jr_schedulertype'] = array('title' => __t("Notification Frequency",true), 'text' => $schedulerText);



    }







    $tmp[$key]['filter'] = $t;



    $tmp[$key]['url'] = $url;



}



} else {



    return false;



}



return $tmp;



}







function _delete() {







    $json = array('error' => false, 'html' => "");



    if (!$this->_user->id) {



        $json['error'] = true;



        $json['html'] = $this->render('elements', 'login');



    }



    $alert_id = Sanitize::getInt($this->data, 'alert_id');







    if (empty($alert_id)) {



        $json['error'] = true;



        $json['html'] = "Can't find this alert!";



    }



    if (!$json['error']) {



        $this->JobsAlert = ClassRegistry::getClass('JobsAlertModel');



        try {



            $conditions = ' user_id = ' . (int) $this->_user->id;



            $json['error'] = !$this->JobsAlert->delete('alert_id', $alert_id, $conditions);



            $json['html'] = "Can't find this alert!";



        } catch (Exception $e) {



            $json['error'] = true;



            $json['html'] = $e;



        }



    }



    if (!$json['error']) {



        ob_start();



        $this->index();



        $json['html'] = ob_get_contents();



        ob_end_clean();



    }







    return cmsFramework::jsonResponse($json);



}







function _save() {







    $json = array();



    $this->JobsAlert = ClassRegistry::getClass('JobsAlertModel');



    $Fields = Sanitize::getVar($this->data, 'Field');



    if (count($Fields)) {







        foreach ($Fields['Listing'] as $key => $field) {



            if ($field != '') {



                $tmpField[$key] = $field;



            }



        }



            //romove scope if not have keyworks



        if(!isset($tmpField['keywords'])){



            unset($tmpField['search_query_type']);



            if(isset($tmpField['scope'])) {



                unset($tmpField['scope']);



            }



        }



        $latField = Sanitize::getString($this->Config,'geomaps.latitude');



        $longField = Sanitize::getString($this->Config,'geomaps.longitude');



        $advsearch_input = Sanitize::getString($this->Config,'geomaps.advsearch_input');



        if(isset($advsearch_input)&&(!isset($tmpField[$latField])||!isset($tmpField[$longField]))){



            unset($tmpField[$advsearch_input]);



            unset($tmpField['jr_radius']);



            if(isset($tmpField[$latField])){



                unset($tmpField[$latField]);



            };



            if(isset($tmpField[$longField])){



                unset($tmpField[$longField]);



            };



        }



    }







    $alertType = Sanitize::getVar($Fields, 'jr_alerttype') ? array_sum(Sanitize::getVar($Fields, 'jr_alerttype')) : 0;



    $alertScheluder = Sanitize::getVar($Fields, 'jr_alert_scheduler') ? Sanitize::getVar($Fields, 'jr_alert_scheduler') : 0;



    if (isset($tmpField) && count($tmpField) && trim(Sanitize::getString($this->data, 'jrtitle')) != '') {



        $this->data['JobsAlert']['user_id'] = $this->_user->id;



        $this->data['JobsAlert']['title'] = Sanitize::getString($this->data, 'jrtitle');



        $params['filter'] = $tmpField;



        $criteria_id = Sanitize::getVar($this->Config, 'criteria_id');



        $params['filter_old'] = Sanitize::getVar($Fields , 'filter_old',0);



        if ($criteria_id) {



            $params['criteria'] =  $criteria_id;



        }



        $alert_id = Sanitize::getVar($Fields , 'alert_id',false);



        $alert_id and $this->data['JobsAlert']['alert_id']=$alert_id;



        $this->data['JobsAlert']['params'] = json_encode($params);



        $this->data['JobsAlert']['created'] = date('Y-m-d H:i:m');



        $this->data['JobsAlert']['updated'] = '';



        $this->data['JobsAlert']['last_alert'] = '';



        $this->data['JobsAlert']['type_alert'] = $alertType;



        $this->data['JobsAlert']['type_scheduler'] = $alertScheluder;



    } else {



        if (trim(Sanitize::getString($this->data, 'jrtitle')) == '') {



            $json['error'] = 1;



            $json['msg'] = 'Please enter the listing title.</br>';



        }



        if (!isset($tmpField) || !count($tmpField)) {



            $json['error'] = 1;



            $json['msg'] = !isset($json['msg']) ? "Must enter at least one field.</br>" : $json['msg'] . "Must enter at least one field.</br>";



        }



    }



    if ($alertType > 1 && (!$alertScheluder || $alertScheluder == '')) {



        $json['error'] = 1;



        $json['msg'] = isset($json['msg']) ? $json['msg'] . "Please enter the Scheduler Alert.</br>" : "Please enter the Scheduler Alert.</br>";



    } elseif ($alertType < 1) {



        $json['error'] = 1;



        $json['msg'] = isset($json['msg']) ? $json['msg'] . "Please enter the type alert.</br>" : "Please enter the type alert.</br>";



    }



//        elseif ($alertType == 1) {



//            $this->data['JobsAlert']['type_scheduler'] = 0;



//        }



    if (!isset($json['error'])) {



        try {



            $alert = $this->JobsAlert->store($this->data);



            $json['insertid']=0;



            if(!$alert_id){



                $json['insertid']=Sanitize::getInt($this->data, 'insertid',0);



            }



            $json['msg'] = "New alert created successfully.</br>";



        } catch (Exception $e) {



            $json['error'] = 1;



            $json['msg'] .= $e;



        }



    }



    return cmsFramework::jsonResponse($json);



}







function renderLayout($controller, $action) {



    if (!$this->_user->id) {



        return $this->render('elements', 'login');



    }



    $criteria_id = Sanitize::getInt($this->params, 'criteria');











    $dateFields = array();







        // Check if the criteria list should be limited to specified ids



        $separator = "_"; // For url specified criterias







        $used_criterias = array();







        if ($criteria_id > 0) {







            $criterias = array($criteria_id);



        } else {







            if (isset($criteria_id) && is_array($criteria_id)) {



                $criterias_tmp = explode("_", urldecode($criteriaid));







                for ($i = 0; $i < count($criterias_tmp); $i++) {



                    if ((int) $criterias_tmp[$i] > 0) {



                        $used_criterias[$i] = $criterias_tmp[$i];



                    }



                }







                if (count($used_criterias) == 1) {



                    $separator = ","; // For menu param specified criterias



                    $criterias_tmp = explode(",", urldecode($criteriaid));



                    $used_criterias = array();



                    for ($i = 0; $i < count($criterias_tmp); $i++) {



                        if ((int) $criterias_tmp[$i] > 0) {



                            $used_criterias[$i] = $criterias_tmp[$i];



                        }



                    }



                }



            }







            if (empty($used_criterias)) {



                // Find the criteria that has been assigned to com_content categories



                $query = "



                SELECT



                DISTINCTROW criteriaid



                FROM



						#__jreviews_categories



                WHERE



                `option`='com_content'



                ";







                $used_criterias = $this->Criteria->query($query, 'loadColumn');



            }







            $used_criterias = implode(',', $used_criterias);







            $query = "



            SELECT



            id AS value,title AS text



            FROM



					#__jreviews_criteria



            WHERE



            groupid <> '' AND id in ($used_criterias) AND search = 1



            ORDER BY title



            ";







            $criterias = $this->Criteria->query($query, 'loadObjectList');







            if (count($criterias) == 1) {



                $criterias = array($criterias[0]->value);



            }



        }



        /*         * ****************************************************************



         * Process page title and description



         * ***************************************************************** */















        // With one listing type, there's no need to select it to see the form.



        if (count($criterias) == 1) {



            $criteria_id = $criterias[0];







            # Process custom fields



            $search = 1;







            $searchFields = $this->Field->getFieldsArrayNew($criteria_id, 'listing', null, $search);







            # Get category list for selected listing type



            $categoryList = $this->Category->getCategoryList(array('type_id' => $criteria_id));







            $this->set(



                array(



                    'criteria_id' => $criteria_id,



                    'categoryList' => $categoryList,



                    'searchFields' => $searchFields



                    )



                );







            // If there's more than one criteria show the criteria select list



        } elseif (count($criterias) >= 1) {



            $this->set(



                array(



                    'criterias' => $criterias



                    )



                );



        }







        echo $this->render($controller, $action);



    }







    function sendNotification($alert, $listings) {





        $count_jobs = count($listings);

        $data = array();

        $alert['url'].='&date_notification='.date('Y-m-d H:i:s').'&count_job='.$count_jobs ;

        $data['title'] = "Notifications from {b}" . $alert['title'] . '{/b}';



        $dat['author'] = 53;



        $data['content']="You are have " . $count_jobs . " listings posted.";



        $data['permalink']= $alert['url'];



        $data['target'] = $alert['user_id'];



        switch ($alert['type_alert']) {



            case 1:



            $this->notificationDisscuss($data,$alert);



            $this->notificationEasysocial($data);



            break;



            case 2:



            $this->notificationEmail( $alert,$listings);



            break; 



            case 3:



            $this->notificationDisscuss($data,$alert);



            $this->notificationEasysocial($data);



            $this->notificationEmail( $alert,$listings);



            break;



            default:



                # code...



            break;



        }







    }



    function notificationEasysocial($dat) {



        $notification = FD::notification();



        $data = $notification->getTemplate();



        $data->setTitle( $dat['title'] );



        $data->setContent($dat['content']);



        $data->setUrl($dat['permalink']);



        $data->setTarget($dat['target'] );



        $notification->create($data);



    }



    function notificationDisscuss($dat=null,$alert) {

            jimport('joomla.utilities.date');

            $date   = JFactory::getDate();

            $params = new CParameter( '' );

            

            $url = 'http://industrialjobs.org/index.php?option=com_jreviews&view=jobsalert&Itemid=982';

            $params->set( 'url' , $dat['permalink'] );

             $params->set( 'actor_url' , $dat['permalink']);

            $params->set( 'actor' , $alert['title']);

            $notification = JTable::getInstance( 'notification' , 'CTable' );

            $notification->target   = 55;

            $notification->content  = "Notifications from <b>{actor}</b>: ".$dat['content'];

            $notification->created  = $date->toSql();

            $notification->params   = ( is_object( $params ) && method_exists( $params , 'toString' ) ) ? $params->toString() : '';

            $notification->cmd_type = 'notif_profile_status_update';

            $notification->type     = 0;

           $notification->store();

    }





    function notificationEmail($alert,$listings){
          $count_jobs = count($listings);
        $mailer = JFactory::getMailer();
	 $recipient = $alert['useremail'];



        $mailer->addRecipient($recipient);

        

        $mailer->setSubject("You are have " . $count_jobs . " listings posted.");

        $this->set(array('count_alert'=>$count_jobs,'username'=>$alert['username'],'url'=>$alert['url'],'alert_title'=>$alert['title'],'listings'=>$listings));

        

        $body = $this->buidMainBody($listings,$alert);

        $mailer->isHTML(true);



        $mailer->Encoding = 'base64';



        $mailer->setBody($body);    



        $send = $mailer->Send();          



}

    function buidMainBody($listings,$alert){

        $alert['count_listing'] = count($listings);

        $customFields = $this->Field->getFieldNames();

        $this->set(array('alert'=>$alert,'url'=>$alert['url'],'listings'=>$listings,'customFields'=>$customFields ));

        $body =  $this->render('jobsalert','email_layout');

        return $body;



    }

    



}

