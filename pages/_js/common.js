try {
	prototypeAvail = typeof Prototype!='undefined';
} catch (e) {
	prototypeAvail = false;
}
try {
	scriptaculousAvail = typeof Scriptaculous!='undefined';
} catch (e) {
	scriptaculousAvail = false;
}
// -- this block will make available: is_moz, is_webkit, is_ie, is_op, is_safari, is_firefox, is_chrome, agt_txt, is_legacy (old, might not run some scripts,specially ie)
// -- updated 2014.10.22, now detects IE 11+ too
var agt=navigator.userAgent.toLowerCase();
var agt_major = parseInt(navigator.appVersion);
var is_chrome = (agt.indexOf("chrome") != -1);
if (is_chrome) {
	verOffset = agt.indexOf("chrome");
	agt_major = parseInt(agt.substring(verOffset+7));
}
var is_moz = !is_chrome && ((agt.indexOf('mozilla')!=-1) && (agt.indexOf('spoofer')==-1) && (agt.indexOf('compatible') == -1) && (agt.indexOf('opera')==-1) && (agt.indexOf('webtv')==-1) && (agt.indexOf('hotjava')==-1));
var is_firefox =  agt.indexOf('firefox')!=-1;
if (is_firefox) {
	verOffset = agt.indexOf("firefox");
	agt_major = parseInt(agt.substring(verOffset+8));
}
var is_webkit = (agt.indexOf('webkit') != -1);
var is_ie = ((agt.indexOf("trident/") != -1) || ((agt.indexOf("msie") != -1) && (agt.indexOf("opera") == -1))); // IE 11+ too
if (is_ie) {
	try {
		MyRegExp = new RegExp("(msie |rv:)([0-9]*)"); // works for all IE versions
		r = MyRegExp.exec(agt);
		agt_major = r[2];
	} catch (ee) {
		agt_major = 5;
	}
}
var is_op = ((agt.indexOf("msie") == -1) && (document.all) && (agt.indexOf("opera") != -1)) && !is_ie;
var is_safari = (agt.indexOf("safari") != -1);
var is_legacy = (is_ie && agt_major < 9) ||
				(is_op && agt_major < 10) ||
				(is_safari && agt_major < 4) ||
				(is_webkit && agt_major < 4) ||
				(is_moz && agt_major < 5) ||
				(is_firefox && agt_major < 12) ||
				(is_chrome && agt_major < 17);
var agt_txt = is_ie?"Internet Explorer ":
			(is_chrome?"Chrome ":
				(is_safari?"Safari ":
					(is_op?"Opera ":
						(is_firefox?"Firefox ":"Unknown ")
					)
				)
		) + agt_major;
var is_mobile = agt.indexOf("mobile") != -1;
if (is_ie && agt_major < 9) { // IE8- doesn't have Array.isArray
	if (!Array.isArray) {
 		Array.isArray = function(arg) {
			return Object.prototype.toString.call(arg) === '[object Array]';
		};
	}
}

