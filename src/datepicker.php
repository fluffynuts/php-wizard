<html>
<head>
<?php
/***************   About this file:   ******************************** <<<
			Date picker page -- ported from my ASP date picker

	purpose: to provide a fast, generic method to handle
		selecting a date. On an intranet, the requery time
		for a new month is lower than the time taken to render
		this with javascript. There is a javascript date picker
		which may be better for internet application, though
		slow on the client

	usage: invoke this page from javascrip as follows:
		window.open('./{path to datepicker.php}?<arguments>', '', 'height=100,width=110,noresize');

		height and width settings are irrelevant -- the window resizes of 
		its own accord. Setting a small number for each means that the window
		"grows" rather than shrinks, and spawns in more neatly
		
		Arguments: (appended to the url of the php location)
			target: 	name of control to receive the picked value (required
						if no separate targets are specified (see below)
			targetform:	form on which that control resides
							(defaults to "frm")
			title:		custom title for the window. Defaults to 
						"Date Selector"
			targetday:	control to recieve a days value (not required)
			targetmonth:	control to recieve a months value (not required)
			targetyear:	control to receive a years value (not required)
			onclickcallback:	function to call in the receiving page after
								a date is selected (not required)
			startdate:	date to start with. Defaults to today.
			dateformat:	choose one of: 
							dd-mm-yyyy | d-m-Y
							yyyy-mm-dd | Y-m-d	(default -- safest for SQL)
							dd/mm/yyyy | d/m/Y
							yyyy/mm/dd | Y/m/d
							american formats *are not supported* because
							they just cause confusion.
			timeout:	time to wait (in ms) until the window is destroyed 
						from no use. Defaults to 30000 (30 seconds). Cannot 
						be less than 5 seconds
			allowweekends:	1 or 0; determines whether or not weekends can 
				be selected; defaults to 1;
			allowholidays:	1 or 0; determines whether or not holidays can
				be selected; defaults to 1;
				The only holidays catered for are Christmas and Easter. Add
				locale-specific ones with baddays parameter.
			baddays:	semi-colon delimited list of non-selectable days,
						in the format: year-month-day, month-day, or just 
						day; months may be string names or month numbers,
							but string names must be >= 3chars:
						eg: 2004-03-18;06-15;13
						prevents selection of 18 march 2004, 15 june all years, 
							and every 13th of every month
						note, you can also select bad weekdays (eg "wed" or 
							"wednesday", and combine that with a month, eg 
							"01-wed" to disable all wednesdays in january
	Template input box with asscociated button:
	============================
<input type=text name="[$NAME]" style="width:100px" value="[VALUE]"
	class="textbox_sml" onfocus="this.blur();" onmousedown="this.blur();"
	onkeypress="this.blur();" title="Select a date..." 
	onmouseover="window.status='Select a date...';return true;"
	onmouseout="window.status='';return true;"><input type=button 
	onclick="window.open('./_private/DatePicker.php?target=[NAME]&targetform=[FORM]&startdate=[VALUE]','', 'height=69,width=69,noresize');" value=" ... ">
********************************************************************>>>*/
function rod($varname, $default="") { // <<<1
	// "Request or Default"
	if (array_key_exists($varname, $_REQUEST)) {
		return $_REQUEST[$varname];
	} else {
		return $default;
	}
}
function zeropad($str, $len=2) { // <<<1
	while (strlen($str)<$len) {
		$str="0".$str;
	}
	return $str;
}
function val($strIn) { // <<<1
	$strIn = strtolower(trim($strIn));
	$bNegative=false;
	$RET="";
	if ($strIn=="true") {
		return 1;
	} else {
		if ($strIn=="false") {
			return 0;
		} else {
			$j=strlen($strIn);
			if ($j) {
				for ($i=0;$i<=$j;$i++) {
					$C=$strIn[$i];
					if (is_numeric($C)) {
						break;
					}
					if ($C=="-") {
						$bNegative=!$bNegative;
					}
				}
				while ($i<=$j) {
					if (is_numeric($C)) {
						$RET.=$C;
					} else {
						break;
					}
					$C=$strIn[++$i];
				}
				if($bNegative) {
					return (-1*$RET);
				} else {
					return (1*$RET);
				}
			} else {
				return 0;
			}
		}
	}
}
function year_cmb($year="", $onchange="") { // <<<1
// creates a year select, with $year selected. Keeps years within sane range
//	of +- 20 years from $year. $year defaults to this year, if left blank
	$year=val($year);
	if ($year==0) {
		$d=getdate();
		$year=$d["year"];
	}
	$lower_year=$year-20;
	$upper_year=$year+20;
	$h="<form name=\"frm\"><select style=\"width: 60px; font-size: 12px; "
		."font-family: verdana, helvetica; background-color: #FFFFFF; color: "
		."#000099\" name=\"year\"";
	if (strlen($onchange)) {
		$h.=" onchange=\"".$onchange."\"";
	}
	$h.=">";
	for ($i=$lower_year; $i<$year; $i++) {
		$y=zeropad($i, 4);
		$h.="<option value=\"".$y."\">".$y."</option>";
	}
	$y=zeropad($year, 4);
	$h.="<option value=\"".$y."\" selected>".$y."</option>";
	for ($i=$year+1; $i<=$upper_year; $i++) {
		$y=zeropad($i, 4);
		$h.="<option value=\"".$y."\">".$y."</option>";
	}
	// force "smart" browsers that remember form values to select date
	$h.="</select><script language=\"Javascript\">for(i=0;i<document.frm.year"
		.".length;i++){if(document.frm.year.options[i]==".$year.") {document."
		."frm.year.options[i].selected=true;break;}}</script></form>";
	return $h;
}
function days_in_month($timestamp) { // <<<1
	$d=getdate($timestamp);
	switch ($d["mon"]) {
		case 1: {
			return 31;
		}
		case 2: {
			return (($d["year"]%4)==0)?29:28;
		}
		case 3: {
			return 31;
		}
		case 4: {
			return 30;
		}
		case 5: {
			return 31;
		}
		case 6: {
			return 30;
		}
		case 7: {
			return 31;
		}
		case 8: {
			return 31;
		}
		case 9: {
			return 30;
		}
		case 10: {
			return 31;
		}
		case 11: {
			return 30;
		}
		case 12: {
			return 31;
		}
	}
}
function dateadd($part, $val, $timestamp) { // <<<1
	if (!is_int($timestamp)) {
		global $dateformat;
		$retint=false;
		$timestamp=strtotime($timestamp);
		if ($timestamp == -1) {
			return "";
		}
	} else {
		$retint=true;
	}
	switch ($part) {
		case "s":
		case "seconds":
		case "sec": {
			$ret=val($val);
			break;
		}
		case "n": 
		case "min":
		case "minutes":	{
			$ret=val($val)*60;
			break;
		}
		case "h": 
		case "hr":
		case "hrs":
		case "hours": {
			$ret=val($val)*3600;
			break;
		}
		case "d": 
		case "days": {
			$ret=$val*86400;
			break;
		}
		case "w": 
		case "wk":
		case "wks":
		case "weeks": {
			$ret=val($val)*604800;
			break;
		}
		case "m": 
		case "mon": 
		case "months": {
			// first, how many days in this month?
			$adddays=days_in_month($timestamp);
			$ret=val($val)*86400*$adddays;
			break;
		}
		case "y": 
		case "yr":
		case "yrs":
		case "years": {
			$ret=val($val)*31536000;
			break;
		}
		default: {
			$ret=$timestamp;
		}
	}
	if ($retint) {
		return $ret+$timestamp;
	} else {
		return date($dateformat, $ret+$timestamp);
	}
}
function weekday_name($daynum, $len=3) { // <<<1
	switch ($daynum) {
		case 1: {
			return substr("Sunday", 0, $len);
		}
		case 2: {
			return substr("Monday", 0, $len);
		}
		case 3: {
			return substr("Tuesday", 0, $len);
		}
		case 4: {
			return substr("Wednesday", 0, $len);
		}
		case 5: {
			return substr("Thursday", 0, $len);
		}
		case 6: {
			return substr("Friday", 0, $len);
		}
		case 7: {
			return substr("Saturday", 0, $len);
		}
		default: {
			return "?";
		}
	}
}
function add_bad_day(&$abaddays, $datestring) { // <<<1
	$thisdate=explode("-", $datestring);
	switch (count($thisdate)) {
		case 1: {
			break;
		}
		case 2: {
			if (!is_int($thisdate[0])) {
				$thisdate[0]=monthnum($thisdate[0]);
			}
			break;
		}
		case 3: {
			if (!is_int($thisdate[1])) {
				$thisdate[1]=monthnum($thisdate[1]);
			}
			break;
		}
	}
	$abaddays[]=$thisdate;
}
function monthnum($monthname) { // <<<1
	if (is_int($monthname)) {
		return $monthname;
	}
	switch (substr(strtolower($monthname), 0, 3)) {
		case "jan": 
		case "01":	{
			return 1;
		}
		case "feb": 
		case "02":	{
			return 2;
		}
		case "mar": 
		case "03":	{
			return 3;
		}
		case "apr": 
		case "04":	{
			return 4;
		}
		case "may": 
		case "05":	{
			return 5;
		}
		case "jun": 
		case "06":	{
			return 6;
		}
		case "jul": 
		case "07":	{
			return 7;
		}
		case "aug": 
		case "08":	{
			return 8;
		}
		case "sep": 
		case "09":	{
			return 9;
		}
		case "oct": 
		case "10":	{
			return 10;
		}
		case "nov": 
		case "11":	{
			return 11;
		}
		case "dec": 
		case "12":	{
			return 12;
		}
		default: {
			return 0;
		}
	}
}
function is_bad_day(&$abaddays, $date) { // <<<1
	if (!is_int($date)) {
		$date=strtotime($date);
	}
	$ad=getdate($date);
	foreach ($abaddays as $idx=>$val) {
		if (!is_array($val)) continue;
		switch (count($val)) {
			case 1: {	// day
				if (is_int($val[0])) {
					if ($ad["mday"]==$val[0]) {
					//	print("day fails on day");
						return true;
					}
				} else {
					$dn=substr($ad["weekday"], 0, strlen($val[0]));
					if (strtolower($dn) == strtolower($val[0])) {
					//	print("day fails on weekday");
					//	print("val: ($val[0]) -- dn: ($dn)<br>");
						return true;
					}
				}
				break;
			}
			case 2: {	// month, day
				if ($val[0]==$ad["mon"]) {
					if (is_int($val[1])) {
						if ($val[1]==$ad["mday"]) {
						//	print("day fails on month, day");
							return true;
						}
					} else {
						$dn=substr($ad["weekday"], 0, strlen($val[1]));
						if (strtolower($val[1])==strtolower($dn)) {
							return true;
						}
					}
				}
				break;
			}
			case 3: {	// year, month, day
				if ($val[0]==$ad["year"] && $val[1]==$ad["mon"]) {
					if (is_int($val[2])) {
						if ($val[2]==$ad["mday"]) {
					//	print("day fails on year, month, day");
							return true;
						}
					} else {
						$dn=substr($ad["weekday"], 0, strlen($val[2]));
						if (strtolower($val[2])==strtolower($dn)) {
							return true;
						}
					}
				}
				break;
			}
			default: {
			}
		}
	}
	return false;
}
// initialisation <<<1
	$baddays=trim(rod("baddays", ""));
	$abaddays=array();
	if (strlen($baddays)) {
		$abaddays=explode(";", $baddays);
	}
	foreach ($abaddays as $idx => $val) {
		if (strlen($val)) {
			add_bad_day($abaddays, str_replace("/", "-", $val));
		}
	}
	$allowholidays=val(rod("allowholidays", "1"));
	$allowweekends=val(rod("allowweekends", "1"));
	if (!$allowholidays) {
		// add in some regular holidays here
		add_bad_day($abaddays, "12-25");
		$easter=easter_date();
		for($i=-2;$i<2;$i++) {
			add_bad_day($abaddays, date("m-d", dateadd("d", $i, $easter)));
		}
		add_bad_day($abaddays, "01-01");
		add_bad_day($abaddays, "12-26");
	}
	$title=rod("title", "Date selector");
	$targetform=rod("targetform", "frm");
	$target=rod("target");
	$targetday=rod("targetday");
	$targetmonth=rod("targetmonth");
	$targetyear=rod("targetyear");
	$onclickcallback=rod("onclickcallback");
	$startdate=rod("startdate", date("Y-m-d"));
	$timeout=val(rod("timeout", "30000"));
	if ($timeout<5000) {
		$timeout=30000;
	}
