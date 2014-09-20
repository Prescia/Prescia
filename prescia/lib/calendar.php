<?

	function echoCalendar(&$containerTP,$width=0,$month=0,$year=0,$highlights=array(),$dayborder=0,$prevquery="",$nextquery="",$divname="inlinecalendar") {
		/*
		  width should be divisible by 7
		  highlights is an array, each with the following:
		 	'day' => # day
		 	'title' => title on the cell (if nothing, will use the day #)
		 	'link' => link if click on the cell (if nothing, no link)
		 	'class' => (optional) class for the cell
		  dayborder is the number in pixels of border (+margin +padding) you will use on each cell
		*/
		$tp = new CKTemplate($containerTP);
		if (!is_file(CONS_PATH_SETTINGS."defaults/calendar.html")) return "echoCalendar: File not found";
		$tp->fetch(CONS_PATH_SETTINGS."defaults/calendar.html");
		
		if ($month == 0) $month = date("m");
		if ($year == 0) $year = date("Y");
		
		$width = 7*floor($width/7);
		$widthDay = floor($width/7)-2*$dayborder;
		$month = (int)$month;
		$year = (int)$year;
		if ($year < 100) $year += 2000;
		if ($month < 10) $month = "0".$month;
		
		$initDay = $year."-".$month."-01";
		$endDate = datecalc($initDay,0,1);
		
		$monthLine = $tp->get("_line");
		$dayTp = $tp->get("_day");
		$temp = ""; // <-- main
		$tempL = ""; // <-- a line
	
		$column = date("w",tomktime($initDay)); // where this month starts
		$today = date("Y-m-d");
		
		$daysOnPreviousMonth = $column;
		
		while ($daysOnPreviousMonth>0) {
			$tempL .= $dayTp->techo(array('class' => 'calendarDayEmpty',"title" => "&nbsp;", "widthday" => $widthDay));
			$daysOnPreviousMonth--;
		}
		
		while (datecompare($endDate,$initDay)) { // while we are within the month (loop will increase initDay)
		
			$isWeekend = $column == 0 || $column == 6;
			$isToday = $initDay == $today;
			$day = substr($initDay,8,2);
			// the following line will put the appropriate class on the day depending on start/end of the project, weekend or deadline
			$output = array("class" => $isToday ? "calendarDayToday" : ($isWeekend ? "calendarDayWeekend" : "calendarDayNormal"),
							"title" => (int)$day,
							"widthday" => $widthDay
							);
			
			// now we check if we have a highlight
			foreach ($highlights as $high) {
				if ($high['day'] == $day) {
					$output['class'] = isset($high['class']) && $high['class']!=''?$high['class']:"calendarDayHighlight";
					$output['title'] = isset($high['title'])?$high['title']:(int)$day;
					if (isset($high['link']) && $high['link'] !='')
						$output['title'] = "<a href=\"".$high['link']."\">".$output['title']."</a>";
				}
			}
			
			$tempL .= $dayTp->techo($output);
		
			if ($column == 6) {
				// end of a line
				$temp .= $monthLine->techo(array("_day" => $tempL)); // <-- echo line
				$tempL = "";
			}
			$column++;
			if ($column >= 7) $column =0;
			$initDay = datecalc($initDay,0,0,1);
		}
		if ($column != 0) {
			// we might not have finished the last line ... check it:
			for ($column = $column; $column < 7; $column++) {
				$tempL .= $dayTp->techo(array('class' => 'calendarDayEmpty', "title" => "", "widthday" => $widthDay));
			}
			$temp .= $monthLine->techo(array("_day" => $tempL)); // <-- echo line
			$tempL = "";
		}
		$tp->assign("width",$width);
		$tp->assign("month",$month);
		$tp->assign("year",$year);
		$tp->assign("widthday",$widthDay);
		$tp->assign("_line",$temp);
		$tp->assign("calendar",$divname);
		if ($prevquery != '' && $nextquery != '') {
			$tp->assign("ajaxcommandprev",$prevquery);
			$tp->assign("ajaxcommandnext",$nextquery);
		} else
			$tp->assign("_prevnext");
		return $tp->techo();
		
	}
