<?php
    /**
     * @class  apicenterView
     * @author NHN (developers@xpressengine.com)
     * @brief view class of the apicenter module
     *
     **/

    class apicenterView extends apicenter {
		
		var $auth = null;
		var $instruction_path = "";
        /**
         * @brief Initialization
         **/
        function init() {
			// require auth class
			require_once(_XE_PATH_.'modules/apicenter/auth.php');
			Context::set('module_path',$this->module_path);
			$this->auth = new auth();
			$this->instruction_path = $this->module_path.'instruction';
        }

        /**
         * @brief display Apicenter Data, the index method of the module
         **/
		function dispApicenterData(){
			$vars = Context::getRequestVars();
		
			// check  whether the uid  (url id) is input
			$uid = $vars->uid;
			if(!$uid || !$this->validateUrlParameter($uid,"numeric")) {
				$error_code = 101;
				$error_message =  'URL Parameter Error! Invalid uid value, uid must be a number.';
				$error_content = $this->auth->generateError($error_code, $error_message);
				return $this->sendError($error_content);
			}

			$oApicenterAdminModel = &getAdminModel('apicenter'); 
			$apiUrlInfo = $oApicenterAdminModel->getApiUrl($uid);

			// check the requested API Url is exsited
			if(!$apiUrlInfo || $apiUrlInfo->error) {
				$error_code = 102;
				$error_message =  'URL Parameter Error! Invalid uid value, the uid is not existed.';
				$error_content = $this->auth->generateError($error_code, $error_message);
				return $this->sendError($error_content);
			}

			$api_type = $apiUrlInfo->api_type;
			$output_format = $apiUrlInfo->output_format;
			$module_srl = $apiUrlInfo->module_srl;
			$mid = $apiUrlInfo->mid;
			Context::set('mid',$mid);
			$grants =  $apiUrlInfo->group_srl;

			// check whether api key is input
			$api_key = $vars->api_k;
			if(!$api_key || !$this->validateApiKey($api_key)) {
				$error_code = 121;
				$error_message =  'API Access Error! API Key must be 16 alphanumeric chars.';
				$error_content = $this->auth->generateError($error_code, $error_message,$output_format);
				return $this->sendError($error_content);
			}

			// check the API key is correct
			if($api_key!=$apiUrlInfo->api_key) {
				$error_code = 122;
				$error_message =  'API Access Error! API Key is not existed.';
				$error_content = $this->auth->generateError($error_code, $error_message,$output_format);
				return $this->sendError($error_content);
			}

			// limit and page value must be numeric (List type)
			if($vars->limit && !$this->validateUrlParameter($vars->limit,"numeric")) {
				$error_code = 103;
				$error_message =  'URL Parameter Error! Invalid limit value,  limit must be a number.';
				$error_content = $this->auth->generateError($error_code, $error_message,$output_format);
				return $this->sendError($error_content);
			}

			if(isset($vars->page)){
				if(!$this->validateUrlParameter($vars->page,"numeric") || $vars->page == 0) {
					$error_code = 104;
					$error_message =  'URL Parameter Error! Invalid page value,  page must be a number';
					$error_content = $this->auth->generateError($error_code, $error_message,$output_format);
					return $this->sendError($error_content);
				}
			}

			$moduleSupport = array('board','wiki', 'kin','forum', 'textyle','faq','guestbook');
			$module = $apiUrlInfo->module;

			if(!in_array($module,$moduleSupport)) {
				$error_code = 140;
				$error_message =  'Error! API Center doesn\'t support this module';
				$error_content = $this->auth->generateError($error_code, $error_message,$output_format);
				return $this->sendError($error_content);
			}

			$use_login_api = false;

			if($apiUrlInfo->oauth == 'Y'){
				$token = $vars->token;

				//check the session id is correct
				if($token) {
					$login_member_srl =  $this->auth->doAccessTokenAuth($token,$uid);

					if(!$login_member_srl){
						$error_code = 123;
						$error_message =  Context::get('error_message')?Context::get('error_message'):'API Access Error! Invalid token value, access token is not existed.';
						$error_content = $this->auth->generateError($error_code, $error_message,$output_format);
						return $this->sendError($error_content);
					}else{
						Context::set('login_member_srl',$login_member_srl);
					}
				}
				
				// if session id is not existed, then the request URL must be a Login API request
				if(!$token){
					$use_login_api = true;
					$u_name = Context::get('uname');
					$u_password = Context::get('pwd');

					// check username and password is input
					if(!$u_name || !$u_password) {
						$error_code = 124;
						$error_message =  'API Access Error! It is an Auth API, please use Login API first.';
						$error_content = $this->auth->generateError($error_code, $error_message,$output_format);
						return $this->sendError($error_content);
					}
					
					// do open authentication 
					$tokenInfo = $this->auth->doLoginAuth($uid, $u_name,$u_password,$output_format,$grants);

					// if not get the session id information, return 
					if(!$tokenInfo) {
						$error_code = 131;
						$error_message =  Context::get('error_message')?Context::get('error_message'):'Login API Error! Invalid User Information,  request Login API failed.';
						$error_content = $this->auth->generateError($error_code, $error_message,$output_format);
						return $this->sendError($error_content);
					}
					$retrieveData = $tokenInfo;
				}
			}

			// check the doc_id is valid
			if($vars->doc_id && !$this->validateUrlParameter($vars->doc_id,"numeric"))  {
				$error_code = 105;
				$error_message =  'URL Parameter Error! Invalid doc_id value, doc_id must be a number.';
				$error_content = $this->auth->generateError($error_code, $error_message,$output_format);
				return $this->sendError($error_content);
			}

			// check the cat_id is valid
			if($vars->cat_id && !$this->validateUrlParameter($vars->cat_id,"numeric"))  {
				$error_code = 106;
				$error_message =  'URL Parameter Error! Invalid cat_id value, cat_id must be a number';
				$error_content = $this->auth->generateError($error_code, $error_message,$output_format);
				return $this->sendError($error_content);
			}

			// if the request api is not login api
			if(!$use_login_api){
				// if api type is view or modify or delete, doc_id must be input
				if($api_type == 'view' || $api_type == 'modify' || $api_type == 'delete'){
					if(!$vars->doc_id || !$this->validateUrlParameter($vars->doc_id,"numeric"))  {
						$error_code = 107;
						$error_message =  'URL Parameter Error! This API URL must contain a valid doc_id value';
						$error_content = $this->auth->generateError($error_code, $error_message,$output_format);
						return $this->sendError($error_content);
					}
				}
				// if api type is write or modify or delete, the request must be POST
				if($api_type == 'write' || $api_type == 'modify' || $api_type == 'delete'){
					$request_method = $_SERVER['REQUEST_METHOD'];
					if($request_method != 'POST')  {
						$error_code = 128;
						$error_message =  'API Access Error! This API can only called by an POST HTTP request';
						$error_content = $this->auth->generateError($error_code, $error_message,$output_format);
						return $this->sendError($error_content);
					}
				}
				
				if($api_type == 'category'){
					if(!$vars->target || $vars->target != 'category'){
						$error_code = 108;
						$error_message =  'URL Parameter Error! The category API must contain target value: category.';
						$error_content = $this->auth->generateError($error_code, $error_message,$output_format);
						return $this->sendError($error_content);
					}
				}
				$retrieveData = $this->retrieveData($module,$api_type,$output_format,$module_srl,$grants);
			}

			if(!$retrieveData) {
				$error_code = 150;
				$error_message =  Context::get('error_message')?Context::get('error_message'):'Error! Invalid Request. ';
				$error_content = $this->auth->generateError($error_code, $error_message,$output_format);
				return $this->sendError($error_content);
			}
			Context::set('content',$retrieveData);
			Context::set('output_format',$output_format);
	
			$path = $this->module_path.'tpl/';
	        $this->setTemplatePath($path);
            $this->setTemplateFile('display');
		}

        /**
         * @brief retrieves data from a specific module's API
         **/
		function retrieveData($module, $api_type, $output_format, $module_srl,$grants = 0){
			if(file_exists(_XE_PATH_.'modules/apicenter/apicenter.'.$module.'.php')){
				require_once(_XE_PATH_.'modules/apicenter/apicenter.'.$module.'.php');
			}else{
				Context::set('error_message','The module '.$module.' API class does not exist.');
				return false;
			}
				
			// check if the module exists or not
			$oModuleModel = &getModel('module');
			$moduleInfo = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

			if(!$moduleInfo) return false;
			
			Context::set('module_srl',$module_srl);
			Context::set('module_path',$this->module_path);
			Context::set('grants',$grants);

			// check the apicenter module class exists
			$className = 'apicenter'.ucfirst($module);
			
			if(!class_exists($className)) return false;
			$api_obj = new $className();

			// check the api function exists 
			$api_function = '_'.$api_type;
			if(!method_exists($api_obj, $api_function)) {
				Context::set('error_message','The '.$api_type.' API method of '.$module.' module does not exist.');
				return false;
			}

			$content = $api_obj->$api_function($output_format);

			return $content;
		}

        /**
         * @brief check whehter API key is valid, 16 chars [A-Z0-9]
         **/
		function validateApiKey($apikey){
			$match="/^[A-Z0-9]{16}$/";
			$validate = preg_match($match,$apikey);

			return $validate;
		}

        /**
         * @brief check whehter URL variable is valid
         **/
		function validateUrlParameter($source, $type='integer'){
			$validate = true;
			if($type == 'interger') $validate =  is_int($source);
			if($type == 'numeric') $validate =  is_numeric($source);
			
			return $validate;
		}

        /**
         * @brief send error method
         **/
		function sendError($error_content){
			Context::set('error_content',$error_content);
			$path = $this->module_path.'tpl/';
	        $this->setTemplatePath($path);
            $this->setTemplateFile('error');
		}

        /**
         * @brief display API instruction page
         **/
       function dispApicenterViewInstruction(){
			$vars = Context::getRequestVars();

		   $api_module = $vars->api_module;
		   $api_output = $vars->api_output;

		   if(!$api_module || !$api_output)  return new Object(-1, 'Invalid Request! ');

		   $file = '_'.$api_module;

		   Context::set('api_module', $api_module);
		   Context::set('api_output', $api_output);
	        
		   $this->setTemplatePath($this->instruction_path);
           $this->setTemplateFile($file);
	   }


	}
?>