// make sure the date format is ok
	$dateformat=rod("dateformat", "Y-m-d");
	switch ($dateformat) {
		case "d-m-Y":
		case "dd-mm-yyyy": {
			$dateformat="d-m-Y";
			break;
		}
		case "d/m/Y":
		case "dd/mm/yyyy": {
			$dateformat="d/m/Y";
			break;
		}
		case "Y/m/d":
		case "yyyy/mm/dd": {
			$dateformat="Y/m/d";
			break;
		}
		case "Y-m-d":
		case "yyy-mm-dd":
		default: {
			$dateformat="Y-m-d";
		}
	}

	$startdate_int=strtotime($startdate);
	$astartdate=getdate($startdate_int);
	$now=gettimeofday();
	$anow=getdate($now["sec"]);
	$nowday=$anow["mday"];
	$overrideyear=val(rod("overrideyear", ""));
	if ($overrideyear>0) {
		$tmp=strtotime($overrideyear."-".$astartdate["mon"]."-"
			.$astartdate["mday"]);
		if ($tmp!=-1) {
			$startdate_int=$tmp;
			$astartdate=getdate($startdate_int);
			$startdate=date($dateformat, $startdate_int);
		}
	}
	$days_in_this_month=days_in_month($startdate_int);
	$firstdate=strtotime($astartdate["year"]."-".$astartdate["mon"]."-1");
	$afirstdate=getdate($firstdate);
	$firstday=$afirstdate["wday"];
	if ($anow["mon"] == $astartdate["mon"]) {
		$show_gototoday=false;
	} else {
		$show_gototoday=true;
	}
