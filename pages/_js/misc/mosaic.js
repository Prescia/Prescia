// Copyright (c) 2012+, Caio Vianna de Lima Netto (www.daisuki.com.br)
// LICENSE TYPE: BSD-new
// REQUIRES prototype.js AND scriptaculous.js
/* Use:
								id for the div which contains the mosaic images (fixed width and height, position relative)
								|		 Time between image switch (ms)
								|		  |   width and height (pixel)
								|		  |	  /   \  number of columns and rows to break the image
								|         |   |   |  / \	(optional) id for preview and next div buttons (will bind onclick)
								|		  |	  |	  |  | |		/			\				(optional) id for div which navigation
								|		  |	  |	  |	 | |		|			|				|			/- hide numbers in navigation?
	myMosaic = mosaic.add('divrotativo',5000,970,345,8,5,'rotativovolta','rotativoprox','rotativonav',true);
	mosaic.addImage(myMosaic,'{IMG_PATH}layout/rotativo01.jpg');
	mosaic.addImage(myMosaic,'{IMG_PATH}layout/rotativo02.jpg');
	mosaic.addImage(myMosaic,'{IMG_PATH}layout/rotativo03.jpg');
	mosaic.addImage(myMosaic,'{IMG_PATH}layout/rotativo04.jpg');
	mosaic.addImage(myMosaic,'{IMG_PATH}layout/rotativo05.jpg');
	mosaic.setEffects(myMosaic,'shrink','tl'); // <-- optional. Effect for each cell and sequence the cells shift (see _transitionMode)
*/

AFFEffects = new Array;
AFFEffects['drop'] = function(cell){new Effect.DropOut(cell);};
AFFEffects['fade'] = function(cell){new Effect.Fade(cell);};
AFFEffects['shrink'] = function(cell){new Effect.Shrink(cell);};
AFFEffects['switchoff'] = function(cell){new Effect.SwitchOff(cell);};
AFFEffects['puff'] = function(cell){new Effect.Puff(cell);};


