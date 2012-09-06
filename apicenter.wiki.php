<?php
    /**
     * @class  apicenterWiki
     * @author NHN (developers@xpressengine.com)
     * @brief wiki class of the apicenter module
     **/

    class apicenterWiki {

		var $module_path = "";
		var $xml_path = "";
		var $json_path = "";
		var $grants = "";

        /**
         * @brief Initialization
         **/
        function apicenterWiki() {
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
         * @brief module: wiki, api_type: list
         **/
		function _list($format = "xml"){
			// with auth
			if(intval($this->grants) != 0){
				$member_info = $this->checkGrants($this->grants);
				if(!$member_info) return null;
				Context::set('member_info', $member_info);
			}

			$module_srl = Context::get('module_srl');
			$document_list = $this->getWikiDocumentList($module_srl);

			Context::set('document_list', $document_list);

			$oTemplate = new TemplateHandler();
			$file = "wikiList";

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
         * @brief module: wiki, api_type: view 
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

			$document_info = $this->getWikiDocument($document_srl);
			if(!$document_info) { Context::set('error_message','The document is not existed'); return false; }
			$oDocument = $document_info->document;

			$comment_list = $this->getWikiDocumentCommentList($document_srl);

			Context::set('document_info', $document_info);
			Context::set('oDocument', $oDocument);
			Context::set('comment_list', $comment_list);

			$oTemplate = new TemplateHandler();		
			$file = "wikiView";

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
         * @brief module: wiki, api_type: write
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

			$document_info = $this->insertWikiDocument($obj);
			if(!$document_info){ 
				if(!Context::get('error_message'))
					Context::set('error_message','Write document failed, please insert valid document title and content.'); 
			
				return false; 
			}

			Context::set('document_info', $document_info);
			
			$oTemplate = new TemplateHandler();
			$file = "wikiWrite";

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
         * @brief module: wiki, api_type: modify
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

			$document_info = $this->updateWikiDocument($obj);
			Context::set('document_info', $document_info);

			$oTemplate = new TemplateHandler();
			$file = "wikiModify";

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
         * @brief module: wiki, api_type: delete
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
			$document_info = $this->deleteWikiDocument($obj);
			Context::set('document_info', $document_info);

			$oTemplate = new TemplateHandler();
			$file = "wikiDelete";

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
         * @brief get wiki document list
         **/
		function getWikiDocumentList($module_srl){
			$oWikiModel = &getModel('wiki');
			$oDocumentModel = &getModel('document');
			$documentTree = $oWikiModel->loadWikiTreeList($module_srl);

			$oModuleModel = &getModel('module');
			$moduleInfo = $oModuleModel->getModuleInfoByModuleSrl(Context::get('module_srl'));
			if(!$moduleInfo) return false;

			$oWikiModule = &getModule('wiki');
			$oWikiModule->module_info = $moduleInfo;

			$document_list = array();

			foreach($documentTree as $key => $document){
				$document_info = $oDocumentModel->getDocument($document->document_srl);
				$document_list[$key]->document_srl = $document_info->document_srl;
				$document_list[$key]->language = $document_info->get('lang_code');
				$document_list[$key]->title = $document_info->get('title');
				$document_list[$key]->parent_srl = $document->parent_srl;
				$document_list[$key]->child = $document->childs;
				$document_list[$key]->depth = $document->depth?$document->depth:0;

				// parser the wiki content
				$content = $document_info->getContentText();
				$wiki_syntax_parser = $oWikiModule->getWikiTextParser();
				$content = trim($wiki_syntax_parser->parse($content));
				$content = str_replace("\n","<br />",$content);

				$document_list[$key]->content = $content;
				$document_list[$key]->comment_count = $document_info->get('comment_count');
				$document_list[$key]->created_date = $document_info->get('regdate');
				$document_list[$key]->author = $document_info->get('nick_name');
			}

			Context::set('count', count($documentTree));

			return $document_list;
		}

        /**
         * @brief get wiki document info
         **/
		function getWikiDocument($document_srl){
			$oDocumentModel = &getModel('document');
			$oWikiModel = &getModel('wiki');
			$document = $oDocumentModel->getDocument($document_srl);
			if(!$document->document_srl) return false;

			$document_info = null;

			// if the document does not belong to the module
			$module_srl = Context::get('module_srl');
			if($document->get('module_srl')!=$module_srl){
					return null;
			}else{
					$document_info->document = $document;
					$contributors = $oWikiModel->getContributors($document_srl);
					$document_info->contributors = array();
					
					foreach($contributors as $contributor){
						$document_info->contributors[] = $contributor->nick_name;
					}
					$document_info->contributors = implode($document_info->contributors, ',');
			}

			$oModuleModel = &getModel('module');
			$moduleInfo = $oModuleModel->getModuleInfoByModuleSrl(Context::get('module_srl'));
			if(!$moduleInfo) return false;

			$oWikiModule = &getModule('wiki');
			$oWikiModule->module_info = $moduleInfo;

			// parser the wiki content
			$content = $document_info->document->get('content');
			$wiki_syntax_parser = $oWikiModule->getWikiTextParser();
			$content = trim($wiki_syntax_parser->parse($content));
			$content = str_replace("\n","<br />",$content);
			$document_info->content = $content;

			return $document_info;
		}
	

        /**
         * @brief get wiki document comments list
         **/
		function getWikiDocumentCommentList($document_srl){
			$comment_list = array();

			$oCommentModel = &getModel('comment');
			$output = $oCommentModel->getCommentList($document_srl);

			$comment_list = $output->data;
			
			return $comment_list;
		}
	
        /**
         * @brief insert wiki document
         **/
		function insertWikiDocument($obj){
			
			if(!$obj->content || !$obj->module_srl) return false;

			$oWikiController = &getController('wiki');
			if(!$oWikiController) return false;

			$oDocumentController = &getController('document');
			$oDocumentModel = &getModel('document');

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
			$args->allow_comment = 'Y';
			$args->is_notice = 'N'; 
			$args->title  = $obj->title;
			$mid = Context::get('mid');
			$document_srl = $oDocumentModel->getDocumentSrlByAlias($mid, $args->title);
			if(!$document_srl) 
				$document_srl = $oDocumentModel->getDocumentSrlByTitle($this->module_info->module_srl, $args->title);
		
			if($document_srl){
				Context::set('error_message','Write document failed, the document is already existed'); 	
				return false;
			}

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

			$aliasName = $oWikiController->beautifyEntryName($args->title); 

			$output = executeQuery('document.insertDocument', $args);
			$oDocumentController->insertAlias($args->module_srl, $args->document_srl, $aliasName);

			$document_info = $this->getWikiDocument($args->document_srl);

			return $document_info;
		}

        /**
         * @brief update wiki document
         **/
		function updateWikiDocument($obj){

			if(!$obj->content || !$obj->module_srl) return false;

			$oModuleModel = &getModel('module');
			$oDocumentController = &getController('document');
			$oWikiController = &getController('wiki');
			if(!$oWikiController) return false;
			
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
			$args->lang_code =  $documentInfo->get('lang_code');

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

			if($output->toBool()) 
			{
				// Update alias
				$oDocumentController->deleteDocumentAliasByDocument($args->document_srl); 
				$aliasName = $oWikiController->beautifyEntryName($args->title); 
				$oDocumentController->insertAlias($args->module_srl, $args->document_srl, $aliasName);
			}

			$document_config = $oModuleModel->getModulePartConfig('document', $args->module_srl);
			$bUseHistory = $document_config->use_history == 'Y' || $document_config->use_history == 'Trace';


			if($bUseHistory) {
				$obj->history_srl = getNextSequence();
				$obj->document_srl = $args->document_srl;
				$obj->module_srl = $args->module_srl;
				if($document_config->use_history == 'Y') $obj->content = $args->content;
				$obj->nick_name = $args->nick_name;
				$obj->member_srl = $args->member_srl;
				$obj->regdate = $args->last_update;
				$obj->ipaddress = $args->ipaddress;
				$output = executeQuery("document.insertHistory", $obj);
			}
			// unset global variable
			unset($GLOBALS['XE_DOCUMENT_LIST'][$args->document_srl]);

			$document_info = $this->getWikiDocument($args->document_srl);

			return $document_info;

		}


       /**
         * @brief delete wiki document
         **/
		function deleteWikiDocument($obj){
			// get document item
			$documentInfo = Context::get('documentInfo');
			if(!$documentInfo->document_srl || ($obj->document_srl != $documentInfo->document_srl)) return false;

			$oDocumentController = &getController('document'); 
			$oDocumentModel = &getModel('document');

			$args->document_srl = $obj->document_srl;
			$output = executeQuery('document.deleteDocument', $args);
			$oDocumentController->deleteDocumentAliasByDocument($args->document_srl); 

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
