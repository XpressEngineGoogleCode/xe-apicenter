/* NHN (developers@xpressengine.com) */
jQuery(function($){

	var selectModuleLayer = $('#selectModule');
	var menuUrl = null;

	var editForm = $('#editForm');
	var editKeyForm = $('#editAPIKey');
	var kindModuleLayer = $('#kindModule');
	var selectModuleLayer = $('#selectModule');
	var apiMethod = $('#api_method');

	$('#kModule').change(getModuleList).change();
	function getModuleList()
	{
		var params = new Array();
		var response_tags = ['error', 'message', 'module_list'];
		var module =$('#kModule').val();

		// forum module must invoke category API
		if(module=='forum'){
			addAddtionalAPIMethod("category",true);
		}else{
			deleteAddtionalAPIMethod("category");
		}

		if(module != 'textyle')
			exec_xml('module','procModuleAdminGetList',params, completeGetModuleList, response_tags);
		else if (module == 'textyle')
			exec_xml('apicenter','procApicenterAdminGetTextyleList',params, completeGetModuleList, response_tags);
	}

	function completeGetModuleList(ret_obj)
	{
		var module = $('#kModule').val();

		var htmlBuffer = "";
		if(ret_obj.module_list[module] != undefined)
		{
			var midList = ret_obj.module_list[module].list;

			for(x in midList)
			{
				var midObject = midList[x];
				htmlBuffer += '<option value="'+midObject.module_srl+'"';
				if(menuUrl == midObject.mid) htmlBuffer += ' selected ';
				htmlBuffer += '>'+midObject.mid+'('+midObject.browser_title+')</option>';
			}
		}
		else htmlBuffer = '';

		selectModuleLayer.find('select').html(htmlBuffer);

		getCategoryConfig();
	}



	$('#sModule_mid').change(getCategoryConfig).change();
	function getCategoryConfig(){
		var module_srl = $('#sModule_mid').val();
		var module = $('#kModule').val();
		var params2 = new Array();
	    params2['module_srl'] = module_srl;

		var response_tags2 = ['error', 'message', 'enable_category'];

		if(module_srl){
			if(module == 'board' || module == 'faq' || module == 'kin')
				exec_xml('apicenter','procApicenterAdminGetCategoryConfig',params2, completeGetCategoryConfig, response_tags2);
		}
	}

	function completeGetCategoryConfig(ret_obj)
	{
		if(ret_obj.enable_category == 'Y'){
			addAddtionalAPIMethod("category");
		}else{
			deleteAddtionalAPIMethod("category");
		}
	}

	function addAddtionalAPIMethod(method, disabled){
			
		deleteAddtionalAPIMethod(method);
		var methodName = "api_type_"+method;
		if(disabled == true)
			var addContents = "<input type='checkbox' name='"+methodName+"'  id='" +methodName +"' value='Y' checked disabled /> <span id='" +methodName+"_span'>" + method.charAt(0).toUpperCase() + method.slice(1) +"</span>";
		else
			var addContents = "<input type='checkbox' name='"+methodName+"'  id='" +methodName +"' value='Y' checked/> <span id='" +methodName+"_span'>" + method.charAt(0).toUpperCase() + method.slice(1) +"</span>";
		apiMethod.append(addContents);
	}

	function deleteAddtionalAPIMethod(method){
		var methodID = "#api_type_"+method;
		var methodSpan = methodID+"_span";
		if(apiMethod.find(methodID).length){
			$(methodID).remove();
			$(methodSpan).remove();
		}
	}

	var copytoclip = false;
	if(window.clipboardData){
		copytoclip = true;
	}
	if(window.netscape){
		copytoclip = true;
	}

	if(copytoclip){
		$('#apiKeyList pre').width('250px');
		$('button.copytoclip').css('display','inline');
	}


	$('a._edit').click(function(){
		editForm.find('.h2').text('Edit API Item');
		resetEditForm();
		api_item_srl =  $(this).attr('data');
		var params = new Array();
		var response_tags = new Array('api_item');
		
		params['api_item_srl'] = api_item_srl;

		exec_xml("apicenter","getApicenterAdminItemInfo", params, completeGetActList, response_tags);
	});

	function completeGetActList(obj)
	{
		var apiItem = obj.api_item;
		menuUrl =  apiItem.mid;
		editForm.find('input[name=api_item_srl]').val(apiItem.api_item_srl);
		editForm.find('input[name=api_title]').val(apiItem.api_title);
		editForm.find('select[name=module_type]').val(apiItem.module);
		editForm.find('select[name=select_module_id]').val(apiItem.mid);
		editForm.find('select[name=select_format]').val(apiItem.output_format);
		editForm.find('input[name=api_key]').val(apiItem.api_key);
		editForm.find('textarea[name=api_description]').val(apiItem.description);

		if(apiItem.apiTypes['list'] == 1) editForm.find('input[name=api_type_list]').attr("checked",true); else editForm.find('input[name=api_type_list]').attr("checked",false); 
		if(apiItem.apiTypes['view'] == 1) editForm.find('input[name=api_type_view]').attr("checked",true);  else  editForm.find('input[name=api_type_view]').attr("checked",false);
		if(apiItem.apiTypes['write'] == 1) editForm.find('input[name=api_type_write]').attr("checked",true); else  editForm.find('input[name=api_type_write]').attr("checked",false);
		if(apiItem.apiTypes['modify'] == 1) editForm.find('input[name=api_type_modify]').attr("checked",true);  else  editForm.find('input[name=api_type_modify]').attr("checked",false);
		if(apiItem.apiTypes['delete'] == 1) editForm.find('input[name=api_type_delete]').attr("checked",true);   else  editForm.find('input[name=api_type_delete]').attr("checked",false);

		getModuleList();
	}

	$('a._add').click(function(){
		editForm.find('.h2').text('Add a New API Item');
		getModuleList();
		resetEditForm();
	});

	function resetEditForm()
	{
		editForm.find('input[name=api_item_srl]').val('');
		editForm.find('input[name=api_title]').val('');
		editForm.find('input[name=api_key]').val('');
		editForm.find('select[name=module_type]').val('')
		editForm.find('select[name=select_module_id]').val('');
		editForm.find('textarea[name=api_description]').val('');
		editForm.find('input[name=api_type_list]').attr("checked",true);
		editForm.find('input[name=api_type_view]').attr("checked",true); 
		editForm.find('input[name=api_type_write]').attr("checked",false);
		editForm.find('input[name=api_type_modify]').attr("checked",false);
		editForm.find('input[name=api_type_delete]').attr("checked",false);
		deleteAddtionalAPIMethod("category");
	}
	
	$('#api_item_submit').click(function(event){
		event.preventDefault();
		var api_title = $('#api_title').val();
		if(!api_title){
			alert('Please input API title.');
			$('#editForm').submit(false);
			return;
		}
		var api_key = $('#api_key').val();
		if(!api_key){
			alert('Please input API key.');
			$('#editForm').submit(false);
			return;
		}
		
		var params = new Array();
		var response_tags = new Array('api_key_exists');
		params['api_key'] = api_key;

		exec_xml("apicenter","getApicenterAdminKeyValidate", params, completeApikeyValiadate, response_tags);

	});

	function completeApikeyValiadate(obj){
			var api_key_exists = obj.api_key_exists;
		    if(api_key_exists==1) { 
				if($('#editForm').find('#api_type_category').length) {
					$('#editForm').find('#api_type_category').attr('disabled',false);
				}
				$('#editForm').submit();
				return;
			}else{
				alert('Please input valid API Key.');
				resetEditForm();
				return;
			}
	}

	$('a._editkey').click(function(){
		api_key_srl =  $(this).attr('data');
		var params = new Array();
		var response_tags = new Array('api_key');
		
		params['api_key_srl'] = api_key_srl;

		exec_xml("apicenter","getApicenterAdminKeyInfo", params, completeGetApiKey, response_tags);
	});

	function completeGetApiKey(obj){
		editKeyForm.find('.h2').text('Edit API Key');
		var apiKey = obj.api_key;
		editKeyForm.find('input[name=api_key_srl]').val(apiKey.api_key_srl);
		editKeyForm.find('textarea[name=api_purpose]').val(apiKey.purpose);
		editKeyForm.find('span.api_key_value').html(apiKey.api_key);
		editKeyForm.find('li.api_key_label').css('display','block');
		editKeyForm.find('#generateKey').val("Update Key");

	}

	$('a._addkey').click(function(){
		editKeyForm.find('.h2').text('Apply a New API Key');
		resetEditKeyForm();
	});

	function resetEditKeyForm()
	{
		editKeyForm.find('li.api_key_label').css('display','none');
		editKeyForm.find('span.api_key_value').html('');
		editKeyForm.find('textarea[name=api_purpose]').val('');
		editKeyForm.find('#generateKey').val("Generate Key");
	}

	$('input[type=checkbox].all_check').click(function(){
		if($(this).attr("checked") == 'checked'){ //check all
			$("input[class=selectedApiItem]").attr("checked","checked");
			$("input[class=selectedApiKey]").attr("checked","checked");
			$('input[type=checkbox].all_check').attr("checked","checked");
		}else{
			$("input[class=selectedApiItem]").attr("checked",false);
			$("input[class=selectedApiKey]").attr("checked",false);
			$('input[type=checkbox].all_check').attr("checked",false);
		}
	});

});