// >>>1
//  style settings & return script <<<
?>
<meta name="generator" content="An infinite number of monkeys in a universe devoid of Shakespeare">
<meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<title><?=$title?></title>
<script language="Javascript">
	var close_timeout=<?=$timeout?>;
	var time_left=<?=$timeout?>;
	function return_date (year, month, day) { // <<<
	<? 
	if (strlen($targetform)) {
		$targets=0;
		if (strlen($target)) { ?>
			var target=window.opener.document.<?=$targetform?>.<?=$target?>;
			<? 
			switch ($dateformat) {
				case "Y-m-d": { ?>
					target.value=year+'-'+month+'-'+day;
				<?
					break;
				}
				case "Y/m/d": { ?>
					target.value=year+'/'+month+'/'+day;
				<?
					break;
				}
				case "d-m-Y": { ?>
					target.value=day+'-'+month+'-'+year;
				<?
					break;
				}
				case "d/m/Y": { ?>
					target.value=day+'/'+month+'/'+year;
				<?
					break;
				}
			}
			$targets++;
		}
		if (strlen($targetyear)) {
		?>
		var target=window.opener.document.<?=$targetform?>.<?=$targetyear?>;
		target.value=year;
		<?
			$targets++;
		}
		if (strlen($targetmonth)) {
		?>
		var target=window.opener.document.<?=$targetform?>.<?=$targetmonth?>;
		target.value=month;
		<?
			$targets++;
		}
		if (strlen($targetday)) {
		?>
		var target=window.opener.document.<?=$target_form?>.<?=$targetday?>;
		target.value=day;
		<?
			$targets++;
		}
		if (strlen($onclickcallback)) {
			print("window.opener.".$onclickcallback.";");
		}
		if ($targets==0) {
		?>
			alert('no target fields were designated for this date selector.');
		<?
		}
	} else {
	?>
		alert('no target form was designated for this date selector.');
	<?
	}
	?>
		self.close();
	}
	// >>>
	function close_inactive () { // <<<
		if (window.focus) {
			window.focus();
		}
		time_left--;
		if (time_left<1) {
			window.close();
		} else {
			window.setTimeout('close_inactive();', 1000);
		}
	}
	//>>>
	function setclass(item, classname) { // <<<
		item.className=classname;
		resetinactive();
	}
	function resetinactive() { // <<<
		time_left=<?=$timeout?>;
	}
	//>>>
