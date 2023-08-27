var computers; //list of Computers on the LAN
var refreshIndex=999999; //index of Computer that status is currently being refreshed.
var notifications = 0; //current number of event notifications displayed;
var maxNotifications = 10; //max event notifications that can be displayed simultaneously
var features = ['hib', 'slp', 'stb', 'shd', 'rst', 'wol'];
var callServerSide = 1;
var deprecateDelay = 10; //status older than this are assumed deprecated
var refreshDelay = deprecateDelay; //only refresh status if older than deprecateDelay minutes
var actionDisplay = 1; //1 to display disabled actions. 0 to hide those.
var osFeatures; // Will be loaded with Os.json.
var lastError; // Last Ajax error
var concurrentCheck = 2;  //number of Computers to be check simultaneously with the current one
var currentCheck = 0;
var subnet = "192.168.0.";
var searchValue = "";
var searchIndex = 0;

var mouseMove = 0;

var current; //id of computer currently selected

var Notification = (function() {
    "use strict";

    var that = {};

    that.show = function(text, type) {
		type = type || 'Info';
		
		if (notifications == maxNotifications) {
			notifications = 1;
		} else {
			notifications +=1;
		}
		
		var id = notifications;
		ShowEvent(id, text, type);
    };

    return that;
}());

function ShowEvent(id, text, type) {
	switch(type) {
		case 'Error':			
			break;
		case 'Info':
			break;
		case 'Warning':
			break;
		case 'Success':
			break;
		default:
			type = 'Info';
			break;
	}
	var elem = $('#not'+id);
	elem.removeClass();
	elem.toggleClass('event'+type);
	elem.find("span").html(text);
	elem.delay(200).fadeIn().delay(4000).fadeOut( function () {
		if (id == notifications) {
			notifications = 0;
		}
	});
}

$(document).ready(function(){
	$('#acpi-on-lan').fadeIn();
	LoadComputers();
});


function InitialLoadComputers() {
	$.getJSON( "acpi.services.php", { service: 'InitComputers'} )
	.done(function( data ) {
		var cell = document.getElementById('InitWaitText');
		if (data.newItems == 0) {
			window.location.reload();
		} else {
			if (cell)
				cell.innerHTML = data.newItems + " devices found.";
			InitialLoadComputers();
		}
	}).fail(function (jqXHR, textStatus, errorThrown) {
		var cell = document.getElementById('InitWaitText');
		LoadImage('InitWaitSpin', 'blocked.png', '');
		cell.innerHTML = "Initialization failed";
		DisplayError(jqXHR, textStatus, errorThrown);
	});
}

function LoadComputers() {
	$.getJSON( "acpi.services.php", { service: 'Computers' } )
	.done(function( data ) {
		if ($.isEmptyObject(data.items)) {
		// If the list of Computers is empty, we are in the initialization phase.
		// There will indeed be at least the NAS in that list after the initialization.		
			$.getJSON( "acpi.services.php", { service: 'InitACPI'} )
			.done(function( data ) {
				var cell = document.getElementById('InitWaitText');
				if ($.isEmptyObject(data.items)) {
					LoadImage('InitWaitSpin', 'blocked.png', '');
					if (cell)
						cell.innerHTML = "Initialization didn't find any network access";
				} else {
					LoadImage('InitWaitSpin', 'processing.gif', '');
					if (cell)
						cell.innerHTML = "Please wait while scanning your network(s)";
					InitialLoadComputers();						
				}
			}).fail(function (jqXHR, textStatus, errorThrown) {
				var cell = document.getElementById('InitWaitText');
				LoadImage('InitWaitSpin', 'blocked.png', '');
				cell.innerHTML = "Initialization failed";
				DisplayError(jqXHR, textStatus, errorThrown);
			});
		} else {
		// Once the initialization is done, the list of Computers is displayed.
		// We just have to refresh their status.
			computers = data;
			LoadIcons();
			LocalUpdate();
		}
	});
}

function RefreshNextComputer() {
	if (refreshIndex != 999999) {
		var key = Object.keys(computers.items)[refreshIndex];
		refreshIndex += 1;
		var computer = computers.items[key];
		if (computer) {
			var row = $('#row'+computer.id);
			if (row.is(':visible')) {
				// Update only "visible" Computers
				// Update the status only if the last update is older than a specified delay.
				var update = new Date(computer.update);
				if (isValidDate(update)) {
					update.setMinutes(update.getMinutes() + refreshDelay);
				} else {
					update = new Date();
				}
				var now = new Date();
				if (update < now) {
					CheckComputerState(computer);
					if (currentCheck < concurrentCheck) {
						RefreshNextComputer();
					}
				} else {
					RefreshNextComputer();
				}
			} else {
				// Skip "hidden" Computers
				RefreshNextComputer();
			}
		} else {
			//Notification.show("Status update completed", "Success");
		}
	}
}

function isValidDate(d) {
  if ( Object.prototype.toString.call(d) !== "[object Date]" )
    return false;
  return !isNaN(d.getTime());
}

function StopUpdate() {
	if (refreshIndex != 999999) {
		refreshIndex = 999999;
		Notification.show("Stopping the update of devices' status", "Warning");
	}
}

function ForcedUpdate() {
	refreshDelay = 0;
	refreshIndex = 0;
	currentCheck = 0;
	Notification.show("Please wait while status of all devices is checked", "Info");
	RefreshNextComputer();
}

function LocalUpdate() {
	refreshDelay = deprecateDelay;
	refreshIndex = 0;
	currentCheck = 0;
	Notification.show("Please wait while deprecated status of devices is checked", "Info");
	RefreshNextComputer();
}

function Backup() {	
	var $preparingFileModal = $("#preparing-file-modal");
	$preparingFileModal.dialog({ modal: true });

	$.fileDownload("acpi.services.php?service=Backup", {
		successCallback: function (url) {
			$preparingFileModal.dialog('close');
		},
		failCallback: function (responseHtml, url) {
			$preparingFileModal.dialog('close');
			$("#error-modal").dialog({ modal: true });
		}
	});
}

function GetService() {	
	var $preparingFileModal = $("#preparing-file-modal");
	$preparingFileModal.dialog({ modal: true });

	$.fileDownload("acpi.services.php?service=GetService", {
		successCallback: function (url) {
			$preparingFileModal.dialog('close');
		},
		failCallback: function (responseHtml, url) {
			$preparingFileModal.dialog('close');
			$("#error-modal").dialog({ modal: true });
		}
	});
}

function Restore() {	
	var $restore = $("#restore_container");
	$restore.dialog({ modal: true, width: "auto" });
}

