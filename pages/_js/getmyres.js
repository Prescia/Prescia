// uses an ajax call to determine the user screen resolution and set to the framework
// called from the bi_stats plugin
function createXMLHttpRequest() {
   try { return new XMLHttpRequest(); } catch(e) {}
   try { return new ActiveXObject("Msxml2.XMLHTTP"); } catch (e) {}
   alert("XMLHttpRequest not supported");
   return null;
}
var xhReq = createXMLHttpRequest();
xhReq.open("POST", "/setres.ajax?layout=2", false);
xhReq.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
xhReq.send("res=" + encodeURIComponent(screen.width + "x" + screen.height));
