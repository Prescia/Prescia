// Draggable Windows for AFF
// Uses Scriptaculos for effects and Prototype for event handler
// Not compatible with shittyexplorer 6.0


var AkrDGW = Class.create();
AkrDGW.prototype = {
	windows: null, // will turn into array on initialize, is the array of draggable windows
	Cwindowminsize: 32,
	Crestricttoparent: false,
	Cminzorder: 1, // minimal zindex
	Cdragarea: 0, // leave 0 for whole area, or set height (usefull to set only title)
	CdragareaW: 0, // leave 0 for whole area, or set width (usefull to prevent dragging when moving scrollbar)
	Ceffects: true, // use scriptaculous effects to show/hide windows
	CeffectBlind: true, // use Blind effects. If False, will use fade/appear
	dragid: 0, // which window is being dragged
	sizeid: 0, // which window is being sized
	lastselected: 0, // last window selected, to prevent zorder rearange
	offsetx: 0, // mouse offset
	offsety: 0,
	originalx: 0, // size of window being sized
	originaly: 0,
	ddEnabled: 0,
	dgwc: 1, // id for next window
	floaty: null, // window being moved/sized element
	floatycnt: null, // window content element
	nowX: 0, // initial position of a window being dragged
	nowY: 0,
	zorder: 10, // put new windows on front
	mouseposition_x: 0,
	mouseposition_y: 0,
	css_window: 'dgw_window',
	css_resizeButton: 'dgw_resize',
	css_closeButton: 'dgw_closearea',
	css_content: 'dgw_conteudo',
	css_content_nr: 'dgw_conteudo_nr',
	css_minButton: 'dgw_minarea',
	css_min_icon: 'dgw_minico',
	initialize: function() {
		this.windows = new Array();
		this.zorder = this.Cminzorder + 10;
	},
	ddInit: function (e){
	  if (this.dragid>0) { // we are DRAGGING this window ID
	    areaH = this.windows[this.dragid][2];
	    areaW = this.windows[this.dragid][6];
	    if (this.ddEnabled == 0 && (
	 	     (areaH > 0 && Event.pointerY(e)-parseInt($(this.windows[this.dragid][4]).style.top) > areaH) ||
	    	 (areaW > 0 && Event.pointerX(e)-parseInt($(this.windows[this.dragid][4]).style.left) > areaW)
	    	 )
	    	) {
	    	this.dragid = 0;
	    	this.ddInit(e); // tests sizing
	    	return;
	    }
	    if(e && e.preventDefault) e.preventDefault(); // some browsers have a default drag behaviour
	    this.floaty = $(this.windows[this.dragid][4]); // main
	    if (this.lastselected != this.dragid) {
	    	this.lastselected = this.dragid;
	    	this.movetofront();
	    }
	    this.offsetx = Event.pointerX(e);
	    this.offsety = Event.pointerY(e);
		this.nowX = parseInt(this.floaty.style.left);
		if (isNaN(this.nowX)) this.nowX = findPosX(this.floaty); // findPosX at common.js
		this.nowY = parseInt(this.floaty.style.top);
		if (isNaN(this.nowY)) this.nowY = findPosY(this.floaty); // findPosY at common.js
		this.ddEnabled = 1;
	  } else if (this.sizeid>0) { // we are SIZING this window ID
	    if (this.windows[this.sizeid][5]) return; // cannot size while minimized
	    if(e && e.preventDefault) e.preventDefault(); // Stupid FF3 have a default drag behaviour
	    this.floaty = $(this.windows[this.sizeid][4]); // main
   	    if (this.lastselected != this.sizeid) {
   	       	this.lastselected = this.sizeid;
	    	this.movetofront();
	    }
	    this.floatycnt = $('dgw_' + this.sizeid); // usefull content
	    this.offsetx= Event.pointerX(e);
		this.offsety= Event.pointerY(e);
		this.originalx = parseInt(this.floaty.style.width);
		this.originaly = parseInt(this.floaty.style.height);
		this.ddEnabled=2;
	  }
	},
	dd: function (e){
		if (this.ddEnabled == 0 || (this.dragid == 0 && this.sizeid == 0)) return;
		var posx = 0;
		var posy = 0;
		this.mouseposition_x = Event.pointerX(e);
		this.mouseposition_y = Event.pointerY(e);

		if (this.ddEnabled == 1) {
	    	objleft= (this.nowX+this.mouseposition_x-this.offsetx);
	    	objtop=(this.nowY+this.mouseposition_y-this.offsety);
	    	restrict = this.windows[this.dragid][1];
		    if (restrict) {
	    		if (objleft<0) objleft = 0;
	    		if (objtop<0) objtop = 0;
	    		maxL = parseInt(this.floaty.parentNode.style.width) - parseInt(this.floaty.style.width);
		    	if (objleft > maxL) objleft = maxL;
		    	maxT = parseInt(this.floaty.parentNode.style.height) - parseInt(this.floaty.style.height);
	    		if (objtop > maxT) objtop = maxT;
	    	}
			this.floaty.style.left = objleft + "px";
	    	this.floaty.style.top = objtop + "px";
	    	if (this.windows[this.dragid][3]) this.windows[this.dragid][3](this.floaty,this.dragid,1);
		} else {
			minsize = this.windows[this.sizeid][0];
			varX = (this.offsetx-this.mouseposition_x)+2;
		    if (varX > (this.originalx-minsize)) varX = this.originalx-minsize;
		    varY = (this.offsety-this.mouseposition_y)+2;
		    if (varY > (this.originaly-minsize)) varY = this.originaly-minsize;
		    this.floaty.style.width = (this.originalx - varX - 2) + "px";
		    this.floaty.style.height = (this.originaly - varY) + "px";
		    if (this.floatycnt) {
			    this.floatycnt.style.width = this.floaty.style.width;
			    this.floatycnt.style.height = (this.originaly - varY - 1) + "px";
			}
		    if (this.windows[this.sizeid][3]) this.windows[this.sizeid][3](this.floaty,this.sizeid,2);
		}
		return false;
	},
	fillwindow: function (id,newcontent) {
		$('dgw_'+id).innerHTML = newcontent;
	},
	makeDraggable: function (div, parameters) {
		/* Parameters:
			dragArea: [number]
				Pixels on top of div that are the draggable area (default Cdragarea)
			dragAreaW: [number]
				Pixels from left of div that are draggable area (default CdragareaW)
			restrictedToContainer: [true|false]
				If the div must be contained to the parent div (default Crestricttoparent)
			centered: [true|false]
				Moves the window to the center of the container (default false)
			canClose: [true|false]
				If the close button will be displayed (default true)
			canResize: [true|false]
				If the resize pane will be displayed (default true)
			fallback: [function]
				Function called when window changes, the function will receive 2 parameters: the DIV element, and the event
				Events: -1 = creation, 0 = close, 1 = drag, 2 = resize
			css_resizeButton: [css_class]
				CSS class to be used in the resize button (default css_resizeButton)
			css_closeButton: [css_class]
				CSS class to be used in the close button (default css_closeButton)
			useEffects: [true|false]
				Either to use or not scriptaculous effects to show/hide the window (default true)
			minSize: [number]
				Minimum size in pixels for the window (width or height, default Cwindowminsize)
			canMinimize: [true|false]
				Will convert the window for a draggable icon (specify it at iconImage) (default false)
			iconImage: [url]
				icon for minimized version. Can be dragged but not sized. Double click to open
			windowName: [name]
				title for window when dragging over icon (default none)
		*/
		var myid;
		div = $(div);
		myid = this.dgwc++;

		if (parameters.dragArea == undefined) parameters.dragArea = this.Cdragarea;
		if (parameters.dragAreaW == undefined) parameters.dragAreaW = this.CdragareaW;
		if (parameters.restrictedToContainer == undefined) parameters.restrictedToContainer = this.Crestricttoparent;
		if (parameters.centered == undefined) parameters.centered = false;
		if (parameters.canClose == undefined) parameters.canClose = true;
		if (parameters.canResize == undefined) parameters.canResize = true;
		if (parameters.css_resizeButton == undefined) parameters.css_resizeButton = this.css_resizeButton;
		if (parameters.css_closeButton == undefined) parameters.css_closeButton = this.css_closeButton;
		if (parameters.css_minButton == undefined) parameters.css_minButton = this.css_minButton;
		if (parameters.useEffects == undefined) parameters.useEffects = this.Ceffects;
		if (parameters.minSize == undefined) parameters.minSize = this.Cwindowminsize;
		if (parameters.canMinimize == undefined) parameters.canMinimize = false;
		if (parameters.css_icon == undefined) parameters.css_icon = this.css_min_icon;
		if (parameters.windowName == undefined) parameters.windowName = "";

		// makes the window floating and it's contents internal, and hides while we work on it
		div.style.display = 'none';
		div.style.position = 'absolute';
		div.style.overflow = parameters.canResize?'auto':'hidden';

		if (parameters.centered) {
			width = div.clientWidth>0?div.clientWidth:parseInt(div.style.width);
			height = div.clientHeight>0?div.clientHeight:parseInt(div.style.height);
			div.style.left = parseInt((parseInt(div.parentNode.offsetWidth) / 2) - width / 2)+"px";
			div.style.top = parseInt((parseInt(div.parentNode.offsetHeight) / 2) - height / 2)+"px";
		}

		div.onmouseover=Function("dragwindows.dragid="+myid);
		div.onmouseout=Function("if (dragwindows.ddEnabled != 1) dragwindows.dragid=0");
		// RESIZE AREA
		if (parameters.canResize) {
			var dvr = document.createElement('div');
			dvr.setAttribute('id',"dragdivcl"+myid);
			dvr.className= parameters.css_resizeButton;
			dvr.onmouseover=Function("dragwindows.sizeid="+myid);
			dvr.onmouseout=Function("if (dragwindows.ddEnabled != 2) dragwindows.sizeid=0");
			div.appendChild(dvr);
		}
		// CLOSE BUTTON
		if (parameters.canClose) {
			var dvc = document.createElement('div');
			dvc.className= parameters.css_closeButton;
			dvc.onclick=Function("dragwindows.close("+myid+")");
			div.appendChild(dvc);
		}

		// MINIMIZE BUTTON
		if (parameters.canMinimize) {
			var dvm = document.createElement('div');
			dvm.className= parameters.css_minButton;
			dvm.onclick=Function("dragwindows.minimize("+myid+")");
			div.appendChild(dvm);
			var dvicon = document.createElement('div');
			dvicon.setAttribute('id','dragdivminico'+myid);
			dvicon.className= 'dgw_minico';
			dvicon.style.display = 'none';
			dvicon.title = parameters.windowName;
			div.appendChild(dvicon);
		}
		if (parameters.useEffects) {
			if (this.CeffectBlind)
				Effect.BlindDown(div); // scriptaculous blindDown
			else
				Effect.Appear(div); // scriptaculous appear
		} else
			div.style.display = '';
		this.windows[myid] = [parameters.minSize,parameters.restrictedToContainer,parameters.dragArea,parameters.fallback,div.id,false,parameters.dragAreaW];

		if (parameters.fallback) parameters.fallback(div,-1);

		return myid;

	},
	minimize: function(id) {
		$('dragdivminico'+id).style.display='';
		this.windows[id][6] = $(this.windows[id][4]).style.width; // store old value
		this.windows[id][7] = $(this.windows[id][4]).style.height;
		this.windows[id][8] = $(this.windows[id][4]).style.background;
		$(this.windows[id][4]).style.width='16px';
		$(this.windows[id][4]).style.height='16px';
		$(this.windows[id][4]).style.background='transparent';
		this.windows[id][5] = true;
	},
	writewindow: function (left,top,width,height,parent,noresize,noclose,fallback) {
		var myid;
		myid = this.dgwc++;

/*
	Creates this DIV structire inside the parent:
	# = myid
	[_nr] if no resize

	<div id="dragdiv#" class="[css_window]">
		<div id="dgw_#" class="[css_content][_nr]"></div>
		<div class="[css_closeButton]"></div>
		<div id="dragdivcl#" class="[css_resizeButton]"></div>
	</div>

*/

		if (!left) {
			left = parseInt((parseInt($(parent).offsetWidth) / 2) - width / 2);
		}
		if (!top) {
			top = parseInt((parseInt($(parent).offsetHeight) / 2) - height / 2);
		}

		// WINDOW
		var dv = document.createElement('div');
		dv.setAttribute('id',"dragdiv"+myid);
		dv.className= this.css_window;
		dv.style.left = left+"px";
		dv.style.top = top+"px";
		dv.style.width = width+"px";
		dv.style.height = height+"px";
		dv.style.display = 'none';

		// RESIZE AREA
		if (!noresize) {
			var dvr = document.createElement('div');
			dvr.setAttribute('id',"dragdivcl"+myid);
			dvr.className= this.css_resizeButton;
			dvr.onmouseover=Function("dragwindows.sizeid="+myid);
			dvr.onmouseout=Function("if (dragwindows.ddEnabled != 2) dragwindows.sizeid=0");
		}

		// CLOSE BUTTON
		if (!noclose) {
			var dvc = document.createElement('div');
			dvc.className= this.css_closeButton;
			dvc.onclick=Function("dragwindows.close("+myid+")");
		}

		// CONTENT
		var dvu = document.createElement('div');
		dvu.setAttribute('id',"dgw_" + myid);
		dvu.className= (noresize ? this.css_content_nr : this.css_content);
		dvu.style.width = (width-1) + "px";
		dvu.style.height = (height - 1) + "px";
		dvu.onmouseover=Function("dragwindows.dragid="+myid);
		dvu.onmouseout=Function("if (dragwindows.ddEnabled != 1) dragwindows.dragid=0");

		// Adds into container
		dv.appendChild(dvu);
		if (!noclose) dv.appendChild(dvc);
		if (!noresize) dv.appendChild(dvr);

		$(parent).appendChild(dv);

		this.lastselected = myid;

    	if (this.Ceffects) {
			Effect.BlindDown('dragdiv'+myid); // scriptaculous blindDown
		} else {
			$('dragdiv'+myid).style.display = '';
		}
		this.windows[myid] = [this.Cwindowminsize,this.Crestricttoparent,this.Cdragarea,fallback,'dragdiv'+myid,false,this.CdragareaW];

    	this.movetofront();

		if (fallback) fallback($('dragdiv'+myid),myid,-1);

		return myid;
	},
	movetofront: function() {
		try {
			t = this.windows.length;
			for (c=1;c<t;c++) {
				if (c != this.lastselected && this.windows[c])
					$(this.windows[c][4]).style.zIndex = this.Cminzorder;
			}
			this.zorder++;
			$(this.windows[this.lastselected][4]).style.zIndex = this.zorder;
		} catch(e) {
			// we dont want a random zIndex error to stop everything
		}
	},
	close: function(qual) {
		if (this.Ceffects) {
			if (this.CeffectBlind)
				Effect.DropOut(this.windows[qual][4]); // scriptaculous Drop
			else
				Effect.Fade(this.windows[qual][4]); // scriptaculous Fade
		} else
			$(this.windows[qual][4]).style.display = 'none';
		if (this.windows[qual][3]) this.windows[qual][3]($(this.windows[qual][4]),qual,0);
	},
	ddDouble: function() {
		if (this.dragid>0 && this.windows[this.dragid][5]) { // is minimized
			$('dragdivminico'+this.dragid).style.display='none';
			$(this.windows[this.dragid][4]).style.width = this.windows[this.dragid][6];
			$(this.windows[this.dragid][4]).style.height = this.windows[this.dragid][7];
			$(this.windows[this.dragid][4]).style.background = this.windows[this.dragid][8];
			this.windows[this.dragid][5] = false;
		}
	},
	stop: function() {
		Event.stopObserving(document, 'mousemove');
		Event.stopObserving(document, 'mousedown');
		Event.stopObserving(document, 'mouseup');
	},
	start: function() {
		Event.observe(document, 'mousemove', dragwindows.dd.bind(this));
		Event.observe(document, 'mousedown', dragwindows.ddInit.bind(this));
		Event.observe(document, 'mouseup', function() {
			dragwindows.ddEnabled=0;} );
		Event.observe(document, 'dblclick', dragwindows.ddDouble.bind(this));
	}

}
var dragwindows = new AkrDGW();
//Event.observe(document,'load',dragwindows.start());