function startRestore() {
	event.preventDefault();
	
	document.getElementById('f1_restore_process').style.visibility = 'visible';
	document.getElementById('f1_restore_form').style.visibility = 'hidden';

	var fd = new FormData(document.getElementById("restore_container_form"));
	
	$.ajax({
	  url: "acpi.services.php?service=Restore",
	  type: "POST",
	  data: fd,
	  processData: false,  // tell jQuery not to process the data
	  contentType: false   // tell jQuery not to set contentType
	}).done(function( data ) {
		$("#restore_container").dialog('close');
		document.getElementById('f1_restore_process').style.visibility = 'hidden';
		document.getElementById('f1_restore_form').style.visibility = 'visible';		
		
		if (data.state == 1) {
			Reload();
		} else {
			$('#results').text("Restore failed");
			$("#restore_error").dialog({ modal: true });
		};			
	}).fail(function (jqXHR, textStatus, errorThrown) {
		$("#restore_container").dialog('close');
		document.getElementById('f1_restore_process').style.visibility = 'hidden';
		document.getElementById('f1_restore_form').style.visibility = 'visible';		

		$("#restore_error").dialog({ modal: true });
		DisplayError(jqXHR, textStatus, errorThrown);
	});
	return false;
}

function Import() {	
	var $import = $("#import_container");
	$import.dialog({ modal: true, width: "auto" });
}

function startImport() {
	event.preventDefault();
	
	document.getElementById('f1_import_process').style.visibility = 'visible';
	document.getElementById('f1_import_form').style.visibility = 'hidden';

	var fd = new FormData(document.getElementById("import_container_form"));
	
	$.ajax({
	  url: "acpi.services.php?service=Import",
	  type: "POST",
	  data: fd,
	  processData: false,  // tell jQuery not to process the data
	  contentType: false   // tell jQuery not to set contentType
	}).done(function( data ) {
		$("#import_container").dialog('close');
		document.getElementById('f1_import_process').style.visibility = 'hidden';
		document.getElementById('f1_import_form').style.visibility = 'visible';		
		
		if (data.state == 1) {
			Reload();
		} else {
			$('#results').text("Import failed");
			$("#import_error").dialog({ modal: true });
		};			
	}).fail(function (jqXHR, textStatus, errorThrown) {
		$("#import_container").dialog('close');
		document.getElementById('f1_import_process').style.visibility = 'hidden';
		document.getElementById('f1_import_form').style.visibility = 'visible';		

		$("#import_error").dialog({ modal: true });
		DisplayError(jqXHR, textStatus, errorThrown);
	});
	return false;
}

function UploadIcon() {	
	var $icon = $("#icon_container");
	$icon.dialog({ modal: true, width: "auto" });
}

function RefreshEth() {
	Notification.show("Refreshing Ethernet", 'Info');
	$.getJSON( "acpi.services.php", { service: 'InitEthernet'} )
	.done(function( data ) {
		Notification.show("Ethernet successfuly refreshed", 'Success');
	}).fail(function (jqXHR, textStatus, errorThrown) {
		Notification.show("Ethernet couldn't be refreshed", 'Error');
		DisplayError(jqXHR, textStatus, errorThrown);
	});
}

function startUploadIcon() {
	event.preventDefault();
	
	document.getElementById('f1_icon_process').style.visibility = 'visible';
	document.getElementById('f1_icon_form').style.visibility = 'hidden';

	var fd = new FormData(document.getElementById("icon_container_form"));
	
	$.ajax({
	  url: "acpi.services.php?service=UploadIcon",
	  type: "POST",
	  data: fd,
	  processData: false,  // tell jQuery not to process the data
	  contentType: false   // tell jQuery not to set contentType
	}).done(function( data ) {
		$("#icon_container").dialog('close');
		document.getElementById('f1_icon_process').style.visibility = 'hidden';
		document.getElementById('f1_icon_form').style.visibility = 'visible';		
		
		if (data.state == 1) {
			LoadIcons();
		} else {
			$('#results').text("icon failed");
			$("#icon_error").dialog({ modal: true });
		};			
	}).fail(function (jqXHR, textStatus, errorThrown) {
		$("#icon_container").dialog('close');
		document.getElementById('f1_icon_process').style.visibility = 'hidden';
		document.getElementById('f1_icon_form').style.visibility = 'visible';		

		$("#icon_error").dialog({ modal: true });
		DisplayError(jqXHR, textStatus, errorThrown);
	});
	return false;
}

$(function(){
    $.contextMenu({
        selector: '.context-menu-device', 
        callback: function(key, options) {
            var id = $(this).attr('computerid');
			SetOS(id, key, 1);
        },
        items: {		
			setOS: {
				name: "Set OS", 
				items: loadOs(),
				icon: "operating"
			}, 
			ping: {name: "Check State", icon: "check",
                callback: function(key, options) {
                    var id = $(this).attr('computerid');
					var computer = computers.items[id];
                    CheckComputerState(computer);
                }
			},
			check: {name: "Check IP", icon: "check",
                callback: function(key, options) {
                    var id = $(this).attr('computerid');
					var computer = computers.items[id];
                    CheckComputerIp(computer);
                }
			},
			setIcon: {name: "Set Icon", icon: "icon",
                callback: function(key, options) {
                    var id = $(this).attr('computerid');
					current = computers.items[id];
					$('#demo').on('hidden.bs.modal', function (e) {
						current = (function(){}()); //=deleted
					});
					$('#myModal').modal({
						keyboard: true
					});
                }
			},
			resetIcon: {name: "Reset Icon", icon: "reset",
                callback: function(key, options) {
                    var id = $(this).attr('computerid');
					var computer = computers.items[id];
					computer.icon= '';
					CheckComputerState(computer);
                }
			},
			setWebpage: {name: "Set Web Port", icon: "browse",
                callback: function(key, options) {
                    var id = $(this).attr('computerid');
					var computer = computers.items[id];
					computer.icon= '';
					GetComputerPort(computer);
                }
			}
        }
    });
});

function loadOs() {
	var osMenu = {};
	$.each(osFeatures, function(index, value){
		osMenu[index] = {name: value.name, icon: value.icon};
	});
		
	return osMenu;
}
				
$(function(){
    $.contextMenu({
        selector: '.context-menu-hostname',
        items: {		
			editHostname: {
				name: "Edit hostname", 
				icon: "type", 
				callback: function(key, options) {
					var id = $(this).attr('computerid');
					EditHost(id);
				}
			},
			fetchHostname: {
				name: "Fetch hostname", 
				icon: "fetch", 
				callback: function(key, options) {
					var id = $(this).attr('computerid');
					FetchHostName(id);
				}
			},
			fetchVendorname: {
				name: "Fetch vendor name", 
				icon: "fetch", 
				callback: function(key, options) {
					var id = $(this).attr('computerid');
					FetchVendorName(id);
				}
			}
        }
    });
});

$(function(){
    $.contextMenu({
        selector: '.context-menu-macaddress',
        items: {		
			fetchVendor: {
				name: "Fetch vendor", 
				icon: "fetch", 
				callback: function(key, options) {
					var id = $(this).attr('computerid');
					FetchVendor(id);
				}
			}
        }
    });
});

