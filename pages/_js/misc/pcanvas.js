/*
	Canvas Controler
	----------------------------------------------------
	Uses prototype.js
	By: Caio Vianna de Lima Netto @ www.daisuki.com.br
	(cc) Please keep this header when using
	Latest Revision: 14.12.9
	
	Sample Usage:
	
	//								canvas id  fps W   H  fullcreen
	//								---------	|  |   |	|	lock-rotate
	//									|		|  |   |	|	|	 /- stop on blur
	//								    |		|  |   |    |   |    |     /-- no capture (disables mouse event monitor)
	var myCC = new CCanvasControler('mycanvas',20,800,500,true,true,true,false,false); 
	//																			  \-- allow stretch (instead of centering, fill screen)
	
	//myCC.addSprite(fileurl,cellsX,cellsY) // add a sprite, that can have multiple cells (X,Y), if not, send 1,1
	//myCC._renderers.push(myRenderer); // add our renderer to the renderers list, a render function receives (CC, canvas, delay) where delay is in ms

	//myCC._mousemovehnd.push(mymmh); // add mouse move/swipe to monitoring
	//myCC._mousedownhnd.push(mymdh); // add mouse/touch down to monitoring
	//myCC._mouseuphnd.push(myMouseUpHandler); // add mouse/touch up to monitoring
	
	myCC._showFPS = true; // show/not show FPS (and other debug info)
	myCC.startPreload(plc); // start preload, call plc when complete (which will call myCC.start)
	
	function plc(pct) { // this function is called when the preloader is complete
		if (pct==1) myCC.start();
	}
	
*/

var CCanvasControler = Class.create();
var CCCanvasObject = 0;
var CCCanvasWidth = 1;
var CCCanvasHeight = 2;
var CCCanvasContext = 3;
var CCCanvasCurW = 4;
var CCCanvasCurH = 5;
var CCCanvasOriginalRatio = 6;
var CCCanvasCurrentRatio = 7;
var CCCanvasInitX = 8;
var CCCanvasInitY = 9;
var CCPmCounter = 0;
var CCPmChkSec = 1;
var CCPmDelay = 2;
var CCPmFPS = 3;
var CCPmFPSActual = 4;
var CCSfile = 0;
var CCScols = 1;
var CCSrows = 2;
var CCSimg = 3;
var CCSwidth = 4;
var CCSheight = 5;
//var CCMev

function getMouseCoordinates(e,relativeFrom) {
	if (e.touches && e.touches.length>0) {
		// this will be single touch only
		posx = e.touches[0].clientX;
		posy = e.touches[0].clientY;
	} else if (e.pageX || e.pageY) 	{
		posx = e.pageX;
		posy = e.pageY;
	}
	else if (e.clientX || e.clientY) 	{
		posx = e.clientX + document.body.scrollLeft
			+ document.documentElement.scrollLeft;
		posy = e.clientY + document.body.scrollTop
			+ document.documentElement.scrollTop;
	} else if (e.offsetX || e.offsetY) 	{
		posx = e.offsetX;
		posy = e.offsetY;
	} else
		return [0,0];
	if (relativeFrom) {
		posx -= relativeFrom.offsetLeft;
		posy -= relativeFrom.offsetTop;
	}
	return [posx,posy];
}

function DoFullScreen() {
    var isInFullScreen = (document.fullScreenElement && document.fullScreenElement !==  null) ||    // alternative standard method  
            (document.mozFullScreen || document.webkitIsFullScreen);

    var docElm = document.documentElement;
    //if (!isInFullScreen) {

        if (docElm.requestFullscreen) {
            docElm.requestFullscreen();
        }
        else if (docElm.mozRequestFullScreen) {
            docElm.mozRequestFullScreen();
        }
        else if (docElm.webkitRequestFullScreen) {
            docElm.webkitRequestFullScreen();
        } else {
			var wscript = new ActiveXObject("Wscript.shell"); // IE
			if (wscript) wscript.SendKeys("{F11}");
		}
    //}
}