function doCopyToClipboard(value) {
	if(window.event) { //IE
		window.event.returnValue = true;
		window.setTimeout(function() { copyToClipboard(value); },25);
	}
	else if(window.netscape){ //Fire Fox  
		try {
			netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
		} catch (e) {
			alert("Refused by the browser！\nPlease input 'about:config' on browser address box, \and set up 'signed.applets.codebase_principal_support' to 'true'");
		}
		var clip = Components.classes['@mozilla.org/widget/clipboard;1'].createInstance(Components.interfaces.nsIClipboard);
		if (!clip) return;
		var trans = Components.classes['@mozilla.org/widget/transferable;1'].createInstance(Components.interfaces.nsITransferable);
		if (!trans) return;
		trans.addDataFlavor('text/unicode');
		var str = new Object();
		var len = new Object();
		var str = Components.classes["@mozilla.org/supports-string;1"].createInstance(Components.interfaces.nsISupportsString);
		var copytext = value;
		str.data = copytext;
		trans.setTransferData("text/unicode",str,copytext.length*2);
		var clipid = Components.interfaces.nsIClipboard;
		if (!clip) return false;
		clip.setData(trans,null,clipid.kGlobalClipboard);
		alert("Copy API Key  Successfully, Press Ctrl+v to Paste.")
	}
}

function copyToClipboard(value) {
	if(window.clipboardData) {
		var result = window.clipboardData.setData('Text', value);
		alert("Copy API Key  Successfully, Press Ctrl+v to Paste.");
	}
}

function confirmDelete()
{
	if(confirm(xe.lang.confirm_delete)) return true;
	return false;
}