$(function(){
    $('#advanced').on('click', function(e) {
        e.preventDefault();
        $('.context-menu-advanced').contextMenu();
    });
	
    $.contextMenu({
        selector: '.context-menu-advanced',
		trigger: 'none',
        items: {		
			pingDevice: {
				name: "Ping a Device",
				callback: function(key, options) {
					StopUpdate();
					PingDevice();
				}
			},
			wolDevice: {
				name: "WOL a Device",
				callback: function(key, options) {
					StopUpdate();
					WolDevice();
				}
			},
			Backup: {
				name: "Backup",
				callback: function(key, options) {
					StopUpdate();
					Backup();
				}
			},
			Restore: {
				name: "Restore",
				callback: function(key, options) {
					StopUpdate();
					Restore();
				}
			},
			Import: {
				name: "Import",
				callback: function(key, options) {
					StopUpdate();
					Import();
				}
			},
			Ethernet: {
				name: "Refresh Eth",
				callback: function(key, options) {
					StopUpdate();
					RefreshEth();
				}
			},
			Icon: {
				name: "Upload Icon",
				callback: function(key, options) {
					StopUpdate();
					UploadIcon();
				}
			},
			Token: {
				name: "Vendor Token",
				callback: function(key, options) {
					StopUpdate();
					UploadToken();
				}
			}
		}
    });
});

$(function(){
    $('#devices').on('click', function(e) {
        e.preventDefault();
        $('.context-menu-devices').contextMenu();
    });
	
    $.contextMenu({
        selector: '.context-menu-devices',
		trigger: 'none',
        items: {		
			Flush: {
				name: "Flush",
				callback: function(key, options) {
					StopUpdate();
					Flush();
				}
			},
			Reload: {
				name: "Reload",
				callback: function(key, options) {
					StopUpdate();
					Reload();
				}
			},
			Update: {
				name: "Update",
				callback: function(key, options) {
					StopUpdate();
					LocalUpdate();
				}
			},
			ForcedCheck: {
				name: "Update All",
				callback: function(key, options) {
					StopUpdate();
					ForcedUpdate();
				}
			},
			StopCheck: {
				name: "Stop Update",
				callback: function(key, options) {
					StopUpdate();
				}
			},
			ResetAll: {
				name: "Reset All",
				callback: function(key, options) {
					StopUpdate();
					ResetAll();
				}
			}
		}
    });
});

function PingDevice() {
	HideMenus();
	$.acpiZoom.disable( true );
	bootbox.prompt({
		title: "Please enter an IP address", 
		value: subnet,
		callback: function(ip) {
			$.acpiZoom.enable( true );
			ShowMenus();
			if(ip!=null) {
				if (!ValidateIPaddress(ip.trim())) {
					Notification.show("not a valid IP", "Warning");
				} else {
					Ping(ip.trim());
				}
			}
		}
	});	
}

function UploadToken() {
	HideMenus();
	$.acpiZoom.disable( true );
	bootbox.prompt({
		title: "Please enter your MAC Vendor's token", 
		value: "",
		callback: function(token) {
			$.acpiZoom.enable( true );
			ShowMenus();
			if(token!=null) {
				$.getJSON( "acpi.services.php", { service: 'UpdateToken', token: token } )
				.done(function( data ) {
					Notification.show("Token updated", "Success");
				}).fail(function (jqXHR, textStatus, errorThrown) {
					Notification.show("Token couldn't be updated", "Error");
					DisplayError(jqXHR, textStatus, errorThrown);
				 });
			}
		}
	});	
}

function WolDevice() {
	HideMenus();
	$.acpiZoom.disable( true );
	bootbox.prompt({
		title: "Please enter an MAC address", 
		value: "",
		callback: function(mac) {
			$.acpiZoom.enable( true );
			ShowMenus();
			if(mac!=null) {
				if (!ValidateMACaddress(mac.trim())) {
					Notification.show(mac+" is not a valid MAC", "Warning");
				} else {
					WakeOnLanMac(mac.trim());
				}
			}
		}
	});	
}

function ValidateMACaddress(address) {  
	if (/^([0-9A-F]{2}[:-]){5}([0-9A-F]{2})$/.test(address)) {
		return (true);
	}
	return (false);
}

function ValidateIPaddress(address) {  
	if (/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(address)) {
		return (true);
	}
	return (false);
}
  
function SetOS(id, os, ping) {
	ping = ping || 0;
	os = os.toLowerCase();
	$.getJSON( "acpi.services.php", { service: 'SetOS', id: id, os: os } )
	.done(function( data ) {
		var computer = computers.items[id];
		computer.os = os;
		Notification.show("OS of "+GetIdName(computer)+" is now "+os, "Success");
		LoadImage('os'+id, 'os_'+os+'.png', os);
		if (ping == 1) {
			CheckComputerState(computer);
		}
	}).fail(function (jqXHR, textStatus, errorThrown) {
		var computer = computers.items[id];
		Notification.show("OS of "+GetIdName(computer)+" couldn't be changed", "Error");
		DisplayError(jqXHR, textStatus, errorThrown);
	});
}

function ResetAll() {
	bootbox.confirm(
		"Do you really want to reset Acpi-On-Lan? All your custom information will be lost ?",
		function(result) {
			if (result == true) {
				Notification.show("Reset Acpi-On-Lan requested.", "Warning");
				$.getJSON( "acpi.services.php", { service: 'ResetAll'} )
				.done(function( data ) {
					Notification.show("Reset Acpi-On-Lan completed.", "Success");
					Notification.show("Network will be scanned.", "Info");
					$('#acpi-on-lan').delay(2000).fadeOut().queue(function() { window.location.reload(); });
				}).fail(function (jqXHR, textStatus, errorThrown) {
					Notification.show("Reset Acpi-On-Lan failed", "Error");
					DisplayError(jqXHR, textStatus, errorThrown);
				});
			}
		}
	);
}

function Flush() {
	Notification.show("Network Flush requested.", "Warning");
	$.getJSON( "acpi.services.php", { service: 'Flush'} )
	.done(function( data ) {
		Notification.show("Network Flush completed.", "Success");
		Notification.show("Network will be rescanned.", "Info");
		$('#acpi-on-lan').delay(2000).fadeOut().queue(function() { window.location='index.php?message=flushing network'; });
	}).fail(function (jqXHR, textStatus, errorThrown) {
		Notification.show("Network Flush failed", "Error");
		DisplayError(jqXHR, textStatus, errorThrown);
	});
}

function LogOut() {
	Notification.show("Log Out requested.", "Warning");
	$.getJSON( "login.php", { service: 'LogOut'} )
	.done(function( data ) {
		Notification.show("Log Out completed.", "Success");
		$('#acpi-on-lan').delay(2000).fadeOut().queue(function() { window.location='login.php'; });
	}).fail(function (jqXHR, textStatus, errorThrown) {
		Notification.show("Log Out failed", "Error");
		DisplayError(jqXHR, textStatus, errorThrown);
	});
}

