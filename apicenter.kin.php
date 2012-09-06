<?php
    /**
     * @class  apicenterKin
     * @author NHN (developers@xpressengine.com)
     * @brief kin class of the apicenter module
     **/

    class apicenterKin {

		var $module_path = "";
		var $xml_path = "";
		var $json_path = "";
		var $grants = "";

        /**
         * @brief Initialization
         **/
        function apicenterKin() {
			$this->module_path =  Context::get('module_path');
			$this->xml_path = $this->module_path.'output/xml';
			$this->json_path = $this->module_path.'output/json';
			$this->grants = Context::get('grants');
        }

        function __construct() {
			$this->module_path =  Context::get('module_path');
			$this->xml_path = $this->module_path.'output/xml';
			$this->json_path = $this->module_path.'output/json';
			$this->grants = Context::get('grants');
        }

        /**
         * @brief module: kin, api_type: list
         **/
		function _list($format = "xml"){
			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}

			$module_srl = Context::get('module_srl');
			$document_list = $this->getKinQuestionList($module_srl);

			Context::set('document_list', $document_list);

			$oTemplate = new TemplateHandler();
			$file = "kinList";

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
         * @brief module: kin, api_type: view 
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

			$document_info = $this->getKinDocument($document_srl);
			if(!$document_info) { Context::set('error_message','The document is not existed'); return false; }
	
			// if the document does not belong to the module
			$module_srl = Context::get('module_srl');
			if($document_info->get('module_srl')!=$module_srl) return false;

			$answer_list = $document_info->get('answer_list');
			$replies = $document_info->get('replies');

			Context::set('document_info', $document_info);
			Context::set('answer_list', $answer_list);
			Context::set('replies', $replies);

			$oTemplate = new TemplateHandler();		
			$file = "kinView";

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
         * @brief module: kin, api_type: write
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
			$obj->category_srl = $_POST['cat_id'];
			$obj->module_srl = Context::get('module_srl');
	
			// check if it is a valid category id
			if($obj->category_srl){ 
				$oDocumentModel = &getModel('document');
				$category_info =$oDocumentModel->getCategory($obj->category_srl);
				if(!$category_info->category_srl) { Context::set('error_message','Write document failed, the cat_id is invalid.'); return false; }

				// if the category does not belong to the module
				$module_srl = Context::get('module_srl');
				if($category_info->module_srl != $module_srl) return false;
			}

			$document_info = $this->insertKinDocument($obj);
			if(!$document_info){ Context::set('error_message','Write document failed, please insert valid document title and content.'); return false; }
	
			Context::set('document_info', $document_info);
			
			$oTemplate = new TemplateHandler();
			$file = "kinWrite";

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
         * @brief module: kin, api_type: modify
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
			$obj->category_srl = $_POST['cat_id'];
			$obj->module_srl = Context::get('module_srl');

			if($obj->category_srl){ 
				$oDocumentModel = &getModel('document');
				$category_info =$oDocumentModel->getCategory($obj->category_srl);
				if(!$category_info->category_srl) { Context::set('error_message','Write document failed, the cat_id is invalid.'); return false; }

				// if the category does not belong to the module
				$module_srl = Context::get('module_srl');
				if($category_info->module_srl != $module_srl) return false;
			}

			$document_info = $this->updateKinDocument($obj);
			Context::set('document_info', $document_info);
		
			$oTemplate = new TemplateHandler();
			$file = "kinModify";

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
         * @brief module: kin, api_type: delete
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
			$document_info = $this->deleteKinDocument($obj);
			Context::set('document_info', $document_info);

			$oTemplate = new TemplateHandler();
			$file = "kinDelete";

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
         * @brief module: kin, api_type: category
         **/
		function _category($format = "xml"){
			$oDocumentModel = &getModel('document');
			$module_srl = Context::get('module_srl');

			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}

			$category_list = $oDocumentModel->getCategoryList($module_srl);
			if(!$category_list) { Context::set('error_message','The catagory list is null.'); return false; }	

			Context::set('category_list', $category_list);

			$oTemplate = new TemplateHandler();		
			$file = "kinCategory";

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
         * @brief get Kin question list
         **/
		function getKinQuestionList($module_srl){
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

			$oKinModel = &getModel('kin');
            
			if(count($document_list)) {
                foreach($document_list  as $key => $oDocument) {
                    $point = $oKinModel->getKinPoint($oDocument->document_srl);
					$document_list[$key]->point = $point;
					$args->parent_srl = $oDocument->document_srl;

					$output = executeQueryArray('kin.getReplyCount', $args);
					$repliesCount = intval($output->data[0]->count);

					$document_list[$key]->repliesCount = $repliesCount;	
                }
            }

			Context::set('count', count($document_list));

			return $document_list;
		}

        /**
         * @brief get Kin question info
         **/
		function getKinDocument($document_srl){
            $oDocumentModel = &getModel('document');
            $oKinModel = &getModel('kin');

            $document_info = $oDocumentModel->getDocument($document_srl);
			if(!$document_info->document_srl) return false;

			$point = $oKinModel->getKinPoint($document_info->document_srl);
			$document_info->add('point',$point);

			$answer_list = $document_info->getComments();
			$document_info->add('answer_list',$answer_list);
			$accepted_answer = $oKinModel->getSelectedReply($document_info->document_srl);
			$document_info->add('accepted_answer',$accepted_answer);

			$args->parent_srl = $document_info->document_srl;
			$replies_count = executeQueryArray('kin.getReplyCount', $args);
			$repliesCount = intval($replies_count->data[0]->count);
			$document_info->add('repliesCount',$repliesCount);
		
		    $args->module_srl = $module_info->module_srl;
            $args->parent_srl = $document_info->document_srl;
			$args->list_count = $repliesCount;
            $args->page = 1;
            $output = executeQueryArray('kin.getReplies', $args);
			$replies = $output->data;
			$document_info->add('replies',$replies);

			$category_srl = $document_info->get('category_srl');
			if($category_srl){
				$categoryInfo = $oDocumentModel->getCategory($category_srl);
				$document_info->add('category_title',$categoryInfo->title);
			}

			return $document_info;
		}

        /**
         * @brief insert Kin question
         **/
		function insertKinDocument($obj){
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
			$args->category_srl  = $obj->category_srl?$obj->category_srl:0;

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

			// update category count
			if($args->category_srl){
				$oDocumentController = &getController('document');
				$oDocumentController->updateCategoryCount($args->module_srl, $args->category_srl);
			}

			$document_info = $this->getKinDocument($args->document_srl);

			return $document_info;
		}

        /**
         * @brief update kin document
         **/
		function updateKinDocument($obj){
			if(!$obj->content || !$obj->module_srl) return false;
			
			// get document item
			$documentInfo = Context::get('documentInfo');
			if(!$documentInfo->document_srl) return false;

			$args->document_srl = $documentInfo->document_srl;
			$args->category_srl =  $obj->category_srl?$obj->category_srl:$documentInfo->get('category_srl');
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

			// update category count
			if($args->category_srl){
				$oDocumentController = &getController('document');
				if($documentInfo->get('category_srl') != $args->category_srl) $oDocumentController->updateCategoryCount($args->module_srl, $documentInfo->get('category_srl'));
			    $oDocumentController->updateCategoryCount($args->module_srl, $args->category_srl);
			}

			// unset global variable
			unset($GLOBALS['XE_DOCUMENT_LIST'][$args->document_srl]);

			$document_info = $this->getKinDocument($args->document_srl);

			return $document_info;
		}

       /**
         * @brief delete kin document
         **/
		function deleteKinDocument($obj){
			// get document item
			$documentInfo = Context::get('documentInfo');
			if(!$documentInfo->document_srl || ($obj->document_srl != $documentInfo->document_srl)) return false;

			$args->document_srl = $obj->document_srl;
			$args->module_srl =  Context::get('module_srl');

			$output = executeQuery('document.deleteDocument', $args);

			// update category count
			$oDocumentController = &getController("document");
			if($documentInfo->get('category_srl')) $oDocumentController->updateCategoryCount($args->module_srl,$documentInfo->get('category_srl'));
			
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
