<?php
    /**
     * @class  apicenterAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief admin controller class of the apicenter module
     *
     **/

    class apicenterAdminController extends apicenter {

		var $api_grants =  array();

        /**
         * @brief Initialization
         **/
        function init() {
			// initial api grants: list-all users, view-all users, write-admin users, modify-admin users, delete-admin users
			$this->api_grants = array("list"=>0, "view"=>0, "write"=>-2, "modify"=>-2, "delete"=>-2,"category"=>0);
		}

		function procApicenterAdminInsertApiItem(){
			// only for admin user
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin != 'Y')  return new Object(-1, "msg_invalid_request");

			$source_args = Context::getRequestVars();
			$api_item_srl = Context::get('api_item_srl');

			if(!$api_item_srl){
				$output = $this->insertApiItem($source_args); 
				$msg_code = 'success_registed';
			}else{
				$source_args->api_item_srl = $api_item_srl;
				$output = $this->updateApiItem($source_args); 
				$msg_code = 'success_updated';
			}
			
			if(!$output->toBool()) return $output;

            $this->add('page',Context::get('page'));
            $this->setMessage($msg_code);
 
        	if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispApicenterAdminList');
				header('location:'.$returnUrl);
				return;
			}
		}

		/**
		 * @brief insert API item
		 **/
		function insertApiItem($obj, $manual_inserted = false) {
			
			$logged_info = Context::get('logged_info');

			$args->api_item_srl = getNextSequence();

			$args->api_title = $obj->api_title?$obj->api_title:"API Sample Subject";
			$args->module = $obj->module_type;
			$args->module_srl = $obj->select_module_id;

			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			$args->mid = $module_info->mid;

			if($module_info->site_srl != 0){
				$site_info = $oModuleModel->getSiteInfo($module_info->site_srl);
				$args->mid = $site_info->domain;
			}


			$args->module_srl = $module_info->module_srl;
			$args->output_format = $obj->select_format;

			$oApicenterAdminModel = &getAdminModel('apicenter');
			$apiKeyInfo = $oApicenterAdminModel->getApiKeyInfoByKey($obj->api_key);
			if(!$apiKeyInfo)  return new Object(-1, "msg_invalid_request");
			
			$args->api_key_srl = $apiKeyInfo->api_key_srl;
			$args->api_key = $obj->api_key;
			$args->description = $obj->api_description;
			$args->member_srl = $logged_info->member_srl;

			// arrange api types
			$api_types = array("list"=>0, "view"=>0, "write"=>0, "modify"=>0, "delete"=>0, "category"=>0);
			if($obj->api_type_list == 'Y') {	
				$api_types["list"] = 1;
				$args->api_type = 'list';
				$this->insertApiUrl($args);
			}
			if($obj->api_type_view == 'Y') {
				$api_types["view"] = 1;
				$args->api_type = 'view';
				$this->insertApiUrl($args);
			}
			if($obj->api_type_write == 'Y') {
				$api_types["write"] = 1;
				$args->api_type = 'write';
				if($obj->api_type_category == 'Y') $args->enable_category = true;
				$this->insertApiUrl($args);
			}
			if($obj->api_type_modify == 'Y') { 
				$api_types["modify"] = 1;
				$args->api_type = 'modify';
				if($obj->api_type_category == 'Y') $args->enable_category = true;
				$this->insertApiUrl($args);
			}
			if($obj->api_type_delete == 'Y') {
				$api_types["delete"] = 1;
				$args->api_type = 'delete';
				$this->insertApiUrl($args);
			}
			if($obj->api_type_category == 'Y') {
				$api_types["category"] = 1;
				$args->api_type = 'category';
				$this->insertApiUrl($args);
			}
			$args->api_types = serialize($api_types);
			$output = executeQuery('apicenter.insertApiItem', $args);

			if(!$output->toBool()) {	return $output;}

			$output->add('api_item_srl',$args->api_item_srl);
			return $output;
		}

		/**
		 * @brief update API item
		 **/
		function updateApiItem($obj) {
			
			$logged_info = Context::get('logged_info');

			$args->api_item_srl = $obj->api_item_srl;

			$args->api_title = $obj->api_title?$obj->api_title:"API Sample Subject";
			$args->module = $obj->module_type;
			$args->module_srl = $obj->select_module_id;

			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
	
			$args->mid = $module_info->mid;

			if($module_info->site_srl != 0){
				$site_info = $oModuleModel->getSiteInfo($module_info->site_srl);
				$args->mid = $site_info->domain;
			}

			$args->output_format = $obj->select_format;

			$oApicenterAdminModel = &getAdminModel('apicenter');
			$apiKeyInfo = $oApicenterAdminModel->getApiKeyInfoByKey($obj->api_key);
			if(!$apiKeyInfo)  return new Object(-1, "msg_invalid_request");

			$args->api_key_srl = $apiKeyInfo->api_key_srl;
			$args->api_key = $obj->api_key;
			$args->description = $obj->api_description;
			$args->member_srl = $logged_info->member_srl;

			// delete API Item's URLs
			$deleteUrls = $this->deleteApiUrlsByApiItem($args->api_item_srl);
			$deleteApiGrants = $this->deleteApiGrantsByApiItem($args->api_item_srl);

			// arrange api types
			$api_types = array("list"=>0, "view"=>0, "write"=>0, "modify"=>0, "delete"=>0, "category"=>0);
			if($obj->api_type_list == 'Y') {
				$api_types["list"] = 1;
				$args->api_type = 'list';
				$this->insertApiUrl($args);
			}
			if($obj->api_type_view == 'Y') {
				$api_types["view"] = 1;
				$args->api_type = 'view';
				$this->insertApiUrl($args);
			}
			if($obj->api_type_write == 'Y') {
				$api_types["write"] = 1;
				$args->api_type = 'write';
				if($obj->api_type_category == 'Y') $args->enable_category = true;
				$this->insertApiUrl($args);
			}
			if($obj->api_type_modify == 'Y') {
				$api_types["modify"] = 1;
				$args->api_type = 'modify';
				if($obj->api_type_category == 'Y') $args->enable_category = true;
				$this->insertApiUrl($args);
			}
			if($obj->api_type_delete == 'Y') {
				$api_types["delete"] = 1;
				$args->api_type = 'delete';
				$this->insertApiUrl($args);
			}
			if($obj->api_type_category == 'Y') {
				$api_types["category"] = 1;
				$args->api_type = 'category';
				$this->insertApiUrl($args);
			}
			$args->api_types = serialize($api_types);

			$output = executeQuery('apicenter.updateApiItem', $args);
			if(!$output->toBool()) {return $output;}

			$output->add('api_item_srl',$args->api_item_srl);
			return $output;
		}

		/**
		 * @brief insert API url
		 **/
		function insertApiUrl($obj) {
			if(!$obj->api_type || !$obj->api_item_srl) return new Object(-1, 'Invalid Request! ');

			$logged_info = Context::get('logged_info');

			$var->api_url_srl = getNextSequence();
			$var->api_type = $obj->api_type;
			$var->api_item_srl = $obj->api_item_srl;
			$var->module = $obj->module;
			$var->module_srl = $obj->module_srl;
			$var->api_key_srl = $obj->api_key_srl;
			$var->api_key = $obj->api_key;
			$var->enable_category = $obj->enable_category;
			$var->member_srl = $logged_info->member_srl;

			$var->grants = $this->api_grants[$var->api_type];
			if(intval($var->grants) != 0){
				$var->oauth = 'Y';
				$var->login_api = $this->generateLoginApiUrl($var);
			}else{
				$var->oauth = 'N';
				$var->login_api = "";
			}

			$var->url = $this->generateApiUrl($var);
			
			$output = executeQuery('apicenter.insertApiUrl', $var);
			if(!$output->toBool()) {return $output;}

			$output->add('api_url_srl',$var->api_url_srl);
			$api_grants = $this->insertApiGrant($var);
			return $output;
		}

		/**
		 * @brief update API url
		 **/
		function updateApiUrl($obj) {
			if(!$obj->api_url_srl || !$obj->api_type)  return new Object(-1, 'Invalid Request! ');

			$var->api_url_srl = $obj->api_url_srl; 
			$var->api_type = $obj->api_type; 
			$var->grants = $obj->grants?$obj->grants:0; 
			$var->module = $obj->module?$obj->module:'board'; 
			$var->enable_category = $obj->enable_category?$obj->enable_category:false; 

			if(intval($var->grants) != 0){
				$var->oauth = 'Y';
				$var->login_api = $this->generateLoginApiUrl($var);
			}else{
				$var->oauth = 'N';
				$var->login_api = "";
			}

			$var->url = $this->generateApiUrl($var);

			$logged_info = Context::get('logged_info');
			$var->member_srl = $logged_info->member_srl;

			$output = executeQuery('apicenter.updateApiUrl', $var);
			if(!$output->toBool()) {return $output;}

			$output->add('api_url_srl',$var->api_url_srl);
			return $output;
		}

		/**
		 * @brief generate API url link
		 **/
		function generateApiUrl($obj){
			if(!$obj->api_type || !$obj->api_url_srl) return new Object(-1, 'Invalid Request! ');

			$url = "";
			$oauth = $obj->oauth?$obj->oauth:'N';

			if($oauth == 'N'){
				switch($obj->api_type){
					case 'list':
						$url = getFullUrl('','module','apicenter','api_k','APIKey', 'uid', $obj->api_url_srl,'limit','20','page','1');
						break;
					case 'view':
						$url = getFullUrl('','module','apicenter','api_k','APIKey', 'uid', $obj->api_url_srl,'doc_id','documentID');
						break;
					case 'write':
						$url = getFullUrl('','module','apicenter','api_k','APIKey', 'uid', $obj->api_url_srl, 'title','docTitle','content', 'docContents');
						break;
					case 'modify':
						$url = getFullUrl('','module','apicenter','api_k','APIKey', 'uid', $obj->api_url_srl,'doc_id','documentID','title','docTitle','content', 'docContents');
						break;
					case 'delete':
						$url = getFullUrl('','module','apicenter','api_k','APIKey', 'uid', $obj->api_url_srl,'doc_id','documentID');
						break;
					case 'category':
						$url = getFullUrl('','module','apicenter','api_k','APIKey', 'uid', $obj->api_url_srl,'target','category');
						break;
				}
			}elseif($oauth == 'Y'){
				switch($obj->api_type){
					case 'list':
						$url = getFullUrl('','module','apicenter','api_k','APIKey', 'token','accessToken', 'uid', $obj->api_url_srl, 'limit','20','page','1');
						break;
					case 'view':
						$url = getFullUrl('','module','apicenter','api_k','APIKey', 'token','accessToken', 'uid', $obj->api_url_srl, 'doc_id','documentID');
						break;
					case 'write':
						$url = getFullUrl('','module','apicenter','api_k','APIKey', 'token','accessToken', 'uid', $obj->api_url_srl, 'title','docTitle','content', 'docContents');
						break;
					case 'modify':
						$url = getFullUrl('','module','apicenter','api_k','APIKey', 'token','accessToken', 'uid', $obj->api_url_srl, 'doc_id','documentID','title','docTitle','content', 'docContents');
						break;
					case 'delete':
						$url = getFullUrl('','module','apicenter','api_k','APIKey', 'token','accessToken', 'uid', $obj->api_url_srl, 'doc_id','documentID');
						break;
					case 'category':
						$url = getFullUrl('','module','apicenter','api_k','APIKey', 'token','accessToken', 'uid', $obj->api_url_srl, 'target','category');
						break;
				}
			}

			// add addtional parameter
			// 1. forum write url must contains a category id
			/*if($obj->module == 'forum' && $obj->api_type == 'write'){
				$url .= htmlspecialchars("&cat_id=categoryID");
			}*/
			// 2. faq write url must with que and answer value
			if($obj->module == 'faq' && $obj->api_type == 'write'){
				if($oauth == 'Y') $url = getFullUrl('','module','apicenter','api_k','APIKey', 'token','accessToken', 'uid', $obj->api_url_srl, 'que','question','ans', 'answer');
				if($oauth == 'N') $url = getFullUrl('','module','apicenter','api_k','APIKey', 'uid', $obj->api_url_srl, 'que','question','ans', 'answer');
			}
			// 3. faq modify url must with que and answer value
			if($obj->module == 'faq' && $obj->api_type == 'modify'){
				if($oauth == 'Y') $url = getFullUrl('','module','apicenter','api_k','APIKey', 'token','accessToken', 'uid', $obj->api_url_srl, 'doc_id','documentID','que','question','ans', 'answer');
				if($oauth == 'N') $url = getFullUrl('','module','apicenter','api_k','APIKey', 'uid', $obj->api_url_srl, 'doc_id','documentID','que','question','ans', 'answer');
			}
			// 4. guestbook write url must only contains content value
			if($obj->module == 'guestbook' && $obj->api_type == 'write'){
				if($oauth == 'Y') $url = getFullUrl('','module','apicenter','api_k','APIKey', 'token','accessToken', 'uid', $obj->api_url_srl, 'content', 'docContents');
				if($oauth == 'N') $url = getFullUrl('','module','apicenter','api_k','APIKey', 'uid', $obj->api_url_srl, 'content', 'docContents');
			}
			// 5. guestbook modify url must only contains content value
			if($obj->module == 'guestbook' && $obj->api_type == 'modify'){
				if($oauth == 'Y') $url = getFullUrl('','module','apicenter','api_k','APIKey', 'token','accessToken', 'uid', $obj->api_url_srl, 'doc_id','documentID','content', 'docContents');
				if($oauth == 'N') $url = getFullUrl('','module','apicenter','api_k','APIKey', 'uid', $obj->api_url_srl, 'doc_id','documentID','content', 'docContents');
			}
			// 6. if category api is enabled, then add category parameter for write and modify API
			if($obj->enable_category && ($obj->api_type == 'write' || $obj->api_type == 'modify')){
				$url .= htmlspecialchars("&cat_id=categoryID");
			}


			return $url;
		}

		/**
		 * @brief generate Login API url
		 **/
		function generateLoginApiUrl($obj){
			if(!$obj->api_type || !$obj->api_url_srl) return new Object(-1, 'Invalid Request! ');

			$login_api = $url = getFullUrl('','module','apicenter','api_k','yourAPIKey', 'uid', $obj->api_url_srl,'uname','yourUserName','pwd','yourPassword');
			return $login_api;
		}

		/**
		 * @brief insert or update API Key
		 **/
		function procApicenterAdminInsertApiKey(){
			// only for admin user
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin != 'Y')  return new Object(-1, "msg_invalid_request");

			$source_args = Context::getRequestVars();
			$api_key_srl = Context::get('api_key_srl');

			if(!$api_key_srl){
				$output = $this->insertApiKey($source_args); 
				$msg_code = 'success_registed';
			}else{
				$source_args->api_key_srl = $api_key_srl;
				$output = $this->updateApiKey($source_args); 
				$msg_code = 'success_updated';
			}
			
			if(!$output->toBool()) return $output;

            $this->add('page',Context::get('page'));
            $this->setMessage($msg_code);
 
        	if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispApicenterAdminKeyList');
				header('location:'.$returnUrl);
				return;
			}
		}

		/**
		 * @brief insert API Key
		 **/
		function insertApiKey($obj, $manual_inserted = false) {
			$logged_info = Context::get('logged_info');

			$args->api_key_srl =  getNextSequence();
			$args->api_key = $this->createApiKey();
			$args->member_srl = $logged_info->member_srl;
			$args->purpose = $obj->api_purpose;

			$output = executeQuery('apicenter.insertApiKey', $args);
			if(!$output->toBool()) {	return $output;}

			$output->add('api_key_srl',$args->api_key_srl);
			return $output;

		}

		/**
		 * @brief update API Key
		 **/
		function updateApiKey($obj, $manual_inserted = false) {
			$logged_info = Context::get('logged_info');

			$args->api_key_srl = $obj->api_key_srl;
			$args->member_srl = $logged_info->member_srl;
			$args->purpose = $obj->api_purpose;

			$output = executeQuery('apicenter.updateApiKey', $args);
			if(!$output->toBool()) {	return $output;}

			$output->add('api_key_srl',$args->api_key_srl);
			return $output;

		}

		/**
		 * @brief generate an API key
		 **/
		function createApiKey(){
			$length = 16; // 16 Chars long
			$api_key = "";

			for ($i=1;$i<=$length;$i++) {
			  // Alphabetical range
			  $alph_from = 65;
			  $alph_to = 90;

			  // Numeric
			  $num_from = 48;
			  $num_to = 57;

			  // Add a random num/alpha character
			  $chr = rand(0,1)?(chr(rand($alph_from,$alph_to))):(chr(rand($num_from,$num_to)));
			  if (rand(0,1)) $chr = strtoupper($chr);
			  $api_key.=$chr;
			 }
			 return $api_key;
		}

		/**
		 * @brief delete API Item
		 **/
		function proApicenterAdminDeleteApiItem(){
			// only for admin user
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin != 'Y')  return new Object(-1, "msg_invalid_request");

			$api_item_srl = Context::get('api_item_srl');
			if(!$api_item_srl)  return new Object(-1, "msg_invalid_request");

			$args->api_item_srl = $api_item_srl;

			$output = executeQuery('apicenter.deleteApiItem', $args);
			if(!$output->toBool()) {return $output;}

			$deleteUrls = $this->deleteApiUrlsByApiItem($api_item_srl);
			$deleteApiGrants = $this->deleteApiGrantsByApiItem($api_item_srl);
			$deleteTokens = $this->deleteAccessTokenByApiItem($api_item_srl);

			$msg_code = 'success_deleted';
            $this->setMessage($msg_code);
 
        	if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispApicenterAdminList');
				header('location:'.$returnUrl);
				return;
			}
		}

		/**
		 * @brief delete API Urls by a given api item 
		 **/
		function deleteApiUrlsByApiItem($api_item_srl){
			if(!$api_item_srl)  return new Object(-1, "msg_invalid_request");

			$args->api_item_srl = $api_item_srl;
			$output = executeQuery('apicenter.deleteApiUrlsByApiItem', $args);
			if(!$output->toBool()) {return $output;}

			return $output;
		}

		/**
		 * @brief delete API Key
		 **/
		function proApicenterAdminDeleteApiKey(){
			// only for admin user
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin != 'Y')  return new Object(-1, "msg_invalid_request");

			$api_key_srl = Context::get('api_key_srl');
			if(!$api_key_srl)  return new Object(-1, "msg_invalid_request");

			$args->api_key_srl = $api_key_srl;

			$output = executeQuery('apicenter.deleteApiKey', $args);
			if(!$output->toBool()) {return $output;}

			$msg_code = 'success_deleted';
            $this->setMessage($msg_code);

        	if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispApicenterAdminKeyList');
				header('location:'.$returnUrl);
				return;
			}
		}

		/**
		 * @brief insert API grants
		 **/
		 function insertApiGrant($obj){
			 if(!$obj->api_item_srl || !$obj->api_url_srl || !$obj->api_type) return false;
			 
			 $vars->api_item_srl = $obj->api_item_srl;
			 $vars->api_url_srl = $obj->api_url_srl;
			 $vars->api_type = $obj->api_type;
			 
			 $vars->group_srl = $this->api_grants[$obj->api_type];

			$output = executeQuery('apicenter.insertApiGrant', $vars);
			if(!$output->toBool()) {return $output;}

			$output->add('api_url_srl',$vars->api_url_srl);
			return $output;
		 }

		/**
		 * @brief update API grants
		 **/
		 function updateApiGrant($obj){
			 if(!$obj->api_item_srl || !$obj->api_url_srl) return false;

			 $args->api_item_srl = $obj->api_item_srl;
			 $args->api_url_srl = $obj->api_url_srl;
			 $args->group_srl = $obj->grants?$obj->grants:0;

			$output = executeQuery('apicenter.updateApiGrant', $args);
			if(!$output->toBool()) {return $output;}

			$output->add('api_url_srl',$args->api_url_srl);
			return $output;
		 }

		/**
		 * @brief delete API grants by api item srl
		 **/
		 function deleteApiGrantsByApiItem($api_item_srl){
			if(!$api_item_srl)  return new Object(-1, "msg_invalid_request");

			$args->api_item_srl = $api_item_srl;
			$output = executeQuery('apicenter.deleteApiGrantsByApiItem', $args);
			if(!$output->toBool()) {return $output;}

			return $output;
		 }

		/**
		 * @brief update API grants, regenerate API Urls
		 **/
		function proApicenterAdminUpdateApiGrants(){
			// only for admin user
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin != 'Y')  return new Object(-1, "msg_invalid_request");

			$vars = Context::getRequestVars();
			$args->api_item_srl = $vars->api_item_srl;
			$args->module = $vars->target_module;
			if(!$args->api_item_srl)  return new Object(-1, "msg_invalid_request");

			if($vars->api_list_url){
				$args->api_url_srl = $vars->api_list_url;
				$args->grants = $vars->api_list_grants?intval($vars->api_list_grants):0;
				$updateGrants = $this->updateApiGrant($args);
				$args->api_type = 'list';
				$updateApiUrl = $this->updateApiUrl($args);
			}
			if($vars->api_view_url){
				$args->api_url_srl = $vars->api_view_url;
				$args->grants = $vars->api_view_grants?intval($vars->api_view_grants):0;
				$updateGrants = $this->updateApiGrant($args);
				$args->api_type = 'view';
				$updateApiUrl = $this->updateApiUrl($args);
			}
			if($vars->api_write_url){
				$args->api_url_srl = $vars->api_write_url;
				$args->grants = $vars->api_write_grants?intval($vars->api_write_grants):0;
				$updateGrants = $this->updateApiGrant($args);
				$args->api_type = 'write';
				if($vars->api_category_url) $args->enable_category = true;
				$updateApiUrl = $this->updateApiUrl($args);
			}
			if($vars->api_modify_url){
				$args->api_url_srl = $vars->api_modify_url;
				$args->grants = $vars->api_modify_grants?intval($vars->api_modify_grants):0;
				$updateGrants = $this->updateApiGrant($args);
				$args->api_type = 'modify';
				if($vars->api_category_url) $args->enable_category = true;
				$updateApiUrl = $this->updateApiUrl($args);
			}
			if($vars->api_delete_url){
				$args->api_url_srl = $vars->api_delete_url;
				$args->grants = $vars->api_delete_grants?intval($vars->api_delete_grants):0;
				$updateGrants = $this->updateApiGrant($args);
				$args->api_type = 'delete';
				$updateApiUrl = $this->updateApiUrl($args);
			}
			if($vars->api_category_url){
				$args->api_url_srl = $vars->api_category_url;
				$args->grants = $vars->api_category_grants?intval($vars->api_category_grants):0;
				$updateGrants = $this->updateApiGrant($args);
				$args->api_type = 'category';
				$updateApiUrl = $this->updateApiUrl($args);
			}

			$msg_code = 'API Grants has been successfully updated, API URLs has been re-generated.';
            $this->setMessage($msg_code);

        	if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispApicenterAdminGrantInfo','api_item_srl', $args->api_item_srl);
				header('location:'.$returnUrl);
				return;
			}
			
		}

		/**
		 * @brief insert user access token, will be called from an API request
		 **/
		function insertAccessToken($access_token, $member_srl, $api_url_srl,$expire=null){
			if(!$access_token || !$member_srl || !$api_url_srl) return new Object(-1, "msg_invalid_request");

			$args->access_token_srl =  getNextSequence();
			$args->access_token = $access_token;
			$args->member_srl = $member_srl;
			$args->api_url_srl = $api_url_srl;
			if($expire) $args->expire = $expire;
   
			$output = executeQuery('apicenter.insertApiAccessToken', $args);
			if(!$output->toBool()) {return $output;}

			$output->add('api_url_srl',$args->api_url_srl);
			return $output;
		}

		/**
		 * @brief delete user access token 
		 **/
		function deleteAccessToken($access_token_srl){
			if(!$access_token_srl) return new Object(-1, "msg_invalid_request");

			$args->access_token_srl = $access_token_srl;
			$output = executeQuery('apicenter.deleteApiAccessToken', $args);
			if(!$output->toBool()) {return $output;}

			$output->add('access_token_srl',$args->access_token_srl);
			return $output;
			
		}

		/**
		 * @brief delete user access tokens by api item
		 **/
		function deleteAccessTokenByApiItem($api_item_srl){
			if(!$api_item_srl) return new Object(-1, "msg_invalid_request");

			$oApicenterAdminModel = &getAdminModel('apicenter');
			$api_urls = $oApicenterAdminModel->getApiItemUrls($api_item_srl);

			$api_url_srls = array();
			foreach ($api_urls as $api_url)
			{
				$api_url_srls[] = $api_url->api_url_srl;
			}
			
			if(!empty($api_url_srls)){
				$api_url_srls = implode(', ', $api_url_srls);
				$output = $this->deleteAccessTokenByApiUrl($api_url_srls);
			}

			return $output;
		}

		/**
		 * @brief delete user access tokens by api url
		 **/
		function deleteAccessTokenByApiUrl($api_url_srl){
			if(!$api_url_srl) return new Object(-1, "msg_invalid_request");

			$args->api_url_srl = $api_url_srl;

			$output = executeQuery('apicenter.deleteApiAccessTokenByApiUrl', $args);
			if(!$output->toBool()) {return $output;}

			return $output;
		}

		/**
		 * @brief get textyle module list (Ajax)
		 **/
		function procApicenterAdminGetTextyleList(){
			$oTextyleModel = &getModel('textyle');
			$oModuleController = &getController('module');
			$oModuleModel = &getModel('mdoule');
			$args->list_count = 20;
            $args->page = $page;
            $args->list_order = 'regdate';
            $output = $oTextyleModel->getTextyleList($args);

            $mid_list = array();
            if(count($output->data)) {
                foreach($output->data as $key => $val) {
                    $module = trim($val->get('module'));
                    if(!$module) continue;

					// replace user defined lang.
					$oModuleController->replaceDefinedLangCode($val->get('browser_title'));

                    $obj = null;
                    $obj->module_srl = $val->module_srl;
                    $obj->browser_title = $val->get('browser_title');
                    $obj->site_srl = $val->site_srl;
					$obj->mid = $val->domain;
                    $mid_list[$module]->list[$val->domain] = $obj;
                }
            }

			$security = new Security($mid_list);
			$security->encodeHTML('....browser_title');

			$this->add('module_list', $mid_list);
		}

		/**
		 * @brief get  module category Config (Ajax)
		 **/
		 function procApicenterAdminGetCategoryConfig(){
			$vars = Context::getRequestVars();
			$module = $vars->target_module;
			$module_srl = $vars->module_srl;

			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

			$this->add('enable_category', $module_info->use_category);
		 }

		/**
		 * @brief delete selected API items
		 **/
		function procApicenterAdminDeleteSelectedApiItems(){
			// only for admin user
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin != 'Y')  return new Object(-1, "msg_invalid_request");

			$vars = Context::getRequestVars();

			if(!$vars->api_item_srls)  return new Object(-1, "msg_invalid_request");

			$args->api_item_srl = $vars->api_item_srls;

			$output = executeQuery('apicenter.deleteApiItem', $args);
			if(!$output->toBool()) {return $output;}

			$deleteUrls = $this->deleteApiUrlsByApiItem($args->api_item_srl);
			$deleteApiGrants = $this->deleteApiGrantsByApiItem($args->api_item_srl);
			$deleteTokens = $this->deleteAccessTokenByApiItem($args->api_item_srl);

			$msg_code = 'success_deleted';
            $this->setMessage($msg_code);
 
        	if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispApicenterAdminList');
				header('location:'.$returnUrl);
				return;
			}
		}

		/**
		 * @brief delete selected API keys
		 **/
		function procApicenterAdminDeleteSelectedKeys(){
			// only for admin user
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin != 'Y')  return new Object(-1, "msg_invalid_request");

			$vars = Context::getRequestVars();

			if(!$vars->api_key_srls)  return new Object(-1, "msg_invalid_request");

			$args->api_key_srl = $vars->api_key_srls;

			$output = executeQuery('apicenter.deleteApiKey', $args);
			if(!$output->toBool()) {return $output;}

			$msg_code = 'success_deleted';
            $this->setMessage($msg_code);
 
        	if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispApicenterAdminKeyList');
				header('location:'.$returnUrl);
				return;
			}
		}

    }
?>