function Search() {
	StopUpdate();
	HideMenus();
	$.acpiZoom.disable( true );
	bootbox.prompt({
		title: "Search", 
		value: searchValue,
		callback: function(text){
			if(text!=null) {
				if (searchValue == text) {
					searchIndex++;
				} else {
					searchIndex=0;
				}
				searchValue = text;
				if (!searchAndHighlight(text)) {
					alert(text + " not found");
					searchValue="";
				}
			} else {
				searchValue = "";
			}					
			$.acpiZoom.enable( true );
			ShowMenus();
			
			if (searchValue=='') {
				var next = $('#searchNext');
				next.fadeOut();
				var previous = $('#searchPrevious');
				previous.fadeOut();
			} else {
				var next = $('#searchNext');
				next.fadeIn();
				var previous = $('#searchPrevious');
				previous.fadeIn();
			}
		}
	});	
}

function SearchNext() {
	if (searchValue) {
		searchIndex++;
		searchAndHighlight(searchValue);
	}
}

function SearchPrevious() {
	if (searchValue && searchIndex>0) {
		searchIndex--;
		searchAndHighlight(searchValue);
	}
}

function Reload() {
	Notification.show("Reload requested.", "Warning");
	$('#acpi-on-lan').delay(2000).fadeOut().queue(function() { window.location='index.php?message=reloading devices'; });
}

function GetComputerPort(computer){
	var webport = prompt("Please, enter a port", computer.webpage);
	if (webport != 0) {
		computer.webpage = webport;
		CheckHttp(computer);					
	}
}

function CheckComputerState(computer){
	if (computer.ip == '0.0.0.0') {
		ComputerWithoutPing(computer.id);		
		RefreshNextComputer();
	} else {
		currentCheck += 1;
		ResetIconState(computer.id);
		LoadImage('img'+computer.id, 'loading.gif', 'Check State running');
		if (callServerSide == 1) {
			// Call the server to check for Acpi-On-Lan
			$.getJSON( "acpi.services.php", { service: 'CheckAcpiOnLan', id: computer.id} )
			.done(function( data ) {
				computer.acpiOnLan = data.acpiOnLan;
				if (computer.acpiOnLan == 1) {
					AcpiOnLanAnswer(computer, data.os, data.hostname);
				} else {
					AcpiOnLanFail(computer);
				}
				currentCheck -= 1;
			}).fail(function (jqXHR, textStatus, errorThrown) {
				AcpiOnLanFail(computer);
				currentCheck -= 1;
				DisplayError(jqXHR, textStatus, errorThrown);
			});						
		} else {
			// check for Acpi-On-Lan from the client
			$.ajax({
				url: 'http://'+computer.ip+':8888/api/computer/state',
				timeout:2000,
				type: 'GET',
				contentType: "application/json; charset=utf-8"
			}).done(function( info ) {
				AcpiOnLanAnswer(computer, info.os, info.hostname);
				currentCheck -= 1;
			}).fail(function (jqXHR, textStatus, errorThrown) {
				AcpiOnLanFail(computer);
				currentCheck -= 1;
				DisplayError(jqXHR, textStatus, errorThrown);
			});			
		}
		return false;
	}
}

function CheckComputerIp(computer){
	/*if (computer.ip == '0.0.0.0') {
		ComputerWithoutPing(computer.id);		
		RefreshNextComputer();
	} else */{
		currentCheck += 1;
		//ResetIconState(computer.id);
		LoadImage('img'+computer.id, 'loading.gif', 'Check IP running');
		// Call the server to check for Acpi-On-Lan
		$.getJSON( "acpi.services.php", { service: 'CheckIp', id: computer.id} )
		.done(function( data ) {
			CheckIpAnswer(computer, data.ip);
			currentCheck -= 1;
		}).fail(function (jqXHR, textStatus, errorThrown) {
			Notification.show("Couldn't Check IP of " + computer.mac, "Error");
			CheckIpAnswer(computer, '0.0.0.0');
			currentCheck -= 1;
			DisplayError(jqXHR, textStatus, errorThrown);
		});
		return false;
	}
}

function AcpiOnLanAnswer(computer, os, hostname) {
	// Acpi-On-Lan was available
	computer.hostname = hostname;
	
	SetOS(computer.id, os);
	ComputerWithACPIOnLan(computer.id);
	SetIPOn(computer.id);
	
	// Check if there is a web site running on this Computer
	CheckHttp(computer);
	RefreshNextComputer();
}

function CheckIpAnswer(computer, ip) {
	var cell = document.getElementById('ip'+computer.id);
	LoadImage('img'+computer.id, computer.icon);
	if (ip == '' || ip == '0.0.0.0') {
		SetIPOff(computer.id);
		Notification.show("No IP assigned for "+computer.mac, 'Info');
		ComputerWithoutPing(computer.id);
	} else if (ip == computer.ip) {
		SetIPOn(computer.id);
		Notification.show("IP unchanged for "+computer.mac, 'Info');
	} else if (ip != '0.0.0.0') {
		SetIPOn(computer.id);
		cell.innerHTML = htmlEncode(ip);
		computer.ip = ip;
		Notification.show("New IP  for "+computer.mac, 'Info');
	} else {
		SetIPOff(computer.id);
		Notification.show("IP unknown for "+computer.mac, 'Warning');
	}
}

function AcpiOnLanFail(computer) {
	// Acpi-On-Lan was not available, try to Ping the Computer
	$.getJSON( "acpi.services.php", { service: 'PingComputer', id: computer.id } )
	.done(function( data ) {
		switch (data.state) {
		  case 1:
			ComputerWithoutACPIOnLan(computer.id);
			SetIPOn(computer.id);
			CheckHttp(computer);
			break;
		  case 0: 
			ComputerWithoutPing(computer.id);
			// Http can be opened while Ping is disabled
			CheckHttp(computer);
			break;
		  case -1: 
			Notification.show("The package 'inetutils' is missing", "Error");
			Notification.show("'inetutils' can be installed with OPKG (Entware)", "Info");
		  default:
			ComputerIssue(computer.id); 	
			break;
		}
		RefreshNextComputer();
	  })
	.fail(function(jqXHR, textStatus, errorThrown) {
		ComputerIssue(computer.id);
		RefreshNextComputer();
		DisplayError(jqXHR, textStatus, errorThrown);
	});
}

function Ping(address) {
	// Acpi-On-Lan was not available, try to Ping the Computer
	$.getJSON( "acpi.services.php", { service: 'Ping', ip: address } )
	.done(function( data ) {
		switch (data.state) {
		  case 1:
			Notification.show("The device on "+address+ " answered", "Info");
			break;
		  default:
			Notification.show("The device on "+address+ " cannot be reached", "Info");
			break;
		}
		RefreshNextComputer();
	  })
	.fail(function(jqXHR, textStatus, errorThrown) {
		Notification.show("Ping failed", "Error");
		DisplayError(jqXHR, textStatus, errorThrown);
	});
}