<? 	if ($show_gototoday) {	?>
var w_width=225,w_height=240;
<?	} else {	?>
var w_width=225,w_height=210;
<?	}	?>
var bInactive=false;
window.setTimeout('close_inactive();', 1000);
self.resizeTo(w_width,w_height);
self.moveTo((self.screen.width-w_width)/2, (self.screen.height-w_height)/2);
</script>
<!-- >>> -->
<!-- style: kept inline to keep this simple <<< -->
<style type="text/css">
a:visited { 
	color: darkblue; 
	font-weight: bold; 
	text-decoration: none; 
	border: 1px white solid; 
	line-height: 13px; 
	font-family: verdana, helvetica; 
	font-size: 12px;
}
a:hover	{ 
	color: #5555FF; 
	font-weight: bold; 
	text-decoration: none; 
	border: 1px darkblue solid; 
	line-height: 13px; 
	font-family: verdana, helvetica; 
	font-size: 12px;
}
a { 
	color: darkblue; 
	font-weight: bold; 
	text-decoration: none; 
	border: 1px white solid; 
	line-height: 13px; 
	font-family: verdana, helvetica; 
	font-size: 12px;
}
body { 
	margin-top: 0px; 
	margin-left: 15px; 
	margin-right: 15px; 
	background-color: white; 
}
.DPInactive {
	color: #000000;
	background-color: #555555;
	font-size: 12px;
	border: 1px solid #222222;
	cursor: pointer;
	cursor: hand;
}
.DPMonthName { 
	color: darkblue; 
	font-size: 12px; 
	font-weight: bold; 
}
.DPYear { 
	color: darkblue; 
	font-size: 10px; 
	font-weight: bold;
}
.DPDOW { 
	background-color: darkblue;
	color: white; 
	font-size: 10px; 
}
.DPDayNormal { 
	background-color: white; 
	color: darkblue; 
	font-size: 12px; 
	border-style: solid; 
	border-width: 1px; 
	border-color: darkgray; 
	cursor: pointer; 
	cursor: hand; 
}
.DPDayWeekend { 
	background-color: silver; 
	color: darkblue; 
	font-size: 12px; 
	border-style: solid; 
	border-width: 1px; 
	border-color: darkgray; 
	cursor: pointer; 
	cursor: hand; 
}
.DPDayToday { 
	background-color: white; 
	color: darkblue; 
	font-size: 8px; 
	border-style: solid; 
	border-width: 1px; 
	border-color: darkblue; 
	cursor: pointer; 
	cursor: hand; 
}
.DPDaySelected {
	background-color: yellow; 
	color: darkblue; 
	font-size: 12px; 
	cursor: pointer; 
	cursor: hand; 
	border-color: black; 
	border-style: solid; 
	border-width: 1px; 
}
.DPDayTodaySelected { 
	background-color: yellow; 
	color: darkblue; 
	font-size: 10px; 
	border-style: solid; 
	border-width: 1px; 
	border-color: darkblue; 
	cursor: pointer; 
	cursor: hand; 
}
.DPMouseOver {
	background-color: #508ae2; 
	color: white; 
	font-size:10px; 
	border: 1px solid blue; 
	cursor: pointer; 
	cursor: hand; 
}
.DPMouseOver_SelDay {
	background-color: lightgreen; 
	color: black; 
	font-size:12px; 
	border-style: solid; 
	border-width: 1px; 
	border-color: blue; 
	cursor: pointer; 
	cursor: hand; 
}
.gototoday {
	text-align: right;
	text-color: darkblue;
}
</style>
<?
// >>>
// >>>
?><body onmouseover="resetinactive();">
	<table width="100%" border="0" cellpadding="1" cellspacing="1">
		<tr>
		<td width="20%" align="left" valign="middle">
		<a href="./datepicker.php?title=<?=$title?>&startdate=<?=dateadd("m", -1, $startdate)?>&target=<?=$target?>&targetday=<?=$targetday?>&targetmonth=<?=$targetmonth?>&targetyear=<?=$targetyear?>&onclickcallback=<?=$onclickcallback?>&targetform=<?=$targetform?>&allowweekends=<?=$allowweekends?>&allowholidays=<?=$allowholidays?>&baddays=<?=$baddays?>" title="Go one month back...">&lt;&lt;</a>
		</td>
		<td width="60%" align="center" valign="middle">
			<span class="DPMonthName"><?=$astartdate["month"]?></span><br>
			<span class="DPYear"><?=year_cmb($astartdate["year"], 
				"window.location='./datepicker.php?title=".$title
				."&startdate=".$startdate."&target=".$target."&targetday="
				.$targetday."&targetmonth=".$targetmonth."&targetyear="
				.$targetyear."&targetform=".$targetform."&onclickcallback="
				.$onclickcallback."&overrideyear='+document.frm.year.value;");?></span>
		</td>
		<td width="20%" align="right" valign="middle">
		<a href="./datepicker.php?title=<?=$title?>&startdate=<?=dateadd("m", 1, $startdate)?>&target=<?=$target?>&targetday=<?=$targetday?>&targetmonth=<?=$targetmonth?>&targetyear=<?=$targetyear?>&onclickcallback=<?=$onclickcallback?>&targetform=<?=$targetform?>&allowweekends=<?=$allowweekends?>&allowholidays=<?=$allowholidays?>&baddays=<?=$baddays?>" title="Go one month forward...">&gt;&gt;</a></td>
		</tr>
	</table>
