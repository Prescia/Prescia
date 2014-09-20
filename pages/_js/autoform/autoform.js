// Copyright (c) 2011+, Caio Vianna de Lima Netto (www.daisuki.com.br)
// Last revision: 13.1.15
// LICENSE TYPE: BSD-new
// implements the autoform javascript interface for the autoform automato
// uses validators.js
// Important: set the warningPosition to absolute or fixed. If one component cannot have it's position detected (ex.: CKE), it will look for the [name]_autoformposition element

// .warning_class { background: #440000; color: #ffffff; z-index: 500; font-weight:bold; line-height:20px;height:25px; min-width:200px; padding-left:4px; text-align:left }

var CAutoform = Class.create();
CAutoform.prototype = {
	options: null, // mandatory, translation, defaults, is_id, is_cpf, is_cnpj, integer, float, mail, date, datetime, time, login
	parent: null,
	nowarning: false,
	warningOffsetX: 0,
	warningOffsetY: 0,
	warningClass: '',
	warningTitle: 'Atenção',
	warningPosition: 'absolute',
	debugMode: true,
	classOK: '',
	classFAIL: '',
	submitCallback: false,
	datePattern: '(([0-9]{1,2})([^0-9])){2}([0-9]{2,4})',
	errorcallback: null,
	initialize: function(myParent,options,warningClass,warningHeight,warningLeft,warningPosition,warningTitle,datePattern,classOK,classFAIL,submitCallback,debugMode) {
		this.parent = myParent;
		this.parent.position = "relative";
		if (options) this.options = options;
		if (!warningClass || warningClass == '') {
			this.nowarning = true;
		} else {
			this.nowarning = false;
			if (warningClass) this.warningClass = warningClass;
			if (warningHeight && warningHeight>0) this.warningOffsetY = warningHeight;
			if (warningLeft && warningLeft>0) this.warningOffsetX = -warningLeft;
		}
		if (warningPosition && warningPosition != '') this.warningPosition = warningPosition;
		if (warningTitle) this.warningTitle = warningTitle;
		if (datePattern) this.datePattern = datePattern;
		if (classOK) this.classOK = classOK;
		if (classFAIL) this.classFAIL = classFAIL;
		if (submitCallback) {
			this.submitCallback = submitCallback;
			if (this.submitCallback.indexOf("(")==-1) this.submitCallback += "()";
		}
		if (debugMode && debugMode === true)
			this.debugMode = true;
		else if (debugMode === false)
			this.debugMode = false;
		Event.observe(window,'load',this.start.bind(this));
	},
	getElement: function(e,getParent,silent) {
		oe =e ;
		e = eval('this.parent.' + e);
		if (!e && !silent) {
			alert("Element not found: " + oe);
			return;
		}
		if (!getParent && e && e.length && e.type != 'select-one') return e[0];
		return e;
	},
	isCheckboxArray: function(e) {
		e = eval('this.parent.' + e);
		return (e.length && e.type != 'select-one');
	},
	start: function() {
		try {
			this.parent = document.getElementsByName(this.parent)[0];
			this.parent.onsubmit = this.onsubmit.bind(this);
			total = this.options.mandatory?this.options.mandatory.length:0;
			totalT = this.options.translation?this.options.translation.length:0;
			totalD = this.options.defaults?this.options.defaults.length:0;
			for(c=0;c<total;c++) {
				trad = (total == totalT)?this.options.translation[c]:this.options.mandatory[c];
				if (!this.nowarning) this.createWarningDiv(this.getElement(this.options.mandatory[c]),'af_'+this.options.mandatory[c],trad);
				if (total == totalD) this.getElement(this.options.mandatory[c]).value = this.options.defaults[c];
			}
			// add standard check onkeyup
			total = this.options.is_id?this.options.is_id.length:0;

			for(c=0;c<total;c++) {
				Event.observe(this.getElement(this.options.is_id[c]),'blur',Function("checkid(this,true,true,'"+this.classOK+"','"+this.classFAIL+"');"));
			}
			total = this.options.is_cpf?this.options.is_cpf.length:0;
			for(c=0;c<total;c++) {
				Event.observe(this.getElement(this.options.is_cpf[c]),'blur',Function("checkid(this,true,false,'"+this.classOK+"','"+this.classFAIL+"');"));
			}
			total = this.options.is_cnpj?this.options.is_cnpj.length:0;
			for(c=0;c<total;c++) {
				Event.observe(this.getElement(this.options.is_cnpj[c]),'blur',Function("checkid(this,false,true,'"+this.classOK+"','"+this.classFAIL+"');"));
			}
			total = this.options.integer?this.options.integer.length:0;
			for(c=0;c<total;c++) {
				Event.observe(this.getElement(this.options.integer[c]),'blur',Function("checknbrfield(this,false,'"+this.classOK+"','"+this.classFAIL+"');"));
			}
			total = this.options.float?this.options.float.length:0;
			for(c=0;c<total;c++) {
				Event.observe(this.getElement(this.options.float[c]),'blur',Function("checknbrfield(this,true,'"+this.classOK+"','"+this.classFAIL+"')"));
			}
			total = this.options.mail?this.options.mail.length:0;
			for(c=0;c<total;c++) {
				Event.observe(this.getElement(this.options.mail[c]),'blur',Function("checkmailfield(this,'"+this.classOK+"','"+this.classFAIL+"');"));
			}
			total = this.options.date?this.options.date.length:0;
			for(c=0;c<total;c++) {
				Event.observe(this.getElement(this.options.date[c]),'blur',Function("checkdatetime(this,true,false,'"+this.classOK+"','"+this.classFAIL+"','"+ this.datePattern+"');"));
			}
			total = this.options.autoformatdate?this.options.autoformatdate.length:0;
			for(c=0;c<total;c++) {
				Event.observe(this.getElement(this.options.autoformatdate[c]),'blur',Function("checkdatetime(this,true,false,'','','"+ this.datePattern+"');"));
			}
			total = this.options.datetime?this.options.datetime.length:0;
			for(c=0;c<total;c++) {
				Event.observe(this.getElement(this.options.datetime[c]),'blur',Function("checkdatetime(this,true,true,'"+this.classOK+"','"+this.classFAIL+"','"+ this.datePattern+"');"));
			}
			total = this.options.time?this.options.time.length:0;
			for(c=0;c<total;c++) {
				Event.observe(this.getElement(this.options.time[c]),'blur',Function("checkdatetime(this,false,true,'"+this.classOK+"','"+this.classFAIL+"','"+ this.datePattern+"');"));
			}
		} catch (ee) {
			if (this.debugMode)
				alert(ee.name + ":" + ee.message + "\n\nautoform hint: field ID not found?");
			return false;
		}
	},
	onsubmit: function() {
		return this.valideForm();
	},
	createWarningDiv: function(e,name,warning) {
		wd = document.createElement('div');
		wd.setAttribute('id',name);
		wd.innerHTML = warning;
		wd.style.position = this.warningPosition;
		wd.style.display = 'none';
		if (this.warningClass != '') wd.className = this.warningClass;
		wd.style.left = (findPosX(e) - this.warningOffsetX) + "px";
		wd.style.top = (findPosY(e) - this.warningOffsetY) + "px";
		wd.style.zIndex = 10000;
		wd.onclick = Function("this.style.display = 'none'");
		Element.setOpacity(wd, 0.8);
		Event.observe(e,'focus',Function("$('"+name+"').style.display = 'none'"));
		Event.observe(e,'click',Function("$('"+name+"').style.display = 'none'"));
		document.body.appendChild(wd);
//		this.parent.appendChild(wd);
	},
	valideForm: function() {
		try {
			if (CKEDITOR != undefined) {
			    for (var instance in CKEDITOR.instances)
			        CKEDITOR.instances[instance].updateElement();
			}
		} catch (ee) {

		}
		try {
			if (this.submitCallback && !eval(this.submitCallback)) {
				if (this.errorcallback) this.errorcallback();
				return false
			}
		} catch (ee) {
			alert(ee.name + ":" + ee.message + "\n\nautoform: error on callback");
			if (this.errorcallback) this.errorcallback();
			return false;
		}

		try { // any errors means not valid, we don't care about bad programmers
			// reposition warning tags, as form might have changed. Also disable them as default
			total = this.options.mandatory?this.options.mandatory.length:0;
			totald = this.options.defaults?this.options.defaults.length:0;
			useDefaults = total == totald;
			if (!this.nowarning) {
				for(c=0;c<total;c++) {
					e = this.getElement(this.options.mandatory[c] + "_autoformposition",false,true)?this.getElement(this.options.mandatory[c] + "_autoformposition"):this.getElement(this.options.mandatory[c]);
					position = [findPosX(e),findPosY(e)];
					wd = $('af_' + this.options.mandatory[c]);
					wd.style.display = 'none'
					if (position[0] == 0 && position[1] == 0) continue; // something is wrong, ignore (if the element is a CKEditor, this will happen, but on the autoform start, the div should be positioned properly)
					wd.style.left = (position[0] - this.warningOffsetX) + "px";
					wd.style.top = (position[1] - this.warningOffsetY) + "px";

				}
			}
			// first, check formats. If fails, empty the field
			total = this.options.is_id?this.options.is_id.length:0;
			for(c=0;c<total;c++) {
				valor = this.getElement(this.options.is_id[c]).value;
				if (!validaCGC(valor) && !validaCPF(valor)) this.getElement(this.options.is_id[c]).value = "";
			}
			total = this.options.is_cpf?this.options.is_cpf.length:0;
			for(c=0;c<total;c++) {
				valor = this.getElement(this.options.is_cpf[c]).value;
				if (!validaCPF(valor)) this.getElement(this.options.is_cpf[c]).value = "";
			}
			total = this.options.is_cnpj?this.options.is_cnpj.length:0;
			for(c=0;c<total;c++) {
				valor = this.getElement(this.options.is_cnpj[c]).value;
				if (!validaCGC(valor)) this.getElement(this.options.is_cnpj[c]).value = "";
			}
			total = this.options.integer?this.options.integer.length:0;
			for(c=0;c<total;c++) {
				valor = this.getElement(this.options.integer[c]).value;
				if (!isnumber(valor,false)) this.getElement(this.options.integer[c]).value = "";
			}
			total = this.options.float?this.options.float.length:0;
			for(c=0;c<total;c++) {
				valor = this.getElement(this.options.float[c]).value;
				if (!isnumber(valor,true)) this.getElement(this.options.float[c]).value = "";
			}
			total = this.options.mail?this.options.mail.length:0;
			for(c=0;c<total;c++) {
				valor = this.getElement(this.options.mail[c]).value;
				if (!isMail(valor)) this.getElement(this.options.mail[c]).value = "";
			}
			total = this.options.date?this.options.date.length:0;
			for(c=0;c<total;c++) {
				valor = this.getElement(this.options.date[c]).value;
				if (!isDate(valor,true,false,this.datePattern)) this.getElement(this.options.date[c]).value = "";
			}
			total = this.options.datetime?this.options.datetime.length:0;
			for(c=0;c<total;c++) {
				valor = this.getElement(this.options.datetime[c]).value;
				if (!isDate(valor,true,true,this.datePattern)) this.getElement(this.options.datetime[c]).value = "";
			}
			total = this.options.time?this.options.time.length:0;
			for(c=0;c<total;c++) {
				valor = this.getElement(this.options.time[c]).value;
				if (!isDate(valor,false,true,this.datePattern)) this.getElement(this.options.time[c]).value = "";
			}
			// now we check mandatory fields
			total = this.options.mandatory?this.options.mandatory.length:0;
			totalT = this.options.translation?this.options.translation.length:0;
			msg = "";
			for(c=0;c<total;c++) {
				isArray = this.isCheckboxArray(this.options.mandatory[c]);
				if (!isArray) {
					valor = this.getElement(this.options.mandatory[c]).value;
					if (valor == '' || (useDefaults && valor == this.options.defaults[c])) {
						if (total == totalT) msg += this.options.translation[c] + "\n";
						else msg += this.options.mandatory[c] + "\n";
						try{
							if (useDefaults) this.getElement(this.options.mandatory[c]).value = this.options.defaults[c];
						} catch(ee) {
						}
						if (!this.nowarning)
							$('af_'+this.options.mandatory[c]).style.display = '';
					}
				} else {
					e = this.getElement(this.options.mandatory[c],true);
					itotal = e.length;
					hasSelected = false;
					for(var i=0;i<itotal;i++) {
						if (e[i].checked) {
							hasSelected = true;
							break;
						}
					}
					if (!hasSelected) {
						if (total == totalT) msg += this.options.translation[c] + "\n";
						else msg += this.options.mandatory[c] + "\n";
						if (!this.nowarning)
							$('af_'+this.options.mandatory[c]).style.display = '';
					}
				}
			}
			if (this.warningTitle != '' && msg != '') {
				alert(this.warningTitle + "_____________\n" + msg + " ");
			}
			if (msg != '' && this.errorcallback) this.errorcallback();
			return msg == '';
		} catch (ee) {
			alert(ee.name + ":" + ee.message + "\n\nautoform hint: field ID not found?");
			return false;
		}
	}
}