function CheckHttp(computer){
	$.getJSON( "acpi.services.php", { service: 'CheckHttp', id: computer.id, webport: computer.webpage } )
	.done(function( data ) {
		if (data.webpage != 0) {
			var img = document.getElementById('brw'+computer.id);
			if (img != null) img.style.visibility='visible';
			
			var a = document.getElementById('brwip'+computer.id);
			if (a != null) {
				a.href = 'http://'+computer.ip+':'+data.webpage;
			}
		} else {
			var img = document.getElementById('brw'+computer.id);
			if (img != null) img.style.visibility='hidden';
			if (computer.webpage != 0)
				Notification.show("Cannot reach port " + computer.webpage + " of " + GetIdName(computer), "Notification");
		}
	})
	.fail(function(jqXHR, textStatus, errorThrown) {
		Notification.show("Cannot check port " + computer.webpage + " of " + GetIdName(computer), "Error");
        DisplayError(jqXHR, textStatus, errorThrown);
	});

	return false;
}

function GetIdName(computer) {
	var name = computer.hostname;
	if (name == null || name.length === 0) {
		name = computer.ip;
		if (name == null || name.length === 0 || name == "0.0.0.0") {
			name = computer.mac;
		}
	}
	return name;
}

function EnableFeatures(id, on) {
	for	(var index = 0; index < features.length; index++) {
		var feature = features[index];
		var isOn = on.indexOf(feature);
		if (isOn >= 0) {
			SetActionImage(feature, id, 1);			
		} else {
			SetActionImage(feature, id, 0);
		}
	}
	var computer = computers.items[id];
}

function ComputerWithACPIOnLan(id) {
	var computer = computers.items[id];
	var name = GetIdName(computer);

	computer.acpiOnLan = 1;
	computer.state = 1;
	computer.features = ['hib', 'slp', 'stb', 'shd', 'rst'];

	ResetIconState(id);
	if (computer.icon.indexOf('icon_') != 0) {
		computer.icon = 'on.png';		
	}
	EnableFeatures(id, computer.features);
	
	LoadImage('img'+id, computer.icon, 'ACPIOnLan is running');
	Notification.show(name+" is ON and ACPI-On-Lan is running", "Success");

	SaveState(computer);
}

function ComputerWithoutACPIOnLan(id) {
	var computer = computers.items[id];
	var name = GetIdName(computer);

	computer.acpiOnLan = 0;
	computer.state = 1;
	ResetIconState(id);
	if (computer.os == 'windows') {
		computer.features = ['shd', 'rst'];
		if (computer.icon.indexOf('icon_') != 0) {
			computer.icon = 'out.png';
		} else {
			SetIconOut(id);
		}
		LoadImage('img'+id, computer.icon, 'ACPIOnLan not running');
		Notification.show(name+" is ON but ACPI-On-Lan is not running", "Warning");
	} else if (computer.os == 'other') {
		computer.features = [];  //Shutdown not supported
		if (computer.icon.indexOf('icon_') != 0) {
			computer.icon = 'other_on.png';
		}		
		LoadImage('img'+id, computer.icon, 'ACPIOnLan not supported');
		Notification.show(name+" is ON", "Success");
	} else {
		computer.features = [];
		if (computer.icon.indexOf('icon_') != 0) {
			computer.icon = 'other_on.png';
		}
		LoadImage('img'+id, computer.icon, 'ACPIOnLan not supported');
		Notification.show(name+" is ON but does not support ACPI-On-Lan", "Success");
	}	
	EnableFeatures(id, computer.features);
	
	SaveState(computer);
}

function ComputerWithoutPing(id) {
	var computer = computers.items[id];
	var name = GetIdName(computer);

	computer.acpiOnLan = 0;
	computer.state = 0;

	ResetIconState(id);
	SetIPOff(id);
	if (computer.os == 'windows') {
		computer.features = ['wol'];
		if (computer.icon.indexOf('icon_') != 0) {
			computer.icon = 'off.png';
		}
		SetIconOff(id);		
		LoadImage('img'+id, computer.icon, 'Computer Off');
		Notification.show(name+" is Off");
	} else if (computer.os == 'other') {
		computer.features = []; //Wol not supported
		if (computer.icon.indexOf('icon_') != 0) {
			computer.icon = 'other_off.png';
		}
		SetIconOff(id);
		LoadImage('img'+id, computer.icon, 'Device not reachable');
		Notification.show(name+" cannot be reached");
	} else {
		computer.features = ['wol'];
		if (computer.icon.indexOf('icon_') != 0) {
			computer.icon = 'other_off.png';
		}
		SetIconOff(id);
		LoadImage('img'+id, computer.icon, 'Device not reachable');
		Notification.show(name+" cannot be reached");
	}
	EnableFeatures(id, computer.features);
	
	SaveState(computer);
}

function ComputerIssue(id) {
	var computer = computers.items[id];
	var name = GetIdName(computer);

	computer.acpiOnLan = -1;
	computer.state = -1;
	computer.features = ['wol'];
	//computer.icon = 'broken.png';	
	EnableFeatures(id, computer.features);
	
	ResetIconState(id);
	LoadImage('img'+id, 'broken.png', 'Access failure');
	Notification.show("A Fatal error occurred when checking "+name, "Error");
	
	SaveState(computer);
}

function ResetIconState(id) {
	var elem = $('#img'+id);
	elem.removeClass('ACPIOff');
	elem.removeClass('ACPIOut');
	SetIPOn(id);
}

function SetIconOff(id) {
	var elem = $('#img'+id);
	elem.addClass('ACPIOff');
}

function SetIconOut(id) {
	var elem = $('#img'+id);
	elem.addClass('ACPIOut');
}

function SetIPOff(id) {
	var elem = $('#ip'+id);
	elem.removeClass('oldip');
	elem.removeClass('ip');	
	elem.addClass('oldip');
}

function SetIPOn(id) {
	var elem = $('#ip'+id);
	elem.removeClass('oldip');
	elem.removeClass('ip');	
	elem.addClass('ip');
}

function SaveState(computer) {
	$.getJSON( "acpi.services.php", { service: 'SaveState', id: computer.id, acpiOnLan: computer.acpiOnLan, state: computer.state, icon: computer.icon, features: computer.features } )
	.done(function( data ) {
		computer.update = data.update;
	}).fail(function (jqXHR, textStatus, errorThrown) {
		Notification.show("State of "+GetIdName(computer)+" couldn't be saved", "Error");
        DisplayError(jqXHR, textStatus, errorThrown);
     });
}

