<?php
    /**
     * @class  apicenterTextyle
     * @author NHN (developers@xpressengine.com)
     * @brief textyle class of the apicenter module
     **/

    class apicenterTextyle {

		var $module_path = "";
		var $xml_path = "";
		var $json_path = "";
		var $grants = "";

        /**
         * @brief Initialization (constructor for PHP < 5)
         **/
        function apicenterTextyle() {
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
         * @brief module: textyle, api_type: list
         **/
		function _list($format = "xml"){
			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}

			$module_srl = Context::get('module_srl');
			$document_list = $this->getTextyleDocumentList($module_srl);

			Context::set('document_list', $document_list);

			$oTemplate = new TemplateHandler();
			$file = "textyleList";

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
         * @brief module: textyle, api_type: view 
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

			$document_info = $this->getTextyleDocument($document_srl);
			if(!$document_info) { Context::set('error_message','The document is not existed'); return false; }

			// if the document does not belong to the module
			$module_srl = Context::get('module_srl');
			if($document_info->get('module_srl')!=$module_srl) return false;

			$comment_list = $document_info->get('comment_list');
			Context::set('document_info', $document_info);
			Context::set('comment_list', $comment_list);

			$oTemplate = new TemplateHandler();		
			$file = "textyleView";

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
         * @brief module: textyle, api_type: write
         **/
		function _write($format = "xml"){
			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}

			$obj->title = $_POST['title'];
			$obj->content = $_POST['content'];
			$obj->module_srl = Context::get('module_srl');
			
			$document_info = $this->insertTextyleDocument($obj);
			if(!$document_info){ Context::set('error_message','Write document failed, please insert valid document title and content.'); return false; }

			Context::set('document_info', $document_info);
			
			$oTemplate = new TemplateHandler();
			$file = "textyleWrite";

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
         * @brief module: textyle, api_type: modify
         **/
		function _modify($format = "xml"){
			$doc_id = Context::get('doc_id');
			$oDocumentModel = &getModel('document');
			$documentInfo = $oDocumentModel->getDocument($doc_id);
			
			// if the document doesn't exist, return null
			if(!$doc_id || !$documentInfo->document_srl) {Context::set('error_message','The document is not existed'); return false;}
			
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

			$obj->title = $_POST['title'];
			$obj->content = $_POST['content'];
			$obj->module_srl = Context::get('module_srl');

			$document_info = $this->updateTextyleDocument($obj);
			Context::set('document_info', $document_info);

			$oTemplate = new TemplateHandler();
			$file = "textyleModify";

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
         * @brief module: textyle, api_type: delete
         **/
		function _delete($format = "xml"){
			$doc_id = Context::get('doc_id');
			$oDocumentModel = &getModel('document');
			$documentInfo = $oDocumentModel->getDocument($doc_id);
						
			// if the document doesn't exist, return null
			if(!$doc_id || !$documentInfo->document_srl){Context::set('error_message','The document is not existed'); return false;}
			
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

			$obj->document_srl = $doc_id;
			$document_info = $this->deleteTextyleDocument($obj);
			Context::set('document_info', $document_info);

			$oTemplate = new TemplateHandler();
			$file = "textyleDelete";

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
         * @brief get textyle document list
         **/
		function getTextyleDocumentList($module_srl){
			$document_list = array();
			
			$oDocumentModel = &getModel('document');
			Context::set('oDocumentModel',$oDocumentModel);

			$args->module_srl = $module_srl;

			$listCount = Context::get('limit');
			$page = Context::get('page');

			if($listCount && is_numeric($listCount) && intval($listCount)!=0){
				$args->list_count = intval($listCount);
				$args->page = $page?$page:1;
			}else{
				$doc_count = $oDocumentModel->getDocumentCount($module_srl);
				$args->list_count = $doc_count;
				$args->page = 1;
			}
			
			$output = $oDocumentModel->getDocumentList($args);

			if (!is_array($output->data)) {
				$document_list = array($output->data);
			} else {
				$document_list = $output->data;
			}

			Context::set('count', count($output->data));

			return $document_list;
		}

        /**
         * @brief get board document info
         **/
		function getTextyleDocument($document_srl){
			$oDocumentModel = &getModel('document');
			$document_info = $oDocumentModel->getDocument($document_srl);

			if(!$document_info->document_srl) return false;

			$category_srl = $document_info->get('category_srl');
			if($category_srl){
				$categoryInfo = $oDocumentModel->getCategory($category_srl);
				$document_info->category_title = $categoryInfo->title;
			}

			$comment_list = $document_info->getComments();
			$document_info->add('comment_list',$comment_list);

			return $document_info;
		}

        /**
         * @brief insert textyle document
         **/
		function insertTextyleDocument($obj){
			
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

			$args->document_srl = getNextSequence();
			$args->title  = $obj->title;
			$args->content  = $obj->content;
			$args->module_srl  = $obj->module_srl;
			$args->update_order = $args->list_order = getNextSequence() * -1;
			$args->lang_code = 'en';

			// If the title is empty, extract string from the contents.
			settype($args->title, "string");
			if($args->title == '') $args->title = cut_str(strip_tags($args->content),20,'...');
			$args->title = removeHackTag($args->title);

			// If no tile extracted from the contents, leave it untitled.
			if($args->title == '') $args->title = 'Untitled';
			// Remove XE's own tags from the contents.
			$args->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $args->content);
			if(Mobile::isFromMobilePhone())
			{
				$args->content = nl2br(htmlspecialchars($args->content));
			}
			// Remove iframe and script .
			$args->content = stripslashes(removeHackTag($args->content));

			$output = executeQuery('document.insertDocument', $args);

			$document_info = $this->getTextyleDocument($args->document_srl);

			return $document_info;
		}

        /**
         * @brief update textyle document
         **/
		function updateTextyleDocument($obj){
			if(!$obj->content || !$obj->module_srl) return false;
			
			// get document item
			$documentInfo = Context::get('documentInfo');
			if(!$documentInfo->document_srl) return false;

			$args->document_srl = $documentInfo->document_srl;
			$args->category_srl =  $documentInfo->get('category_srl');
			$args->is_notice =  $documentInfo->get('is_notice');
			$args->title_bold =  $documentInfo->get('title_bold');
			$args->title_color =  $documentInfo->get('title_color');
			$args->uploaded_count =  $documentInfo->get('uploaded_count');
			$args->password =  $documentInfo->get('password');
			$args->tags =  $documentInfo->get('tags');
			$args->extra_vars =  $documentInfo->get('extra_vars');
			$args->regdate =  $documentInfo->get('regdate');
			$args->list_order =  $documentInfo->get('list_order');
			$args->update_order =  $documentInfo->get('update_order');
			$args->allow_trackback =  $documentInfo->get('allow_trackback');
			$args->notify_message =  $documentInfo->get('notify_message');
			$args->status =  $documentInfo->get('status');
			$args->comment_status =  $documentInfo->get('comment_status');

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

			$args->title  = $obj->title;
			$args->content  = $obj->content;
			$args->module_srl  = $obj->module_srl;

			// If the title is empty, extract string from the contents.
			settype($args->title, "string");
			if($args->title == '') $args->title = $documentInfo->get('title');;
			$args->title = removeHackTag($args->title);

			// Remove XE's own tags from the contents.
			$args->content = preg_replace('!<\!--(Before|After)(Document|Comment)\(([0-9]+),([0-9]+)\)-->!is', '', $args->content);
			if(Mobile::isFromMobilePhone())
			{
				$args->content = nl2br(htmlspecialchars($args->content));
			}
			// Remove iframe and script .
			$args->content = stripslashes(removeHackTag($args->content));

			$output = executeQuery('document.updateDocument', $args);

			// unset global variable
			unset($GLOBALS['XE_DOCUMENT_LIST'][$args->document_srl]);

			$document_info = $this->getTextyleDocument($args->document_srl);

			return $document_info;

		}

       /**
         * @brief delete textyle document
         **/
		function deleteTextyleDocument($obj){
			// get document item
			$documentInfo = Context::get('documentInfo');
			if(!$documentInfo->document_srl || ($obj->document_srl != $documentInfo->document_srl)) return false;

			$args->document_srl = $obj->document_srl;

			$output = executeQuery('document.deleteDocument', $args);
			
			$message = "Document ".$obj->document_srl." has been deleted.";
			$document_info->document_srl = $obj->document_srl;
			$document_info->message = $message;

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
