<?php
defined( 'MVC_FRAMEWORK') or die;

/**
 * The class name matches the file name with the first letter capitalized
 */

class JobsAlertCreateController extends MyController
{
	 var $uses = array('article','user','menu','claim','category','jreviews_category','review','favorite','field','criteria','captcha','vote','media');

    	var $helpers = array('cache','routes','libraries','html','text','assets','form','time','jreviews','community','editor','custom_fields','rating','paginator','widgets','media');


	var $components = array('config','access');

        	var $autoRender = false;

        	var $autoLayout = false;

	function beforeFilter()
	{
		parent::beforeFilter();
	}

	function index()
	{  	
		

		$criteria_id = Sanitize::getInt($this->params,'criteria');

		$menu_id = Sanitize::getInt($this->params,'Itemid');

		$dateFields = array();

		// Check if the criteria list should be limited to specified ids
		$separator = "_"; // For url specified criterias

		$used_criterias = array();

		if($criteria_id > 0) {

			$criterias = array($criteria_id);

		} else {

			if(isset($criteria_id) && is_array($criteria_id))
			{
				$criterias_tmp = explode("_",urldecode($criteriaid));

				for ($i=0;$i<count($criterias_tmp);$i++)
				{
					if ( (int) $criterias_tmp[$i] > 0) {
						$used_criterias[$i] = $criterias_tmp[$i];
					}
				}

				if (count($used_criterias)==1)
				{
					$separator = ","; // For menu param specified criterias
					$criterias_tmp = explode(",",urldecode($criteriaid));
					$used_criterias = array();
					for ($i=0;$i<count($criterias_tmp);$i++) {
						if ( (int) $criterias_tmp[$i] > 0) {
							$used_criterias[$i] = $criterias_tmp[$i];
						}
					}
				}
			}

			if (empty($used_criterias))
			{
				// Find the criteria that has been assigned to com_content categories
				$query = "
				SELECT
				DISTINCTROW criteriaid
				FROM
						#__jreviews_categories
				WHERE
				`option`='com_content'
				";

				$used_criterias = $this->Criteria->query($query,'loadColumn');
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

			$criterias = $this->Criteria->query($query,'loadObjectList');

			if (count($criterias) == 1)
			{
				$criterias = array($criterias[0]->value);
			}
		}
        /******************************************************************
        * Process page title and description
        *******************************************************************/
        $page = $this->createPageArray($menu_id);

        $this->set('page',$page);

		// With one listing type, there's no need to select it to see the form.
        if (count($criterias) == 1)
        {
        	$criteria_id = $criterias[0];

			# Process custom fields
        	$search = 1;

        	$searchFields = $this->Field->getFieldsArrayNew($criteria_id, 'listing', null, $search);

            # Get category list for selected listing type
        	$categoryList = $this->Category->getCategoryList(array('type_id'=>$criteria_id));

        	$this->set(
        		array(
        			'criteria_id'=>$criteria_id,
        			'categoryList'=>$categoryList,
        			'searchFields'=>$searchFields
        			)
        		);

		// If there's more than one criteria show the criteria select list
        }
        elseif (count($criterias) >= 1)
        {
        	$this->set(
        		array(
        			'criterias'=>$criterias
        			)
        		);
        }

        echo $this->render('jobsalert','index');
        echo $this->render('jobsalert','create_alert');
	
	}
	
}