function SetActionImage(tag, id, state) {
	var img = $('#'+tag+id);
	//attr return the value set as src, while prop returns the expanded value (full uri).
	var src = img.prop('src');
	var title = img.attr('title');
	if (state == 1) {
		if (src.indexOf("-0.png") > -1) {
			src = src.replace('-0.png', '-1.png');
			title = title.replace(' \(disabled\)', '');
			img.attr('src', src);
			img.attr('title', title);
		}
		img.parent().parent().fadeIn();
		//img.css('display','block');
		img.parent().removeClass("disabled");
	} else {
		if (src.indexOf("-1.png") > -1) {
			src = src.replace('-1.png', '-0.png');
			title += ' (disabled)';
			img.attr('src', src);
			img.attr('title', title);
		}
		if (actionDisplay == 0) {
			img.parent().parent().fadeOut();
			//img.css('display','none');
		}
	}
}

function SetState(id, state){
	var computer = computers.items[id];	

	var actions = $('#actions'+id);
	var abtAction = $('#abtAction'+id);
	if (state == 'abort') {
		abtAction.fadeOut();
		actions.delay(500).fadeIn();	
	} else {
		actions.fadeOut();
		abtAction.delay(500).fadeIn();
	}
	
	if (computer.os == 'windows' && computer.acpiOnLan == 0)
	{
		// Call the server to execute Net Rpc
		$.getJSON( "acpi.services.php", { service: 'NetCall', id: id, state: state} )
		.done(function( data ) {
			Notification.show(GetIdName(computer)+" going to "+state+" via rpc", "Warning");
			if (state != 'abort') {
				setTimeout(function() {
					var abtAction = $('#abtAction'+id);
					abtAction.fadeOut();
					var actions = $('#actions'+id);
					actions.delay(500).fadeIn();
					}, 20000);
				setTimeout(function() { CheckComputerState(computer) }, 60000);
			}
		}).fail(function (jqXHR, textStatus, errorThrown) {
			var computer = computers.items[id];
			Notification.show(GetIdName(computer)+" couldn't be set to "+state+" via rpc", "Error");
			DisplayError(jqXHR, textStatus, errorThrown);
		});		
	} else {	
		if (callServerSide == 1) {
			// Call the server to Set State
			$.getJSON( "acpi.services.php", { service: 'SetState', id: id, state: state} )
			.done(function( data ) {
				Notification.show(GetIdName(computer)+" going to "+state, "Warning");
				if (state != 'abort') {
					setTimeout(function() {
						var abtAction = $('#abtAction'+id);
						abtAction.fadeOut();
						var actions = $('#actions'+id);
						actions.delay(500).fadeIn();
						}, 20000);
					setTimeout(function() { CheckComputerState(computer) }, 60000);
				}
			}).fail(function (jqXHR, textStatus, errorThrown) {
				var computer = computers.items[id];
				Notification.show(GetIdName(computer)+" couldn't be set to "+state, "Error");
				DisplayError(jqXHR, textStatus, errorThrown);
			});
		} else {
			// Set State from the client
			var url = 'http://'+computer.ip+':8888/api/computer/state?mode='+state;
			 $.ajax({
				url: url,
				type: 'PUT',
				contentType: "application/json; charset=utf-8"
			 })
			 .done(function(data) {
				if (data.current == "state") {
					Notification.show(GetIdName(computer)+" going to "+state, "Warning");
					setTimeout(function() { CheckComputerState(computer) }, 60000);
				} else {
					Notification.show(GetIdName(computer)+" failed to go to "+state, "Error");
				}
			 }).fail(function (jqXHR, textStatus, errorThrown) {
				var computer = computers.items[id];
				Notification.show(GetIdName(computer)+" couldn't be set to "+state, "Error");
				DisplayError(jqXHR, textStatus, errorThrown);
			 });
		}
	}
	return false;
}

function SwitchVisibility(id) {
	var computer = computers.items[id];
	var state = (1 - computer.hidden);
	$.getJSON( "acpi.services.php", { service: 'SwitchVisibility', id: id, state: state } )
	.done(function( data ) {
		var computer = computers.items[id];
		computer.hidden = data.hidden;
		if (computer.hidden == 1) {
			var row = $('#row'+id);
			row.fadeOut();
			var emptyrow = $('#emptyrow'+id);
			emptyrow.fadeOut();
			
			Notification.show(GetIdName(computer)+" is now hidden", "Success");
			LoadImage('rmv'+id, 'restore.png', 'Show');
		} else {
			Notification.show(GetIdName(computer)+" is not hidden anymore", "Success");
			LoadImage('rmv'+id, 'remove.png', 'Show');
		}
		var showAll = $('#showAll');	
		showAll.fadeIn();
	}).fail(function (jqXHR, textStatus, errorThrown) {
		var computer = computers.items[id];
		Notification.show(GetIdName(computer)+" couldn't be hidden", "Error");
        DisplayError(jqXHR, textStatus, errorThrown);
     });
}

function Delete(id) {
	var computer = computers.items[id];
	$.getJSON( "acpi.services.php", { service: 'Delete', id: id } )
	.done(function( data ) {
		var computer = computers.items[id];
		if (data != null && data.hidden == -1) {
			computer.hidden = -1;
			
			var row = $('#row'+id);
			row.fadeOut();
			var emptyrow = $('#emptyrow'+id);
			emptyrow.fadeOut();
		} else {
			Notification.show(GetIdName(computer)+" couldn't be deleted", "Error");
		}
		var showAll = $('#showAll');	
		showAll.fadeIn();
	}).fail(function (jqXHR, textStatus, errorThrown) {
		var computer = computers.items[id];
		Notification.show(GetIdName(computer)+" couldn't be deleted", "Error");
        DisplayError(jqXHR, textStatus, errorThrown);
     });
}

function HideAll() {
	jQuery.each(computers.items, function(id, computer) {
		if (computer.hidden != 0) {
			var row = $('#row'+id);
			row.fadeOut();
			var emptyrow = $('#emptyrow'+id);
			emptyrow.fadeOut();			
		}
	});
	var showAll = $('#showAll');	
	showAll.fadeIn();
	var hideAll = $('#hideAll');	
	hideAll.fadeOut();
}

function ShowAll() {
	jQuery.each(computers.items, function(id, computer) {
		if (computer.hidden == 1) {
			var row = $('#row'+id);
			row.fadeIn();
			var emptyrow = $('#emptyrow'+id);
			emptyrow.fadeIn();
			
			LoadImage('rmv'+id, 'restore.png', 'Show');
		}
	});			
	var showAll = $('#showAll');
	showAll.fadeOut();
	var hideAll = $('#hideAll');	
	hideAll.fadeIn();
}

function LoadImage(id, image, title) {
	var img = $('#'+id);
	if (img.length > 0) {
		//attr return the value set as src, while prop returns the expanded value (full uri).
		var path=img.prop('src');
		path=path.substring(0, path.lastIndexOf("/"));				
		img.attr('src', path+"/"+image);
		if (title) {
			img.attr('title', title);
		} else  {
			img.attr('title', '');
		} 
	}
}

