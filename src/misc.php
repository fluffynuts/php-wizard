<?php
	$image_extensions = "unset";
	$include_from = dirname(__FILE__);
	$tmp = explode(DIRECTORY_SEPARATOR, $include_from);
	$parent_dir = implode(DIRECTORY_SEPARATOR, array_slice($tmp, 0, count($tmp)-1));
	$rinc = array(
		"global.php",
		"base_ds.php",
		"xml_ds.php", 
		"tlcxml_ds.php",
	);
	$incdirs = array(
		".",
		$include_from,
		$parent_dir,
		$parent_dir.DIRECTORY_SEPARATOR."include"
	);
	foreach ($rinc as $inc) {
		$found = false;
		foreach ($incdirs as $incdir) {
			if (file_exists($incdir.DIRECTORY_SEPARATOR.$inc)) {
				include_once($incdir.DIRECTORY_SEPARATOR.$inc);
				$found = true;
				break;
			}
		}
		if ($found) {
			continue;
		} else {
			print("Warning: unable to include $inc<br>");
		}
	}
?>
<!-- Included file: include/misc.php -->
<?php
	function tobool($val) {/*<<<*/
		return (val($val)) ? true : false;
	}
/*>>>*/
	function check_image_extensions () {/*<<<*/
		global $image_extensions;
		if (is_array($image_extensions)) {
			return;
		}
		if (is_array($_SESSION) 
			&& array_key_exists("image_extensions", $_SESSION)) {
			$image_extensions = $_SESSION["image_extensions"];
		} elseif (function_exists("SGetVarByName") 
			&& (SGetVarByName("image_extensions") != "")) {
			$image_extensions = SGetVarByName("image_extensions");
		} elseif (is_array($GLOBALS) &&
					array_key_exists("image_extensions", $GLOBALS)
					&& $GLOBALS["image_extensions"] != "unset") {
			$image_extensions = $GLOBALS["image_extensions"];
		} else {
			$image_extensions = array("gif", "png");
		}
		if (!is_array($image_extensions)) {
			$image_extensions = explode(",", $image_extensions);
		}
	}
/*>>>*/
function get_check($bool) { // <<<
	if ($bool) {
		return " checked";
	} else {
		return "";
	}
}
// >>>
if (!function_exists("std_tlc_conn")) { // <<<
	function std_tlc_conn () {
		print("<!-- connecting to TLC server ".$GLOBALS["Server_host"].", port: ".$GLOBALS["Server_port"]." -->");
		flush();
		return tlc_connect($GLOBALS["server_host"], $GLOBALS["server_port"]);
	}
}
// >>>
if (!function_exists("redirect")) { // <<<
	function redirect($url, $delay=2) {
		?><p style="text-align:center"><?
		if (function_exists("msg") && msg_exists("msg_default_redir")) {
			print(str_replace("[URL]", $url, msg("msg_default_redir")));
		} else {
			print("You are being redirected. If nothing seems to happen, "
				."please click <a href=\"".$url."\">here</a>");
		}
		?></p>
		<meta http-equiv="REFRESH" content="<?=$delay?>;URL=<?=$url?>">
		<script language="Javascript">
			function redir() {
				window.location='<?=$url?>';
			}
			window.setTimeout('redir()', <?=$delay*1000?>);
		</script>
		<?
	}
}
// >>>
function redirect_top($url, $delay=0) { // <<<
	if ($url == $_SERVER["REQUEST_URI"]) return;
	?>
	<script language="Javascript">
		function redir() {
			window.parent.location='<?=$url?>';
		}
		window.setTimeout('redir', <?=$delay*1000?>);
	</script>
	<?
}
// >>>
if (!function_exists("val")) {	// <<<
	function val($strIn) {
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
						$C=substr($strIn, $i, 1);
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
						$C=substr($strIn, ++$i, 1);
					}
					$RET=ltrim($RET, " 0");
					if ($RET=="") $RET=0;
					if ($bNegative) {
						return "-".$RET;
					} else {
						return $RET;
					}
				} else {
					return 0;
				}
			}
		}
	}
}
// >>>
function parray($arr) { // <<<
	if (is_array($arr)) {
		print("Dumping array: <br>");
		foreach ($arr as $idx=>$val) {
			print($idx.": ".$val."<br>");
		}
	} else {
		print("Could not dump scalar variable.");
	}
}
// >>>
if (!function_exists("showtitle")) { // <<<
	function showtitle($msg) {
	// purpose: to do the tedious task of the title, onmouseover and onmouseout
	//	elements of an anchor tag
		$msg=trim($msg);
		if (strlen($msg)) {
			return " title=\"".$msg."\" onmouseover=\"window.status='".$msg."'; return true;\" onmouseout=\"window.status=''; return true;\"";
		} else {
			return "";
		}
	}
}
// >>>
if (!function_exists("find_graphic")) {
	function find_graphic($fn) {/*<<<*/
		if (file_exists($fn)) return $fn;
		check_image_extensions();
		$splitname = explode(".", $fn);
		global $image_extensions;
		if (!is_array($image_extensions)) 
			$image_extensions = array("gif", "png");
		if (!in_array(strtolower($splitname[count($splitname)-1]), 
			$image_extensions)) {
			foreach ($image_extensions as $ext) {
				$ret = find_file($fn.".".$ext);
				if (file_exists($ret)) {
					return $ret;
				}
			}
		}
		return "";
	}
}
/*>>>*/
if (!function_exists("find_file")) {
	function find_file($fn) {/*<<<*/
		if (file_exists($fn)) return $fn;
		global $search_path;
		if (!is_array($search_path)) {
			$search_path = split(":", get_include_path());
		}
		foreach ($search_path as $sidx => $sp) {
			if (substr($sp, strlen($sp)-1, 1) != "/") {
				$search_path[$sidx] = $sp."/";
			}
		}
		foreach ($search_path as $p) {
			if (file_exists($p.$fn)) {
				return $p.$fn;
			}
			if (file_exists($p."images/".$fn)) {
				return $p."images/".$fn;
			}
		}
		return "";
	}
}
/*>>>*/
function anchor($href, $link, $msg="-", $img = "") { // <<<
// creates an anchor tag, given an href, a link title to click and an optional
//	title/statusbar text attribute.
	if ($msg == "-") {
		$msg=$link;
	}
	if ($img != "") {
		$img = find_graphic($img);
		if ($img != "") {
			$img = "<img src=\"".$img."\">&nbsp;";
		}
	}
	if (array_key_exists("skey", $_GET)) {
		$tmp["skey"] = $_GET["skey"];
		$href = alter_url($href, $tmp);
	}
	return $img."<a href=\"".$href."\"".showtitle($msg).">".$link."</a>";
}
// >>>
function post($varname) { // <<<
	if (is_array($_POST)) {
		if (array_key_exists($varname, $_POST)) {
			return $_POST[$varname];
		} else {
			return false;
		}
	} else {
		return false;
	}
}
// >>>
function request($varname) { // <<<
// like the Request object in asp, this gets the variable by retrieving the
//	first non-empty variable from the $_REQUEST and $_POST arrays
	$ret="";
	if (is_array($_GET)) {
		if (array_key_exists($varname, $_GET)) {
			$ret=$_GET[$varname];
		}
	}
	if ($ret=="") {
		if (is_array($_POST)) {
			if (array_key_exists($varname, $_POST)) {
				$ret=$_POST[$varname];
			}
		}
	}
	return $ret;
}

