// manage selected api
jQuery(function($){

	$('a.modalAnchor[href=#manageSelectedAPI]')
	.bind('before-open.mw', function(){
		var $selectedAPI = $('input[type=checkbox].selectedApiItem:checked');
		var $selectedBody = $('#manageSelectedAPIBody');

		if (!$selectedAPI.length) return false;

		$selectedBody.empty();

		var api_item_srls = new Array();
		$selectedAPI.each(function(){
			var $this = $(this);
			var row = '<tr><td>' + $this.data('title') + '</td><td>' + $this.data('module') +  '</td><td>' + $this.data('output') + '</td><td>' + $this.data('author') + '</td><td>' + $this.data('date') +  '</td></tr>';
			$selectedBody.append(row);
			api_item_srls.push($this.val());
		});

		$('#manageSelectedAPI input[name=api_item_srls]').val(api_item_srls);
	});

	$('a.modalAnchor[href=#manageSelectedKey]')
	.bind('before-open.mw', function(){
		var $selectedKey = $('input[type=checkbox].selectedApiKey:checked');
		var $selectedBody = $('#manageSelectedKeyBody');

		if (!$selectedKey.length) return false;

		$selectedBody.empty();

		var api_key_srls = new Array();
		$selectedKey.each(function(){
			var $this = $(this);
			var row = '<tr><td>' + $this.data('key') + '</td><td>' + $this.data('purpose') +  '</td><td>'  + $this.data('author') + '</td><td>' + $this.data('date') +  '</td></tr>';
			$selectedBody.append(row);
			api_key_srls.push($this.val());
		});

		$('#manageSelectedKey input[name=api_key_srls]').val(api_key_srls);
	});

});