// uses an ajax call to determine the user screen resolution and set to the framework
// called from the bi_stats plugin
function createXMLHttpRequest() {
   try { return new XMLHttpRequest(); } catch(e) {}
   try { return new ActiveXObject("Msxml2.XMLHTTP"); } catch (e) {}
   alert("XMLHttpRequest not supported");
   return null;
}
var xhReq = createXMLHttpRequest();
xhReq.open("GET", "/setres.ajax?layout=2&res=" + screen.width + "x" + screen.height, false);
xhReq.send(null);