CCanvasControler.prototype = {
	_canvas: null, //  [ CCCanvasObject, CCCanvasWidth, CCCanvasHeight, CCCanvasContext, curW, curH, W/H, cW/H,initX,initY ]
	_mouseev: null, // [ MouseDown, Xoffset, Yoffset, Xoffset on down, Y offset on down , isTouch, speedX, speedY]
	_perfMan: null, // [ CCPmCounter, CCPmChkSec, CCPmDelay, CCPmFPS, CCPmFPSActual ]
	_isFullScreen: false,
	_FullScreenActive: false,
	_lockRotate: false,
	_gameRotationLandscape: false,
	_allowStretch: false,
	_isRotated: false,
	_stoh: null,
	_debugOut: '', // renderer or whatever can set this to a text to be output (showFPS must be on)
	// sprite control
	_sprites: null,
	_autosprites: null,
	_sploaded: 0,
	_spcallback: false, /* function to callback during preload) */
	// handlers
	_renderers: null,
	_mousemovehnd: null, // includes swipe
	_mouseuphnd: null, // includes touch end
	_mousedownhnd: null, // includes touch start
	_notifyViewportUpdate: null, // if the viewport changes (and at init)
	// working variables
	_playing: false,
	_paused: false,
	_error: false,
	_showFPS: false,
	_preloadComplete: false,
	_fillStyle : 'black', // default bg
	_fontColor : 'white', // debug, loading
	initialize: function(canvasID, fps,width,height,fullScreen,lockrotate,stopOnBlur,noCapture,allowStretch) { /* public constructor */
		/*
		 * canvasID: HTML DOM id for the canvas element
		 * fps: desired FPS
		 * width: Render width (fixed, the system zooms/stretches as needed if fullScreen)
		 * height
		 * fullSCreen: true will fill the screen, preserving aspect (might create letterbox)
		 * lockrotate: true will automatically rotate the canvas if viewport changes from landscape to portrait (which means will preserve orientation on a mobile)
		 * stopOnBlur: if the window looses focus, pauses animation. Clicking on the canvas again unpauses
		 * noCapture: if true, will not track mouse events
		 * allowStretch: fullScreen will allow composing outside the letterbox (true 0,0), false will CENTER the canvas (0,0 is relative)
		 */
		this._canvas = [null,null,null,null,null,0,0,0,0];
		this._mouseev = [false,0,0,0,0, false,0,0];
		this._perfMan = [0,0,0,28,0];
		this._sprites = [];
		this._autosprites = [];
		this._renderers = [];
		this._mousemovehnd= [];
		this._mouseuphnd= [];
		this._mousedownhnd= [];
		this._notifyViewportUpdate = [];
		this._allowStretch = allowStretch;
	
		if (!fps || parseInt(fps) < 5) fps = 5;
		this._canvas = [$(canvasID),0,0,null,0];
		this._canvas[CCCanvasContext] = this._canvas[CCCanvasObject].getContext('2d');
		this._canvas[CCCanvasContext].fillStyle = this._fillStyle;
		this._mouseev = [false,0,0];
		this._perfMan = [0,0,Math.floor(1000/fps),fps,0];
		this._canvas[CCCanvasObject].focus();
		if (!noCapture) {
			Event.observe(this._canvas[CCCanvasObject],'mousedown',this.mousedown.bind(this,false));
			Event.observe(this._canvas[CCCanvasObject],'mouseup',this.mouseup.bind(this)); 
			Event.observe(this._canvas[CCCanvasObject],'mouseout',this.mouseup.bind(this)); 
			Event.observe(this._canvas[CCCanvasObject],'mousemove',this.mousemove.bind(this)); 
			Event.observe(this._canvas[CCCanvasObject],'touchstart',this.mousedown.bind(this,true));
			Event.observe(this._canvas[CCCanvasObject],'touchmove',this.mousemove.bind(this));
			Event.observe(this._canvas[CCCanvasObject],'touchend',this.mouseup.bind(this));
			Event.observe(this._canvas[CCCanvasObject],'touchcancel',this.mouseup.bind(this));
		} else if (stopOnBlur)
			Event.observe(this._canvas[CCCanvasObject],'mousedown',this.mousedown.bind(this,false));
		if (stopOnBlur) {
			Event.observe(this._canvas[CCCanvasObject],'onblur',this.stop.bind(this)); // to capture focus
			window.onblur = this.stop.bind(this);
		}
		
		this._isFullScreen = fullScreen;
		this._lockRotate = lockrotate;
		this._canvas[CCCanvasWidth] = width;
		this._canvas[CCCanvasCurW] = width;
		this._canvas[CCCanvasHeight] = height;
		this._canvas[CCCanvasCurH] = height;
		this._canvas[CCCanvasOriginalRatio] = width/height;
		this._canvas[CCCanvasObject].style.width = width + "px";
		this._canvas[CCCanvasObject].style.height = width + "px";
		this._canvas[CCCanvasObject].setAttribute('width',width);
		this._canvas[CCCanvasObject].setAttribute('height',height);
		this._canvas[CCCanvasContext].width = width;
		this._canvas[CCCanvasContext].heigth = height;
		this._canvas[CCCanvasContext].save();
		this._gameRotationLandscape = width>height;
		

	},
	isLandscape: function() {
		return this._canvas[CCCanvasCurW]>this._canvas[CCCanvasCurH];
	},
	getOffset: function(size) { // from original ratio TO current ratio. Use 1/this to get current TO original (ex. detect where mouse is) 
		return this._canvas[CCCanvasCurrentRatio]>this._canvas[CCCanvasOriginalRatio]?
			size*this._canvas[CCCanvasCurH]/this._canvas[CCCanvasHeight]: // W greater, crop by HEIGHT (landscape)
			size*this._canvas[CCCanvasCurW]/this._canvas[CCCanvasWidth]; // H greater, crop by WIDTH (portrait)
	},
	resetMouseEvents: function() { // the speed of the mouse is collected and stored until consumed by the renderer, which MUST call this function when it uses the actual value
		this._mouseev[6] = 0;
		this._mouseev[7] = 0;
	},
	getDistance: function(x1,y1,x2,y2) { // Phytagoras anyone?
		var dX = x1-x2;
		var dY = y1-y2;
		return Math.sqrt(dX*dX+dY*dY);
	},
	mousedown: function(istouch,e) { /* private */
		// mhev: [ MouseDown, Xoffset, Yoffset, Xoffset on down, Y offset on down , isTouch, speedX, speedY]
		/*if (this._isFullScreen && !this._FullScreenActive) {
			DoFullScreen();
			this._FullScreenActive = true;
		}*/
		var ev = e||event;
		epos = getMouseCoordinates(ev,this._canvas[CCCanvasObject]);
		this._mouseev[0] = true;
		this._mouseev[5] = istouch && istouch === true?true:false;
		
		if (this._isRotated) { // coordinates are inverted
			this._mouseev[1] = Math.floor((epos[1]-this._canvas[CCCanvasInitX])/this.getOffset(1));
			this._mouseev[2] = Math.floor((this._canvas[CCCanvasCurH]-epos[0]-this._canvas[CCCanvasInitY])/this.getOffset(1));
			
		} else {
			this._mouseev[1] = Math.floor((epos[0]-this._canvas[CCCanvasInitX])/this.getOffset(1));
			this._mouseev[2] = Math.floor((epos[1]-this._canvas[CCCanvasInitY])/this.getOffset(1));
		}
		this._mouseev[3] = this._mouseev[1];
		this._mouseev[4] = this._mouseev[2];
		this._mouseev[6] = 0;
		this._mouseev[7] = 0;
		// handlers
		for (var c=0;c<this._mousedownhnd.length;c++)
			this._mousedownhnd[c](this._mouseev);
		
		// causes a mousemove (important when in touch)
		this.mousemove(e);
		
		// stop browser default
		ev.preventDefault(); 
		if (this._paused) {
			this.start();
		}
	},
	mousemove: function(e) { /* private */
		var ev = e||event;
		epos = getMouseCoordinates(ev,this._canvas[CCCanvasObject]);
		lastPosX = this._mouseev[1];
		lastPosY = this._mouseev[2];
		if (this._isRotated) { // coordinates are inverted
			this._mouseev[1] = Math.floor((epos[1]-this._canvas[CCCanvasInitX])/this.getOffset(1));
			this._mouseev[2] = Math.floor((this._canvas[CCCanvasCurH]-epos[0]-this._canvas[CCCanvasInitY])/this.getOffset(1));
		} else {
			this._mouseev[1] = Math.floor((epos[0]-this._canvas[CCCanvasInitX])/this.getOffset(1));
			this._mouseev[2] = Math.floor((epos[1]-this._canvas[CCCanvasInitY])/this.getOffset(1));
		}
		if (this._mouseev[0]) {
			this._mouseev[6] += this._mouseev[1] - lastPosX; // add speedX, renderer will use only when ready (alas, on tick) and reset this calling resetMouseEvents
			this._mouseev[7] += this._mouseev[2] - lastPosY; 
		}
		
		// handlers
		for (var c=0;c<this._mousemovehnd.length;c++)
			this._mousemovehnd[c](this._mouseev); 
		ev.preventDefault();
	},
	mouseup: function(e) { /* private */
		var ev = e||event;
		if (this._mouseev[0]) {
			this._mouseev[0] = false;
			if (this._isRotated) { // coordinates are inverted
				deltaX = this._mouseev[1]-this._mouseev[3];
				deltaY = this._mouseev[2]-this._mouseev[4];
			} else {
				deltaX = this._mouseev[1]-this._mouseev[3];
				deltaY = this._mouseev[2]-this._mouseev[4];
			}
			
			// handlers
			for (var c=0;c<this._mouseuphnd.length;c++)
				this._mouseuphnd[c](this._mouseev,deltaX,deltaY);
				
			// reset movement
			this._mouseev[6] = 0;
			this._mouseev[7] = 0;
		}
	},
	start: function() { /* public */
		this.viewportUpdate();
		this._canvas[CCCanvasObject].focus();
		this._paused = false;
		if (this._stoh == null) {
			this._stoh = setTimeout(this.tick.bind(this),this._perfMan[CCPmDelay]*2);
		}
		this._playing = true;
	},
	stop: function() { /* public */
		if (!this._playing || this._paused !== false) return;
		this._stoh = null;
		this._playing = false;
		this._paused = 2;
	},
	tick: function() { /* public */
		if (this._paused == true) return;
		// start performanceManager (tries to keep a smooth FPS)
		this._perfMan[CCPmCounter]++
		var d = new Date();
		if (d.getSeconds() != this._perfMan[CCPmChkSec]) { // this will guarantee the game pace is the same in all plataforms (as long as the CPU can handle it)
			this._perfMan[CCPmChkSec] = d.getSeconds();
			this._perfMan[CCPmFPSActual] = this._perfMan[CCPmCounter]; // <-- FPS
			if (this._perfMan[CCPmCounter]<this._perfMan[CCPmFPS]*0.9 && this._perfMan[CCPmDelay]>1 ) // too low, increase FPS
				this._perfMan[CCPmDelay]--;
			if (this._perfMan[CCPmCounter]>this._perfMan[CCPmFPS]*1.1) // too fast, decrease FPS
				this._perfMan[CCPmDelay]++;
			this._perfMan[CCPmCounter] = 0;
			if (this._isFullScreen) this.viewportUpdate();
		}
		// call renderers
		this.render();
		if (this._showFPS) {
			this._canvas[CCCanvasContext].fillStyle = this._fontColor;
			this._canvas[CCCanvasContext].font = Math.floor(this.getOffset(10)) + 'px sans-serif';
			this._canvas[CCCanvasContext].fillText("FPS: " + this._perfMan[CCPmFPSActual]+" ("+this._perfMan[CCPmDelay]+"ms)" + (this._error?"e":"") + (this._paused == 2?" PAUSED":""),1,this.getOffset(12));
			this._canvas[CCCanvasContext].fillText("curW,curH (offset(1)): " + this._canvas[CCCanvasCurW] + "," +  this._canvas[CCCanvasCurH] + " ("+this.getOffset(1)+") " + (this.isLandscape()?"landscape":"portrait"),1,this.getOffset(24));
			this._canvas[CCCanvasContext].fillText("initX, initY: " + this._canvas[CCCanvasInitX] + "," +  this._canvas[CCCanvasInitY] + " " +(this._canvas[CCCanvasCurrentRatio]>this._canvas[CCCanvasOriginalRatio] ? "cbH (landscape)" : "cbW (portrait)"),1,this.getOffset(36));
			this._canvas[CCCanvasContext].fillText("I/O mouseev[1], mouseeev[2]: " +(this._mouseev[0]?"moving" +(this._mouseev[5]?" (touch)":" (mouse)") + this._mouseev[1] + "," + this._mouseev[2] :"idle"),1,this.getOffset(48));
			if (this._debugOut != '') this._canvas[CCCanvasContext].fillText(this._debugOut,1,this.getOffset(60));
			if (!this._allowStretch) {
				this._canvas[CCCanvasContext].strokeStyle = "#cccccc";
				this._canvas[CCCanvasContext].globalAlpha = 0.33;
				this._canvas[CCCanvasContext].beginPath();
				this._canvas[CCCanvasContext].rect(this._canvas[CCCanvasInitX],this._canvas[CCCanvasInitY],this.getOffset(this._canvas[CCCanvasWidth]),this.getOffset(this._canvas[CCCanvasHeight]));
				this._canvas[CCCanvasContext].stroke();
				this._canvas[CCCanvasContext].closePath()	
			}
			
		}		
		
		if (this._paused == 2) this._paused = true;
		else this._stoh = setTimeout(this.tick.bind(this),this._perfMan[CCPmDelay]);
		

	},
	fillText: function(text,x,y,fontFace,fontColor,fontSize) {
		this._canvas[CCCanvasContext].fillStyle = fontColor;
		this._canvas[CCCanvasContext].font =  Math.ceil(this.getOffset(fontSize)) + "px " + fontFace;
		this._canvas[CCCanvasContext].fillText(text,this.getTrueX(x),this.getTrueY(y));
		
	},
	viewportUpdate: function() { /* if 100% viewport, this will check it each second */
		vp = document.viewport.getDimensions();
		dims = [vp.width,vp.height];
		if (this._canvas[CCCanvasCurW] == dims[0] && this._canvas[CCCanvasCurH] == dims[1]) {
			return;
		}
		this._canvas[CCCanvasContext].restore();
		this._canvas[CCCanvasCurW] = dims[0];
		this._canvas[CCCanvasCurH] = dims[1];
		this._canvas[CCCanvasObject].style.width = dims[0] + "px";
		this._canvas[CCCanvasObject].style.height = dims[1] + "px";
		this._canvas[CCCanvasContext].width = dims[0];
		this._canvas[CCCanvasContext].height = dims[1];
		this._canvas[CCCanvasObject].setAttribute('width',dims[0]);
		this._canvas[CCCanvasObject].setAttribute('height',dims[1]);
		this._isRotated = this._lockRotate && this.isLandscape() != this._gameRotationLandscape;
		this._canvas[CCCanvasCurrentRatio] = this._isRotated?dims[1]/dims[0]:dims[0]/dims[1];
		if (this._isRotated) {
			this._canvas[CCCanvasCurW] = dims[1];
			this._canvas[CCCanvasCurH] = dims[0];
		}
		if (this._allowStretch) {
			this._canvas[CCCanvasInitY] = 0;
			this._canvas[CCCanvasInitX] = 0;
		} else {
			if (this._canvas[CCCanvasCurrentRatio]>this._canvas[CCCanvasOriginalRatio]) { // cropped by HEIGHT
				this._canvas[CCCanvasInitY] = 0;
				this._canvas[CCCanvasInitX] = (this._canvas[CCCanvasCurW] - this._canvas[CCCanvasWidth]*this._canvas[CCCanvasCurH]/this._canvas[CCCanvasHeight])/2;
			} else {
				this._canvas[CCCanvasInitX] = 0;
				this._canvas[CCCanvasInitY] = (this._canvas[CCCanvasCurH] - this._canvas[CCCanvasHeight]*this._canvas[CCCanvasCurW]/this._canvas[CCCanvasWidth])/2;
			}
		}
		this._canvas[CCCanvasContext].save();
		for (var c=0;c<this._notifyViewportUpdate.length;c++)
			this._notifyViewportUpdate[c](dims[0],dims[1],this._isRotated);
	},
	render: function() { /* public */
		this._canvas[CCCanvasContext].restore();
		this._canvas[CCCanvasContext].save();
		if (this._isRotated ) {
			this._canvas[CCCanvasContext].rotate(Math.PI/2);
			this._canvas[CCCanvasContext].translate(0,-this._canvas[CCCanvasCurH]);
			this._canvas[CCCanvasContext].save();
		}
		this._canvas[CCCanvasContext].fillStyle = this._fillStyle;
		this._canvas[CCCanvasContext].fillRect(0,0,this._canvas[CCCanvasCurW],this._canvas[CCCanvasCurH]);
		
		// handlers
		for (var c=0;c<this._renderers.length;c++)
			this._renderers[c](this,this._canvas[CCCanvasContext],this._perfMan[CCPmDelay]); // sends the canvas object and how long it took since the last render.
			
			
		if (this._isRotated ) {
			this._canvas[CCCanvasContext].restore();
		} 
	},
	getTrueX: function(X) {
		return this._canvas[CCCanvasInitX]+this.getOffset(X);
	},
	getTrueY: function(Y) {
		return this._canvas[CCCanvasInitY]+this.getOffset(Y);
	},
	drawSprite: function(sprite,whereX,whereY,targetWidth,rotation,alpha,cellX,cellY,flipW) { // which sprite, where X, where Y, targetW (H auto calc), rotation, alpha, cell X, cell Y, flip Image
		// where is regarding the CENTER of the image
		if (!alpha) alpha = 1;
		if (!rotation) rotation = 0;
		if (!targetWidth) targetWidth = 1;
		if (!cellX) cellX = 0;
		if (!cellY) cellY = 0;
		if (this._sprites[sprite][CCSwidth]==0) {
			this._error = true;
			return;
		}
		
		this._canvas[CCCanvasContext].save(); // save current context
		
		var cellWidth = this._sprites[sprite][CCSwidth]/this._sprites[sprite][CCScols];
		var cellHeight = this._sprites[sprite][CCSheight]/this._sprites[sprite][CCSrows];
		var cellStartX = cellX*cellWidth;
		var cellStartY = cellY*cellHeight;
		whereX = this.getOffset(whereX);
		whereY = this.getOffset(whereY);
		outputWidth = this.getOffset(targetWidth);
		ratio = cellWidth/cellHeight;
		outputHeight = this.getOffset(targetWidth/ratio);
		
		whereX -= outputWidth/2;
		whereY -= outputHeight/2;
				
		this._canvas[CCCanvasContext].globalAlpha = alpha;
		maxW = this._canvas[CCCanvasCurW]-this._canvas[CCCanvasInitX]; // canvas X bounds (right)
		maxH = this._canvas[CCCanvasCurH]-this._canvas[CCCanvasInitY]; // canvas Y bounds (bottom)	
		if (rotation != 0) { // if it is rotated, use translate/rotate
			// set clip region			
			this._canvas[CCCanvasContext].beginPath();
			this._canvas[CCCanvasContext].moveTo(this._canvas[CCCanvasInitX],this._canvas[CCCanvasInitY]);
			this._canvas[CCCanvasContext].lineTo(this._canvas[CCCanvasInitX],maxH);
			this._canvas[CCCanvasContext].lineTo(maxW,maxH);
			this._canvas[CCCanvasContext].lineTo(maxW,this._canvas[CCCanvasInitY]);
			this._canvas[CCCanvasContext].clip();
			// draw
			this._canvas[CCCanvasContext].translate(this._canvas[CCCanvasInitX]+whereX+(outputWidth/2),this._canvas[CCCanvasInitY]+whereY+outputHeight/2);
			this._canvas[CCCanvasContext].rotate(rotation);
			
			this._canvas[CCCanvasContext].drawImage(this._sprites[sprite][CCSimg],cellStartX,cellStartY,cellWidth,cellHeight,-outputWidth/2,-outputHeight/2,outputWidth,outputHeight);
			
		} else { // if not rotated, we can simply echo it
			// we can manually clip the image if it's not rotated (faster)!
			XBounds = this._canvas[CCCanvasInitX]+whereX + outputWidth; // sprite X bounds (right)
			YBounds = this._canvas[CCCanvasInitY]+whereY + outputHeight; // sprite Y bounds (bottom)
			if (XBounds>maxW || YBounds>maxH || whereX<0 || whereY<0) {
				// set clip region			
				this._canvas[CCCanvasContext].beginPath();
				this._canvas[CCCanvasContext].moveTo(this._canvas[CCCanvasInitX],this._canvas[CCCanvasInitY]);
				this._canvas[CCCanvasContext].lineTo(this._canvas[CCCanvasInitX],maxH);
				this._canvas[CCCanvasContext].lineTo(maxW,maxH);
				this._canvas[CCCanvasContext].lineTo(maxW,this._canvas[CCCanvasInitY]);
				this._canvas[CCCanvasContext].clip();
			}
			if (flipW) {
				this._canvas[CCCanvasContext].scale(-1,1);
				this._canvas[CCCanvasContext].drawImage(this._sprites[sprite][CCSimg],cellStartX,cellStartY,cellWidth,cellHeight,-this._canvas[CCCanvasInitX]-whereX-outputWidth,this._canvas[CCCanvasInitY]+whereY,outputWidth,outputHeight);
			} else {
				this._canvas[CCCanvasContext].drawImage(this._sprites[sprite][CCSimg],cellStartX,cellStartY,cellWidth,cellHeight,this._canvas[CCCanvasInitX]+whereX,this._canvas[CCCanvasInitY]+whereY,outputWidth,outputHeight);
			}
			
		}
		
		this._canvas[CCCanvasContext].restore(); // restore context before this call
	},
	draw: function(isSquare,whereX,whereY,width,height,rotation,alpha,strike,fill) { /* public, if not square is a circle (position is center) */
		// where is regarding the CENTER of the image
		this._canvas[CCCanvasContext].save(); // save context before this call
		
		if (strike) this._canvas[CCCanvasContext].strokeStyle = strike;
		if (fill) this._canvas[CCCanvasContext].fillStyle = fill;
		this._canvas[CCCanvasContext].globalAlpha = alpha;
		whereX = this.getOffset(whereX);
		whereY = this.getOffset(whereY);
		width = this.getOffset(width);
		height = this.getOffset(height);
		maxW = this._canvas[CCCanvasCurW]-this._canvas[CCCanvasInitX]; // canvas X bounds (right)
		maxH = this._canvas[CCCanvasCurH]-this._canvas[CCCanvasInitY]; // canvas Y bounds (bottom)
		XBounds = this._canvas[CCCanvasInitX]+whereX + outputWidth; // sprite X bounds (right)
		YBounds = this._canvas[CCCanvasInitY]+whereY + outputHeight; // sprite Y bounds (bottom)
		if (XBounds>maxW || YBounds>maxH || whereX<0 || whereY<0) {
			// set clip region			
			this._canvas[CCCanvasContext].beginPath();
			this._canvas[CCCanvasContext].moveTo(this._canvas[CCCanvasInitX],this._canvas[CCCanvasInitY]);
			this._canvas[CCCanvasContext].lineTo(this._canvas[CCCanvasInitX],maxH);
			this._canvas[CCCanvasContext].lineTo(maxW,maxH);
			this._canvas[CCCanvasContext].lineTo(maxW,this._canvas[CCCanvasInitY]);
			this._canvas[CCCanvasContext].clip();
		}
		
		this._canvas[CCCanvasContext].beginPath();

		radius = width/2;
		if (isSquare)
			this._canvas[CCCanvasContext].rect(whereX-radius,whereY-(height/2),width,height);
		else {
			this._canvas[CCCanvasContext].arc(whereX,whereY, radius, 0, 2*Math.PI);	
		}
		if (fill) this._canvas[CCCanvasContext].fill();
		if (strike) this._canvas[CCCanvasContext].stroke();
		this._canvas[CCCanvasContext].closePath();
		
		this._canvas[CCCanvasContext].restore(); // restore context before this call
	},
	addSprite: function(file,cellsCols,cellsRows) { /* public */
		if (!cellsCols || cellsCols<1) cellsCols = 1;
		if (!cellsRows || cellsRows<1) cellsRows = 1;
		nextSprite = this._sprites.length;
		this._sprites[nextSprite] = [file,cellsCols,cellsRows,new Image(),0,0]; // file str, cells cols, cells rows, image, image width, image height
		this._sprites[nextSprite][CCSimg].onload = this.spriteLoaded.bind(this);
		return nextSprite;
	},
	startPreload: function(callbackf) { /* public */
		this._spcallback = callbackf;
		for (var c=0;c<this._sprites.length;c++)
			this._sprites[c][CCSimg].src = this._sprites[c][CCSfile];
	},
	spriteLoaded: function() { /* private */
		this._sploaded++;
		size = this._sprites.length;
		if (this._sploaded == size) {
			for(var s=0;s<size;s++) {
				var tmp = new Image(); // some browsers will not properly fill width/height in the image object WHILE it's pre-loaded
				tmp.src = this._sprites[s][CCSfile]; // since we know it's now loaded, this WILL fill width/heigth
				this._sprites[s][CCSwidth] = 0+tmp.width;
				this._sprites[s][CCSheight] = 0+tmp.height;
				if (tmp.width == 0 || tmp.height == 0) { // error on download, file does not exist, etc TODO: raise error
					this._sploaded--; // try again
					this._sprites[c][CCSimg] = new Image();
					this._sprites[c][CCSimg].src = this._sprites[c][CCSfile];
				} 
				var tmp = null;
			}
			if (this._sploaded == size) this._preloadComplete = true;
		}
		if (!this._preloadComplete) {
			// draw preload
			this._canvas[CCCanvasContext].restore();
			this._canvas[CCCanvasContext].save();
			if (this._isRotated ) {
				this._canvas[CCCanvasContext].rotate(Math.PI/2);
				this._canvas[CCCanvasContext].translate(0,-this._canvas[CCCanvasCurH]);
				this._canvas[CCCanvasContext].save();
			}
			this._canvas[CCCanvasContext].fillStyle = this._fillStyle;
			this._canvas[CCCanvasContext].fillRect(0,0,this._canvas[CCCanvasCurW],this._canvas[CCCanvasCurH]);
			if (this._isRotated ) {
				this._canvas[CCCanvasContext].restore();
			} 
			this._canvas[CCCanvasContext].fillStyle = this._fontColor;
			this._canvas[CCCanvasContext].font = Math.floor(this.getOffset(10)) + 'px sans-serif';
			this._canvas[CCCanvasContext].fillText("Loading: " + Math.ceil(this._sploaded*100/size) + "%",1,this.getOffset(12));
		}
		this._spcallback(this._sploaded/size);
	},
	cosmove: function(percent,half) { /* public */
		/* send a percentage of a position, it will send you a smooth approach using cos, or a smooth acceleration (half cos) */
		if (percent<0) percent = 0;
		if (percent>1) percent = 1;
		if (half) percent /=2;
		a = Math.PI * percent;
		return -(Math.cos(a)-1)/2;
	},
};