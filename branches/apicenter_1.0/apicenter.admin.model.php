<?php
    /**
     * @class  apicenterAdminModel
     * @author NHN (developers@xpressengine.com)
     * @brief Model class of apicenter module
     **/

    class apicenterAdminModel extends module {
        /**
         * @brief Initialization
         **/
        function init() {
        }

		/**
         * @brief select installed module list that API center supports
         **/
		function getModuleListInSupport()
		{
			$moduleSupport = array('board', 'wiki', 'kin', 'forum' , 'textyle', 'faq' ,'guestbook');

			$oModuleModel = &getModel('module');
			$columnList = array('module');
			$moduleList = array('page');

			//$output = $oModuleModel->getModuleListByInstance($site_srl, $columnList);
			$output = executeQuery("apicenter.getModuleListInstalled");
			if(is_array($output->data))
			{
				foreach($output->data AS $key=>$value)
				{
					array_push($moduleList, $value->module);
				}
			}
			$moduleList = array_unique($moduleList);

			$moduleInfoList = array();
			if(is_array($moduleList))
			{
				foreach($moduleList AS $key=>$value)
				{
					if(in_array($value,$moduleSupport)){
						$moduleInfo = $oModuleModel->getModuleInfoXml($value);
						$moduleInfoList[$value] = $moduleInfo;
					}
				}
			}

            return $moduleInfoList;
		}

		/**
         * @brief get API items list
         **/
		function getApiItems($obj =  null){
			$args->sort_index = "api_item_srl";
            $args->page = Context::get('page');
            $args->list_count = 10;
            $args->page_count = 10;

			if($obj->s_module) $args->s_module = $obj->s_module;
			if($obj->s_output_format) $args->s_output_format = $obj->s_output_format;
			if($obj->s_contents) $args->s_contents = $obj->s_contents;

			$output = executeQueryArray('apicenter.getApiItems', $args);
			if(!$output->toBool()) {	return $output;}

			// page navigation
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_navigation', $output->page_navigation);
			Context::set('page_navigation', $output->page_navigation);

			return $output->data;
		}

		/**
         * @brief get API items Info (Ajax method)
         **/
		function getApicenterAdminItemInfo(){
			$api_item_srl = Context::get('api_item_srl');
			$apiItem = $this->getApiItemInfo($api_item_srl);

			$apiTypes = unserialize($apiItem->api_types);
			$apiItem->apiTypes = $apiTypes;

			$this->add('api_item', $apiItem);
		}

		/**
         * @brief get API item Info
         **/
		function getApiItemInfo($api_item_srl){
			$args->api_item_srl = $api_item_srl;
			$output = executeQuery('apicenter.getApiItemInfo', $args);
			if(!$output->toBool()) {return $output;}
			return $output->data;
		}

		/**
         * @brief get API item URLs
         **/
		function getApiItemUrls($api_item_srl){
			$args->api_item_srl = $api_item_srl;
			$output = executeQuery('apicenter.getApiItemUrls', $args);
			if(!$output->toBool()) {return $output;}
			return $output->data;
		}

		/**
         * @brief get API url Info
         **/
		function getApiUrl($api_url_srl){
			$args->api_url_srl = $api_url_srl;
			$output = executeQuery('apicenter.getApiUrl', $args);
			if(!$output->toBool()) {return $output;}
			return $output->data;
		}

		/**
         * @brief get API key list
         **/
		function getApiKeyList(){
			$args->sort_index = "api_keys.regdate";
            $args->page = Context::get('page');
            $args->list_count = 10;
            $args->page_count = 10;

			$output = executeQueryArray('apicenter.getApiKeyList', $args);
			if(!$output->toBool()) {return $output;}

			// page navigation
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_navigation', $output->page_navigation);
			Context::set('page_navigation', $output->page_navigation);

			return $output->data;
		}

		/**
         * @brief check the API Key is existed or not
         **/
		function getApicenterAdminKeyValidate(){
			$api_key = Context::get('api_key');
			$apikeyInfo = $this->getApiKeyInfoByKey($api_key);

			$api_key_exists = 0;

			if(!empty($apikeyInfo)){
				$api_key_exists = 1;
			}

			$this->add('api_key_exists', $api_key_exists);
		}

		/**
         * @brief get API key Info by a given key
         **/
		function getApiKeyInfoByKey($api_key){
			if(!$api_key) return new Object(-1, "msg_invalid_request");
			$args->api_key = $api_key;
			$output = executeQuery('apicenter.getApiKeyInfoByKey', $args);
			if(!$output->toBool()) {return $output;}
			return $output->data;
		}

		/**
         * @brief get API Key Info (Ajax method)
         **/
		function getApicenterAdminKeyInfo(){
			$api_key_srl = Context::get('api_key_srl');
			$apiKey = $this->getApiKeyInfoByKeySrl($api_key_srl);

			if(!$apiKey) return new Object(-1, "msg_invalid_request");
			
			$this->add('api_key', $apiKey);

		}

		/**
         * @brief get API key Info by a given key srl
         **/
		function getApiKeyInfoByKeySrl($api_key_srl){
			if(!$api_key_srl) return new Object(-1, "msg_invalid_request");
			$args->api_key_srl = $api_key_srl;
			$output = executeQuery('apicenter.getApiKeyInfoByKeySrl', $args);
			if(!$output->toBool()) {return $output;}
			return $output->data;
		}

		/**
         * @brief get API grants Info by a given api item srl
         **/
		function getApiGrantsByApiItem($api_item_srl){
			if(!$api_item_srl) return new Object(-1, "msg_invalid_request");
			$args->api_item_srl = $api_item_srl;
			$output = executeQuery('apicenter.getApiGrantsByApiItem', $args);
			if(!$output->toBool()) {return $output;}
			return $output->data;
		}

		/**
         * @brief get API access token info by member_srl
         **/
		function getApiAccessTokenByMemberUrl($member_srl, $api_url_srl){
			if(!$member_srl || !$api_url_srl) return new Object(-1, "msg_invalid_request");
			$args->member_srl = $member_srl;
			$args->api_url_srl = $api_url_srl;
			$output = executeQuery('apicenter.getApiAccessTokenByMemberUrl', $args);
			if(!$output->toBool()) {return $output;}
			return $output->data;
		}

		/**
         * @brief get API access token info by a given access token
         **/
		function getApiAccessTokenByAccessTokenUrl($access_token, $url_id){
			if(!$access_token || !$url_id) return new Object(-1, "msg_invalid_request");
			$args->access_token = $access_token;
			$args->api_url_srl = $url_id;
			$output = executeQuery('apicenter.getApiAccessTokenByAccessTokenUrl', $args);
			if(!$output->toBool()) {return $output;}
			return $output->data;
		}

    }
?>
