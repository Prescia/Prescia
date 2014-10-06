// requires common.js
function isMail( email ) {	
	return ereg( email,"^[A-Za-z0-9]+(([_\.\-]?[a-zA-Z0-9]+(_)?)*)@([A-Za-z0-9]+)(([\.\-]?[a-zA-Z0-9]+)*)\.([A-Za-z]{2,})$");
}
function isLogin(value,allowSpace) {
	return ereg(value,allowSpace?"^([ A-Za-z0-9_\.@\-]){4,30}$":"^([A-Za-z0-9_\.@\-]){4,30}$");
}
function isnumber( value, accept_commas ) {
	return ereg(value,accept_commas?"^(\-)?([0-9]+)(([,\.])([0-9]{3}))*(([,\.]{1})([0-9]*))?$":"^(\-)?([0-9]+)$");
}
function validhtmlpost(data) { // detects malicious/invalid HTML content. Return TRUE if you are ok
	return !ereg(data,"(<( \t\n\r/)*(form|input|button|layer|object|embed|frame|iframe|textarea|select|option|optgroup|fieldset|label|applet|!doctype|audio|video|canvas|script|style|meta|head|title|body|htmy))",'i');
}
function isDate (value, canDate, canTime, datePattern) { // supports d/m/Y and m/d/Y. Hour (second optional) should come before the date
	if (!datePattern) datePattern = "(([0-9]{1,2})([^0-9])){2}([0-9]{2,4})";
	if (canDate) {
		if (canTime)
			ok = ereg(value,"^( )*((([0-9]{1,2})([^0-9])){2,3})?"+datePattern+"( )*$"); // s is optional
		else
			ok = ereg(value,"^( )*"+datePattern+"( )*$");
	} else {
		ok = ereg(value,"^( )*(([0-9]{1,2})([^0-9])){1,2}([0-9]{2})( )*$"); // s is optional
	}
	return ok;
}
function getNumber(pv) {
	temPonto = pv.indexOf('.')>-1;
	temVirgula = pv.indexOf(',')>-1;
	if (temPonto || temVirgula) {
		pv = str_replace(",",".",pv);
		pv = pv.split(".");
		decimal = pv.pop();
		valor = '';
		for (c=0;c<pv.length;c++) {
			valor = '' + valor + pv[c];
		}
		valor += '.' + decimal;
	} else
		valor = pv;
	return parseFloat(valor);
}
function cleanString(S,modo){
  var Digits = " 0123456789" + (modo?".,-":"");
  var temp = "";
  var digito = "";
  for (var i=0; i<S.length; i++)	{
    digito = S.charAt(i);
    if (Digits.indexOf(digito) >= 0)	{
      temp=temp+digito;
    }
  }
  return temp;
}
function validaCGC(s) { // Brazil social number
  var i;
  var s = cleanString(s);
  if (s.length < 14) return false;
  var c = s.substr(0,12);
  var dv = s.substr(12,2);
  var d1 = 0;
  for (i = 0; i < 12; i++) {
    d1 += c.charAt(11-i)*(2+(i % 8));
  }
  if (d1 == 0) return false;
    d1 = 11 - (d1 % 11);
  if (d1 > 9) d1 = 0;
  if (dv.charAt(0) != d1) {
    return false;
  }
  d1 *= 2;
  for (i = 0; i < 12; i++) {
    d1 += c.charAt(11-i)*(2+((i+1) % 8));
  }
  d1 = 11 - (d1 % 11);
  if (d1 > 9) d1 = 0;
  if (dv.charAt(1) != d1) {
    return false;
  }
  return true;
}
function validaCPF(cpf) { // Brazil again
  var i;
  var s = cpf;
  if (s.length < 11) return false;
  var c = s.substr(0,9);
  var dv = s.substr(9,2);
  var d1 = 0;
  for (i = 0; i < 9; i++) {
    d1 += c.charAt(i)*(10-i);
  }
  if (d1 == 0) {
    return false;
  }
  d1 = 11 - (d1 % 11);
  if (d1 > 9) d1 = 0;
  if (dv.charAt(0) != d1) {
    return false;
  }
  d1 *= 2;
  for (i = 0; i < 9; i++) {
    d1 += c.charAt(i)*(11-i);
  }
  d1 = 11 - (d1 % 11);
  if (d1 > 9) d1 = 0;
  if (dv.charAt(1) != d1) {
    return false;
  }
  return true;
}
