<?php
    /**
     * @class  apicenterFaq
     * @author NHN (developers@xpressengine.com)
     * @brief faq class of the apicenter module
     **/

    class apicenterFaq {

		var $module_path = "";
		var $xml_path = "";
		var $json_path = "";
		var $grants = "";

        /**
         * @brief Initialization (constructor for PHP < 5)
         **/
        function apicenterFaq() {
			$this->module_path =  Context::get('module_path');
			$this->xml_path = $this->module_path.'output/xml';
			$this->json_path = $this->module_path.'output/json';
			$this->grants = Context::get('grants');
        }

        /**
         * @brief constructor for PHP >= 5
         **/
        function __construct() {
			$this->module_path =  Context::get('module_path');
			$this->xml_path = $this->module_path.'output/xml';
			$this->json_path = $this->module_path.'output/json';
			$this->grants = Context::get('grants');
        }

        /**
         * @brief module: faq, api_type: list
         **/
		function _list($format = "xml"){
			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}
	
			$module_srl = Context::get('module_srl');
			$document_list = $this->getFaqDocumentList($module_srl);
	
			Context::set('document_list', $document_list);

			$oTemplate = new TemplateHandler();
			$file = "faqList";

			if($format == "xml"){
				Context::setResponseMethod("XMLRPC");
				$content = $oTemplate->compile($this->xml_path, $file);
			}elseif($format =="json"){
				Context::setResponseMethod("JSON");
				$content = $oTemplate->compile($this->json_path, $file);
			}
			
			return $content;
		}

        /**
         * @brief module: faq, api_type: view 
         **/
		function _view($format = "xml"){
			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}

			$document_srl = Context::get('doc_id');
			if(!$document_srl) return false;

			$document_info = $this->getFaqDocument($document_srl);
			if(!$document_info) { Context::set('error_message','The document is not existed'); return false; }

			// if the document does not belong to the module
			$module_srl = Context::get('module_srl');
			if($document_info->get('module_srl')!=$module_srl) return false;

			Context::set('document_info', $document_info);

			$oTemplate = new TemplateHandler();		
			$file = "faqView";

			if($format == "xml"){
				Context::setResponseMethod("XMLRPC");
				$content = $oTemplate->compile($this->xml_path, $file);
			}elseif($format== "json"){
				Context::setResponseMethod("JSON");
				$content = $oTemplate->compile($this->json_path, $file);
			}

			return $content;
		}

        /**
         * @brief module: faq, api_type: write
         **/
		function _write($format = "xml"){
			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}
	
			$obj->question = $_POST['que'];
			$obj->answer = $_POST['ans'];
			$obj->category_srl = $_POST['cat_id'];
			$obj->module_srl = Context::get('module_srl');

			if($obj->category_srl){
				$oFaqModel = &getModel('faq');
				$category_info =$oFaqModel->getCategory($obj->category_srl);
				if(!$category_info->category_srl) { Context::set('error_message','Write document failed, the cat_id is invalid.'); return false; }
			}

			// if the category does not belong to the module
			$module_srl = Context::get('module_srl');
			if($category_info->module_srl != $module_srl) return false;

			$document_info = $this->insertFaqDocument($obj);
			if(!$document_info){ Context::set('error_message','Write document failed, please insert valid question and answer.'); return false; }

			Context::set('document_info', $document_info);
			
			$oTemplate = new TemplateHandler();
			$file = "faqWrite";

			if($format == "xml"){
				Context::setResponseMethod("XMLRPC");
				$content = $oTemplate->compile($this->xml_path, $file);
			}elseif($format== "json"){
				Context::setResponseMethod("JSON");
				$content = $oTemplate->compile($this->json_path, $file);
			}

			return $content;
		}

		/**
         * @brief module: faq, api_type: modify
         **/
		function _modify($format = "xml"){
			$doc_id = Context::get('doc_id');
			$oFaqModel = &getModel('faq');
			$documentInfo = $oFaqModel->getQuestion($doc_id);
			
			// if the document doesn't exist, return null
			if(!$doc_id || !$documentInfo->question_srl) {Context::set('error_message','The document is not existed'); return false;}
			
			// if the document does not belong to the module
			$module_srl = Context::get('module_srl');
			if($documentInfo->get('module_srl')!=$module_srl) return false;

			Context::set('documentInfo', $documentInfo);

			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}

			$obj->question = $_POST['que'];
			$obj->answer = $_POST['ans'];
			$obj->category_id = $_POST['cat_id'];
			$obj->module_srl = Context::get('module_srl');

			// check the category is valid
			if($obj->category_srl){
				$oFaqModel = &getModel('faq');
				$category_info =$oFaqModel->getCategory($obj->category_srl);
				if(!$category_info->category_srl) { Context::set('error_message','Write document failed, the cat_id is invalid.'); return false; }
			}

			$document_info = $this->updateFaqDocument($obj);
			Context::set('document_info', $document_info);

			$oTemplate = new TemplateHandler();
			$file = "faqModify";

			if($format == "xml"){
				Context::setResponseMethod("XMLRPC");
				$content = $oTemplate->compile($this->xml_path, $file);
			}elseif($format == "json"){
				Context::setResponseMethod("JSON");
				$content = $oTemplate->compile($this->json_path, $file);
			}

			return $content;
		}

		/**
         * @brief module: faq, api_type: delete
         **/
		function _delete($format = "xml"){
			$doc_id = Context::get('doc_id');
			$oFaqModel = &getModel('faq');
			$documentInfo = $oFaqModel->getQuestion($doc_id);
						
			// if the document doesn't exist, return null
			if(!$doc_id || !$documentInfo->question_srl){Context::set('error_message','The document is not existed'); return false;}
			
			// if the document does not belong to the module
			$module_srl = Context::get('module_srl');
			if($documentInfo->get('module_srl')!=$module_srl) return false;

			Context::set('documentInfo', $documentInfo);

			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}

			$obj->question_srl = $doc_id;
			$obj->module_srl = Context::get('module_srl');
			$document_info = $this->deleteFaqDocument($obj);
			Context::set('document_info', $document_info);
	
			$oTemplate = new TemplateHandler();
			$file = "faqDelete";

			if($format == "xml"){
				Context::setResponseMethod("XMLRPC");
				$content = $oTemplate->compile($this->xml_path, $file);
			}elseif($format == "json"){
				Context::setResponseMethod("JSON");
				$content = $oTemplate->compile($this->json_path, $file);
			}

			return $content;
			
		}

		/**
         * @brief module: faq, api_type: category
         **/
		function _category($format = "xml"){
			$oFaqModel = &getModel('faq');
			$module_srl = Context::get('module_srl');

			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}

			$category_list = $oFaqModel->getAllCategoryList($module_srl,0);
			if(!$category_list) { Context::set('error_message','The catagory list is null.'); return false; }		

			Context::set('category_list', $category_list);

			$oTemplate = new TemplateHandler();		
			$file = "faqCategory";

			if($format == "xml"){
				Context::setResponseMethod("XMLRPC");
				$content = $oTemplate->compile($this->xml_path, $file);
			}elseif($format== "json"){
				Context::setResponseMethod("JSON");
				$content = $oTemplate->compile($this->json_path, $file);
			}

			return $content;
		}

        /**
         * @brief get faq document list
         **/
		function getFaqDocumentList($module_srl){
			$document_list = array();

			$oFaqModel = &getModel('faq');
			Context::set('oFaqModel',$oFaqModel);
			$oMemberModel = &getModel('member');
			Context::set('oMemberModel',$oMemberModel);

			$member_info = $oMemberModel->getMemberInfoByMemberSrl('4');

			$args->module_srl = $module_srl;

			$listCount = Context::get('limit');
			$page = Context::get('page');

			if($listCount && is_numeric($listCount) && intval($listCount)!=0){
				$args->list_count = intval($listCount);
				$args->page = $page?$page:1;
			}else{
				$doc_count = $oFaqModel->getQuestionCount($module_srl);
				$args->list_count = $doc_count;
				$args->page = 1;
			}
			
			$output = $oFaqModel->getQuestionList($args);

			if (!is_array($output->data)) {
				$document_list = array($output->data);
			} else {
				$document_list = $output->data;
			}

			Context::set('count', count($output->data));

			return $document_list;
		}

        /**
         * @brief get faq document info
         **/
		function getFaqDocument($document_srl){
			$oFaqModel = &getModel('faq');
			$document_info = $oFaqModel->getQuestion($document_srl);

			if(!$document_info->question_srl) return false;

			$category_srl = $document_info->get('category_srl');
			if($category_srl){
				$categoryInfo = $oFaqModel->getCategory($category_srl);
				$document_info->category_srl = $category_srl;
				$document_info->category_title = $categoryInfo->title;
			}

			$oMemberModel = &getModel('member');
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($document_info->get('member_srl'));
			if($member_info)
				$document_info->nick_name = $member_info->nick_name;

			return $document_info;
		}

        /**
         * @brief insert faq document
         **/
		function insertFaqDocument($obj){		
			if(!$obj->question || !$obj->answer || !$obj->module_srl) return false;

			$oFaqController = &getController("faq");

			$member_info = Context::get('member_info');
	
			if($member_info){
				$args->member_srl = $member_info->member_srl;
			}else{
				$args->member_srl = 0;
			}

			$args->question_srl = getNextSequence();
			$args->question  = $obj->question;
			$args->answer  = $obj->answer;
			$args->module_srl  = $obj->module_srl;
			$args->update_order = $args->list_order = getNextSequence() * -1;
			$args->category_srl = $obj->category_srl?$obj->category_srl:0;

			// Remove script
			settype($args->question, "string");
			$args->question = removeHackTag($args->question);

			// Remove XE's own tags from the answer.
			$args->answer = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $args->answer);
			if(Mobile::isFromMobilePhone())
			{
				$args->answer = nl2br(htmlspecialchars($args->answer));
			}
			// Remove iframe and script .
			$args->answer = stripslashes(removeHackTag($args->answer));

			$output = executeQuery('faq.insertQuestion', $args);

			// update category count
			if($args->category_srl) $oFaqController->updateCategoryCount($args->module_srl, $args->category_srl);

			$document_info = $this->getFaqDocument($args->question_srl);

			return $document_info;
		}

        /**
         * @brief update faq document
         **/
		function updateFaqDocument($obj){
			if(!$obj->question || !$obj->answer || !$obj->module_srl) return false;

			$oFaqController = &getController("faq");
			
			// get document item
			$documentInfo = Context::get('documentInfo');
			if(!$documentInfo->question_srl) return false;
	

			$args->question_srl = $documentInfo->question_srl;
			$args->category_srl =  $obj->category_id?$obj->category_id:$documentInfo->get('category_srl');
			$args->regdate =  $documentInfo->get('regdate');

			$member_info = Context::get('member_info');

			if($member_info){
				$args->member_srl = $member_info->member_srl;
			}else{
				$args->member_srl = 0;
			}

			$args->question  = $obj->question;
			$args->answer  = $obj->answer;
			$args->module_srl  = $obj->module_srl;

			// If the title is empty, extract string from the contents.
			settype($args->question, "string");
			$args->question = removeHackTag($args->question);

			// Remove XE's own tags from the contents.
			$args->answer = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $args->answer);
			if(Mobile::isFromMobilePhone())
			{
				$args->answer = nl2br(htmlspecialchars($args->answer));
			}
			// Remove iframe and script .
			$args->answer = stripslashes(removeHackTag($args->answer));

			$output = executeQuery('faq.updateQuestion', $args);

			if($args->category_srl){
				if($documentInfo->get('category_srl') != $args->category_srl) $oFaqController->updateCategoryCount($args->module_srl, $documentInfo->get('category_srl'));
			    $oFaqController->updateCategoryCount($args->module_srl, $args->category_srl);
			}

			$document_info = $this->getFaqDocument($args->question_srl);

			return $document_info;
		}

       /**
         * @brief delete faq document
         **/
		function deleteFaqDocument($obj){
			// get document item
			$documentInfo = Context::get('documentInfo');
			if(!$documentInfo->question_srl || ($obj->question_srl != $documentInfo->question_srl)) return false;

			$args->question_srl = $obj->question_srl;
			$args->module_srl = Context::get('module_srl');

			$output = executeQuery('faq.deleteQuestion', $args);

			// update category count
			$oFaqController = &getController("faq");
			if($documentInfo->get('category_srl')) $oFaqController->updateCategoryCount($args->module_srl,$documentInfo->get('category_srl'));

			return $document_info;
		}


		function checkGrants($grants){
				$login_member_srl = Context::get('login_member_srl');
				//check the login member is existed
				 if(!$login_member_srl) return null;
	
				$oMemberModel= &getModel('member');
				// check the user is existed, either by use user_id or user_email
				$member_info = $oMemberModel->getMemberInfoByMemberSrl($login_member_srl);
				if(!$member_info) return null;

				// only for admin user, if grants equals to -2
				if((intval($grants) == -2) && ($member_info->is_admin !='Y')) {
					Context::set('error_message','Error! You don\'t have permission to call this API.'); 
					return null;
				}

				// only for document owner. if grants equals to -3
				if((intval($grants) == -3)){
					$documentInfo = Context::get('documentInfo');
					if(!$documentInfo || ($documentInfo->get('member_srl') != $member_info->member_srl)){
						Context::set('error_message','Error! You don\'t have permission to call this API.'); 
						return null;
					}
				}

				return $member_info;
		}

    }
?>
