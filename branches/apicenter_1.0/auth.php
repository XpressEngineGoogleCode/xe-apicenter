<?php
    /**
     * @class  auth
     * @author NHN (developers@xpressengine.com)
     * @brief auth class of the apicenter module
     *
     **/

    class auth {

		var $module_path = "";
		var $token_expire = "";

        /**
         * @brief Initialization
         **/
        function auth() {
			$this->module_path =  Context::get('module_path');
			// default access token expire is one week
			$this->token_expire =  date('YmdHis',(time() + (7 * 24 * 60 * 60)));
        }

        /**
         * @brief operate a login auth when a login API called 
         **/
		function doLoginAuth($url_id, $u_name, $u_password, $response, $grants = 0){
			if(!$response || !($response=='xml' || $response=='json')) return false; 
			if(!$u_name || !$u_password ||!$url_id) return false; 
			
			$content = null;

			$userInfo = $this->checkUserInfo($u_name,$u_password, $grants);
			
			// if the user is invalid, return null
			if(!$userInfo->member_srl) return null; 
			
			$access_token = $this->generateAccessToken($u_name,$u_password,'',$this->token_expire);

			// insert access token
			$oApicenterAdminController = &getAdminController('apicenter');
			$oApicenterAdminModel = &getAdminModel('apicenter');
			$tokenInfo = $oApicenterAdminModel->getApiAccessTokenByMemberUrl($userInfo->member_srl,$url_id);

			if($tokenInfo) $output = $oApicenterAdminController->deleteAccessToken($tokenInfo->access_token_srl);
			$output = $oApicenterAdminController->insertAccessToken($access_token,$userInfo->member_srl,$url_id, $this->token_expire);

			Context::set('access_token', urlencode($access_token));
			Context::set('expire', $this->token_expire);

			// return a relevant data file with session id
			$path = "";
			if($response == 'xml'){
				Context::setResponseMethod("XMLRPC");
				$path = $this->module_path.'output/xml';
			}
			elseif($response == 'json'){
				Context::setResponseMethod("JSON");
				$path = $this->module_path.'output/json';
			}
			$oTemplate = new TemplateHandler();
			$file = "session";
			$content = $oTemplate->compile($path, $file);
			
			return $content;
		}

        /**
         * @brief check user info by a given username and password
         **/
		function checkUserInfo($u_name, $u_pwd, $grants = 0){
			if(!$u_name || !$u_pwd) return false;
			$u_name = trim($u_name);
			$u_pwd = trim($u_pwd);
			
			$oMemberModel= &getModel('member');

			// check the user is existed, either use user_id or user_email
			$member_info = $oMemberModel->getMemberInfoByUserID($u_name);
			if(!$member_info)$member_info = $oMemberModel->getMemberInfoByEmailAddress($u_name);

			// check the password is correct
			if(!$member_info) return false;
			if(!($oMemberModel->isValidPassword($member_info->password, $u_pwd, $member_info->member_srl))) {Context::set('error_message', 'Error! Invlid user information'); return false;}

			// grants equals to -2, then the user must be administrator
			if(intval($grants) == -2){
				if($member_info->is_admin != 'Y') {Context::set('error_message','Error! You don\'t have permission to call this API.'); return false;}
			}

			return $member_info;
		}

        /**
         * @brief generate access token based on username and password
         **/
		function generateAccessToken($u_name, $u_pwd, $auth_token_secret = '',$expire=null){
			if(!$expire)
				$access_token = base64_encode(hash_hmac('sha1',$u_name,$u_pwd."&".$auth_token_secret,true));
			else
				$access_token = base64_encode(hash_hmac('sha1',$u_name,$u_pwd."&".$auth_token_secret."&".$expire,true));

			$access_token = str_replace('+','=',$access_token);
			return $access_token;
		}

        /**
         * @brief operate a token auth when a token API called 
         **/
		function doAccessTokenAuth($access_token,$url_id){
			if(!$access_token) return false;
			$oApicenterAdminModel = &getAdminModel('apicenter');
			$tokenInfo = $oApicenterAdminModel->getApiAccessTokenByAccessTokenUrl(urldecode($access_token),$url_id);
			
			if(!$tokenInfo) return false;

			// check the token is expired or not
			$now = date('YmdHis',time());
			if($now>$tokenInfo->expire) {
				Context::set('error_message','Error! The access token is expired, please call the login API first.');
				return false;
			}	
			return $tokenInfo->member_srl;
		}

        /**
         * @brief generate Error Message
         **/
		function generateError($error_code = 100, $error_message = null,$response = 'xml'){
			if($response == 'xml'){
				Context::setResponseMethod("XMLRPC");
				$path = $this->module_path.'output/xml';
			}
			elseif($response == 'json'){
				Context::setResponseMethod("JSON");
				$path = $this->module_path.'output/json';
			}

			Context::set('error_code', $error_code);
			Context::set('error_message', $error_message);

			$oTemplate = new TemplateHandler();
			$file = "error";
			$content = $oTemplate->compile($path, $file);
			
			return $content;
		}
    }
?>