// >>>
if (!function_exists("requested_url_base")) { // <<<
	function requested_url_base() {
		$U=$_SERVER["REQUEST_URI"];
		$aU=explode("?", $U);
		$U=$aU[0];
		return "http://".$_SERVER["HTTP_HOST"].$U;
	}
}
// >>>
if (!function_exists("requested_url")) { // <<<
	function requested_url() {
		$U=$_SERVER["REQUEST_URI"];
		return "http://".$_SERVER["HTTP_HOST"].$U;
	}
}
// >>>
function dateadd($part, $val, $timestamp) { // <<<
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
// >>>
function days_in_month($timestamp) { // <<<
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
// >>>
if (function_exists("tlc_req_sync")) {
	// some functions that will rely on a standard tlc connection
	function srs($module, $cmd, $data = "") {/*<<<*/
		global $svr;
		return tlc_req_sync($svr, $module, $cmd, $data);
	}
	/*>>>*/
	function get_seclevel_options ($modname="") { // <<<
		// returns an array of available security levels for the system
		if ($modname == "") {
			$qpath="settings";
		} else {
			$qpath="settings/${modname}";
		}
		$res=srs("hconf", "load", $qpath);
		if (strlen($res)) {
			array_set($set, $res);
			if (array_key_exists("max_user_level", $set)) {
				$max=val($set["max_user_level"]);
				if ($max == 0) $max=10;
			} else {
				$max=10;
			}
			for ($i=0; $i< $max; $i++) {
				$sl[]=$i+1;
			}
			return $sl;
		} else {
			// default: 1 to 10
			return array(1,2,3,4,5,6,7,8,9,10);
		}
	}
	// >>>
	function user_exists ($login) {/*<<<*/
		global $svr;
		include_once("include/tlchdobj.php");
		$obj = new TLCHDObj(array(
			"svrobj"	=>	$svr,
			"module"	=>	"user",
			"cmd"		=>	"user_exists",
			"data"		=>	array("login" => $login),
		));
		return $obj->exists;
	}
/*>>>*/
	function group_exists ($groupname) {/*<<<*/
		global $svr;
		include_once("include/tlchdobj.php");
		$obj = new TLCHDObj(array(
			"svrobj"	=>	$svr,
			"module"	=>	"user",
			"cmd"		=>	"group_exists",
			"data"		=>	array("group" => $login),
		));
		return $obj->exists;
	}
/*>>>*/
}
function lpadstring ($str, $len, $padwith) { // <<<
	while (strlen($str)<$len) {
		$str=$padwith.$str;
	}
	return $str;
}
// >>>
function resolve_username($userid) {/*<<<*/
	return get_user_attrib($userid, "username");
}
/*>>>*/
function get_user_attrib($userid, $attrib) {/*<<<*/
	return srs("user", "get_user_attrib", tcl_list($userid, $attrib));
}
/*>>>*/
function set_if_blank(&$arr, $idx, $val) {/*<<<*/
	if (!is_array($arr)) $arr=array();
	if (!array_key_exists($idx, $arr) || $arr[$idx] == "") {
		$arr[$idx] = $val;
	}
}
/*>>>*/
function sp_pad ($str) {/*<<<*/
	// space-pads a string on the left if it's not empty
	if ($str=="") {
		return "";
	} else {
		return " ".$str;
	}
}
/*>>>*/
function alterurl(&$replacers, $url="") {/*<<<*/
	// original interface -- was extended to allow direct modification on url
	if ($url == "") $url = requested_url();
	return alter_url($replacers, $url);
}
/*>>>*/
function alter_url(&$replacers, &$url) {/*<<<*/
/* About this function: <<<
	takes the current URL (if none sent)
	and modifies it with the array of replacers. Good for
	pages that allow re-ordering of columns or something. Remember that this
	function can only change the $_GET array that the page receives.
>>> */
	if ((!isset($url)) || ($url=="")) {
		$url=requested_url_base();
		$args_array=$_GET;
	} else {
		$tmp=explode("?", $url);
		$args_array=array();
		if (count($tmp) > 1) {
			$tmp2=explode("&", $tmp[1]);
			foreach ($tmp2 as $val) {
				$tmp3=explode("=", $val);
				$args_array[$tmp3[0]]=$tmp3[1];
			}
		}
	}
	$url=$tmp[0];
	$q=true;
	if (!is_array($replacers)) return $url;
	foreach ($replacers as $idx => $val) {
		$args_array[$idx] = $val;
	}
	$tmp = explode("?", $url);
	$url = $tmp[0];
	$q = false;
	foreach ($args_array as $idx => $val) {
		if ($q) {
			$url.="&";
		} else {
			$url.="?";
			$q = true;
		}
		$url.=$idx."=".$val;
	}
	return $url;
}
/*>>>*/
function lead_space(&$str) {/*<<<*/
	if (strlen($str)) {
		if (substr($str, 0, 1) != " ") {
			$str=" ".$str;
		}
	}
}
/*>>>*/
function tlc_table_query($module, $cmd, $data, $settings) {/*<<<*/
	include_once("include/tlchdobj.php");
	$tmp = new TLCHDObj(array(
		"module"	=>	$module,
		"cmd"		=>	$cmd,
		"data"		=>	$data,
	));
	//$tmp->dump_errors();
	// showattribs basically converts attribs to mvars, since the table
	//	render function only looks at elements, not at their attributes
	if (array_key_exists("showattribs", $settings)) {
		foreach ($settings["showattribs"] as $attrib) {
			$tmp->attrib_to_mvar($attrib);
		}
	}
	foreach ($tmp->list_childnames() as $cname) {
		$tmp->sort_children($cname, "rownum", "attrib");
	}
	$tmp->sort_mvars("fieldorder");
	render_tlc_table($tmp->toXML(), $settings);
}
/*>>>*/
function render_tlc_table($res, $settings) {/*<<<*/
/* About this function, and how to use it <<<
	Purpose: to render an html table based on the raw output from a tlc module
	inputs: $res -- raw output
			$settings -- array of settings to follow.
				* no_rec_msg: message to display when there are no records
							default is ""
				* allow_reorder: allows column re-ordering by clicking on the th
							causes a reload of current page with order and 
							orderorder set: up to the calling page to handle
							default is 1
				* headings: array of heading names. If not present (default)
							then the function assumes that the first row 
							returned is the heading row, and treats it as such
							default: not present
							addendum: xml input defines the fields...
				* no_headings: simple boolean to override heading capture
							default: 0
				* table_style:	anything you want to appear in the <table>
							definition.
							default: cellspacing="2" cellpadding="2"
				* td_style: anything you want to appear in the <td> tag.
							default is empty (ie follows stylesheet)
				* th_style: <th> style settings
				* reorder_title: title to display on mouseover of reorderable
							columns. Defaults to reading from the message
							catalog ("msg_col_reorder")
				* follow_url:	base url to apply to one or more table fields
							as a click-through url.
							default is ""
				* follow_url_idname: field name to use on the follow through
							for the id value to pass through.
							default is "id"
				* click_fields: list of field names or indeces that will have
							click-through capabilities, delimited with ;
							default is just the first field (0)
				* click_title: title to display over a clickable field;
				* centered:	boolean, determines whether or not the table is
							centered on the page: I thought this was something
							that should be more easily changed than by changing
							the table_style; still, if an alignment is found
							in the table style, it overrides this directive.
							default: centered.
				* cap_headings: often column headings will come back as lower
							case only. This gives an initial capital to each
							heading
				* hide_id:	hide the id column? defaults to 1
				* id_col:	name / id of id column. Given a name, a search is
							done over the headings (either gleaned or given)
				* head_override: typically, if you don't suggest headings, the
							column headings are gleaned from the first row
							of the result set (meaning, we assume the first row
							to be the headings). Setting the headings has the
							opposite effect. If you want to be able to override
							one or more of the heading values in a result set
							that has headings, set the headings array, and turn
							this on.
				* hashrows:	rows are in a hash-array format: we will get col
								names from the rows, and do things a little
								differently
>>>*/
	// glean caller's requirements
	set_if_blank($settings, "allow_reorder", 1);
	set_if_blank($settings, "no_headings", 0);
	set_if_blank($settings, "table_style", "cellpadding=\"2\" "
		."cellspacing=\"0\"");
	set_if_blank($settings, "centered", 1);
	set_if_blank($settings, "follow_url_idname", "id");
	set_if_blank($settings, "follow_url", requested_url());
	set_if_blank($settings, "click_fields", 1);
	set_if_blank($settings, "cap_headings", 1);
	set_if_blank($settings, "hide_id", 1);
	set_if_blank($settings, "id_col", 0);
	set_if_blank($settings, "head_override", 0);
	set_if_blank($settings, "xml", 0);
	set_if_blank($settings, "hashrows", 0);
	set_if_blank($settings, "limits", array());
	set_if_blank($settings, "valmap", array());
	set_if_blank($settings, "click", array());
	set_if_blank($settings, "colids", array());
	set_if_blank($settings, "underscore2space", 1); // only affects headings
	set_if_blank($settings, "skipfields", array());
	set_if_blank($settings, "tablestyle", array());
	set_if_blank($settings, "td_style", "vertical-align: top;");
	set_if_blank($settings, "toolbar", array());
	set_if_blank($settings, "no_rec_msg", "There are no records to display");
	
	if (strpos("style=", $settings["td_style"]) === false) {
		$settings["td_style"] = "style=\"".$settings["td_style"]."\"";
	}
	// neatening and stuff
	if (strpos("align", $settings["table_style"]) === false) {
		$settings["table_style"].=" align=center";
	}
	lead_space($settings["table_style"]);
	lead_space($settings["td_style"]);
	lead_space($settings["th_style"]);
	if (function_exists("msg")) {
		set_if_blank($settings, "reorder_title", msg("msg_col_reorder"));
	}

	if (is_xml($res)) {
		$settings["xml"] = 1;
	} else {
		$settings["xml"] = 0;
	}
	if ($settings["xml"]) {
		$xmlds = new XML_DS(array("xml" => $res));
		$rows = $xmlds->all_rows;
		if (is_array($rows) && (count($rows) > 0)) {
			$settings["hashrows"] = 1;
		} else {
			print("<div style=\"width: 60%; border: none; "
				."padding: 10px; margin: auto\">"
				."<p style=\"text-align: center\">".$settings["no_rec_msg"]
				."</p></div>");
			return;
		}
	} else {
		array_set_1d($rows, $res);
		if (count($rows) ==1) {
			// we only have headers -- print out the no_rec_msg
			print("<table".$settings["table_style"]."><tr><td"
				.$settings["td_style"].">".$settings["no_rec_msg"]
				."</td></tr></table>");
			return;
		}
	}
	if (array_key_exists("header", $settings)) {
		print("<h3>".$settings["header"]."</h3>");
	}
	if ($settings["hashrows"]) {
		if (is_array($rows[0])) {
			foreach ($rows[0] as $idx => $val) {
				$cols[] = $idx;
			}
			$settings["headings"] = $cols;
		} else {
			print("<table".sp_pad($settings["table_style"])."><tr><td>"
				.$settings["no_rec_msg"]."</td></tr></table>");
			return;
		}
	}
	print("<table".sp_pad($settings["table_style"]).">");
	$current_order=$_GET["order"];
	$current_orderorder=$_GET["orderorder"];
	if ($current_orderorder != "desc") $current_orderorder="asc";
	
	if ($settings["follow_url"] == "") {
		$click_fields=array();
	} else {
		$click_fields=explode(";", $settings["click_fields"]);
	}
	if ($settings["no_headings"] == 0) {
		print("<thead><tr>");
		if (!is_array($settings["headings"])) {
			array_set_1d($settings["headings"], $rows[0]);
			$start_idx=1;
		} else {
			if ($settings["head_override"]) {
				// check for left out cols in override
				foreach ($rows[0] as $idx => $val) {
					if ($settings["headings"][$idx] == "") {
						$settings["headings"][$idx] = $val;
					}
				}
				$start_idx=0;
			} else {
				$start_idx=0;
			}
		}
		foreach ($settings["headings"] as $idx=>$col) {
			if (in_array($col, $settings["skipfields"])) continue;
			if ($settings["hide_id"]) {
				if ($settings["id_col"] == $idx) continue;
			}
			if (array_key_exists($col, $settings["tablestyle"])) {
				$colstyle = " style=\"".$settings["tablestyle"][$col]."\"";
			} else {
				$colstyle = "";
			}
			print("<th".sp_pad($settings["th_style"]).$colstyle.">");
			if ($settings["allow_reorder"]) {
				if ($current_order == $col) {
					if ($current_orderorder == "asc") {
						$orderorder="desc";
					} else {
						$orderorder="asc";
					}
				} else {
					$orderorder="asc";
				}
				$tmp["order"]=$col;
				$tmp["orderorder"]=$orderorder;
				print("<a href=\"".alterurl($tmp)."\" title=\"".
					$settings["reorder_title"]."\">");
			}
			// translation on heading, if available
			if (function_exists("msg_exists")) {
				if (msg_exists("table_head_".$col)) {
					if (function_exists("msg")) {
						$col = msg("table_head_".$col);
					}
				}
			}
			if ($settings["underscore2space"]) {
				$col = str_replace("_", " ", $col);
			}
			if ($settings["cap_headings"]) {
				$col = (ucfirst($col));
			} 
			print($col);
			if ($settings["allow_reorder"]) {
				print("</a>");
			}
			print("</th>");
		}
		if (is_array($settings["toolbar"])) {
			print("<th></th>");
		}
		print("</tr></thead>");
		if (!is_num($settings["id_col"])) {
			$set=false;
			foreach ($settings["headings"] as $idx => $val) {
				if (strcasecmp($val, $settings["id_col"]) == 0) {
					$settings["id_col"] = $idx;
					$set=true;
					break;
				}
			}
			if (!$set) {
				print("turning off id col hiding");
				$settings["id_col"] = -1;
				$settings["hide_id"] = 0; // since the id_col ain't worth NEthing
			}
		}
	} else {
		$start_idx=0;
	}
	// resolving field names in click_fields to field numbers
if (count($click_fields) == 1)
	if ($click_fields[0] == "all") {
		$click_fields = array_keys($settings["headings"]);
	} else {
		foreach ($click_fields as $idx => $val) {
			if (!is_num($val)) {
				//search for this name in the header list
				$click_fields[$idx]=array_search($val, $settings["headings"]);
				if ($click_fields[$idx] === False) $click_fields[$idx]=-1;
			}
		}
	}
	print("<tbody>");
	if ($settings["hashrows"]) {
		$col_idx = 0;
		foreach ($rows[0] as $idx => $val) {
			if ($col_idx == $settings["id_col"]) {
				$idxcol_name = $idx;
				break;
			}
		}
		for ($i = $start_idx; $i < count($rows); $i++) {
			print("<tr".$settings["tr_style"].">");
			if (!is_array($rows)) {
				array_set($row, $rows[$i]);
			} else {
				$row = $rows[$i];
			}
			$col_idx=0;
			foreach ($row as $col => $val) {
				if ($settings["hide_id"]) {
					if ($settings["id_col"] == $col_idx) {
						$col_idx++;
						continue;
					}
				}
				if (in_array($col, $settings["skipfields"])) continue;
				if (array_key_exists($col, $settings["valmap"])) {
					// used to map booleans to yes/no; true/false, or checkbox
					switch ($settings["valmap"][$col]) {
						case "checkbox": {
							$lcol = strtolower($col);
							if ($val) {
								$checked = " checked";
							} else {
								$checked = "";
							}
							if (array_key_exists($lcol, $settings["click"])) {
								$code = " onclick=\"".$settings["click"][$col]
									."\"";
								// replace symbols in code with row values
								foreach ($row as $ridx => $rval) {
									$ridx = strtolower($ridx);
									$code = str_replace("[".$ridx."]", 
										$rval, $code);
								}
							} else {
								$code = "";
							}
							// allow arb script and form access.
							if (array_key_exists($lcol, $settings["colids"])) {
								$nameid = str_replace("[ROWID]", $i, 
									$settings["colids"][$lcol]);
								// replace symbols in code with row values
								foreach ($row as $ridx => $rval) {
									$ridx = strtolower($ridx);
									$nameid = str_replace("[".$ridx."]", 
										$rval, $nameid);
								}
							} else {
								$nameid = "autochk__"
									.str_replace(" ", "_", $col)."_".$i;
							}
							if (array_key_exists($lcol, $settings["colvals"])) {
								$chkval = str_replace("[ROWID]", $i,
									$settings["colvals"][$col]);
								foreach ($row as $ridx => $rval) {
									$ridx = strtolower($ridx);
									$chkval = str_replace("[".$ridx."]", 
										$rval, $chkval);
								}
							} else {
								$chkval = "1";
							}
							$val = "<center><input type=\"checkbox\" value=\""
								.$chkval."\""
								.$checked.$code." name=\"".$nameid
								."\" id=\"".$nameid."\"></center>";
							
							break;
						}
						default: {
							// any string set like <trueval>/<falseval>
							if (!isset($valopts) || !isset($valopts[$col])) {
								$valopts[$col] = split("/", 
									$settings["valmap"][$col]);
								if (count($valopts[$col]) != 2) {
									print("warning: valmap for $col changed to"
										." yes/no because "
										.$settings["valmap"][$col]." is not"
										."&quot;checkbox&quot; or a &lt;trueval"
										."&gt;/&lt;falseval&gt; string pair.");
									$valopts[$col] = array("yes", "no");
								}
							}
							if ($val) {
								$val = $valopts[$col][0];
							} else {
								$val = $valopts[$col][1];
							}
						}
					}
				} elseif (in_array($col, $settings["limits"])) {
					$val = shorten($val, $settings["limits"][$col]);
				}
				print("<td".sp_pad($settings["td_style"]).">");
				if (in_array($col_idx, $click_fields)) {
					$replacers[$settings["follow_url_idname"]]
						= $row[$idxcol_name];
					$tmpurl=$settings["follow_url"];
					alter_url($replacers, $tmpurl);
					print("<a href=\"".$tmpurl."\"");
					if ($settings["click_title"] != "") {
						print(" title=\"".$settings["click_title"]."\"");
					}
					print(">".$val."</a>");
				} else {
					print($val);
				}
				print("</td>");
				$col_idx++;
			}
			if (is_array($settings["toolbar"])) {
				include_once("include/toolbar.php");
				$tmp = new Toolbar();
				foreach ($settings["toolbar"] as $caption => $def) {
					foreach (array("url", "code") as $el) {
						if (!array_key_exists($el, $def)) continue;
						foreach ($row as $col => $val) {
							$pos = strpos("[".$col."]", $def[$el]);
							if ($pos !== false) {
							// allow escaping -- not perfect: one escape 
							//	prevents all. But this is quicker than a more
							//	correct solution
								if ($pos) {
									if (substr($def[$el], $pos-1, 1) == "\\")
										continue;
								}
							}
							$def[$el] = str_replace("[".$col."]", $val, 
								$def[$el]);
						}
					}
					foreach ($row as $col => $val) {
						$pos = strpos("[".$col."]", $caption);
						if ($pos !== false) {
							if ($pos) {
								if (substr($caption, $pos-1, 1) == "\\")
									continue;
							}
						}
						$caption = str_replace("[".$col."]", $val, $caption);
					}
					$btndef = array("caption" => $caption);
					foreach (array("img", "imgpos", "url", "code") as $el) {
						if (array_key_exists($el, $def)) {
							$btndef[$el] = $def[$el];
						}
					}
					$tmp->add_button($btndef);
				}
				print("<td>");
				$tmp->render();
				print("</td>");
			}
			print("</tr>");
		}
	} else {
		for ($i=$start_idx; $i<count($rows); $i++) {
			print("<tr".sp_pad($settings["tr_style"]).">");
			if (!is_array($rows[$i])) {
				array_set_1d($row, $rows[$i]);
			} else {
				$row = $rows[$i];
			}
			foreach ($row as $col_idx => $col) {
				if ($settings["hide_id"]) {
					if ($settings["id_col"] == $col_idx) {
						continue;
					}
				}
				print("<td".sp_pad($settings["td_style"]).">");
				if (in_array($col_idx, $click_fields)) {
					$replacers[$settings["follow_url_idname"]]
						= $row[$settings["id_col"]];
					$tmpurl=$settings["follow_url"];
					alter_url($replacers, $tmpurl);
					print("<a href=\"".$tmpurl."\"");
					if ($settings["click_title"] != "") {
						print(" title=\"".$settings["click_title"]."\"");
					}
					print(">".$col."</a>");
				} else {
					print($col);
				}
				print("</td>");
			}
			if (is_array($settings["toolbar"])) {
				include_once("include/toolbar.php");
				$tmp = new Toolbar();
				foreach ($settings["toolbar"] as $caption => $def) {
					foreach (array("url", "code") as $el) {
						if (!array_key_exists($el, $def)) continue;
						foreach ($row as $col => $val) {
							$pos = strpos("[".$col."]", $def[$el]);
							if ($pos !== false) {
							// allow escaping -- not perfect: one escape 
							//	prevents all. But this is quicker than a more
							//	correct solution
								if ($pos) {
									if (substr($def[$el], $pos-1, 1) == "\\")
										continue;
								}
							}
							$def[$el] = str_replace("[".$col."]", $val, 
								$def[$el]);
						}
					}
					foreach ($row as $col => $val) {
						$pos = strpos("[".$col."]", $caption);
						if ($pos !== false) {
							if ($pos) {
								if (substr($caption, $pos-1, 1) == "\\")
									continue;
							}
						}
						$caption = str_replace("[".$col."]", $val, $caption);
					}
					$btndef = array("caption" => $caption);
					foreach (array("img", "imgpos", "url", "code") as $el) {
						if (array_key_exists($el, $def)) {
							$btndef[$el] = $def[$el];
						}
					}
					$tmp->add_button($btndef);
				}
				print("<td>");
				$tmp->render();
				print("</td>");
			}
			print("</tr>");
		}
	}
	print("</tbody></table>");
}
/*>>>*/
function shorten ($str, $maxlen) {/*<<<*/
	if (strlen($str) > ($maxlen - 3)) {
		$str = substr($str, 0, ($maxlen - 3))."...";
	}
	return $str;
}
/*>>>*/
function array_set_head(&$arr, &$res) {/*<<<*/
// takes a tcl sql resource consisting of two lines:
//	first line is column headers
//	second line is column values
//	and returns a php array from that.
	array_set_1d($rows, $res);
	array_set_1d($coldefs, $rows[0]);
	array_set_1d($colvals, $rows[1]);
	for ($i=0; $i<count($coldefs); $i++) {
		$arr[$coldefs[$i]]=$colvals[$i];
	}
}
/*>>>*/
function post_or_default($idx, $def_val) {/*<<<*/
	if (array_key_exists($idx, $_POST)) {
		return $_POST[$idx];
	} else {
		return $def_val;
	}
}
/*>>>*/
function first_field(&$str) {/*<<<*/
	$i=strpos($str, " ");
	if ($i===false) return $str;
	return substr($str, 0, $i);
}
/*>>>*/
function other_fields(&$str) {/*<<<*/
	$i=strpos($str, " ");
	if ($i===false) return $str;
	return substr($str, $i+1);
}
/*>>>*/
function array_or_default(&$arr, $idx, $def_val) {/*<<<*/
	if (!is_array($arr)) return $def_val;
	if (array_key_exists($idx, $arr)) {
		return $arr[$idx];
	} else {
		return $def_val;
	}
}
/*>>>*/
function clean_empty_vals (&$arr) {/*<<<*/
	foreach ($arr as $idx=>$val) {
		if ($val == "") {
			unset($arr[$idx]);
		}
	}
}
/*>>>*/
function create_tlc_user_select($defn) {/*<<<*/
	global $gusc;
	if ($gusc == "") {
		$gusc=0;
	} else {
		$gusc=$gusc++;
	}
	$val=array_or_default($defn, "value", "");
	$name=array_or_default($defn, "name", "user_select".$gusc);
	$id=array_or_default($defn, "id", $name);
	// when server information isn't given, the information is pulled from
	//	the GLOBALS array by the browse page itself -- more secure.
	$style=array_or_default($defn, "style", "");
	$url=array_or_default($defn, "url", "include/tlc_select.php");
	
	$r="<input name=\"".name."\" value=\"".$val."\" id=\"".$id."\"";
	if ($style != "") {
		if (strpos($style, ":") === false) {
			$r.=" class=\"".$style."\"";
		} else {
			$r.=" style=\"".$style."\"";
		}
	}
	$url_args=array(
		"module"	=> array_or_default($defn, "module", "user"),
		"cmd"		=> array_or_default($defn, "cmd", "test_list"),
		"data"		=> array_or_default($defn, "data", ""),
		"target"	=> $id,
		"port"		=> array_or_default($defn, "server_port", ""),
		"host"		=> array_or_default($defn, "server_host", ""),
		"wwidth"	=> array_or_default($defn, "wwidth", ""),
		"wheight"	=> array_or_default($defn, "wheight", ""),
		"cols"		=> "Users",
		"multi"		=> array_or_default($defn, "multi", ""),
	);
	clean_empty_vals($url_args);
	alter_url($url_args, $url);
	
	$r.="><input type=\"button\" onclick=\""
		."if (cw=window.open('".$url
		."', 'selecta', 'resize=0,status=0,toolbar=0,location=0,menubar=0,directories=0,scrollbars=0')) this.disabled=true; wait_for_close(cw, this);\""
		." value=\"...\" id=\"__btn_".$id."\">";
	print($r);
}
/*>>>*/
function is_num($str, $include_numeric_delimiters=0) {/*<<<*/
	$str=ltrim(rtrim($str));
	$j=strlen($str);
	if ($include_numeric_delimiters) {
		$valid_chars="0123456789";
	} else {
		$valid_chars="0123456789 ,-.";
	}
	for ($i=0; $i<$j; $i++) {
		if (strpos($valid_chars, $str[$i]) === false) {
			return false;
		}
	}
	return true;
}
/*>>>*/
function load_from_post(&$arr, $expectedfields) {/*<<<*/
/*
	takes care of loading an array from the post array, based on 
	an expectedfields definition that takes the form of
	POST_FIELD => WORK_FIELD
	where POST_FIELD is the name of the field in the _POST array
	and WORK_FIELD is the name of the field in the array to work with
*/
	foreach($expectedfields as $infld => $outfld) {
		if (array_key_exists($infld, $_POST)) {
			$arr[$outfld]=$_POST[$infld];
		} else {
			print("Error: this page expected a post field named $infld, but it"
				." was not received. Processing halted.");
			var_dump($_POST);
			die();
		}
	}
	return true;
}/*>>>*/
function post_2_tlcxml ($opts = array()) {/*<<<*/
/* about this function: <<<
	purpose: to take a list of post fields, create a 2-node deep xml document,
	and post it to the tlc server as per instructions in opt array
	inputs: 
		$opts: array of settings:
		Key			Meaning							Default Value
		source		source array for data			"post"
						possible sources: post, get
		svrobj		tlc server object				gets from global $svr
		module		name of tlc module to use		"" (causes an error/abort)
		cmd			name of module cmd to use		"" (causes an error/abort)
		format		xml document format to use		"hd" (2-node depth)
						could also use "ds", which will create a ds-compat.
						3-node deep document, normally reserved for tabular
						data.
		fields 		array of field names to 		empty array
						harvest from the (default) post	array
						can also use a white-space separated string, eg:
						"name descr foo bar"
		missing_fatal	boolean, describing			true
						whether or not missing fields are a fatal error
						if set to false, missing fields are reported
						as ""
		rootname	root name of output xml doc		"data"
		rowname	name of row nodes (ds only)		"row"
>>>*/
	global $svr;	// should have a global server object... thanks sanity.php
	set_if_blank($opts, "source", "post");
	set_if_blank($opts, "svrobj", $svr);
	set_if_blank($opts, "module", "");
	set_if_blank($opts, "cmd", "");
	set_if_blank($opts, "format", "hd");
	set_if_blank($opts, "fields", array());
	set_if_blank($opts, "missing_fatal", 1);
	set_if_blank($opts, "rootname", "data");
	set_if_blank($opts, "rowname", "row");
	set_if_blank($opts, "debug", 0);
	set_if_blank($opts, "remapped", 0);
	// make multi-selects look like they came from an asp page.
	set_if_blank($opts, "arraydelimiter", ",");
	// sanity checks
	if (($opts["module"] == "")) {
		die("post_2_tlcxml: no module specified");
	}
	if ($opts["cmd"] == "") {
		die("post_2_tlcxml: no cmd specified");
	}
	if (!in_array($opts["format"], array("hd", "ds"))) {
		die("post_2_tlcxml: bad format ".$opts["format"]." specified.");
	}
	if (!is_array($opts)) {
		$opts = explode(" ", $opts);
	}
	if (count($opts["fields"]) == 0) {
		die("post_2_tlcxml: no data field names given");
	}
	
	// get the source data in an array
	$src = array();
	$mustdie = false;
	switch ($opts["source"]) {
		case "get": {
			$deathsource = "_GET";
			if ((!is_array($_GET)) || (count($_GET) == 0)) {
				die("post_2_tlcxml: post array is empty");
			}
			if ($opts["remapped"]) {
				foreach ($opts["fields"] as $postfld => $fld) {
					if (array_key_exists($postfld, $_GET)) {
						if (is_array($_GET[$postfld])) {
						// handle multi-value inputs
							$src[$fld] = implode($opts["arraydelimiter"], 
								$_GET[$postfld]);
						} else {
							$src[$fld] = $_GET[$postfld];
						}
					} else {
						if ($opts["missing_fatal"]) {
							$mustdie = true;
							$missingfields[] = $fld;
						} else {
							$src[$fld] = "";
						}
					}
				}
			} else {
				foreach ($opts["fields"] as $fld) {
					if (array_key_exists($fld, $_GET)) {
						if (is_array($_GET[$fld])) {
							$src[$fld] = implode($opts["arraydelimiter"],
								$_GET[$fld]);
						} else {
							$src[$fld] = $_GET[$fld];
						}
					} else {
						if ($opts["missing_fatal"]) {
							$mustdie = true;
							$missingfields[] = $fld;
						} else {
							$src[$fld] = "";
						}
					}
				}
			}
			break;
		}
		case "post": 
		default: 	{
			$deathsource = "_POST";
			if ((!is_array($_POST)) || (count($_POST) == 0)) {
				die("post_2_tlcxml: post array is empty");
			}
			if ($opts["remapped"]) {
				foreach ($opts["fields"] as $postfld => $fld) {
					if (array_key_exists($postfld, $_POST)) {
						if (is_array($_POST[$postfld])) {
							$src[$fld] = implode($opts["arraydelimiter"],
								$_POST[$postfld]);
						} else {
							$src[$fld] = $_POST[$postfld];
						}
					} else {
						if ($opts["missing_fatal"]) {
							$mustdie = true;
							$missingfields[] = $fld;
						} else {
							$src[$fld] = "";
						}
					}
				}
			} else {
				foreach ($opts["fields"] as $fld) {
					if (array_key_exists($fld, $_POST)) {
						if (is_array($_POST[$fld])) {
							$src[$fld] = implode($opts["arraydelimiter"],
								$_POST[$fld]);
						} else {
							$src[$fld] = $_POST[$fld];
						}
					} else {
						if ($opts["missing_fatal"]) {
							$mustdie = true;
							$missingfields[] = $fld;
						} else {
							$src[$fld] = "";
						}
					}
				}
			}
		}
	}
	if ($mustdie) {
		print("Fatal error: the following fields are missing from the"
			." $deathsource array:<ul>");
		foreach ($missingfields as $fld) {
			print("<li>".$fld."</li>");
		}
		print("</ul>");
		die();
	}
	
	// get the xml data.
	switch ($opts["format"]) {
		case "ds": 	{
			if (file_exists($include_dir.DIRECTORY_SEPARATOR."tlcxml_ds.php")) {
				include_once($include_dir.DIRECTORY_SEPARATOR."tlcxml_ds.php");
			} else {
				die("Fatal error: cannot include tlcxml_ds.php");
			}
			$ds = new TLCXML_DS(array(
					"svrobj"	=>	$opts["svrobj"],
					"module"	=>	$opts["module"],
					"cmd"		=>	$opts["cmd"],
					"dsname"	=>	$opts["rootname"],
					"rowname"	=>	$opts["rowname"],
					"auto_query"=>	0,
				));
			$ds->addrow($src);
			// ds returns the result as an HDObj
			if ($opts["debug"]) {
				print("debug flag set in post array... debugging:<br>"
					.($ds->toXML(true, true)));
				var_dump($_POST);
				die();
			} else {
				return $ds->update();
			}
			break;
		}
		case "hd":
		default: 	{
			$include_dir = dirname(__FILE__);
			if (file_exists($include_dir.DIRECTORY_SEPARATOR."tlchdobj.php")) {
				include_once($include_dir.DIRECTORY_SEPARATOR."tlchdobj.php");
			} else {
				die("Fatal error: cannot include tlchdobj.php");
			}
			$hd = new TLCHDObj(array(
				"svrobj"	=>	$opts["svrobj"],
				"module"	=>	$opts["module"],
				"cmd"		=>	$opts["cmd"],
				"dsname"	=>	$opts["rootname"],
				"auto_query"=>	0,
			));
			$hd->load_from_array($src);
			if ($opts["debug"]) {
				print("debug flag set in post array... debugging:<br>"
					.$hd->toXML(true, true));
				var_dump($_POST);
				die();
			} else {
				$hd->query();
			}
			// HDObj loads the result into itself
			return $hd;
		}
	}
}
/*>>>*/
function col_array_data(&$arr) {/*<<<*/
/*	about this function <<<
	purpose: to deliver the data line that will be interpreted by a
	tlc module using load_col_array. This line has one required list
	of field values and an optional list which names the field values.
	I would prefer to always use both, since it allows the flexibility of
	(1) partial data settings
	(2) no constraint on field orders
	the output could look something like:
	{{field1 val} {field2 val}} {field1 field2}
>>> */
	$part1=array_val_get(array_values($arr));
	$part2=array_val_get(array_keys($arr));
	$data="";
	//print("part1: ".$part1."<br>");
	//print("part2: ".$part2."<br>");
	lappend($data, $part1);
	lappend($data, $part2);
	$data=str_replace("\\{", "{", str_replace("\\}", "}", $data));
	return $data;
}
/*>>>*/
function get_data_grid($tlc_resource) {/*<<<*/
// purpose: to transform a properly-formatted list of lists into 
//	a 2 php array
	array_set_1d($tclrows, $tlc_resource);
	for($i=0; $i<count($tclrows); $i++) {
		array_set_1d($tmp, $tclrows[$i]);
		$rows[]=$tmp;
	}
	return $rows;
}
/*>>>*/
function get_data_grid_head($tlc_resource) {/*<<<*/
// as for the function above, but this function assumes that the first row
//	is headings for the grid (giving field names)
	array_set_1d($tlcrows, $tlc_resource);
	$head = array_slice($tclrows[0], 0);
	for ($i=1; $i<count($tclrows); $i++) {
		foreach ($tclrows[$i] as $idx => $val) {
			$rows[$head[$idx]] = $val;
		}
	}
	return $rows;
}
/*>>>*/
function xmlwrap($data, $docname="dataset", $elemname="id") {/*<<<*/
	include_once("include/hdobj.php");
	$tmp = new HDObj();
	$tmp->__name = $docname;
	if (is_array($data)) {
		$tmp->load_from_array($data);
	} else {
		$tmparray = array();
		$tmparray[$elemname] = $data;
		$tmp->load_from_array($tmparray);
	}
	return $tmp->toXML();
}
/*>>>*/
	function is_xml($str) {/*<<<*/
		// simplistic function to determine whether or not a given string
		//	is xml. Not idiot-proof.
		$str = trim($str);
		// blindly trust a "<?xml" header
		if (substr($str, 0, 5) == "<?xml") return true; //simple case
		// find first tag
		$len = strlen($str);
		$tag = "";
		$intag = false;
		for ($i = 0; $i < $len; $i++) {
			if (ctype_alnum($str[$i])) {
				if ($intag) {
					$tag.=$str[$i];
				} else return false;
			} else {
				if ($intag) {
					break;
				} else {
					if ($str[$i] == "<") {
						$intag = true;
					} else {
						return false;
					}
				}
			}
		}
		// find matching close tag -- also do a lame check for trailing chars
		$endtag = "</".$tag.">";
		$pos = strpos($str, $endtag);
		if ($pos == ($len - strlen($endtag))) {
			return true;
		} else {
			return false;
		}
	}
/*>>>*/
	function get_relative_dir($relative_to, $src_dir, $dir_sep="/") {/*<<<*/
		// given two paths, with the same root dir, this should give back
		//	the path to the $src_dir from $relative_to in a relative path
		//	style. dir_sep is for the output: you only need to change it
		//	if you don't want a *nix / web relative path (unlikely)
		$relative_to = str_replace(DIRECTORY_SEPARATOR, "/", $relative_to);
		$src_dir = str_replace(DIRECTORY_SEPARATOR, "/", $src_dir);
		$ardir = explode("/", $relative_to);
		$asdir = explode("/", $src_dir);
		
		if ($ardir[0] != $asdir[0]) {
			// root path doesn't match: can't be sure about relative paths then
			return "__cannot__compare__(".$relative_to."):(".$src_dir.")";
		}
		
		$aodir = array();
		for ($i = 0; $i < count($ardir); $i++) {
			if ($i == count($asdir)) break;
			if ($ardir[$i] != $asdir[$i]) break;
		}
		// ok, we're at a point where either we ran out of sdir, or we have a 
		//	non-match on the path element i in rdir and sdir
		if ($i == count($asdir)) {
			// sdir is simply a parent dir of rdir -- get the ..'s required
			if ($i == count($ardir)) {
				// simple case: rdir *is* sdir
				return ".";
			}
			for ($x = $i; $x < count($ardir); $x++) {
				$aodir[] = "..";
			}
			return trim(implode($dir_sep, $aodir), " $dir_sep");
		} else {
			// tricker: there is a shared portion of parenting in the path,
			//	but we have a deviation at node $i
			//first, get the ..'s
			
			for ($x = $i; $x < count($ardir); $x++) {
				$aodir[] = "..";
			}
			// now, the rest of sdir
			for ($x = $i; $x < count($asdir); $x++) {
				$aodir[] = $asdir[$x];
			}
			return trim(implode($dir_sep, $aodir), " $dir_sep");;
		}
		
	}
/*>>>*/
	function hdobj2htmltree(&$hdobj, &$options) {/*<<<*/
		/* about the function <<<
			This function takes in an hd object, and provides
			a navigable html tree structure out of it, based on some
			special settings:
			each node has an attribute: type. If not set, then it defaults to
			the value "node", but it can also be one of "col" (a textural
			column, which can have a click-through url) or "button" (a button 
			which can have a click-through url, or javascript code)
			"col" and "button" types are not treated as branches on the tree --
			instead, they are put in line with their parent node
			xml prototype:
			<node>
				<node>
					<name>Node Name</name>
					<title>Mouse-over Title</title>
					<url>Click-through url</url>
					<node type="col">
						<name>Node Col 1</name>
						<title>Title of column</title>
						<url>Click-through URL</url>
					</node>
					<node type="button>
						<name>Caption on button</name>
						<url type="popup">popup window url</url>
							[ OR ]
						<url {type="inline"}>inline url</url>
							[ OR ]
						<js>javascript function -- put your code elswhere</js>
					</node>
				</node>
			</node>
		>>> */
		if (strtolower(get_class($hdobj)) != "hdobj") {
			print("Error: cannot use object of class: ".get_class($hdobj)
				." to construct html tree with this function.<br>");
			return;
		}
		
		$options["imgdir"] = $this->arrdef($options, "imgdir", "images");
		if (!file_exists($imgsdir)) {
			if (array_key_exists("HTMLTree_imgsdir", $GLOBALS)) {
				$options["imgsdir"] = $GLOBALS["HTMLTree_imgdir"];
			}
		}
		$options["style"] = $this->arrdef($options, "style");
		$options["img_open"] = $this->img_loc($this->arrdef($options,
			"img_open", "open.gif"), $options["imgdir"],
				$options["style"]);
		$options["img_opena"] = $this->img_loc($this->arrdef($options,
			"img_opena", $options["img_open"]), $options["imgdir"],
				$options["style"]);
		$options["img_closed"] = $this->img_loc($this->arrdef($options,
			"img_closed", "closed.gif"), $options["imgdir"],
				$options["style"]);
		$options["img_closeda"] = $this->img_loc($this->arrdef($options,
			"img_closeda", "closeda.gif"), $options["imgdir"],
				$options["style"]);
		$options["img_node"] = $this->img_loc($this->arrdef($options,
			"img_node", "node.gif"), $options["imgdir"],
				$options["style"]);
		$options["open_level"] = $this->arrdef($options, "open_level", 1);
		$r = hdobj2htmltree_nr($hdobj, $options);
		$options["debug"] = $this->arrdef($options, "debug", 0);
		if ($options["debug"]) {
			var_dump($options);
			print("<br>");
			print(htmlentities($r));
			print("<br>");
			print($r);
		} else {
			print($r);
		}
	}
/*>>>*/
	function hdobj2htmltree_nr(&$hdobj, &$options, $level=0) {/*<<<*/
	}
/*>>>*/
	function aasort(&$aarays, $key) {/*<<<*/
		if (!is_array($aarays)) return;
		$topa = count($aarays);
		for ($i = 0; $i < count($aarays); $i++) {
			for ($j = 1; $j < $topa; $j++) {
				if (!is_array($aarays[$j])) continue;
				if (array_key_exists($key, $aarays[$j])) {
					$valj = $aarays[$j][$key];
				} else {
					$valj = "";
				}
				if (array_key_exists($key, $aarays[$j-1])) {
					$valj_1 = $aarays[$j-1][$key];
				} else {
					$valj_1 = "";
				}
				if ($valj < $valj_1) {
					$tmp = $aarays[$j-1];
					$aarays[$j-1] = $aarays[$j];
					$aarays[$j] = $tmp;
				}
			}
			$topa--;
		}
	}
/*>>>*/
	function printn($str) {/*<<<*/
		// just appends a \n onto a string and prints it -- for easier
		//	source debugging
		print($str."\n");
	}
/*>>>*/
function phparray_2_jsarray ($arr, $jsarrname, $printout = 1) {/*<<<*/
	$r = "<script language=\"Javascript\">\n";
	$r.="if (typeof(".$jsarrname.") == \"undefined\") "
		.$jsarrname." = new Array();\n";
	foreach ($arr as $idx => $val) {
		if (!is_numeric($idx)) {
			$idx = "\"".$idx."\"";
		}
		$r.=$jsarrname."[".$idx."] = \"".str_replace("\"", "&quot;", 
			str_replace("&", "&amp;", str_replace("\n", "<br>", $val)))."\";\n";
	}
	$r.="</script>";
	if ($printout) print($r);
	return $r;
}
/*>>>*/
function do_defined_tlc_func ($options = array()) {/*<<<*/
	/*	about the function: <<<
		purpose: to do a tlc interaction, based on options set in the post arr
		inputs: optional array with the following keys set (overridden by same
				elements of the $_POST array):
			module				tlc server module to talk to
			cmd					command to call on that module
			fields				array of field names to look for in post array
			field_delimiter		string which delimits field names in "fields"
								-- defaults to ;
			success_redirect	page to redirect to on success
			success_title		title on page after success (default: Success!)
			success_msg			message to display on success. May contain
									symbolic replacements in the form:
									[varname]
			fail_title			title on page when something goes wrong 
									(default: Sorry!)
			fail_msg			message when things go pear-shaped (default:
									An error occurred (followed by dump from
									xml return)
			allow_missing 		a list of fields specified to save, which may
									be missing without consequence. Useful
									if you want to cut information to be saved
									from the source form: disable the input
									and it won't be in the post array; so
									if you add it in to this parameter, the
									function will happily skip the field.
									USE AT OWN RISK -- if you are really 
									missing the field from your form, this
									function can't tell.
>>>

template of hidden fields:<<<
<!-- required: -->
<input type="hidden" name="module" value="">
<input type="hidden" name="cmd" value="">
<input type="hidden" name="fields" value="">
<!-- recommended: -->
<input type="hidden" name="success_redirect" value="">
<input type="hidden" name="success_title" value="">
<input type="hidden" name="success_msg" value="">
<input type="hidden" name="success_vars" value="">
<input type="hidden" name="fail_title" value="">
<input type="hidden" name="fail_msg" value="">
<!-- optional: -->
<input type="hidden" name="allowmissing" value="">
<input type="hidden" name="field_delimiter" value=";">
<input type="hidden" name="host" value="localhost">
<input type="hidden" name="port" value="1234">
<input type="hidden" name="rootname" value="data">
<!-- translation fields (requires one fld_trans_<fieldname>
	for each field therafter): translations are when you want the server
	to do some processing before using the field value. Currently supported
	translations are:
		md5
	-->
<input type="hidden" name="fld_trans_" value="md5">

// and the above, as wizard hidden input definitions
$w->addhinput("module", "");
$w->addhinput("cmd", "");
$w->addhinput("fields", "");
$w->addhinput("success_redirect", "");
$w->addhinput("success_title", "");
$w->addhinput("success_msg", "");
$w->addhinput("fail_title", "");
$w->addhinput("fail_msg", "");
$w->addhinput("field_delimiter", ";");
$w->addhinput("host", "localhost");
$w->addhinput("port", "1234");
$w->addhinput("rootname", "data");
$w->addhinput("fld_trans_", "");
>>>
	*/

	// paranoia...
	if (!is_array($options)) {
		print("do_defined_tlc_func called with non-array argument:<br>");
		var_dump($options);
		die();
	}
	$required_options = array("module", "cmd", "fields");
	foreach ($required_options as $r) {
		if (array_key_exists($r, $_POST)) {
			$options[$r] = $_POST[$r]; // allow these fields to be set in 
										//	the opts
		} else {
			$missing_options[] = $r;
		}
	}
	
	if (isset($missing_options)) {
		print("do_defined_tlc_func: one or more missing options:");
		foreach ($missing_options as $m) {
			print("<br>$m");
			die();
		}
	}
	// ok, on to some actual work!
	if (!array_key_exists("fields", $options)) {
		if (array_key_exists("fields", $_POST)) {
			$options["fields"] = $_POST["fields"];
		} else {
			die("do_defined_tlc_func: no fields defined in options or post "
				."array.");
		}
	}
	// optional settings
	if (!array_key_exists("allowmissing", $options)) {
		if (array_key_exists("allowmissing", $_POST)) {
			$options["allowmissing"] = $_POST["allowmissing"];
		} else {
			$options["allowmissing"] = "";
		}
	}
	$optional_settings = array("field_delimiter" => ";", "hash" => 0, 
		"rootname" => "data");
	foreach ($optional_settings as $setting => $default) {
		if (!array_key_exists($setting, $options)) {
			if (array_key_exists($setting, $_POST)) {
				$options[$setting] = $_POST[$setting];
			} else {
				$options[$setting] = $default;
			}
		}
	}
	$fields = explode($options["field_delimiter"], $options["fields"]);
	$allowmissing = explode($options["field_delimiter"], 
		$options["allowmissing"]);
	$allowedmissing = array();
	if (array_key_exists("hash", $options) && $options["hash"]) {
		// allow hashing of post fields to other TLC fields -- use with
		//	caution!
		foreach ($fields as $idx => $postfld) {
			if (array_key_exists($postfld, $_POST)) {
				if (array_key_exists("fld_hash_".$postfld, $_POST)) {
					$tlcfld = $_POST["fld_hash_".$postfld];
					$fields[$idx] = $tlcfld;
					if (in_array($postfld, $allowmissing)) {
						foreach ($allowmissing as $idx => $val) {
							if ($val == $postfld) {
								$allowmissing[$idx] = $tlcfld;
								break;
							}
						}
					}
				} else {
					$tlcfld = $postfld;
				}
				if (array_key_exists("fld_trans_".$postfld, $_POST)) {
					// note that translation is done according to the hash
					//	index (ie the post field name, not the tlc field name
					switch ($_POST["fld_trans_".$postfld]) {
						case "md5": {
							$values[$tlcfld] = md5($_POST[$postfld]);
							break;
						}
						default: {
							die("unknown field translation: "
								.$_POST["fld_trans_".$fld]);
						}
					}
				} else {
					$values[$tlcfld] = $_POST[$postfld];
				}
			} else {
				if (in_array($postfld, $allowmissing)) {
					$allowedmissing[] = $postfld;
				} else {
					$missing_fields[] = $postfld;
				}
			}
		}
	} else {
		foreach ($fields as $fld) {
			if (array_key_exists($fld, $_POST)) {
				if (array_key_exists("fld_trans_".$fld, $_POST)) {
					switch ($_POST["fld_trans_".$fld]) {
						case "md5": {
							$values[$fld] = md5($_POST[$fld]);
							break;
						}
						default: {
							die("unknown field translation: "
								.$_POST["fld_trans_".$fld]);
						}
					}
				} else {
					$values[$fld] = $_POST[$fld];
				}
			} else {
				if (in_array($fld, $allowmissing)) {
					$allowedmissing[] = $fld;
				} else {
					$missing_fields[] = $fld;
				}
			}
		}
	}
	if (isset($missing_fields)) {
		print("do_defined_tlc_func: one or more expected fields was not in the"
			." post array:<ul>");
		foreach ($missing_fields as $m) {
			print("<li>$m</li>");
		}
		print("</ul>");
		if (count($allowmissing)) {
			print("the following fields were allowed to be missing:<ul>");
			foreach ($allowmissing as $fld) {
				print("<li>$fld</li>");
			}
			print("</ul>");
		}
		die();
	}
	foreach ($values as $idx => $val) {
		if (!in_array($idx, $allowedmissing)) {
			$newvalues[$idx] = $val;
		}
	}
	$values = $newvalues;
	$fields = array_keys($values);
	// ok, talk to that tlc server, if possible!
	if (array_key_exists("svrobj", $options)) {
		// we use the passed server object
		$svr = $options["svrobj"];
	} elseif (array_key_exists("host", $options) 
				&& array_key_exists("port", $options)) {
		// connect to a new server...
		$svr = tlc_connect($options["host"], $options["port"]);
	} else {
		global $svr; // use the globally registered server object, provided
						// by sanity.php
	}
	if (array_key_exists("debug", $options) && $options["debug"]) {
		$debug = 1;
	} else {
		if (array_key_exists("debug", $_POST) && $_POST["debug"]) {
			$debug = 1;
		} else {
			$debug = 0;
		}
	}
	
	$ro = post_2_tlcxml(array(
		"module"		=>	$options["module"],
		"cmd"			=>	$options["cmd"],
		"rootname"		=>	$options["rootname"],
		"fields"		=>	$fields,
		"debug"			=>	$debug,
		"svrobj"		=>	$svr,
	));
	
	if (!array_key_exists("handle", $options)) {
		// default is to handle the return from the server
		$options["handle"] = 1;
	}
	if ($options["handle"]) {
		handle_xml_ret($ro);
	} else {
		return $ro;
	}
}
/*>>>*/
function handle_xml_ret($obj) {/*<<<*/
	global $_POST;
	if ($obj->success) {
		print("<h3>".svarinject($obj, punwrap(post_or_default("success_title", 
			msg("msg_default_post_success_title"))."</h3>")));
		print("<p class=\"cen\">");
		print(svarinject($obj, punwrap(post_or_default("success_msg", 
			msg("msg_default_post_success_title")))."</p>"));
		if (array_key_exists("success_redirect", $_POST)) {
			redirect(svarinject($obj, $_POST["success_redirect"]));
		}
	} else {
		print("<h3>".punwrap(post_or_default("fail_title", 
			msg("msg_sorry")))."</h3>");
		print("<p class=\"cen\">");
		print(punwrap(post_or_default("fail_msg", 
			msg("msg_default_post_fail")))."</p>");
		print("<p class=\"cen\">");
		if ($obj->has_mvar("error")) {
			if (array_key_exists("errmsg_".$obj->error, $_POST)) {
				print($_POST["errmsg_".$obj->error]);
			} elseif (msg_exists("errmsg_".$obj->error)) {
				print(msg("errmsg_".$obj->error));
			} else {
				print("<p class=\"cen\">");
				print(punwrap(post_or_default("debug_msg", 
					msg("msg_debug_info")))."</p>");
				print($obj->error." :: ".$obj->result);
				print("<br>{missing translation message for errmsg_"
					.$obj->result."}");
			}
		} else {
			if ($obj->has_mvar("result")) {
				print("<p class=\"cen\"".$obj->result."</p>");
			} else {
				print("<p class=\"cen\">".msg("msg_no_error_info")."</p>");
			}
		}
		print("</p>");
	}
}
/*>>>*/
function svarinject(&$hdobj, $str) {/*<<<*/
	$fld = find_field($str, $p1, $p2);
	while ($p1 !== false) {
		if ($hdobj->has_mvar($fld) == 1) {
			$str = str_replace("[".$fld."]", $hdobj->$fld, $str);
		} else {
			print("was looking to inject field ($fld) in string ($str)<br>");
			print($hdobj->toXML(true, true));
			die();
		}
		$fld = find_field($str, $p1, $p2);
	}
	return $str;
}
/*>>>*/
function find_field ($str, &$p1, &$p2, $leftbound = "[", $rightbound = "]") {/*<<<*/
// finds a field delimited with a left & right bound as specified, and
//	returns the field name, as well as placing the start and end positions
//	(including bounds) in $p1 and $p2
	if (!is_integer($p1)) $p1 = 0;
	if ($p1 > 0) {
		$p1++;
	}

	$p1 = strpos($str, $leftbound, $p1);
	if ($p1 !== false) {
		$p2 = strpos($str, $rightbound, $p1);
		if ($p1 !== false) {
			$ret = substr($str, $p1+1, $p2 - $p1 - 1);
		} else {
			$p2 = false;
			$ret = "";
		}
	} else {
		$p2 = false;
		$ret = "";
	}
	return $ret;
}
/*>>>*/
function pwrap($str) {/*<<<*/
	return htmlentities($str);
	//return str_replace("\"", "&quot;", str_replace("&", "&amp;", $str));
}
/*>>>*/
function punwrap($str) {/*<<<*/
	return html_entity_decode($str);
	//return str_replace("&amp;", "&", str_replace("&quot;", "\"", $str));
}
/*>>>*/
function create_select($inputname, $options = array(), $value=NULL, $hash = true, $style="", $id="") {/*<<<*/
	if (!is_array($options)) {
		print("create_select: options not defined as array");
		return;
	}
	print("<select name=\"".$inputname."\"");
	if (strlen($style)) {
		if (strpos(":", $style)) {
			print(" style=\"".$style."\"");
		} else {
			print(" class=\"".$style."\"");
		}
	}
	if (strlen($id)) {
		print(" id=\"".$id."\"");
	} else {
		$id = $inputname;
		print(" id=\"".$inputname."\"");
	}
	print(">\n");
	if ($hash) {
		foreach($options as $idx=>$val) {
			print("<option value=\"".$idx."\"");
			if ($value == $idx) {
				print(" selected");
			}
			print(">".htmlentities($val)."</option>\n");
		}
	} else {
		foreach($options as $val) {
			print("<option value=\"".$val."\"");
			if ($value == $val) {
				print(" selected");
			}
			print(">".$val."</option>\n");
		}
	}
	print("</select>\n");
	// a little extra paranoia for option selection
	if ($value != NULL) {
		print("\n<script language=\"Javascript\">\n"
				."// a little something to defeat \"smart\" browsers\n"
				."if (obj=document.getElementById(\"".$id."\")) {\n"
				."	for (var idx=0; idx < obj.options.length; idx++) {\n"
				."		if (obj.options[idx].text == \"".$value."\" "
					."|| obj.options[idx].value == \"".$value."\") {\n"
				."			obj.options[idx].selected=true;\n"
				."			break;\n"
				."		}"
				."	}\n"
				."}\n"
				."</script>\n");
		
	}
}
/*>>>*/
function friendly_check($name, $value, $caption, $checked=0, $id="", $print=1) {/*<<<*/
	if ($id == "") $id = $name;
	$r = "<input type=\"checkbox\" value=\"".$value."\" name=\"".$name
		."\" id=\"".$id."\"";
	if ($checked) {
		$r.=" checked>";
	} else {
		$r.=">";
	}
	$r.="<span id=\"__checkbox_".$id."\" style=\"cursor: pointer"
		."\" onclick=\"if (obj=document.getElementById('".$id.
		"')) {obj.checked=!obj.checked;}\">".$caption."</span>";
	if ($print) {
		print($r);
	} else {
		return $r;
	}
}
/*>>>*/
function get_conf ($module, $identifier, $default = "") {/*<<<*/
	include_once("include/tlchdobj.php");
	$tmp = new TLCHDObj(array(
		"module"	=>	"user",
		"cmd"		=>	"get_conf",
		"data"		=>	array(
							"module"		=>	$module,
							"identifier"	=>	$identifier,
							"default"		=>	$default,
						),
	));
	if ($tmp->child_count("val")) {
		return $tmp->val;
	} else {
		return $default;
	}
}
/*>>>*/
function gen_tlc_select($opts = array()) {/*<<<*/
	foreach (array("module", "cmd", "name") as $req) {
		if (!array_key_exists($req, $opts)) {
			die("gen_tlc_select called without required option ($req)");
		}
	}
	foreach (array(
				"value"				=>	"",
				"button_caption"	=>	"",
				"button_image"		=>	"",
				"image_pos"			=>	"left",
				"popup_title"		=>	"",
				"winargs"			=> "resize=0,status=0,toolbar=0,location=0,"
									."menubar=0,directories=0,scrollbars=0",
				) as $didx => $dval) {
		if (!array_key_exists($didx, $opts)) {
			$opts[$didx] = $dval;
		}
	}
	if (!array_key_exists("id", $opts)) {
		$opts["id"] = $opts["name"];
	}
	print("<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>");
	print("<input class=\"textbox_med\" value=\"".$opts["value"]
		."\" onfocus=\"this.blur()\" name=\"".$opts["name"]
		."\" id=\"".$opts["id"]."\"></td><td>");
	$tmptb = new Toolbar();
	if (($opts["button_caption"] == "") && ($opts["button_image"] == "")) {
		$opts["button_caption"] = "...";
	}
	if (file_exists("include/tlc_select.php")) {
		$phpfile = "include/tlc_select.php";
	} elseif (file_exists("../include/tlc_select.php")) {
		$phpfile = "../include/tlc_select.php";
	} else {
		die("can't find tlc_select.php");
	}
	$phpfile.="?module=".$opts["module"]."&cmd=".$opts["cmd"];
	if (array_key_exists("data", $opts)) {
		if (is_array($opts["data"])) {
			$phpfile.="&data=".xmlwrap($opts["data"], "data");
		} else {
			$phpfile.="&data=".$opts["data"];
		}
	}
	$phpfile.="&target=".$opts["id"];
	$popcode = "window.open('".$phpfile."', '".$opts["popup_title"]."', '"
		.$opts["winargs"]."');";
	$tmptb->add_button(array(
		"caption"	=>	$opts["button_caption"],
		"img"		=>	$opts["button_image"],
		"imgpos"	=>	$opts["image_pos"],
		"code"		=>	$popcode,
	));
	$tmptb->render();
	unset($tmptb);
	print("</td></tr></table>");
}
/*>>>*/
function require_post_fields() {/*<<<*/
	$argcount = func_num_args();
	if ($argcount == 0) return; //why are we even here?!
	$args = func_get_args();
	foreach ($args as $arg) {
		if (is_array($arg)) {
			foreach ($arg as $miniarg) {
				if (!array_key_exists($miniarg, $_POST)) {
					$missing[] = $miniarg;
				}
			}
		} else {
			if (!array_key_exists($arg, $_POST)) {
				$missing[] = $arg;
			}
		}
	}
	if (is_array($missing)) {
		print("The following expected fields were not to be found in the post"
			." array:<br><ul>");
		foreach ($missing as $m) {
			print("<li>".$m."</li>");
		}
		print("</ul>");
		die();
	}
}
/*>>>*/
function require_get_fields() {/*<<<*/
	$argcount = func_num_args();
	if ($argcount == 0) return; //why are we even here?!
	$args = func_get_args();
	foreach ($args as $arg) {
		if (is_array($arg)) {
			foreach ($arg as $miniarg) {
				if (!array_key_exists($miniarg, $_GET)) {
					$missing[] = $miniarg;
				}
			}
		} else {
			if (!array_key_exists($arg, $_GET)) {
				$missing[] = $arg;
			}
		}
	}
	if (is_array($missing)) {
		print("The following expected fields were not to be found in the get"
			." array:<br><ul>");
		foreach ($missing as $m) {
			print("<li>".$m."</li>");
		}
		print("</ul>");
		die();
	}
}
/*>>>*/
?>

<script language="Javascript">
var wins=new Array();
var btns=new Array();

function swapimg (img, newsrc, title) { // <<<
	if (img.src) {
		img.src=newsrc;
		if (typeof(title) != "undefined") {
			window.status=title;
			img.title=title;
		} else {
			window.status='';
			img.title='';
		}
	}
}
// >>>
function wait_for_close(win, obj) { // <<<
	if (win.document) {
		if (dobj=document.getElementById("debug_div")) {
			dobj.innerHTML="waiting";
		}
		i=wins.length;
		wins[i]=win;
		btns[i]=obj;
		window.setTimeout("watch_win("+i+")", 500);
	} else {
		obj.disabled=false;
	}
}
// >>>
function watch_win(i) { // <<<
	if (wins[i].document) {
		if (obj=document.getElementById("debug_div")) {
			obj.innerHTML+=".";
		}
		window.setTimeout("watch_win("+i+")", 500);
	} else {
		btns[i].disabled=false;
	}
}
// >>>
</script>