AFFmosaicEffect = Class.create();
AFFmosaicEffect.prototype = {
	_activeSlideClass: 'activeSlide',
	_container: null,
	_timerInterval: null,
	_objPrev: false,
	_objNext: false,
	_objNavbar: false,
	_itens: [], // each item is an array [img object, img path str]
	_cells: [[],[]], // sequential main cells
	_width: 0,
	_height: 0,
	_cols: 1,
	_rows: 1,
	_loaded: 0,
	_tickHnd: false,
	_current: 0,
	_tickCells: 0, // toggle between 0 and 1 to tell which cell is in place
	_loadingDiv: null, // the DIV which holds the loading message
	_transition: [0,0], // which cell is currently being animated
	_effect: 'drop', // AFFEffects
	_transitionMode: 'tl', // tl, tr, bl, br, l, r, random
	_celltickHnd: false,
	_randomOrder: [],
	_forceNext: false,
	_id: 0,
	_addNumber: true, // in navigation
	initialize: function(seed) {
		this._id = seed;
		this._loadingDiv = document.createElement('div');
		this._loadingDiv.id = "mosaicloader" + seed;
		this._loadingDiv.style.display = '';
		this._loadingDiv.style.position = 'relative';
	},
	bindObjects: function() {
		if (this._objPrev && this._objPrev != '') {
			$(this._objPrev).onclick = Function("evt","mosaic.click(evt,"+this._id+",'p');");
			$(this._objPrev).href = "";
			$(this._objPrev).style.cursor = 'pointer';
		}
		if (this._objNext && this._objNext != '') {
			$(this._objNext).onclick = Function("evt","mosaic.click(evt,"+this._id+",'n');");
			$(this._objNext).style.cursor = 'pointer';
			$(this._objNext).href = "";
		}
		if (this._objNavbar) {
			for (var p=0;p<this._itens.length;p++) {
				div = document.createElement('a');
				div.innerHTML = this._addNumber?(p+1):"&nbsp;";
				div.href = "#";
				div.onclick = Function("evt","mosaic.click(evt,"+this._id+","+p+");");
				div.setAttribute("id","mosaicanchor"+this._id+"_"+p);
				if (p==0) {
					div.setAttribute('class',this._activeSlideClass);
					div.className = this._activeSlideClass; // ie sux
				}

				$(this._objNavbar).appendChild(div);
			}
		}
	},
	onload: function() {
		var cellWidth = Math.ceil(this._width/this._cols);
		var cellHeight = Math.ceil(this._height/this._rows);

		if (this._timerInterval < 1250+(25*this._rows*this._cols))
			this._timerInterval = 1250+(25*this._rows*this._cols); // makes sure transitions happens after all cells animated
		// loading
		this._container.style.position = 'relative';
		this._container.style.overflow = 'hidden';
		this._container.style.width = this._width + "px";
		this._container.style.height = this._height + "px";
		this._container.style.textAlign = "left";
		this._container.style.display = 'block';
		this._loadingDiv.style.width = this._width + "px";
		this._loadingDiv.style.height = this._height + "px";
		this._loadingDiv.style.lineHeight = this._height;
		// load images
		for (var c=0;c<this._itens.length;c++)
			this._itens[c][0].src = this._itens[c][1]; // actually loads the image
		// create cells
		if (this._itens.length == 1) {
			this._container.innerHTML = "<img src='"+this._itens[0][1]+"' width='"+this._width+"' height='"+this._height+"'/>";
			return;
		}
		for (var c=0;c<2;c++) {
			for (var t=0;t<this._rows;t++) {
				this._cells[c][t] = new Array();
				for (var l=0;l<this._cols;l++) {
					this._cells[c][t][l] = document.createElement('div');
					this._cells[c][t][l].style.position = 'absolute';
					this._cells[c][t][l].style.overflow = 'hidden';
					this._cells[c][t][l].style.textAlign = "left";
					this._cells[c][t][l].style.width = cellWidth + "px";
					this._cells[c][t][l].style.height = cellHeight + "px";
					this._cells[c][t][l].style.left = (l * cellWidth) + "px";
					this._cells[c][t][l].style.top = (t * cellHeight) + "px";
					baseW = Math.floor(this._width/2 - this._itens[0][0].width/2);
					baseH = Math.floor(this._height/2 - this._itens[0][0].height/2);
					this._cells[c][t][l].style.background = "url(" + this._itens[0][1] + ") " + (-l * cellWidth + baseW) + "px " + (-t * cellHeight + baseH) + "px no-repeat";
					this._cells[c][t][l].style.display = c==0?'block':'none';
					this._cells[c][t][l].style.zIndex = 1-c;
					this._container.appendChild(this._cells[c][t][l]);
				}
			}
		}
	},
	setText: function(text) {
		if (!text || text == '') this._loadingDiv.style.display = 'none';
		else this._loadingDiv.innerHTML = text;
	},
	addImg: function(img,link) {
		var i = [document.createElement('img'),img,!link?false:link];
		i[0].onload = this.setAsLoaded.bind(this);
		this._itens.push(i);
	},
	setAsLoaded: function() {
		this._loaded++;
		if (this._loaded == this._itens.length) this.setText();
		else this.setText(Math.ceil(100*this._loaded/this._itens.length) + "%");
	},
	tick: function() {
		if (this._celltickHnd != false || this._itens.length <= 1) return false;
		var cellWidth = Math.ceil(this._width/this._cols);
		var cellHeight = Math.ceil(this._height/this._rows);

		if (this._forceNext !== false) {
			nextImg = this._forceNext;
			this._forceNext = false;
		} else {
			var nextImg = this._current+1;
			if (nextImg>=this._itens.length) nextImg = 0;
			if (nextImg == this._current) return false;
		}

		// fill image behind
		var cellBehind = this._tickCells + 1;
		if (cellBehind == 2) cellBehind = 0;

		if (this._objNavbar) {
			$("mosaicanchor"+this._id+"_"+this._current).setAttribute('class','');
			$("mosaicanchor"+this._id+"_"+this._current).className = '';
			$("mosaicanchor"+this._id+"_"+nextImg).setAttribute('class',this._activeSlideClass);
			$("mosaicanchor"+this._id+"_"+nextImg).className = this._activeSlideClass;
		}

		for (var t=0;t<this._rows;t++) {
			for (var l=0;l<this._cols;l++) {
				this._cells[this._tickCells][t][l].style.display = ''; // make sure the current image is visible
				this._cells[cellBehind][t][l].style.left = (l * cellWidth) + "px";
				this._cells[cellBehind][t][l].style.top = (t * cellHeight) + "px";
				baseW = Math.floor(this._width/2 - this._itens[nextImg][0].width/2);
				baseH = Math.floor(this._height/2 - this._itens[nextImg][0].height/2);
				this._cells[cellBehind][t][l].style.background = "url(" + this._itens[nextImg][1] + ") " + (-l * cellWidth + baseW) + "px " + (-t * cellHeight + baseH) + "px no-repeat";
				this._cells[cellBehind][t][l].style.display = 'none';
			}
		}
		this._current = nextImg;

		// start transition (custom effects go here)
		switch (this._transitionMode) {
			case 'tl': this._transition = [0,0];
			break;
			case 'tr': this._transition = [0,this._cols-1];
			break;
			case 'bl': this._transition = [this._rows-1,0];
			break;
			case 'br': this._transition = [this._rows-1,this._cols-1];
			break;
			case 'l': this._transition = [0,0];
			break;
			case 'r': this._transition = [0,this._cols-1];
			break;
			case 'random':
				this._transition = [0,0]; // will use random array
				this._randomOrder = [];
				for (var t=0;t<this._rows;t++) {
					for (var l=0;l<this._cols;l++) {
						this._randomOrder.push([t,l]);
					}
				}
				function randOrd(){
					return (Math.round(Math.random())-0.5);
				};
				this._randomOrder.sort( randOrd );
				this._transition = this._randomOrder.pop();
			break;
		}
		this.transitionStart();
	},
	transitionStart: function() {
		if (this._forceNext !== false) {
			this._celltickHnd = false;
			this.tick();
			return;
		}

		var cellBehind = this._tickCells + 1;
		if (cellBehind == 2) cellBehind = 0;

		AFFEffects[this._effect](this._cells[this._tickCells][this._transition[0]][this._transition[1]]);
		new Effect.Appear(this._cells[cellBehind][this._transition[0]][this._transition[1]]);

		hasEnded = false;
		switch (this._transitionMode) {
			case 'tl':
				this._transition[1]++;
				if (this._transition[1] == this._cols) {
					this._transition[1] = 0;
					this._transition[0]++;
					if (this._transition[0] == this._rows) {
						hasEnded = true;
					}
				}
			break;
			case 'tr':
				this._transition[1]--;
				if (this._transition[1] == -1) {
					this._transition[1] = this._cols-1;
					this._transition[0]++;
					if (this._transition[0] == this._rows) {
						hasEnded = true;
					}
				}
			break;
			case 'bl':
				this._transition[1]++;
				if (this._transition[1] == this._cols) {
					this._transition[1] = 0;
					this._transition[0]--;
					if (this._transition[0] == -1) {
						hasEnded = true;
					}
				}
			break;
			case 'br':
				this._transition[1]--;
				if (this._transition[1] == -1) {
					this._transition[1] = this._cols-1;
					this._transition[0]--;
					if (this._transition[0] == -1) {
						hasEnded = true;
					}
				}
			break;
			case 'l':
				this._transition[0]++;
				if (this._transition[0] == this._rows) {
					this._transition[0] = 0;
					this._transition[1]++;
					if (this._transition[1] == this._cols) {
						hasEnded = true;
					}
				}
			break;
			case 'r':
				this._transition[0]++;
				if (this._transition[0] == this._rows) {
					this._transition[0] = 0;
					this._transition[1]--;
					if (this._transition[1] == -1) {
						hasEnded = true;
					}
				}
			break;
			case 'random':
				if (this._randomOrder.length > 0)
					this._transition = this._randomOrder.pop();
				else
					hasEnded = true;
			break;

		}

		if (hasEnded) {
			this._celltickHnd = setTimeout(this.transitionEnd.bind(this),1075);
			return;
		} else {
			this._celltickHnd = setTimeout(this.transitionStart.bind(this),25);
		}

	},
	transitionEnd: function() {
		var cellBehind = this._tickCells + 1;
		if (cellBehind == 2) cellBehind = 0;
		for (var t=0;t<this._rows;t++) {
			for (var l=0;l<this._cols;l++) {
				this._cells[cellBehind][t][l].style.zIndex = 1;
				this._cells[this._tickCells][t][l].style.zIndex = 0;
			}
		}
		this._tickCells++;
		if (this._tickCells == 2) this._tickCells = 0;
		this._celltickHnd = false;
	},
	click: function(sl) {
		if (sl == 'p') { // previous
			this._forceNext = this._current-1;
			if (this._forceNext == -1) this._forceNext = this._itens.length-1;
			if (this._celltickHnd == false) this.tick(); // else waits current animation end to start a new
		} else if (sl == 'n') { // next
			this._forceNext = this._current+1;
			if (this._forceNext == this._itens.length) this._forceNext = 0;
			if (this._celltickHnd == false) this.tick(); // else waits current animation end to start a new
		} else { // numeric
			this._forceNext = sl;
			if (this._celltickHnd == false) this.tick(); // else waits current animation end to start a new
		}
	}

};


