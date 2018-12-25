var lastError; // Last Ajax error

// Execute something when loading the application
$(document).ready(function(){
	$('application').fadeIn();
	//Do Something here
});

// Display an Ajax Error in a Div named 'error_logs'
function DisplayError(jqXHR, textStatus, errorThrown) {
	lastError = jqXHR.responseText;
	var currentdate = new Date(); 
	var datetime = currentdate.getHours() + ":"  
				+ currentdate.getMinutes() + ":" 
				+ currentdate.getSeconds();
	var errorMessage = "Timestamp: " + datetime 
					+ "<br/>Status: " + textStatus 
					+ "<br/>Error: " + errorThrown;
	if (jqXHR.responseText) {
		 errorMessage += "<br/><a href='javascript:ShowLastError();'>Show details</a>";
	}
	$('#error_logs').html(errorMessage);
	$('#error_logs').show();
}

// Display an Error managed by the application
function DisplayHandledError(data) {
	lastError = data.msg + "\r\n\r\n" + data.stack;
	var currentdate = new Date(); 
	var datetime = currentdate.getHours() + ":"  
				+ currentdate.getMinutes() + ":" 
				+ currentdate.getSeconds();
	var errorMessage = "Timestamp: " + datetime 
					+ "<br/>Status: " + data.code 
					+ "<br/>Error: " + data.msg
					+ "<br/><a href='#' onclick='javascript:ShowLastError();'>Show details</a>";
	$('#error_logs').html(errorMessage);
	$('#error_logs').show();
}

// Display the details of the last Ajax Error in a popup
function CleanError() {
	$('#error_logs').html("");
	$('#error_logs').hide();
}

// Display the details of the last Ajax Error in a popup
function ShowLastError() {
	error = lastError.replace(/(?:\r\n|\r|\n)/g, '<br>');
	$('#error_details').html(error);
	$('.error_popup').show();
	return false;
}

function isJson(obj) {
    var t = typeof obj;
    return ['boolean', 'number', 'string', 'symbol', 'function'].indexOf(t) == -1;
}

// Call a service 'name' with a string 'param' as input value. It can be a JSON string.
// The response is a JSON string with at least an element 'html' as output text (possibly empty).
// The element 'html' is displayed as inner html of the element named 'display'
function CallService(name, param, display) {
	if (isJson(param)) {
		param = JSON.stringify(param);
	}
	$.getJSON( "services.php", { service: name, parameters: param } )
	.done(function( data ) {
		//alert(JSON.stringify(data, null, 4));
		CleanError();
		var cell = document.getElementById(display);
		if ($.isEmptyObject(data)) {
			cell.innerHTML = "No response from '"+name+"'";
		} else {
			if (data.error) {
				cell.innerHTML = "Call to '"+name+"' failed";
				DisplayHandledError(data.error);
			} else {
				cell.innerHTML = data.html;
			}
		}
	}).fail(function (jqXHR, textStatus, errorThrown) {
		var cell = document.getElementById(display);
		cell.innerHTML = "Call to '"+name+"' failed";
		DisplayError(jqXHR, textStatus, errorThrown);
	});
}