<table width="100%" border="0" cellpadding="1" cellspacing="1">
	<?	for ($col=1; $col<8; $col++) { ?>
		<th class="DPDOW" width="14%"><?=weekday_name($col)?></th>
	<?	}
		$onfirstrow=true;
		$currday=1;
		for ($row=0; $row<6; $row++) {
		?><tr><?
			for ($col=0; $col<7; $col++) {
				if ($firstday==$col) $onfirstrow=false;
				if ($onfirstrow || ($currday>$days_in_this_month)) {
				?><td></td><?
				} else {
					$clickdate=dateadd("d", $currday-1, $firstdate);
					$aclickdate=getdate($clickdate);
					if (is_bad_day($abaddays, $clickdate)) {
						$thisclass="DPInactive";
					} else {
						if (($col==0)||($col==6)) {
							if ($allowweekends) {
								$thisclass="DPDayWeekend";
								$thismoclass="DPMouseOver";
							} else {
								$thisclass="DPInactive";
							}
						} elseif (($astartdate["year"] == $anow["year"]) &&
							($astartdate["mon"] == $anow["mon"])) {
							if ($currday==$anow["mday"]) {
								if ($currday==$startdate["mday"]) {
									$thisclass="DPDayTodaySelected";
									$thismoclass="DPMouseOver_SelDay";
								} else {
									$thisclass="DPDayToday";
									$thismoclass="DPMouseOver";
								}
							} elseif ($currday==$astartdate["mday"]) {
								$thisclass="DPDaySelected";
								$thismoclass="DPMouseOver_SelDay";
							} else {
								$thisclass="DPDayNormal";
								$thismoclass="DPMouseOver";
							}
						} else {
							$thisclass="DPDayNormal";
							$thismoclass="DPMouseOver";
						}
					}
					if ($thisclass=="DPInactive") {
				?>
				<td class="DPInactive" align="center"><?=$currday?></td>
				<?
					} else {
				?>
				<td class="<?=$thisclass?>" align="center"
				onclick="javascript: return_date(document.frm.year.value, '<?
					print(zeropad($aclickdate["mon"]));
				?>', '<?
					print(zeropad($aclickdate["mday"]));
				?>'); return false;"
				onmouseover="setclass(this, '<?=$thismoclass?>');"
				onmouseout="setclass(this, '<?=$thisclass?>');"><?=$currday?></td>
				<? }
					$currday++;
				}
			} ?>
			</tr>
	<?	if ($currday>$days_in_this_month) break;
		}
		?>
	</table>
	<?	if ($show_gototoday) { ?>
<p class="gototoday"><a href="./datepicker.php?title=<?=$title?>&target=<?=$target?>&targetform=<?$targetform?>&targetday=<?=$targetday?>&targetmonth=<?=$targetmonth?>&targetyear=<?=$targetyear?>&dateformat=<?=$dateformat?>&allowweekends=<?=$allowweekends?>&allowholidays=<?=$allowholidays?>&baddays=<?=$baddays?>&startdate=<?=date("Y-m-d", $now["sec"]);?>" title="Go to today">Go to today</a></p>
	<?	}	?>
</body>
</html>