AFFmosaic = Class.create();
AFFmosaic.prototype = {
	_effects: new Array,
	initialize: function() {
		Event.observe(window,"load",this.onload.bind(this));
	},
	onload: function() {
		for (var c=0;c<this._effects.length;c++) {
			this._effects[c].onload();
			this._effects[c].bindObjects();
			this._effects[c]._tickHnd = setTimeout(this.tick.bind(this,c),this._effects[c]._timerInterval);
		}

	},
	tick: function(ev) {
		if (this._effects[ev]._loaded < this._effects[ev]._itens.length) {
			this._effects[ev].setText ("Loading ("+Math.ceil(100*this._effects[ev]._loaded/this._effects[ev]._itens.length)+"%)...");
			this._effects[ev]._tickHnd = setTimeout(this.tick.bind(this,ev),500);
		} else {
			this._effects[ev].tick();
			this._effects[ev]._tickHnd = setTimeout(this.tick.bind(this,ev),this._effects[ev]._timerInterval);
		}

	},
	click: function(evt,ev,sl) {
		evt = evt?evt:event;
		try {
			evt.preventDefault();
		} catch (ee) {

		}
		this._effects[ev].click(sl);
		return false;
	},
	add: function(divContainer,timerInterval,width,height,cols,rows,objPrev,objNext,objNavbar,hideNumbersInNav) {
		newEffect = new AFFmosaicEffect(this._effects.length);
		newEffect._container = $(divContainer);
		newEffect._timerInterval = timerInterval;
		newEffect._objPrev = objPrev;
		newEffect._objNext = objNext;
		newEffect._objNavbar = objNavbar;
		newEffect._width = width;
		newEffect._height = height;
		newEffect._cols = cols;
		newEffect._rows = rows;
		newEffect._addNumber = !hideNumbersInNav;
		this._effects.push(newEffect);
		return this._effects.length-1;
	},
	addImage: function(ev,img,link) { // link not implemented
		this._effects[ev].addImg(img,link);
	},
	setEffects: function(ev,eff,trans) {
		if (eff != '' && eff !== false) this._effects[ev]._effect = eff;
		if (trans && trans != '' && trans !== false) this._effects[ev]._transitionMode = trans;
	},
};
var mosaic = new AFFmosaic();