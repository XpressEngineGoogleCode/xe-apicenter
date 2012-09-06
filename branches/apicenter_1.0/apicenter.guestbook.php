<?php
    /**
     * @class  apicenterGuestbook
     * @author NHN (developers@xpressengine.com)
     * @brief guestbook class of the apicenter module
     **/

    class apicenterGuestbook {

		var $module_path = "";
		var $xml_path = "";
		var $json_path = "";
		var $grants = "";

        /**
         * @brief Initialization (constructor for PHP < 5)
         **/
        function apicenterGuestbook() {
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
         * @brief module: guestbook, api_type: list
         **/
		function _list($format = "xml"){
			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}

			$module_srl = Context::get('module_srl');
			$document_list = $this->getGuestbookDocumentList($module_srl);

			Context::set('document_list', $document_list);

			$oTemplate = new TemplateHandler();
			$file = "guestbookList";

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
         * @brief module: guestbook, api_type: view 
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

			$document_info = $this->getGuestbookDocument($document_srl);
			if(!$document_info) { Context::set('error_message','The document is not existed'); return false; }
	
			// if the document does not belong to the module
			$module_srl = Context::get('module_srl');
			if($document_info->module_srl !=$module_srl) return false;
			
			$comment_list = $document_info->comment_list;
			Context::set('document_info', $document_info);
			Context::set('comment_list', $comment_list);

			$oTemplate = new TemplateHandler();		
			$file = "guestbookView";

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
         * @brief module: guestbook, api_type: write
         **/
		function _write($format = "xml"){
			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}

			$obj->content = $_POST['content'];
			$obj->module_srl = Context::get('module_srl');

			$document_info = $this->insertGuestbookDocument($obj);
			if(!$document_info){ Context::set('error_message','Write document failed, please insert valid document content.'); return false; }

			Context::set('document_info', $document_info);
			
			$oTemplate = new TemplateHandler();
			$file = "guestbookWrite";

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
         * @brief module: guestbook, api_type: modify
         **/
		function _modify($format = "xml"){
			$doc_id = Context::get('doc_id');
			$oGuestbookModel = &getModel('guestbook');
			$documentInfo = $oGuestbookModel->getGuestbookItem($doc_id);
			$documentInfo = $documentInfo->data[0];

			// if the document doesn't exist, return null
			if(!$doc_id || !$documentInfo->guestbook_item_srl) {Context::set('error_message','The document is not existed'); return false;}

			// if the document does not belong to the module
			$module_srl = Context::get('module_srl');
			if($documentInfo->module_srl !=$module_srl) return false;

			Context::set('documentInfo', $documentInfo);

			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}

			$obj->content = $_POST['content'];
			$obj->module_srl = Context::get('module_srl');

			$document_info = $this->updateGuestbookDocument($obj);
			Context::set('document_info', $document_info);
		
			$oTemplate = new TemplateHandler();
			$file = "guestbookModify";

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
         * @brief module: guestbook, api_type: delete
         **/
		function _delete($format = "xml"){
			$doc_id = Context::get('doc_id');
			$oGuestbookModel = &getModel('guestbook');
			$documentInfo = $oGuestbookModel->getGuestbookItem($doc_id);
			$documentInfo = $documentInfo->data[0];
						
			// if the document doesn't exist, return null
			if(!$doc_id || !$documentInfo->guestbook_item_srl){Context::set('error_message','The document is not existed'); return false;}
			
			// if the document does not belong to the module
			$module_srl = Context::get('module_srl');
			if($documentInfo->module_srl !=$module_srl) return false;

			Context::set('documentInfo', $documentInfo);

			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}

			$obj->guestbook_item_srl = $doc_id;
			$document_info = $this->deleteGuestbookDocument($obj);
			Context::set('document_info', $document_info);

			$oTemplate = new TemplateHandler();
			$file = "guestbookDelete";

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
         * @brief get guestbook document list
         **/
		function getGuestbookDocumentList($module_srl){
			$document_list = array();
			
			$oGuestbookModel = &getModel('guestbook');
			Context::set('oGuestbookModel',$oGuestbookModel);

			$args->module_srl = $module_srl;

			$listCount = Context::get('limit');
			$page = Context::get('page');

			if($listCount && is_numeric($listCount) && intval($listCount)!=0){
				$args->list_count = intval($listCount);
				$args->page = $page?$page:1;
			}else{
				$doc_count = $oGuestbookModel->getGuestbookItemCount($module_srl);
				$args->list_count = $doc_count;
				$args->page = 1;
			}
			
			$output = $oGuestbookModel->getGuestbookItemList($args);

			if (!is_array($output->data)) {
				$document_list = array($output->data);
			} else {
				$document_list = $output->data;
			}

			$oMemberModel = &getModel('member');
			foreach($document_list as $key => $oDocument){
				$obj->module_srl = $module_srl;
				$obj->guestbook_item_srl = $oDocument->guestbook_item_srl;
				$comment_list = $oGuestbookModel->getChildGuestbookItemList($obj);
				$document_list[$key]->comment_count = count($comment_list->data);

				$member_profile = $oMemberModel->getProfileImage($oDocument->member_srl);
				$document_list[$key]->author_profile_image = $member_profile->src;
			}

			return $document_list;
		}

        /**
         * @brief get guestbook document info
         **/
		function getGuestbookDocument($document_srl){
			$oGuestbookModel = &getModel('guestbook');
			$document_info = $oGuestbookModel->getGuestbookItem($document_srl);
			$document_info = $document_info->data[0];

			if(!$document_info->guestbook_item_srl) return false;

			// get the author profile image
			$oMemberModel = &getModel('member');
			$member_profile = $oMemberModel->getProfileImage($document_info->member_srl);
			$document_info->author_profile_image = $member_profile->src;

			$obj->module_srl = Context::get('module_srl');
			$obj->guestbook_item_srl = $document_info->guestbook_item_srl;
			$comment_list = $oGuestbookModel->getChildGuestbookItemList($obj);
			$document_info->comment_count = count($comment_list->data);
			if($document_info->comment_count){
				$document_info->comment_list = $comment_list->data;
			}

			Context::set('document_info', $document_info);
			$security = new Security();
			$security->encodeHTML('document_info..content');

			Context::set('oMemberModel',$oMemberModel);

			return $document_info;
		}

       /**
         * @brief insert guestbook document
         **/
		function insertGuestbookDocument($obj){
			
			if(!$obj->content || !$obj->module_srl) return false;

			$member_info = Context::get('member_info');
	
			if($member_info){
				$args->member_srl = $member_info->member_srl;
				$args->user_id = $member_info->user_id;
				$args->user_name = $member_info->user_name;
				$args->nick_name = $member_info->nick_name;
				$args->email_address = $member_info->email_address;
				$args->homepage = $member_info->homepage;
			}else{
				$args->member_srl = 0;
				$args->user_id = "anonymous";
				$args->user_name = "anonymous";
				$args->nick_name = "anonymous";
				$args->email_address = "";
				$args->homepage = "";
			}

			$args->guestbook_item_srl = getNextSequence();
			$args->content  = $obj->content;
			$args->module_srl  = $obj->module_srl;

			// Remove XE's own tags from the contents.
			$args->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $args->content);
			if(Mobile::isFromMobilePhone())
			{
				$args->content = nl2br(htmlspecialchars($args->content));
			}
			// Remove iframe and script .
			$args->content = stripslashes(removeHackTag($args->content));
			$args->content = html_entity_decode(strip_tags($args->content));

			$output = executeQuery('guestbook.insertGuestbookItem', $args);

			$document_info = $this->getGuestbookDocument($args->guestbook_item_srl);

			return $document_info;
		}

       /**
         * @brief update guestbook document
         **/
		function updateGuestbookDocument($obj){
			if(!$obj->content || !$obj->module_srl) return false;
			
			// get document item
			$documentInfo = Context::get('documentInfo');
			if(!$documentInfo->guestbook_item_srl) return false;

			$args->guestbook_item_srl = $documentInfo->guestbook_item_srl;

			$member_info = Context::get('member_info');

			if($member_info){
				$args->member_srl = $member_info->member_srl;
				$args->user_id = $member_info->user_id;
				$args->user_name = $member_info->user_name;
				$args->nick_name = $member_info->nick_name;
				$args->email_address = $member_info->email_address;
				$args->homepage = $member_info->homepage;
			}else{
				$args->member_srl = 0;
				$args->user_id = "anonymous";
				$args->user_name = "anonymous";
				$args->nick_name = "anonymous";
				$args->email_address = "";
				$args->homepage = "";
			}

			$args->content  = $obj->content;
			$args->module_srl  = $obj->module_srl;

			// Remove XE's own tags from the contents.
			$args->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $args->content);
			if(Mobile::isFromMobilePhone())
			{
				$args->content = nl2br(htmlspecialchars($args->content));
			}
			// Remove iframe and script .
			$args->content = stripslashes(removeHackTag($args->content));
			$args->content = html_entity_decode(strip_tags($args->content));

			$output = executeQuery('guestbook.updateGuestbookItem', $args);

			$document_info = $this->getGuestbookDocument($args->guestbook_item_srl);

			return $document_info;
		}

       /**
         * @brief delete guestbook document
         **/
		function deleteGuestbookDocument($obj){
			// get document item
			$documentInfo = Context::get('documentInfo');
			if(!$documentInfo->guestbook_item_srl || ($obj->guestbook_item_srl != $documentInfo->guestbook_item_srl)) return false;

			$oGuestbookController = &getController('guestbook');
			$args->guestbook_item_srl = $obj->guestbook_item_srl;

			$document_info = $oGuestbookController->deleteGuestbookItem($args->guestbook_item_srl);

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
			if((intval($grants) == -2) && ($member_info->is_admin !='Y')) return null;

			// only for document owner. if grants equals to -3
			if((intval($grants) == -3)){
				$documentInfo = Context::get('documentInfo');
				if(!$documentInfo || ($documentInfo->get('member_srl') != $member_info->member_srl)) return null;
			}

			return $member_info;
		}

    }
?>
