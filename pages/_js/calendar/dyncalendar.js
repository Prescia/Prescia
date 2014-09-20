

var dynCalendar = Class.create();
dynCalendar.prototype = {
	monthnames:['Jan.','Fev.','Mar.','Abr.','Mai.','Jun.','Jul.','Ago.','Set.','Out.','Nov.','Dez.'],
	currentMonth:1,
	currentYear:2000,
	yearComboRangePast:90,
	yearComboRangeFuture:30,
	receiver: false,
	imagesPath:'/pages/_js/calendar/gifs',
	objName:'div_dynCalendar',
	isDisplayed:false,
	storedValue:'',
	initialize: function(imagePath,monthNames,dayNames){
		if (imagePath) this.imagesPath = imagePath;
		var today = new Date();
		this.currentDay = today.getDate();
		this.currentMonth  = today.getMonth();
		this.currentYear  = today.getFullYear();
		Event.observe(window,'load',this._echoDiv.bind(this));
	},
	_getDaysInMonth: function(m,y) {
		var monthdays = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
		if (m != 1) {
			return monthdays[m];
		} else {
			return ((y % 4 == 0 && y % 100 != 0) || y % 400 == 0 ? 29 : 28);
		}
	},
	_getHTML:function(month,year) {
		// Variable declarations to prevent globalisation
		var month, year,  numdays, thisMonth, firstOfMonth;
		var ret, row, i, cssClass, linkHTML, previousMonth, previousYear;
		var nextMonth, nextYear, prevImgHTML, prevLinkHTML, nextImgHTML, nextLinkHTML;
		var monthComboOptions, monthCombo, yearComboOptions, yearCombo, html;

		if (!month && month != 0)	month = this.currentMonth;
		else this.currentMonth = month;
		if (!year) year = this.currentYear;
		else this.currentYear = year;

		numdays    = this._getDaysInMonth(month, year);
		thisMonth    = new Date(year, month, 1);
		firstOfMonth = thisMonth.getDay();

		// First few blanks up to first day
		ret = new Array(new Array());
		for(i=0; i<firstOfMonth; i++){
			ret[0][ret[0].length] = '<td>&nbsp;</td>';
		}

		// Main body of calendar
		row = 0;
		i   = 1;

		while(i <= numdays){
			if(ret[row].length == 7){
				ret[++row] = new Array();
			}

			cssClass = (i == this.currentDay && month == this.currentMonth && year == this.currentYear) ? 'dynCalendar_today' : 'dynCalendar_day';
			linkHTML = '<a href="javascript: calendarHandler._setAndHide(' + i + ', ' + (Number(month) + 1) + ', ' + year + ');">' + (i++) + '</a>';
			ret[row][ret[row].length] = '<td align="center" class="' + cssClass + '">' + linkHTML + '</td>';
		}

		// Format the HTML
		for(i=0; i<ret.length; i++){
			ret[i] = ret[i].join('\n') + '\n';
		}

		previousYear  = thisMonth.getFullYear();
		previousMonth = thisMonth.getMonth() - 1;
		if(previousMonth < 0){
			previousMonth = 11;
			previousYear--;
		}

		nextYear  = thisMonth.getFullYear();
		nextMonth = thisMonth.getMonth() + 1;
		if(nextMonth > 11){
			nextMonth = 0;
			nextYear++;
		}

		prevImgHTML  = '<img src="' + this.imagesPath + '/prev.gif" alt="<<" border="0" />';
		prevLinkHTML = '<a href="javascript: calendarHandler._show(' + previousMonth + ', ' + previousYear + ')">' + prevImgHTML + '</a>';
		nextImgHTML  = '<img src="' + this.imagesPath + '/next.gif" alt="<<" border="0" />';
		nextLinkHTML = '<a href="javascript: calendarHandler._show(' + nextMonth + ', ' + nextYear + ')">' + nextImgHTML + '</a>';

		monthComboOptions = '';
		for (i=0; i<12; i++) {
			selected = (i == thisMonth.getMonth() ? 'selected="selected"' : '');
			monthComboOptions += '<option value="' + i + '" ' + selected + '>' + this.monthnames[i] + '</option>';
		}
		monthCombo = '<select id="dyn_ms" name="months" style="width:60px" onchange="calendarHandler._show(this.options[this.selectedIndex].value, calendarHandler.currentYear)">' + monthComboOptions + '</select>';
		yearComboOptions = '';
		for (i = thisMonth.getFullYear() - this.yearComboRangePast; i <= (thisMonth.getFullYear() + this.yearComboRangeFuture); i++) {
			selected = (i == thisMonth.getFullYear() ? 'selected="selected"' : '');
			yearComboOptions += '<option value="' + i + '" ' + selected + '>' + i + '</option>';
		}
		yearCombo = '<select id="dyn_ys" style="border: 1px groove;width:60px" name="years" onchange="calendarHandler._show(calendarHandler.currentMonth, this.options[this.selectedIndex].value)">' + yearComboOptions + '</select>';

		html = '<table border="0" bgcolor="#eeeeee">';
		html += '<tr><td class="dynCalendar_header">' + prevLinkHTML + '</td><td colspan="5" align="center" class="dynCalendar_header" nowrap><nobr>' + monthCombo + ' ' + yearCombo + '</nobr></td><td align="right" class="dynCalendar_header">' + nextLinkHTML + '</td></tr>';
		html += '<tr>';
		html += '<td class="dynCalendar_dayname">Dom</td>';
		html += '<td class="dynCalendar_dayname">Seg</td>';
		html += '<td class="dynCalendar_dayname">Ter</td>';
		html += '<td class="dynCalendar_dayname">Qua</td>';
		html += '<td class="dynCalendar_dayname">Qui</td>';
		html += '<td class="dynCalendar_dayname">Sex</td>';
		html += '<td class="dynCalendar_dayname">Sab</td></tr>';
		html += '<tr>' + ret.join('</tr>\n<tr>') + '</tr>';
		html += '</table>';

		return html;
	},
	_echoDiv: function()  {
		objOverlay = document.createElement('div');
		objOverlay.innerHTML = "<div class='dynCalendar' id='"+this.objName+"' style='display:none;z-index:100;'>&nbsp;</div>";
		document.body.appendChild(objOverlay);
	},
	_show: function(m,y) {
		$(this.objName).innerHTML = this._getHTML(m,y);
		if (!this.isDisplayed) {
			$(this.objName).style.display = '';
			this.isDisplayed = true;
		}
	},
	_hide: function() {
		if (this.isDisplayed) {try {
				Effect.toggle(this.objName,'BLIND');
			} catch (ee) {
				$(this.objName).style.display = 'none';
			}
		}
		this.isDisplayed = false;
	},
	_setAndHide: function(d,m,y) {
		// extract all data from the stored value, and fill in the date/time
		this.receiver.value = this.storedValue + d + '/' + m + '/' + y;
		this._hide();
	},
	_extractData: function(data) {
		// this ereg works only with intl time, alas h:m:s d/m/Y (no US support)
		ok = ereg(data,"^( )*(([0-9]{1,2})([^0-9])){2,5}([0-9]{2,4})( )*$"); // s is optional
		if (ok) {
			this.storedValue = data;
			MyRegExp = new RegExp("^( )*(([0-9]{1,2})([^0-9]))(([0-9]{1,2})([^0-9]))(([0-9]{1,2})([^0-9]))?(([0-9]{1,2})([^0-9]))?(([0-9]{1,2})([^0-9]))?([0-9]{2,4})( )*$");
			results = MyRegExp.exec(data);
			v1 = results[3];
			v2 = results[6];
			v3 = results[9];
			v4 = results[12];
			v5 = results[15];
			vy = results[17];
			vm = v5?v5:(v4?v4:(v3?v3:v2));
			fixedDate = (v5?v1+":"+v2+":"+v3+" "+v4+"/"+v5:
					    (v4?v1+":"+v2+":00 "+v3+"/"+v4:
					    (v3?v1+":00:00 "+v2+"/"+v3:v1+"/"+v2)))
					    + "/"+vy; // full fixed datetime
			this.storedValue = (v5?v1+":"+v2+":"+v3+" ":
					    	   (v4?v1+":"+v2+":00 ":
					    	   (v3?v1+":00:00 ":""))); // only the TIME with trailing
			this.currentMonth  = vm-1;
			vy = parseInt(vy);
			this.currentYear  = vy<100?2000+vy:vy;
		} else {
			this.storedValue = '';
			var today = new Date();
			this.currentMonth  = today.getMonth();
			this.currentYear  = today.getFullYear();
		}
	},
	showCalendar: function(component,positionedComponent,offsetX,offsetY) { // input, div where to show calendar, offsets from positioned
		this.receiver = $(component);
		this._extractData(this.receiver.value);
		if (!positionedComponent)
			positionedComponent = this.receiver;
		else
			positionedComponent = $(positionedComponent);
		if (!offsetX) offsetX = 0;
		if (!offsetY) offsetY = 0;
		$(this.objName).style.left = (findPosX(positionedComponent) + offsetX) + "px";
		$(this.objName).style.top = (findPosY(positionedComponent) + offsetY) + "px";
		this._show();
	}


}
/* HOW TO USE:
  1. include .js and .css
  2. create calendarHandler (even if you have multiple calendars, only ONE will show, so only one handler):
		calendarHandler = new dynCalendar([imgpath]);
  3. add some link/image/whatever to show the calendar (an image with dyncalendar.gif for instance)
  4. onclick at said link above:
  		calendarHandler.showCalendar(inputWithDate);
  5. yeah, you are done
*/
