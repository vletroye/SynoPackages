// ext-3 js code section
function estimateHeight() {
	var myWidth = 0, myHeight = 0;
	if( typeof( window.innerWidth ) == 'number' ) {
		//Non-IE
		myHeight = window.innerHeight;
	} else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
		//IE 6+ in 'standards compliant mode'
		myHeight = document.documentElement.clientHeight;
	} else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
		//IE 4 compatible
		myHeight = document.body.clientHeight;
	}
	return myHeight;
}
	
Ext.onReady(function() {

	var conn = new Ext.data.Connection();

	function onInfoBtnClick(item){
		Ext.Msg.alert('Info','Demo Synology package for UI authentication and other DSM-7 requirements: see conf folder with required privilege and resource files plus INFO file with mandatory label os_min_ver=7.0-x. UI is implemented via Perl and syno-cgi module incl. DSM authentication and ExtJS 3.4 shipped with Syno: https://docs.sencha.com/extjs/3.4.0.');
	}

	function onSaveBtnClick(item){
		conn.request({
			url: 'writefile.cgi',
			params: Ext.urlEncode({name: fileCmb.value, action: texta.getValue()}),
			success: function(responseObject) {
				if (responseObject.responseText=="ok\n") {
					Ext.Msg.alert('Status','Changes&nbsp;saved.');
				} else {
					Ext.Msg.alert('Status',responseObject.responseText);
				}
				saveBtn.disable();
			}
		});
	}

	function onRunBtnClick(item){
		texta.show();
		texta.setReadOnly(true);
		saveBtn.disable();
		texta.setValue('Waiting for reply of modswebconsole-cli '+cmdTxt.value+' (none could be ajax time-out)..');
		// when refresh is requested switch to bgmode; run with timeout: 150 instead default 30 mili-sec
		conn.request({
			//url: 'runcmd.cgi?'+Ext.urlEncode({cmd: 'modswebconsole-cli'})+'&'+Ext.urlEncode({action: cmdTxt.value}),
			url: 'runcmd.cgi',
			params: Ext.urlEncode({cmd: 'modswebconsole-cli', action: cmdTxt.value}),
			timeout: 150000,
			success: function(responseObject) {
			texta.setValue(responseObject.responseText);
			},
			failure: function(responseObject) {
				texta.setValue('failure code (e.g. due to time-out): ' + responseObject.responseText);
			}
		});
		// clear cmdText at login init load event
	   	if( cmdTxt.value == 'login init' ) {
			cmdTxt.setValue('');
	   	}
	}

	function onFileCmbClick(item){
	    	texta.show();
		texta.setReadOnly(true);
		saveBtn.disable();
		conn.request({
			url: 'getfile.cgi?'+Ext.urlEncode({action: fileCmb.value}),
			success: function(responseObject) {
				texta.setValue(responseObject.responseText);
			}
		});
	   	if( fileCmb.value == 'CLI-Start-Stop-Status' ) {
			texta.setReadOnly(false);
			saveBtn.enable();
	   	}
	}

	var texta = new Ext.form.TextArea ({
		hideLabel: true,
		name: 'msg',
		style: 'font-family:monospace',
		grow: false,
		preventScrollbars: false,
		anchor: '100% -53'
	});

	var fileCmb = new Ext.form.ComboBox ({
		store: [==:names:==],
		width: 170,
		name: 'file',
		shadow: true,
		editable: false,
		mode: 'local',
		triggerAction: 'all',
		emptyText: 'Choose file to show',
		selectOnFocus: true
	});

	var cmdTxt = new Ext.form.TextField ({
		width: 250,
		height: 20,
		name: 'command',
		shadow: true,
		editable: true,
		mode: 'local',
		triggerAction: 'all',
		emptyText: 'Parameter for demouispk7-cli',
		listeners: {
			specialkey: function(f,e) {
				if(e.getKey() == e.ENTER) {
					// weired need one getValue or it fails
					var txtVal=cmdTxt.getValue();
					//console.log('Key=Enter for txt-value: '+txtVal);
					Ext.get('runbtn').dom.click();
				}
			}
		}
	});

	var InfoBtn = new Ext.Toolbar.Button({
		handler: onInfoBtnClick,
		name: 'info',
		text: 'Info',
		icon: 'images/info.png',
		cls: 'x-btn-text-icon',
		disabled: false
	});

	var saveBtn = new Ext.Toolbar.Button({
		handler: onSaveBtnClick,
		name: 'save',
		text: 'Save',
		icon: 'images/save.png',
		cls: 'x-btn-text-icon',
		disabled: true
	});

	var runBtn = new Ext.Toolbar.Button({
		handler: onRunBtnClick,
		name: 'run',
		id: 'runbtn',
		text: 'Run',
		icon: 'images/run.png',
		cls: 'x-btn-text-icon',
		disabled: false
	});

	var form = new Ext.form.FormPanel({
		renderTo: 'content',
		baseCls: 'x-plain',
		url:'save-form.php',
		height: estimateHeight(),
		items: [
			new Ext.Toolbar({
				items: [
					'-',
					InfoBtn,
					'-',
					saveBtn,
					'-',
					fileCmb,
					'-',
					runBtn,
					'-',
					cmdTxt
				]
			}),
			texta
		]
	});

	Ext.EventManager.onWindowResize(function() {
		form.doLayout();
		form.setHeight(estimateHeight());
	});

	fileCmb.addListener('select',onFileCmbClick);

	// init actions when UI is loaded: click runBtn with health init
	cmdTxt.setValue('login init');
	Ext.get('runbtn').dom.click();
});