function WakeOnLanId(id) {
	$.getJSON( "acpi.services.php", { service: 'WakeOnLan', id: id } )
	.done(function( data ) {
		var computer = computers.items[id];
		Notification.show(GetIdName(computer)+" should be awake soon", "Success");
	}).fail(function (jqXHR, textStatus, errorThrown) {
		var computer = computers.items[id];
		Notification.show(GetIdName(computer)+" couldn't be waked", "Error");
        DisplayError(jqXHR, textStatus, errorThrown);
     });
}

function WakeOnLanMac(address) {
	$.getJSON( "acpi.services.php", { service: 'WakeOnLan', mac: address } )
	.done(function( data ) {
        Notification.show("Device "+data.hostname+" should be awake soon", "Success");
	}).fail(function (jqXHR, textStatus, errorThrown) {
		Notification.show("Device "+address+" couldn't be awake", "Error");
        DisplayError(jqXHR, textStatus, errorThrown);
     });
}

function FetchHostName(id) {
	var computer = computers.items[id];
	var cell = document.getElementById('host'+id);
	cell.style.backgroundRepeat = "no-repeat";
	cell.style.backgroundPosition = "center";
	cell.style.backgroundImage = "url('images/loading.gif')";
	cell.style.backgroundSize = "20px 20px";
	$.getJSON( "acpi.services.php", { service: 'FetchHost', ip: computer.ip } )
	.done(function( data ) {
		var computer = computers.items[id];
		var cell = document.getElementById('host'+id);
		cell.style.background = "none";
		if (data && data.hostname) {
			bootbox.confirm(
				"Do you accept the hostname '"+data.hostname+"' for "+computer.mac+" ?",
				function(result) {
					if (result == true) {
						cell.innerHTML = htmlEncode(data.hostname);
						LoadImage('ftc'+id, 'loading.gif', '');
						$.getJSON( "acpi.services.php", { service: 'EditHost', hostname: data.hostname, id: id } )
						.done(function( data ) {
							if (data.hostname) {
								var computer = computers.items[id];
								computer.hostname = data.hostname;

								var cell = document.getElementById('host'+id);
								cell.innerHTML = htmlEncode(data.hostname);
								Notification.show(computer.mac+" has been renamed into "+computer.hostname, "success");
							} else {
								LoadImage('ftc'+id, 'fetch.png', '');
							}
						}).fail(function (jqXHR, textStatus, errorThrown) {
							var computer = computers.items[id];
							Notification.show(GetIdName(computer)+" couldn't be renamed", "Error");
							DisplayError(jqXHR, textStatus, errorThrown);
						});
					}
				});
		} else {
			Notification.show("Hostname not found for "+computer.mac);
		}
	}).fail(function (jqXHR, textStatus, errorThrown) {
		var computer = computers.items[id];
		var cell = document.getElementById('host'+id);
		cell.style.background = "none";
		Notification.show("Host of "+GetIdName(computer)+" couldn't be fetched", "Error");
        DisplayError(jqXHR, textStatus, errorThrown);
    });
}

function FetchVendor(id) {
	var computer = computers.items[id];
	var cell = document.getElementById('mac'+id);
	cell.style.backgroundRepeat = "no-repeat";
	cell.style.backgroundPosition = "center";
	cell.style.backgroundImage = "url('images/loading.gif')";
	cell.style.backgroundSize = "20px 20px";
	$.getJSON( "acpi.services.php", { service: 'FetchVendor', mac: computer.mac } )
	.done(function( data ) {
		var computer = computers.items[id];
		var cell = document.getElementById('mac'+id);
		cell.style.background = "none";
		if (data) {
			bootbox.alert(
				"Vendor of '"+computer.mac+"' is '"+data+"'");
		} else {
			Notification.show("Vendor not found for "+computer.mac);
		}
	}).fail(function (jqXHR, textStatus, errorThrown) {
		var computer = computers.items[id];
		var cell = document.getElementById('mac'+id);
		cell.style.background = "none";
		Notification.show("Vendor of '"+computer.mac+"' couldn't be fetched", "Error");
        DisplayError(jqXHR, textStatus, errorThrown);
    });
}

function FetchVendorName(id) {
	var computer = computers.items[id];
	var cell = document.getElementById('host'+id);
	cell.style.backgroundRepeat = "no-repeat";
	cell.style.backgroundPosition = "center";
	cell.style.backgroundImage = "url('images/loading.gif')";
	cell.style.backgroundSize = "20px 20px";
	$.getJSON( "acpi.services.php", { service: 'FetchVendor', mac: computer.mac } )
	.done(function( data ) {
		var computer = computers.items[id];
		var cell = document.getElementById('host'+id);
		cell.style.background = "none";
		if (data) {
			bootbox.confirm(
				"Do you accept '"+data+"' as hostname for "+computer.mac+" ?",
				function(result) {
					if (result == true) {
						LoadImage('ftc'+id, 'loading.gif', '');
						$.getJSON( "acpi.services.php", { service: 'EditHost', hostname: data, id: id } )
						.done(function( data ) {
							if (data.hostname) {
								var computer = computers.items[id];
								computer.hostname = data.hostname;

								var cell = document.getElementById('host'+id);
								cell.innerHTML = htmlEncode(data.hostname);
								Notification.show(computer.mac+" has been renamed into "+computer.hostname, "success");
							} else {
								LoadImage('ftc'+id, 'fetch.png', '');
							}
						}).fail(function (jqXHR, textStatus, errorThrown) {
							var computer = computers.items[id];
							Notification.show(GetIdName(computer)+" couldn't be renamed", "Error");
							DisplayError(jqXHR, textStatus, errorThrown);
						});
					}
				});
		} else {
			Notification.show("Vendor not found for "+computer.mac);
		}
	}).fail(function (jqXHR, textStatus, errorThrown) {
		var computer = computers.items[id];
		var cell = document.getElementById('host'+id);
		cell.style.background = "none";
		Notification.show("Host of "+GetIdName(computer)+" couldn't be fetched", "Error");
        DisplayError(jqXHR, textStatus, errorThrown);
    });
}

function HideMenus() {
	var elem = $('#ACPImenus');
	elem.fadeOut();
	var elem = $('#ACPInotifications');
	elem.fadeOut();
}

function ShowMenus() {
	var elem = $('#ACPImenus');
	elem.fadeIn();
	var elem = $('#ACPInotifications');
	elem.fadeIn();
}

function EditHost(id) {
	var computer = computers.items[id];
	HideMenus();
	$.acpiZoom.disable( true );
	bootbox.prompt({
		title: "Please enter a hostname", 
		value: computer.hostname,
		callback: function(hostname) {
			$.acpiZoom.enable( true );
			ShowMenus();
			if (hostname === null) {
				var computer = computers.items[id];
				Notification.show(GetIdName(computer)+" has not be renamed", "Warning");
			} else {
				LoadImage('ftc'+id, 'loading.gif', '');
				$.getJSON( "acpi.services.php", { service: 'EditHost', hostname: hostname, id: id } )
				.done(function( data ) {
					if (data.hostname) {
						var computer = computers.items[id];
						computer.hostname = data.hostname;

						var cell = document.getElementById('host'+id);
						cell.innerHTML = htmlEncode(data.hostname);
						Notification.show(computer.mac+" has been renamed into "+computer.hostname, "success");
					} else {
						LoadImage('ftc'+id, 'fetch.png', '');
					}
				}).fail(function (jqXHR, textStatus, errorThrown) {
					var computer = computers.items[id];
					Notification.show(GetIdName(computer)+" couldn't be renamed", "Error");
					DisplayError(jqXHR, textStatus, errorThrown);
				});
			}
		}
	});
}

