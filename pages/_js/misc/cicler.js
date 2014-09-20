// Copyright (c) 2012+, Caio Vianna de Lima Netto (www.daisuki.com.br)
// LICENSE TYPE: BSD-new
// A SIMPLE ELEMENT CICLER USING PROTOTYPE/SCRIPTACULOUS
// USE: cicler.add([container div],[effect],[timeout interval],[prev btn obj],[next btn obj],[navbar container],[forceMove width/height],[donotposition],[title container])
// effects: see _knownEffects list
// buttons, nav and forceMov are optional
// forceMove will specify the distance to move per circle (instead of the size of the first item)
// if timeout interval is 0, will not cicle, but rather wait user input on the buttons or navbar
// NOTE: the cicler element MUST be absolute or relative positioned.

// REQUIRES prototype.js AND scriptaculous.js

AFFcicler = Class.create();
AFFcicler.prototype = {
	_minZindex: 1,
	_effects: new Array,
	_autoposition: true,
	_activeSlideClass: 'activeSlide',
	_knownEffects: ['fade','drop','shrink','left','right','up','down','random'],
	initialize: function() {
		try {
			this._effects[i][0].style.overflow = 'hidden'; // as soon as possible
		} catch (e) {
			// nop
		}
		Event.observe(window,"load",this.onload.bind(this));
	},
	onload: function() {
		for (var i=0;i<this._effects.length;i++) {
			this._effects[i][0].style.overflow = 'hidden';
			if (this._effects[i][3]) {
				$(this._effects[i][3]).onclick = Function("evt","cicler.click(evt,"+i+",'p');");
				$(this._effects[i][3]).href = "";
				$(this._effects[i][3]).style.cursor = 'pointer';
			}
			if (this._effects[i][4]) {
				$(this._effects[i][4]).onclick = Function("evt","cicler.click(evt,"+i+",'n');");
				$(this._effects[i][4]).style.cursor = 'pointer';
				$(this._effects[i][4]).href = "";
			}
			if (this._effects[i][5] && $(this._effects[i][5])) {
				for (var p=0;p<this._effects[i][6].length;p++) {
					div = document.createElement('a');
					div.innerHTML = (p+1);
					div.href = "#";
					div.onclick = Function("evt","cicler.click(evt,"+i+","+p+");");
					div.setAttribute("id","cicleranchor"+i+"_"+p);
					if (p==0) {
						div.setAttribute('class',this._activeSlideClass);
						div.className = this._activeSlideClass; // ie sux
					}
					
					$(this._effects[i][5]).appendChild(div);
				}
			}
			if (this._effects[i][6].length > 1 && this._effects[i][2]>0)
					this._effects[i][10] = setTimeout(this.tick.bind(this,i),this._effects[i][2]);
			this.fixPositions(i);
		}
		
	},
	tick: function(ev) {
		if (this._effects[ev][1] == 'random') {
			r = Math.floor((Math.random()*(this._knownEffects.length-1)));
			this._effects[ev][1] = this._knownEffects[r];
			isRandom = true;
		} else
			isRandom = false;
		if (this._effects[ev][6].length > 1) {
			current = this._effects[ev][9];
			nextObj = current+(this._effects[ev][1]=='right' || this._effects[ev][1] == 'down'?-1:1);
			if (nextObj >= this._effects[ev][6].length) nextObj = 0;
			if (nextObj < 0) nextObj = this._effects[ev][6].length-1;
			this.fixPositions(ev);
			/*
			$('debug').innerHTML = "CURRENT: "+current+" ("+this._effects[ev][6][current][1]+","+this._effects[ev][6][current][2]+")<br/>";
			$('debug').innerHTML += "NEXT: "+nextObj+" ("+this._effects[ev][6][nextObj][1]+","+this._effects[ev][6][nextObj][2]+")<br/>";
			$('debug').innerHTML += "TOTAL: " + this._effects[ev][6].length;
			 */
			switch (this._effects[ev][1]) {
				case 'shrink':
					new Effect.Shrink(this._effects[ev][6][current][0]);
					this._effects[ev][6][nextObj][0].style.width = this._effects[ev][6][nextObj][1] + "px";
					this._effects[ev][6][nextObj][0].style.height = this._effects[ev][6][nextObj][2] + "px";
					new Effect.Appear(this._effects[ev][6][nextObj][0]);
					break;
				case 'drop':
					new Effect.DropOut(this._effects[ev][6][current][0]);
					new Effect.Appear(this._effects[ev][6][nextObj][0]);
					break;
				case 'fade':
					new Effect.Fade(this._effects[ev][6][current][0]);
					new Effect.Appear(this._effects[ev][6][nextObj][0]);
					break;
				case 'left':
				case 'right':
					for (var i=0;i<this._effects[ev][6].length;i++) {
						this._effects[ev][6][i][0].style.display = '';
						new Effect.Move(this._effects[ev][6][i][0], { x: this._effects[ev][1]=='left'?-this._effects[ev][7]:this._effects[ev][7] , y: 0, mode: 'relative' });
					}
					break;
				case 'up':
				case 'down':
					for (var i=0;i<this._effects[ev][6].length;i++) {
						this._effects[ev][6][i][0].style.display = '';
						new Effect.Move(this._effects[ev][6][i][0], { x: 0 , y: this._effects[ev][1]=='up'?-this._effects[ev][8]:this._effects[ev][8], mode: 'relative' });
					}
					break;
			}
			if (this._effects[ev][5] && $(this._effects[ev][5])) {				
				$("cicleranchor"+ev+"_"+current).setAttribute('class','');
				$("cicleranchor"+ev+"_"+current).className = '';
				$("cicleranchor"+ev+"_"+nextObj).setAttribute('class',this._activeSlideClass);
				$("cicleranchor"+ev+"_"+nextObj).className = this._activeSlideClass;
			}
			if (this._effects[ev][11] && $(this._effects[ev][11])) {
				$(this._effects[ev][11]).innerHTML = this._effects[ev][6][nextObj][0].title?this._effects[ev][6][nextObj][0].title:(nextObj+1) + "/" + this._effects[ev][6].length; 
			}
			this._effects[ev][9] = nextObj;
			if (this._effects[ev][2]>0) this._effects[ev][10] = setTimeout(this.tick.bind(this,ev),this._effects[ev][2]);
		}
		if (isRandom) this._effects[ev][1] = 'random';
	},
	click: function(evt,ev,sl) {
		evt = evt?evt:event;
		try {
			evt.preventDefault();
		} catch (ee) {
			
		}
		if (this._effects[ev][2]>0) clearTimeout(this._effects[ev][10]);
		if (sl == 'p') {
			if (this._effects[ev][1]=='left') this._effects[ev][1]='right';
			if (this._effects[ev][1]=='up') this._effects[ev][1]='down';
			this.tick(ev); // forces change
		} else if (sl == 'n') {
			if (this._effects[ev][1]=='right') this._effects[ev][1]='left';
			if (this._effects[ev][1]=='down') this._effects[ev][1]='up';
			this.tick(ev); // forces change
		} else {
			if (this._effects[ev][5] && $(this._effects[ev][5])) {				
				$("cicleranchor"+ev+"_"+this._effects[ev][9]).setAttribute('class','');
				$("cicleranchor"+ev+"_"+sl).setAttribute('class','activeSlide');
			}
			this._effects[ev][9] = sl;
			this.fixPositions(ev);
			if (this._effects[ev][2]>0) this._effects[ev][10] = setTimeout(this.tick.bind(this,ev),this._effects[ev][2]);
		}

		return false;
	},
	fixPositions: function(ev) {
		current = this._effects[ev][9];
		switch (this._effects[ev][1]) {
			case 'left':
			case 'up':
			case "fade":
			case "drop":
			case "shrink":
			case "random": // random will just show the current, which is what all of these do
				required = this._effects[ev][6].length;
				for (var el=0;el<required;el++) {
					if (el != current && this._effects[ev][1] != 'left') this._effects[ev][6][el][0].style.display = 'none';
					else this._effects[ev][6][el][0].style.display = '';
				}
				break;
			case 'down':
				required = Math.ceil(this._effects[ev][0].offsetHeight / this._effects[ev][6][current][2]);
				if (required >= this._effects[ev][6].length) required = this._effects[ev][6].length-1;
				break;
			case 'right':
				required = Math.ceil(this._effects[ev][0].offsetWidth / this._effects[ev][6][current][1]);
				if (required >= this._effects[ev][6].length) required = this._effects[ev][6].length-1;
				break;
		}
		if (this._autoposition) {
			i = current;
			for (var el=0;el<required;el++) {
				if (this._effects[ev][6][i][1] == 0 || this._effects[ev][6][i][2] == 0) {
					thisPositionX = 0;
					thisPositionY = 0;
				} else {
					thisPositionX = Math.ceil((this._effects[ev][7]/2) - (this._effects[ev][6][i][1]/2));
					thisPositionY = Math.ceil((this._effects[ev][8]/2) - (this._effects[ev][6][i][2]/2));
				}
				this._effects[ev][6][i][0].style.left = ((this._effects[ev][1] == 'left' || this._effects[ev][1] == 'right' ? this._effects[ev][7] * el : 0) + thisPositionX) + "px";
				this._effects[ev][6][i][0].style.top = ((this._effects[ev][1] == 'up' || this._effects[ev][1] == 'down' ? this._effects[ev][8] * el : 0) + thisPositionY ) + "px";
				i++;
				if (i>=this._effects[ev][6].length) i =0;
			}
			if (required != this._effects[ev][6].length) {
				for (var el=this._effects[ev][6].length-required;el>0;el--) {
					if (this._effects[ev][1] == 'left' || this._effects[ev][1] == 'right') {
						thisPositionX = 0;
						thisPositionY = 0;
					} else {
						thisPositionX = Math.ceil((this._effects[ev][7]/2) - (this._effects[ev][6][i][1]/2));
						thisPositionY = Math.ceil((this._effects[ev][8]/2) - (this._effects[ev][6][i][2]/2));
					}
					this._effects[ev][6][i][0].style.left = ((this._effects[ev][1] == 'left' || this._effects[ev][1] == 'right' ? -this._effects[ev][7] * el : 0) + thisPositionX) + "px";
					this._effects[ev][6][i][0].style.top = ((this._effects[ev][1] == 'up' || this._effects[ev][1] == 'down' ? -this._effects[ev][8] * el : 0) + thisPositionY) + "px";
					i++;
					if (i>=this._effects[ev][6].length) i =0;
				}
			}
		}
	},
	setEffect: function(ev,eff) {
		this._effects[ev][1] = eff;	
	},
	add: function(divContainer,effectMode,timerInterval,objPrev,objNext,objNavbar,forceMove,donotposition,objTitle) {
		if (this._knownEffects.indexOf(effectMode) == -1)
			effectMode = 'fade';
		// 0 container, 1 effect, 2 timer, 3 objPrev, 4 objNext, 5 objNav, 6 elements, 7 width, 8 height, 9 current, 10 timerHandler, 11 obj title
		$(divContainer).style.display = 'block';
		newEffect = [$(divContainer),effectMode,timerInterval,objPrev,objNext,objNavbar,[],$(divContainer).offsetWidth,$(divContainer).offsetHeight,0,null,$(objTitle)];
		el = 0;
		for (var i=0;i<$(divContainer).childNodes.length;i++) {
			if ($(divContainer).childNodes[i].nodeType ==1 && parseInt($(divContainer).childNodes[i].offsetWidth)>0) {
				// container, width, height
				newItem = [$(divContainer).childNodes[i],$(divContainer).childNodes[i].offsetWidth,$(divContainer).childNodes[i].offsetHeight]; 
				newEffect[6].push(newItem);
				$(divContainer).childNodes[i].style.position = 'absolute';
				if (el==0) {
					newEffect[7] =  $(divContainer).childNodes[i].offsetWidth;
					newEffect[8] =  $(divContainer).childNodes[i].offsetHeight;
					$(divContainer).childNodes[i].style.display = '';
				} else if (effectMode == 'fade' || effectMode == 'drop' || effectMode == 'shrink' || effectMode == 'random')
					$(divContainer).childNodes[i].style.display = 'none';
				$(divContainer).childNodes[i].style.zIndex = this._minZindex;
				el++;
			}
		}
		newEffect[7] =  $(divContainer).offsetWidth;
		newEffect[8] =  $(divContainer).offsetHeight;
		if (forceMove) {
			if (effectMode == 'left' || effectMode == 'right') newEffect[7] = forceMove;
			if (effectMode == 'up' || effectMode == 'down') newEffect[8] = forceMove;
		}
		this._effects.push(newEffect);
		if (objNext && el == 1) $(objNext).style.display = 'none';
		if (objPrev && el == 1) $(objPrev).style.display = 'none';
		if (objTitle) $(objTitle).innerHTML = newEffect[6][0][0].title?newEffect[6][0][0].title:'';
		if (donotposition) this._autoposition = false;
		return this._effects.length-1;
	},

};
var cicler = new AFFcicler();

