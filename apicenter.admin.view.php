<?php
    /**
     * @class  apicenterAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief admin view class of the apicenter module
     *
     **/

    class apicenterAdminView extends apicenter {

        var $config = null;

        /**
         * @brief Initialization
         **/
        function init() {
            // Get configurations (using module model object)
            $oModuleModel = &getModel('module');
            $this->config = $oModuleModel->getModuleConfig('apicenter');
            Context::set('config',$this->config);

            $this->setTemplatePath($this->module_path."/tpl/");
        }

        /**
         * @brief display api items list
         **/
		function dispApicenterAdminList(){
			$oApicenterAdminModel = &getAdminModel('apicenter');
			$module_list = $oApicenterAdminModel->getModuleListInSupport();
			Context::set('module_list',$module_list);

			$source_args = Context::getRequestVars();
			if($source_args->s_module){
				if($source_args->s_module == 'all') $args->s_module = "%%";
				else $args->s_module = $source_args->s_module;
			}

			if($source_args->s_output_format){
				if($source_args->s_output_format == 'all') $args->s_output_format = "%%";
				else $args->s_output_format = $source_args->s_output_format;
			}

			if($source_args->s_contents){
				$args->s_contents = $source_args->s_contents;
			}

			$apiItemList = $oApicenterAdminModel->getApiItems($args);
			Context::set('apiItemList',$apiItemList);

			$this->setTemplateFile('index');
		}

        /**
         * @brief display api item view page
         **/
		function dispApicenterAdminView(){
			$api_item_srl = Context::get('api_item_srl');
			if(!$api_item_srl) return new Object(-1, 'Invalid Request! ');

			$oApicenterAdminModel = &getAdminModel('apicenter');
			
			$apiItem = $oApicenterAdminModel->getApiItemInfo($api_item_srl);
			if(!$apiItem) return new Object(-1, 'Invalid Request! ');
			Context::set('apiItem',$apiItem);

			$apiUrls = $oApicenterAdminModel->getApiItemUrls($api_item_srl);
			Context::set('apiUrls',$apiUrls);

			$oModuleModel = &getModel('module');
			$target_module_Info = $oModuleModel->getModuleInfoByModuleSrl($apiItem->module_srl);
			Context::set('target_module_Info',$target_module_Info);

			$this->setTemplateFile('api_view');
			
		}

        /**
         * @brief display api key list
         **/
		function dispApicenterAdminKeyList(){
			$oApicenterAdminModel = &getAdminModel('apicenter');
			$apiKeyList = $oApicenterAdminModel->getApiKeyList();
			Context::set('apiKeyList',$apiKeyList);
			
			$this->setTemplateFile('api_key_list');
		}

        /**
         * @brief display api urls grants info
         **/
		function dispApicenterAdminGrantInfo(){
			$api_item_srl = Context::get('api_item_srl');
			if(!$api_item_srl) return false;

			$oApicenterAdminModel = &getAdminModel('apicenter');
			$apiGrants = $oApicenterAdminModel->getApiGrantsByApiItem($api_item_srl);
			$api_item = $oApicenterAdminModel->getApiItemInfo($api_item_srl);

			foreach($apiGrants as $key => $grant){
				$api_grants[$grant->api_type]->api_item_srl = $api_item_srl;
				$api_grants[$grant->api_type]->api_url_srl = $grant->api_url_srl;
				$api_grants[$grant->api_type]->api_type = $grant->api_type;
				$api_grants[$grant->api_type]->group_srl = $grant->group_srl;
			}

			Context::set('api_item', $api_item);
			Context::set('api_grants', $api_grants);

			$this->setTemplateFile('api_grants');
		}

    }
?>