function htmlEncode(value){
  //create a in-memory div, set it's inner text(which jQuery automatically encodes)
  //then grab the encoded contents back out.  The div never exists on the page.
  return $('<div/>').text(value).html();
}

function htmlDecode(value){
  return $('<div/>').html(value).text();
}

(function( $ ) {
	var	meta = $( "meta[name=viewport]" ),
		initialContent = meta.attr( "content" ),
		disabledZoom = initialContent + ",maximum-scale=1, user-scalable=no",
		enabledZoom = initialContent + ",maximum-scale=10, user-scalable=yes",
		disabledInitially = /(user-scalable[\s]*=[\s]*no)|(maximum-scale[\s]*=[\s]*1)[$,\s]/.test( initialContent ),
		acpiZoom = {};

	$.acpiZoom = $.extend( {}, {
		enabled: !disabledInitially,
		locked: false,
		disable: function( lock ) {
			if ( !disabledInitially && !$.acpiZoom.locked ) {
				meta.attr( "content", disabledZoom );
				$.acpiZoom.enabled = false;
				$.acpiZoom.locked = lock || false;
			}
		},
		enable: function( unlock ) {
			if ( !disabledInitially && ( !$.acpiZoom.locked || unlock === true ) ) {
				meta.attr( "content", enabledZoom );
				$.acpiZoom.enabled = true;
				$.acpiZoom.locked = false;
			}
		},
		restore: function() {
			if ( !disabledInitially ) {
				meta.attr( "content", initialContent );
				$.acpiZoom.enabled = true;
			}
		}
	});

}( jQuery ));

function LoadIcons() {
	$.getJSON( "acpi.services.php", { service: 'GetIcons' } )
	.done(function( data ) {
		if (! $.isEmptyObject(data)) {
			var $loader = $('#image-loader');
			var $container = $loader.find('#image-container');

			// add new images
			var items = getItems(data);
			$container.prepend( $(items) );

			// use ImagesLoaded
			$container.imagesLoaded().progress( onProgress );
		}
	}).fail(function (jqXHR, textStatus, errorThrown) {
		$('#results').text(jqXHR.responseText || textStatus);
		Notification.show("ACPI On Lan couldn't load any icons", "Error");
		DisplayError(jqXHR, textStatus, errorThrown);
	});
	
	// return doc fragment with
	function getItems(icons) {
	  var items = '';
		$.each(icons, function( index, icon ) {
			items += getImageItem(icon);
		});
	  return items;
	}

	// return an <li> with a <img> in it
	function getImageItem(icon) {
	  var item = '<li class="is-loading">';
	  item += '<a href="#" onclick="SetIcon(\''+icon+'\'); return false;">'
	  item += '<img src="images/'+icon+'" style="width:64px;height:64px;"/>';
	  item += '</a></li>';
	  return item;
	}
	
	// triggered after each item is loaded
	function onProgress( imgLoad, image ) {
	  // change class if the image is loaded or broken
	  var $item = $( image.img ).parent().parent();
	  $item.removeClass('is-loading');
	  if ( !image.isLoaded ) {
		$item.addClass('is-broken');
	  }
	}
};

function SetIcon(icon) {
	current.icon = icon;
    CheckComputerState(current);
	$('#myModal').modal('hide');
}

$(function(){
	var buttons = $(".ACPIHaptic");
	buttons.bind("touchmove", function (event) {
		mouseMove += 1;
	});
  	buttons.bind("touchstart", function (event) {
		mouseMove = 0;
	    window.setTimeout( function() {
    		if (mouseMove < 3) Vibrate();
	    }, 400);  		
	});
	buttons.bind("touchend", function (event) {
		mouseMove = 3;
	});	

	var isMac = navigator.platform.toUpperCase().indexOf('MAC')!==-1;
	var isWindows = navigator.platform.toUpperCase().indexOf('WIN')!==-1;
	var isLinux = navigator.platform.toUpperCase().indexOf('LINUX')!==-1;
	var isAndroid = navigator.platform.toUpperCase().indexOf('ANDROID')!==-1;
	var isMacPpc = navigator.platform.toUpperCase().indexOf('MACPPC')!==-1;
	var isMacIntel = navigator.platform.toUpperCase().indexOf('MACINTEL')!==-1;
	if (!isWindows) {
		var elem = $('#installAcpiSrvc');
		elem.fadeOut();
	}
});

function Vibrate(pattern) {
	if ("vibrate" in navigator) {
		navigator.vibrate([50,50]);
	}
}

function DisplayError(jqXHR, textStatus, errorThrown) {
	lastError = jqXHR;
	var currentdate = new Date(); 
	var datetime = currentdate.getHours() + ":"  
				+ currentdate.getMinutes() + ":" 
				+ currentdate.getSeconds();
	var errorMessage = "Timestamp: " + datetime 
					+ "<br/>Status: " + textStatus 
					+ "<br/>Error: " + errorThrown
					+ "<br/><a href='javascript:ShowLastError();'>Show details</a>";
	$('#results').html(errorMessage);
	$('#results').show();
}

function ShowLastError() {
	alert(lastError.responseText);
}

function searchAndHighlight(searchTerm) {
    if (searchTerm) {
		var aTags = document.getElementsByTagName("tr");
		var found=-1;
		var firstFound=-1;
		var item;
		var previousItem;
		var match=0;

		for (var i = 0; i < aTags.length; i++) {
		  aTags[i].classList.remove('highlighted');
		  if (aTags[i].style.display != 'none' && aTags[i].textContent.toLowerCase().includes(searchTerm.toLowerCase())) {			  
			if (firstFound==-1)
				firstFound=i;
			if (match == searchIndex) {
				found = i;
			}
			match++;
		  }
		}		

		if(found==-1 && firstFound>=0) {
			found=firstFound;
			searchIndex=1;
		}

		if (found>=0) {
			item = aTags[found];

			previousItem = document.getElementById("headRow");
			var previous = 0;
			for(var i = found-1;i >= 0; i--) {
				if (aTags[i].id.startsWith("row") && aTags[i].style.display != 'none') {
					previousItem = aTags[i];
					previous++;
					if (previous > 3) break;
				}
			}
		
			item.classList.add('highlighted');
			previousItem.scrollIntoView();
            //$(window).scrollTop(found.offsetTop);
		}
    }
    return (found>0);
}