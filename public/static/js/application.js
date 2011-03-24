function init() {
	$('#command-input').focus();
	$('#command-input').keypress(function(event) {
	    if (event.which == '13') {
		    event.preventDefault();
		    command($('#command-input').val());
		}
	});
	$('#command-input').live('keydown', function(e){
		var keyCode = e.keyCode || e.which; 
		if (keyCode == 9) { 
		    e.preventDefault();
		    suggest($('#command-input'));
		} 
	});
	$('#content-form').submit(function() {
		save($(this).serialize());
	    return false;
	});
}

var lastcommand = '';
$.address.init(function(event) {
	// NOOP
}).externalChange(function(event) {
	var cmd = '';
	$.each($.address.pathNames(), function(key, value) {
		cmd += value + ' ';
	});
	if (cmd != lastcommand) {
		command(cmd);
	}
	lastcommand = cmd;
});

function command(cmd) {
	$('#command-input').attr('disabled', 'disabled');
	$('#loading').show();
	$.ajax({
		url: "api/command.php",
		dataType: "json",
		data: "command=" + encodeURIComponent(cmd),
		success: function(data) {
			$('#command-input').removeAttr('disabled');
			$('#command-input').val('');
			$('#message').text(data.message);
			$('#message').attr('class', data.status);
			$('#status').show();
			$('#content').empty();
			if (data.command == 'data' || data.command == 'db' || data.command == 'query' || data.command == 'help' || data.command == 'filter') {
				createRows(data);
			}
			if (data.command == 'edit' || data.command == 'insert') {
				createRowEditor(data);
			}
			if (data.command == 'delete') {
				command('data ' + data.table);
			}
			var path = '/' + cmd.replace(' ','/');
			if ($.address.value() != path) {
				$.address.value(path);
			}
			$('#loading').fadeOut();
	  	}
	});
}

function createRowEditor(data) {
	var table = data.table;
	var html = '<div class="row-editor">';
	var count = 0;
	var first = '';
	var action = data.command;
	$.each(data.data, function(key, row) {
		html += '<div id="row-' + key + '">';
		$.each(row, function(column, value) {
			html += '<div class="column">';
			html += '<label for="data-' + key + '-' + column + '">' + column + '</label>';
			html += '<div class="value">';
			if (value) {
				value = value.replace(/</g, '&lt;');
				value = value.replace(/>/g, '&gt;');
				value = value.replace(/"/g, '&quot;');
			}
			html += '<input name="data-' + key + '-' + column + '" id="data-' + key + '-' + column + '" type="text" value="' + value + '" />';
			html += '</div>';
			html += '<div class="clear"></div>';
			html += '</div>';
			if (count == 0) {
				first = 'data-' + key + '-' + column;
			}
			count++;
		});
		html += '<div class="column">';
		html += '<input class="button" type="submit" value="Save" />';
		html += '</div>';
		html += '</div>';
	});
	html += '<input type="hidden" name="action" value="' + action + '" />';
	html += '<input type="hidden" name="table" value="' + table + '" />';
	$('#content').append(html);
	$('#' + first).focus();
}

function createRows(data) {
	var width = 135;
	var html = '<div class="datatable">';
	if (data.columns) {
		html += '<div class="header row">';
		html += '<div class="column" style="width: 30px">Nr.</div>';
		$.each(data.columns, function(index, row) {
			var classname = "column";
			if (row.Key == 'PRI') { classname += ' primarykey'; }
			html += '<div class="' + classname + '" style="width: ' + width + 'px">' + row.Field + "</div>";
		});
		html += '<div class="clear"></div>';
		html += '</div>';
	}
	$.each(data.data, function(index, row) {
		html += '<div id="row-" class="row">';
		var col = 0;
		html += '<div class="column" style="width: 30px; color: #BBB;">' + (index + 1) + "</div>";
		$.each(row, function(column, value) {
			var classname = "column";
			if (data.columns) {
				if (data.columns[col].Type.substr(0,3) == 'int' || data.columns[col].Type.substr(0,7) == 'tinyint') { classname += ' integer'; }
			}
			if (value + ' ' == 'null ') { classname += ' null'; }
			if (value + '*' == '*') { value = '&nbsp;' }
			if (value) {
				value = value.replace(/</g, '&lt;');
				value = value.replace(/>/g, '&gt;');
				value = value.replace(/"/g, '&quot;');
			}
			html += '<div class="' + classname + '" style="width: ' + width + 'px">' + value + "</div>";
			col++;
		});
		html += '<div class="clear"></div>';
		html += '</div>';
	});
	html += '</div>';
	$('#content').append(html);
}

function save(data) {
	$.ajax({
		url: "api/row.php",
		dataType: "json",
		data: data,
		success: function(data) {
			$('#message').text(data.message);
			$('#message').attr('class', data.status);
			$('#status').show();
			$('#command-input').val('data ' + data.table);
			$('#command-input').focus();
	  	}
	});
}

function suggest(el) {
	var command = el.val();
	$.ajax({
		url: "api/suggest.php",
		dataType: "json",
		data: "command=" + command,
		success: function(data) {
			el.val(data.suggestion);
	  	}
	});
}