function checkmailfield(field,classOK,classNOK) {

	if (!isMail(field.value)) {
  	    if (classNOK != '') field.className = classNOK;
    	return false;
	} else {
  		try {
  			if (classOK != '')  field.className = classOK;
		} catch (ee) {
		}
    	return true;
	}
}
function checknbrfield(field,allowdots,classOK,classNOK) {

	if (!isnumber(field.value,allowdots)) {
	    if (classNOK != '') field.className = classNOK;
    	return false;
	} else {
  		try {
  			if (classOK != '') field.className = classOK;
		} catch (ee) {
		}
    	return true;
	}
}
function checklogin(field,allowSpace,classOK,classNOK) {

	if (!isLogin(field.value,allowSpace) ) {
		if (classNOK != '') field.className = classNOK;
	    return false;
  	} else {
	  	try {
	  		if (classOK != '') field.className = classOK;
		} catch (ee) {
		}
    	return true;
  	}
}
function checkdatetime(field,canDate,canTime,classOK,classNOK,datePattern) {

	ok = isDate(field.value,canDate,canTime,datePattern);
	if (!ok) {
		if (classNOK != '') field.className = classNOK;
	    return false;
  	} else {
	  	try {
	  		if (classOK != '') field.className = classOK;
		} catch (ee) {
		}
    	return true;
  	}
}
function checkid(field,accept_cpf,accept_cnpj,classOK,classNOK) {
  field.value = cleanString(field.value,false);

  if ( (accept_cpf && validaCPF(field.value)) ||
       (accept_cnpj && validaCGC(field.value))
      ) {
	  	if (classOK != '') {
	    	try {
		    	field.className = classOK;
			} catch (ee) {
			}
	  	}
    	return true;
    return true;
  } else  {
	 if (classNOK != '') field.className = classNOK;
    return false;
  }
}