//-- following functions mimic prototype/jquery functions for when you are not sure if they are available (or need only them and don't want to add the library)
function getElement(x) { // use this instead of $ to guarantee prototype/jquery compatibility
	// prototype/jQuery compatible
  if (typeof x != "string") return x;
  if (document.getElementById) return document.getElementById(x);
    else if (document.all) return document.all[x];
    else if (document.layers) return document.layers[x];
    else return null;
}
function setOpacity(el,value) {  // prototype/jQuery compatible
	// prototype/jQuery compatible
	if (typeof el == "string") el = getElement(el);
	if (el) {
		if (!is_ie) el.style.opacity = value;
		else el.style.filter = 'alpha(opacity=' + value*100 + ')';
	}
}
//-- general functions
function str_replace(what,to,into,maxreplaces) { // prototype/jQuery compatible
	var antiloop = 0;
	if (!maxreplaces) maxreplaces = 100;
	while (into.indexOf(what)!=-1 && antiloop++<=maxreplaces) {
		into = into.substring(0,into.indexOf(what)) + to + into.substring(into.indexOf(what) + what.length);
	}
	return into;
}
function ereg(text,mask,param) { // prototype/jQuery compatible
	if (param)
		MyRegExp = new RegExp(mask,param);
	else
		MyRegExp = new RegExp(mask);
	return MyRegExp.test(text);
}
function setCookie(c_name,value,exdays) { // prototype/jQuery compatible
	var exdate=new Date();
	exdate.setDate(exdate.getDate() + exdays);
	var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
	document.cookie=c_name + "=" + c_value;
}
function getAnchor() { // prototype/jQuery compatible
	regexp = new RegExp('([^#]*)#((.*))');
	r = regexp.exec(window.location.href);
	if (r==null) return "";
	else return r[2];
}
function querySt(ji) { // prototype/jQuery compatible
	hu = window.location.search.substring(1);
	gy = hu.split("&");
	for (var i=0;i<gy.length;i++) {
		ft = gy[i].split("=");
		if (ft[0] == ji) {
			return ft[1];
		}
	}
}
function parseajax(ajaxobj,receiverDiv) { // use as callback on ajax to get scripts (prototype)
	// prototype/jQuery compatible
  try {
	  if (ajaxobj.responseText)
	  	  var dados=ajaxobj.responseText;
	  else
		  dados=ajaxobj;
	  dados = " " + dados; // <-- to diferentiate indexOf 0
	  posa = dados.indexOf("<script type=\"text/javascript\">");
	  posb = dados.indexOf("</script>");
	  if (posa>0 && posb>0) {
		js = dados.substring(posa+31,posb);
		if (js != "") {
			dados = dados.substring(1,posa) + "" + dados.substring(posb+9,dados.length);
			if (receiverDiv) receiverDiv.innerHTML = dados;
			eval(js);
			return dados;
		}
	  }
	  dados = dados.substring(1,dados.length);
	  if (receiverDiv) receiverDiv.innerHTML = dados;
	  return dados;
  } catch (ee) {
	alert("Simpla/AJAX error: " + ee);
	return ajaxobj;
  }
}
function str_count(what,where) { // prototype/jQuery compatible
	var temp = where;
	var found = 0;
	while (temp.indexOf(what) != -1) {
		found++;
		temp = temp.substring(temp.indexOf(what) + what.length);
	}
	return found;
}
function findPosX(obj) { // get the X (left) position of an element. Note these elements (and parents) should be positioned relative
	// prototype/jQuery compatible
	if (prototypeAvail) return obj.cumulativeOffset()[0];
   	var curleft = 0;
   	if(obj.offsetParent  && obj.offsetParent.nodeType == 1) {
	   	while(obj.nodeType == 1) {
	   		curleft += obj.offsetLeft;
	 		if(!obj.offsetParent)
	   	   		break;
   			obj = obj.offsetParent;
	   	}
   	} else if(obj.offsetLeft)
	   	curleft += obj.offsetLeft;
   	return curleft;
}
function findPosY (obj) { // get the Y (top) position of an element. Note these elements (and parents) should be positioned relative
	// prototype/jQuery compatible
	if (prototypeAvail) return obj.cumulativeOffset()[1];
   	var curtop = 0;
   	if(obj.offsetParent && obj.offsetParent.nodeType == 1) {
	   	while(obj.nodeType == 1) {
	   		curtop += obj.offsetTop;
	   		if(!obj.offsetParent || obj.offsetParent.nodeType != 1)
   		   		break;
	   		obj = obj.offsetParent;
	  	}
   	} else if(obj.offsetTop)
	   	curtop += obj.offsetTop;
   	return curtop;
}
function selectall(frm,para,filter) { // prototype/jQuery compatible
  for (var i=0;i<frm.elements.length;i++) {
	if ((!filter && frm.elements[i].id.substring(0,2) == "id") || (filter && frm.elements[i].id.indexOf(filter) != -1)) {
	  if (!frm.elements[i].disabled) {
		  frm.elements[i].checked = para;
		  try {
			  if (frm.elements[i].onchange) frm.elements[i].onchange();
		  } catch (ee) {

		  }
	  }
	}
  }
}
function radioValue(radioName) { // prototype/jQuery compatible
	for (var i=0;i<radioName.length;i++) {
		if (radioName[i].checked) return radioName[i].value;
	}
	return '';
}
function windowDimensions() { // prototype/jQuery compatible
	var myWidth = 0, myHeight = 0;
	if( typeof( window.innerWidth ) == 'number' ) {
		//Non-IE or IE 9+ non-quirks
		myWidth = window.innerWidth;
		myHeight = window.innerHeight;
	} else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
		//IE 6+ in 'standards compliant mode'
		myWidth = document.documentElement.clientWidth;
		myHeight = document.documentElement.clientHeight;
	} else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
		//IE 4 compatible
		myWidth = document.body.clientWidth;
		myHeight = document.body.clientHeight;
	}
	if (myWidth < 1) myWidth = screen.width; // emergency fallback
	if (myHeight < 1) myHeight = screen.height; // emergency fallback
	return [myWidth,myHeight];
}
function componentDimensions(el) { // prototype/jQuery compatible
	if (typeof el == "string") el = getElement(el);
	if (el.offsetWidth && parseInt(el.offsetWidth)>0) {
		return [parseInt(el.offsetWidth),parseInt(el.offsetHeight)];
	} else if (el.clientWidth && parseInt(el.clientWidth)>0) {
		return [parseInt(el.clientWidth),parseInt(el.clientHeight)];
	}
	return [0,0];
}
function centerWindow(el) {// prototype/jQuery compatible
	if (typeof el == "string") el = getElement(el);
	ed = componentDimensions(el);
	elParent = el.parentNode;
	wd = componentDimensions(elParent);
	el.style.position = "absolute";
	el.style.left = parseInt(wd[0]/2 - ed[0]/2) + "px";
	el.style.top = parseInt(wd[1]/2 - ed[1]/2) + "px";
}
function startAjaxSelectFill(container,module,ajaxQuery,sourceModule,theForm,addOnClick,preSelected,className,widthValue,allowEmpty) { // prototype required
	// container select for the select, ex: id_cidade (will actually change the div named [container]_ara with the select)
	// module: which module will be filled on the container, ex: cidade
	// ajaxQuery: ajax Query with keys from other fields, ex: id_estado=4&...
	// sourceModule: since fields can be named differently on each module, we must know which module the filters we are sending are related to. Leave empty to no translation
	// theForm: which form the select will be created in (and supposebly field is also in)
	// addOnClick: add onClick js (cascading modules)
	// preSelected: if set, which option should come pre-selected
	// className: if set, which class the select should belong to
	// widthValue: if set, style width for this select (put AFTER class so it will work with both)
	// allowEmpty: true|false if the first value is an empty option
	if (!prototypeAvail) {
		$(container + "_ara").innerHTML = "ERROR - prototype not detected";
		return;
	}
	if (!sourceModule) sourceModule = '';
	ajaxQuery = "ajaxQuery.php?layout=2&container=" + container + "&" + ajaxQuery + "&module=" + module + "&sourcemodule=" + sourceModule + "&aoc=" + (addOnClick?"true":"false");
	if (preSelected && preSelected != '') ajaxQuery += "&preSelected=" + preSelected;
	if (className && className != '') ajaxQuery += "&className=" + className;
	if (widthValue && widthValue != '') ajaxQuery += "&widthValue=" + widthValue;
	if (allowEmpty) ajaxQuery += "&allowEmpty=true";
	try{
		if (!CONS_JS_WAITING) CONS_JS_WAITING = "...";
	} catch(e) {
		CONS_JS_WAITING = "...";
	}
	$(container + "_ara").innerHTML = CONS_JS_WAITING; // fill this in the HTML that called startAjaxSelectFill
	new Ajax.Updater(container + "_ara", ajaxQuery, { encoding: 'UTF-8',
								  asynchronous: true,
								  onComplete: executeAjaxSelectFill
		}); // in case you are wondering, core::checkActions will capture this and send to /config/defaults/ajaxQuery.php

}
function numberFormat(n,decimals,tsep,dsep) { // prototype/jQuery compatible
	if (!tsep) tsep = '.';
	if (!dsep) dsep = ',';
	if (decimals == false|| isNaN(decimals)) decimals = 2;
	sign = n < 0 ? "-" : "";
    i = parseInt(n = Math.abs(+n || 0).toFixed(decimals)) + "";
    j = (j = i.length) > 3 ? j % 3 : 0;
    return sign + (j ? i.substr(0, j) + tsep : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + tsep) + (decimals ? dsep + Math.abs(n - i).toFixed(decimals).slice(2) : "");
}
function executeAjaxSelectFill(rawData) {
	// returns new new select object (field + '_ara')
	incommingData = parseajax(rawData);
	// content should have already been filled because this was an Updater
}
