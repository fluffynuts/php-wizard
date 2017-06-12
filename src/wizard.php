<?php
/*
	Author: Dave McColl

	licensing: this code is released under the BSD license. You may alter and
	use it in any way that you deem fit, except that you may not claim that it
	is yours  (:

	please note that there may also be references to the opensource 
	client-server model for Tcl called tlc. If you don't intend to use tlc
	anywhere, then don't worry about references to wizard controls specific to
	the model


Changelog:
move to an aptly-named CHANGELOG file!
*/
// go looking for the includes files...
$include_lookin = array(".", "..", "include", "../include", "images", 
	"../images", "lang", "../lang", dirname(__FILE__));
	
function get_wizard_step_height() {
	global $wizard_body_height;
	// the stuff below has been empirically obtaned
	$max_perc = 35;
	$min_perc = 18;
	$max_perc_mark = 1200;
	$min_perc_mark = 450;
	$diff = $wizard_body_height - 500;
	$diffperc = $diff / ($max_perc_mark - $min_perc_mark);
	$perc = $max_perc - (($max_perc - $min_perc) * $diffperc);
	//print("max_perc: $max_perc; min_perc: $min_perc; diffperc: $diffperc; perc: $perc;");
	return $wizard_body_height * ((100 - $perc) / 100);
}
if (!function_exists("wiz_find_graphic")) {
function wiz_find_graphic($fn) {/*<<<*/
	global $graphic_extensions;
	if (!is_array($graphic_extensions))
		$graphic_extensions = array("gif", "png", "bmp");
	$splitfile = explode(".", $fn);
	if (in_array(strtolower($splitfile[count($splitfile) - 1]), 
		$graphic_extensions)) {
		return wiz_find_file($fn);
	} else {
		foreach ($graphic_extensions as $ext) {
			$ret = wiz_find_file($fn.".".$ext);
			if (file_exists($ret)) {
				return $ret;
			}
		}
	}
	return "";
}
/*>>>*/
}
if (!function_exists("wiz_find_file")) {
function wiz_find_file($fn, $dontcomplain = true) {/*<<<*/
	//finds the web / *nix relative path to a required file
	global $include_lookin;
	foreach ($include_lookin as $dir) {
		if (file_exists($dir."/".$fn)) {
			return $dir."/".$fn;
		}
	}
	if (!$dontcomplain) {
		print("-- unable to find $fn<br>");
	var_dump($include_lookin);
		print("<br>");
	}
	return "";
}
/*>>>*/
}
function insert_include_location($loc) {/*<<<*/
	global $include_lookin;
	array_unshift($include_lookin, $loc);
}
/*>>>*/
function gettrans($msgid, $default) {/*<<<*/
	// gets a translation message, if available; or uses a default
	if (function_exists("msg_exists")) {
		if (msg_exists($msgid)) {
			if (function_exists("msg")) {
				return msg($msgid);
			}
		}
	}
	return $default;
}
/*>>>*/
include_once(wiz_find_file("misc.php"));	// for val function
include_once(wiz_find_file("log.php"));	// for log object
$tmp = wiz_find_file("cassel.php");
if ($tmp != "") {
	include_once($tmp);
}
// set wizard_body_height before including this script to alter the height of
//	the generated wizard. Be warned though: the current height works nicely on
//	a 1024x768 screen (with a page title)
//		-- perhaps you should just leave it alone  (:
//	I've instituted a minimum of 450px for the height 
//	and a max height of 1200 px. Smaller wizards get their headings and
//	titles all over each other, and larger ones end up with slack space around
//	the navigation buttons (and aren't friendly, since you have to scroll to 
//	get to content, and that's kind of opposite to the point of a wizard
if (!(isset($wizard_body_height))) $wizard_body_height=500;
if (is_numeric($wizard_body_height)) {
	if ($wizard_body_height < 450) {
		$wizard_body_height = 450;
	}
	if ($wizard_body_height > 1200) {
		$wizard_body_height = 1200;
	}
} else {
	$wizard_body_height = 500;
}
function tack_on_nonblank_url_arg(&$url, $arg, $val) {/*<<<*/
	if ($val == "") return;
	if (is_array($val)) {
		foreach ($val as $subval) {
			tack_on_nonblank_url_arg($url, $arg."[]", $subval);
		}
	}
	if (strpos($url, "?") ===false) {
		$url.="?".$arg."=".$val;
	} else {
		$url.="&".$arg."=".$val;
	}
}
/*>>>*/
class Wizard extends Logger { // <<<1
	var $steps;
	var $stepdata;
	var $data;
	var $settings;
	var $summarystep;
	var $summarystepdata;
	var $summarydef;
	var $last_step_added;
	var $arb_scripts;
	
	function Wizard ($set) { // <<<2
		global $got_summary;
		$got_summary = 0;
		$this->clear_errors();
		$this->settings = array (
			"postpage"		=> "",
			"datepickstyle" => "dhtml",
			"formname"		=> "frmWizard",
			"cancelpage"	=> "",
			"formmethod"	=> "post",
			"cancelmsg"		=> gettrans("msg_confirm_cancel", 
					"Are you sure you would like to cancel this operation?"),
			"summary"		=> false,
			"summary_title"	=> "Summary:",
			"summary_caption" => "Below is a summary of the data you have entered.",
			"summary_image"	=> "",
			"summary_label"	=> "Click &quot;Finish&quot; to save this data.",
			"images_dir"	=> "",
			"includes_dir"	=> "",
			"mark_required"	=> true,
			"wizdisplay"	=> true,
			"smartsummary"	=> true,
		);
		// datepickstyle can be one of: dhtml (inline) or popup.
		if (array_key_exists("formaction", $set)) {
			// allow for consistent naming
			$set["postpage"] = $set["formaction"];
			unset($set["formaction"]);
		}
		if (is_array($set)) {
			foreach ($set as $idx => $val) {
				$this->settings[$idx]=$val;
			}
		}
		if (file_exists($this->settings["includes_dir"])) {
			insert_include_location($this->settings["includes_dir"]);
		}
		if (file_exists($this->settings["images_dir"])) {
			insert_include_location($this->settings["images_dir"]);
		}
		// allow global default datepicker override
		global $DATEPICKSTYLE;
		if (isset($DATEPICKSTYLE)) {
			switch ($DATEPICKSTYLE) {
				case "pop":
				case "popup":
				case "pop-up": {
					$this->settings["datepickstyle"] = "popup";
					break;
				}
				default: {
					$this->settings["datepickstyle"] = "dhtml";
				}
			}
		}

		$this->steps=array();
		$this->data=array();
		$summarystep=array();
		$summarystepdata=array();
		if ($this->settings["summary"]) {
			$this->summarydef["title"]=$this->settings["summary_title"];
			$this->summarydef["caption"]=$this->settings["summary_caption"];
			$this->summarydef["image"]=$this->settings["summary_image"];
			$this->summarydef["summary_label"]=$this->settings["summary_label"];
		}
		$this->last_step_added=0;
	}
	
	function find_file($filename, $locations) { // <<<2
		if (!is_array($locations)) $locations = explode(":", $locations);
		foreach ($locations as $loc) {
			if (strpos("\\", $loc) !== false) {
				$sep = "\\";
			} else {
				$sep="/";
			}
			$testfile = $loc.$sep.$$filename;
			if (file_exists($testfile)) {
				return $testfile;
			}
		}
		return "";
	}

	function render ($doprint = true) { // <<<2
		// right, calling this basically says "I've set up the steps, now
		//	let the user have a bash at this  (:
		// print out a pretty "please wait"
		
		$wiztime=$this->get_unique_time_val();
		$waitingid="wizard_waiting_".$wiztime;
		print("<div id=\"".$waitingid."\" class=\"wizard_waiting\">Generating wizard... please be patient...</div>");
		flush();
		
		$stepcount=count($this->steps);
		$thisstep=1;
		$havefirstshown=false;
		if ($this->settings["wizdisplay"]) {
			$r="\n<div class=\"wizard_form\"><form name=\""
				.$this->settings["formname"]."\" method=\""
				.$this->settings["formmethod"]."\" action=\""
				.$this->settings["postpage"]."\">"
				."\n<div class=\"wizard_body\">";
		} else {
			$r.="<form name=\"".$this->settings["formname"]."\" method=\""
				.$this->settings["formmethod"]."\" action=\""
				.$this->settings["postpage"]."\">";
		}
		ksort($this->steps);	// just in case;
		$dsteps=array();
		$allsteps=array();
		$vsteps=array();
		
		// if there is a summary step generated, tack it on to the wizard
		if ($this->settings["smartsummary"]) {
			// with the smart setting on (default true),
			//	a summary is only generated if there
			//	are more than one steps in the wizard
			if (count($this->steps) <= 1) {
				$this->settings["summary"] = false;
			}
		}
		if ($this->settings["summary"] && $this->settings["wizdisplay"]) {
			if (!is_array($this->summarystep)) {
				$this->gen_summary_page();
			}
		}
		if (is_array($this->summarystep)) {
			$this->steps[]=$this->summarystep;
			$this->stepdata[]=$this->summarystepdata;
			$stepcount++;
		}
		
		if ($this->settings["wizdisplay"]) {
			$r.="<div class=\"wizard_head\">"
				."<table cellpadding=\"2\" cellspacing=\"2\" "
				."border=\"0\" width=\"100%\"><tr><td width=\"10%\" id=\"imgtd_"
				.$wiztime."\">"
				."<img src=\"\" id=\"wizard_head_image_".$wiztime."\">"
				."</td><td><h3 style=\"text-align: left;\" id=\""
				."wizard_head_title_"
				.$wiztime."\"></h3></td><td align=\"right\" width=\"10%\">";
			if (count($this->stepdata)>1) {
				$r.="\n<div class=\""
					."wizard_stepnum\" id=\"wizard_head_stepnum_".$wiztime."\">"
					."</div>\n";
			}
			$r.="</td></tr><tr><td></td><td><div class=\"wizard_caption\" "
				."id=\"wizard_head_caption_".$wiztime."\">"
				."</div></td></tr></table></div><hr class=\"wizard_hr\">";
		}
		$script_vars="\nvar titles=new Array();\n"
			."var captions=new Array();\n"
			."var images=new Array();\n";
		
		foreach ($this->steps as $stepnum => $inputs) {
			$next_disabled="";
			$script_vars.="titles[".$stepnum."]='"
				.$this->jhesc($this->stepdata[$stepnum]["title"])."';\n"
				."captions[".$stepnum."]='"
				.$this->jhesc($this->stepdata[$stepnum]["caption"])."';\n"
				."images[".$stepnum."]='"
				.$this->stepdata[$stepnum]["image"]."';\n";
			if ($this->settings["wizdisplay"]) {
				$step_class = "wizard_step";
			} else {
				$step_class = "wizard_step_flat";
			}
			$r.="\n<div id=\"wizard_step_".$stepnum."_".$wiztime
				."\" class=\"".$step_class."\"";
			if ($havefirstshown) {
				$r.="style=\"display:none;\">";
			} else {
				$havefirstshown=true;
				$firststepnum=$stepnum;	// just in case numbers are funny
				$r.=">";
			}
			$r.="<table align=\"center\" border=\"0\" width=\"95%\""
				."cellpadding=\"2\" cellspacing=\"2\" style=\"margin: 10px\""
				."><thead><col width=\"50%\"><col width=\"50%\"></thead>";
			ksort($inputs);	// just in case, again (:
			$vcontrols=array();
			foreach ($inputs as $item) {
				if (!isset($this->settings["focus_input"]) 
					|| $this->settings["focus_input"] == "") {
					$this->settings["focus_input"] = $item["id"];
				}
				if ($item["required"]) {
					if ((($item["required_val"]=="") 
						&& ($item["value"]=="")) 
						||
						(($item["required_val"]!="") 
						&& (strtolower($item["value"])==strtolower($item["required_val"])))) {
						$next_disabled=" disabled ";
					}
					$vcontrols[]=$item;
				}
				$r.=$this->renderinput($item);
			}
			if ($this->settings["focus_input"] != "__none__") {
				$this->add_object_event("window", "onload", 
					"focus_item('".$this->settings["focus_input"]."')");
			}
			$r.="</table></div>\n";
			if (count($vcontrols)>0) {
				$dsteps[]=$stepnum;
				$vsteps[$stepnum]=$vcontrols;
			}
			$allsteps[]=$stepnum;
			
			if ($this->stepdata[$stepnum]["code"] != "") {
				$r.="\n<script language=\"Javascript\">\n"
					."step_scripts[".$stepnum."]='wiz_enterstep_".$stepnum
					."()';\nfunction wiz_enterstep_".$stepnum."() {\n"
					.$this->stepdata[$stepnum]["code"]
					."\n}\n"
					."</script>";
			} else {
				$r.="<script language=\"Javascript\">\n"
					."step_scripts[".$stepnum."]='';\n"
					."</script>";
			}
			
		}
		$r.="<hr class=\"wizard_hr\"><table cellspacing=\"2\" "
			."cellpadding=\"2\" border=\"0\" align=\"right\""
			."style=\"padding-right: 45px\"><tr>";
		$btnmsgs = array(
			"btn_cancel"	=>	gettrans("btn_cancel", "Cancel"),
			"btn_prev"		=>	gettrans("btn_prev", "Prev"),
			"btn_next"		=>	gettrans("btn_next", "Next"),
			"btn_finish"	=>	gettrans("btn_finish", "Finish"),
			"btn_save"		=>	gettrans("btn_save", "Save"),
		);
		// if there is more than one stage, we need the back/next buttons
		$toolbar_include=wiz_find_file("toolbar.php");
		if ($toolbar_include != "") {
		// make use of the toolbar class if we have it -- to get uniform
		//	button looks over the app
			include_once($toolbar_include);
			$tb = new Toolbar();
			if ($this->settings["cancelpage"] != "") {
				$tb->add_button(array(
					"caption"	=>	$btnmsgs["btn_cancel"],
					"code"		=>	"cancel_wizard('"
										.$this->settings["cancelpage"]."')",
					"img"		=>	"cancel",
					"imgpos"	=>	"left",
					"id"		=>	"wizard_btncancel",
				));
			}
			if ($stepcount>1 && $this->settings["wizdisplay"]) {
				$tb->add_button(array(
					"caption"	=>	$btnmsgs["btn_prev"],
					"code"		=>	"incstep(-1, '".$wiztime."')",
					"img"		=>	"back",
					"imgpos"	=>	"left",
					"id"		=>	"wizard_btnprev",
				));
				$tb->add_button(array(
					"caption"	=>	$btnmsgs["btn_next"],
					"code"		=>	"incstep(1, '".$wiztime."')",
					"img"		=>	"forward",
					"imgpos"	=>	"right",
					"id"		=>	"wizard_btnnext",
				));
				$tb->add_button(array(
					"caption"	=>	$btnmsgs["btn_finish"],
					"code"		=>	"document.".$this->settings["formname"]
										.".submit();",
					"img"		=>	"finish",
					"imgpos"	=>	"right",
					"id"		=>	"wizard_btnfinish",
				));
			} else {
				$tb->add_button(array(
					"caption"	=>	$btnmsgs["btn_save"],
					"code"		=>	"document.".$this->settings["formname"]
										.".submit();",
					"id"		=>	"wizard_btnfinish",
					"img"		=>	"save",
					"imgpos"	=>	"right",
				));
			}
			$r.=$tb->render(false);
		} else {
			if ($this->settings["cancelpage"] != "") {
				$r.="<td><input type=\"button\" value=\""
					.$btnmsgs["btn_cancel"]."\" onclick=\""
					."cancel_wizard('".$this->settings["cancelpage"]
					."');\" id=\"wizard_btncancel\"></td>";
			}
			if ($stepcount>1 && $this->settings["wizdisplay"]) {
				$r.="<td><input type=\"button\" value=\"&lt;&lt; "
					.$btnmsgs["btn_prev"]."\""
					."onclick=\""
					."incstep(-1 ,'".$wiztime
					."')\" disabled id=\"wizard_btnprev\""
					."></td><td><input ".$next_disabled
					." type=\"button\" value=\"".$btnmsgs["btn_next"]
					." &gt;&gt;\" onclick=\"incstep(1, '".$wiztime."')\" "
					."id=\"wizard_btnnext\"></td>"
					."<td><input type=\"submit\" value=\""
					.$btnmsgs["btn_finish"]."\" disabled"
					." id=\"wizard_btnfinish\"></td></tr></table>";
			} else {
				$r.="<td><input type=\"submit\" value=\""
					.$btnmsgs["btn_save"]."\" id=\""
					."wizard_btnfinish\"></td></tr></table>";
			}
		}
		$r.="</form>";
		if ($this->settings["wizdisplay"]) {
			$r.="</div>\n";
		}
		$r.="<script language=\"Javascript\">\n"
			."\tvar validswitch = new Array();\n"
			."\tvar cancel_msg='".str_replace("'", "\'",
				$this->settings["cancelmsg"])."';\n"
			."\tvar current_step='".$firststepnum."';\n"
			."\tvar osteps='';\n"
			."\tvar dsteps='|".implode("||", $dsteps)."|';\n"
			."\tvar holds=0;\n"
			."\tvar allsteps=Array('".implode("','", $allsteps)."');\n"
			."\tvar numsteps=".$stepcount.";\n"
			."\tvar step_idx=0;\n"
			."\tvar validation=Array();\n";
		if ($this->settings["wizdisplay"]) {
			$r.="\tvar wizard_flattened = false;\n";
		} else {
			$r.="\tvar wizard_flattened = true;\n";
		}
		foreach ($vsteps as $idx => $vcontrols) {
			$r.="\n\tvalidation['".$idx."'] = new Array();";
			$r.="\n\tvalidswitch['".$idx."'] = new Array();";
			foreach ($vcontrols as $vcontrol) {
				if ($vcontrol["type"] == "compound") {
					$cidx = 0;
					while (array_key_exists("id".$cidx, $vcontrol)) {
						$r.="\n\tvalidation['".$idx."']['"
							.$vcontrol["id".$cidx]."']"
							."= '".str_replace("'", "\'", 
							$vcontrol["required_val".$cidx])."';";
						$r.="\n\tvalidswitch['".$idx."']['"
							.$vcontrol["id".$cidx]."'] = 0;";
					}
				} else {
					$r.="\n\tvalidation['".$idx."']['".$vcontrol["id"]."'] ="
						." '".str_replace("'", "\'", $vcontrol["required_val"])
						."';";
					$r.="\n\tvalidswitch['".$idx."']['"
						.$vcontrol["id"]."'] = 0;";
				}
			}
		}
		$r.=$script_vars;
		if ($this->settings["wizdisplay"]) {
			$r.="incstep(0, '".$wiztime."');\n";
		} else {
			$r.="showallsteps('".$wiztime."');\n";
		}
		$r.="\n\tshowitem('".$waitingid."', false);\n";
		global $tooltips;
		if(is_array($tooltips)) {
			foreach ($tooltips as $tt) {
				$r.="createtip('".$tt["id"]."', '".$tt["title"]."', '"
					.$tt["text"]."');\n";
			}
		}
		if (is_array($this->arb_scripts)) {
			foreach ($this->arb_scripts as $script) {
				print($script."\n");
			}
		}
		if (is_array($this->js_events)) {
			$r.="\n// event handler registrations:\n";
			foreach ($this->js_events as $objid => $eventsarray) {
				if (!is_array($eventsarray)) continue;
				foreach ($eventsarray as $ev => $script) {
					if (trim($ev) == "") continue;
					if (is_array($script)) {
						$script = implode(";", $script);
					}
					if (trim($script) == "") continue;
					
					$r.="addEvent(\"".$this->jquote($objid)."\", \""
						.$this->jquote($ev)."\", \""
						.$this->jquote($script)."\");\n";
				}
			}
		}
		if ($this->settings["blocked"]) {
			$this->add_object_event("window", "onload", 
				"disable_toolbar_btn_byid('wizard_btnnext');");
			$this->add_object_event("window", "onload", 
				"disable_toolbar_btn_byid('wizard_btnfinish');");
		}
		if (is_array($this->js_obj_events)) {
			foreach ($this->js_obj_events as $obj => $eventsarray) {
				if (!is_array($eventsarray)) continue;
				foreach ($eventsarray as $ev => $script) {
					if (trim($ev) == "") continue;
					if (is_array($script)) {
						$script = implode(";", $script);
					}
					if (trim($script) == "") continue;
					$r.="addEventToObject(".$this->jquote($obj).", \""
						.$this->jquote($ev)."\", \""
						.$this->jquote($script)."\");\n";
				}
			}
		}
		if (isset($this->skip_steps) && (is_array($this->skip_steps))) {
			$r.="var skip_steps = new Array();\n";
			foreach ($this->skip_steps as $ss) {
				$r.="skip_steps.push($ss);\n";
			}
		}
		$r.="</script>";
		if ($doprint) print($r);
	}
	function addstep ($def = array()) { // <<<2
		/* about this function: <<<
			input: array with indeces:
					title		: title of step
					caption		: about this step (help the user)
					image		: associative image (not required)
					stepnum		: numeric or "append"/"prepend".
									using a stepnumber that already
									exists shuffles the current step
									and all after it down the array.
		>>> */
		$def["stepnum"]=$this->aod($def, "stepnum", "append");
		switch ($def["stepnum"]) {
			case "append": {
				$this->stepdata[] = array();
				$def["stepnum"] = count($this->stepdata)-1; 
				break;
			}
			case "prepend": {
				$def["stepnum"]=0;
			}
			default:	{
				if (is_array($this->stepdata[$def["stepnum"]])) {
					// shuffle everything down...
					$steps=count($this->stepdata);
					$last=$this->stepdata[$def["stepnum"]];
					for ($i=$def["stepnum"]+1; $i<=$steps; $i++) {
						$swp=$this->stepdata[$i];
						$this->stepdata[$i]=$last;
						$last=$swp;
					}
				} else {
					$this->stepdata[$def["stepnum"]]=array();
				}
			}
		}
		
		$def["title"]=$this->aod($def, "title", "Step ".$def["stepnum"]);
		$def["caption"]=$this->aod($def, "caption", "This is step number: "
			.$def["stepnum"]);
		$def["image"]=$this->aod($def, "image", "");
		$this->stepdata[$def["stepnum"]]=$def;
		$this->last_step_added=$def["stepnum"];
		return $def["stepnum"];
	}
	
	function aod(&$array, $idx, $default) { // <<<2
		// "array or default"
		if (array_key_exists($idx, $array)) {
			return $array[$idx];
		} else {
			return $default;
		}
	}
	function addinput($def) { // <<<2
		if (!is_array($def)) {
			$this->log("addinput called with non-array definition.");
			return 0;
		}
		/* about the definition array <<<
		the definition array can contain the following elements:
			step	--	the step number to add this to. If the step
							doesn't exist, it is created.
			prompt	--	the textural prompt string that the user sees.
							IOW, the question he/she must answer
			type	--	the input type of the input; possible values are: <<<
							textbox 	-- simplest input
							textarea	-- larger text area
							select *	-- drop-down list
							list *		-- multi-select list. 
											Avoid if possible 
											-- checks are much better for small
											lists, and single-selects are 
											better accomplished with select
							date		-- date select box, with pretty button
							spinner *	-- text-select with "up" and "down"
											buttons to move between choices
							radio *		-- single-select option list
							checkbox *	-- multiple select option list
							helpertext *-- text box with select -- gives a list
											to help the user, but allows user-
											defined input.
							memorytext *-- as above, but saves the user's input
											for others to use (eg company name)
											NB: requires a special parameter:
											"savepage" that actually does the 
											saving into your system.
							label -- not an input, simply information
								* requires an option list >>>
							tlc_select -- uses another page as a popup lookup,
								the user sees a textbox (non-editable) and
								and ellipsis button ( [...] ). This is a
								specific implementation of something more
								generic, but it's place is in TLC-based
								systems (for which this wizard was originally
								created).
							checklabel -- produces a label which allows a user
								to select a boolean value -- like a license
								agreement selector, or a "This item is
								enabled" selector.
			options	--	the allowable options for input types that require
							options. Empty option lists are allowed (but why?)
							but are logged. Options are brought in in array
							format. (yes, this is a sub-array)
			position --	where to place this. Defaults to append, but may be 
							numeric (watch out: will overwrite anything there)
							or "first", "last" (same as "append"), "prepend"
							(same as "first"). In the interests of keeping code
							tighter, be warned that prepending inputs will end
							up with inputs with *negative* positions, which
							may produce unexpected results.
			required -- the step cannot progress until a valueis chosen / input
			required_val -- the step cannot progress until the required value
							has been selected (eg "Do you accept the license?"
							requiring a "yes")
			varname		--	name of variable to load with this value
								no checking done: make sure you don't use same
								names, otherwise expect unexpected results (:
			value		--	the loaded value of the input
			style		--	extra css style parameters that an input may have
			extra		--	extra parameters to the input generator
							this is to come in as an array
			title		--	title to display in regular tooltip and 
							statusbar for this input -- appears over input
							item
			caption		-- short title used as a mini-heading in the summary.
							not required: defaults to the prompt.
			tooltip		-- longer tooltip that will appear over the prompt,
							using a dhtml tooltip item. Not required.
		>>> */
		if (array_key_exists("name", $def)) {	
			// allows the "name" shorthand for "varname"
			$def["varname"] = $def["name"];
		}
		$input["prompt"] = trim($this->aod($def, "prompt", 
			"Please supply a prompt."));
		$input["type"] = strtolower(trim($this->aod($def, "type", "textbox")));
		switch ($input["type"]) {
		   	case "demo":
		   	case "widget_demo":
		   	case "democontrols":
		   	case "largelabel":
		   	case "license":
		   	case "infobox":
		   	case "label": {
				$default_ignore = 1;
				break;
			}
			default: {
				$default_ignore = 0;
			}
		}
		$input["options"] = $this->aod($def, "options", "");
		$input["required"] = val($this->aod($def, "required", "0"));
		$input["required_val"] = $this->aod($def, "required_val", "");
		if (strlen($input["required_val"])) {
			// setting a required val should automagically set the required
			//	value on
			$input["required"] = 1;
		}
		$input["value"] = $this->aod($def, "value", "");
		$input["style"] = $this->aod($def, "style", "");
		$input["title"] = $this->aod($def, "title", "");
		$input["tooltip"] = $this->aod($def, "tooltip", "");
		$input["cascades"] = $this->aod($def, "cascades", array());
		$input["caption"] = $this->aod($def, "caption", "");
		$input["ignore"] = $this->aod($def, "ignore", $default_ignore);
		$stepnum=val($this->aod($def, "step", ""));
		if ($stepnum == "") {
			// default is to add to last step added
			$stepnum=$this->last_step_added;
		}
		if (is_array($def["extra"])) {
			// force coying the array
			$input["extra"]=array_slice($def["extra"], 0);
			if (array_key_exists("scripts", $input["extra"])) {
				if (!is_array($input["extra"]["scripts"])) {
					print("Please convert your scripts for ".$input["varname"]
						." to the array style. Until you do so, they will be"
						." ignored.");
					$input["extra"]["scripts"] = array();
				}
			}
		}
		// 20040117: adding support for multiple input controls per item
		if ($def["type"] == "compound") {
			$iidx=1;
			while ($def["varname".$iidx]!="") {
				switch ($input["type".$iidx]) {
					case "demo":
					case "widget_demo":
					case "democontrols":
					case "largelabel":
					case "license":
					case "infobox":
					case "label": 
					case "compound": {
						$default_ignore = 1;
						break;
					}
					default: {
						$default_ignore = 0;
					}
				}
				$input["type".$iidx]=strtolower(trim($this->aod($def, 
					"type".$iidx, "textbox")));
				$input["options".$iidx]=$this->aod($def, "options".$iidx, "");
				$input["required".$iidx]=val($this->aod($def, 
					"required".$iidx, "0"));
				$input["required_val".$iidx]=$this->aod($def, 
					"required_val".$iidx, "");
				$input["value".$iidx]=$this->aod($def, "value".$iidx, "");
				$input["style".$iidx]=$this->aod($def, "style".$iidx, "");
				$input["varname".$iidx]=$this->aod($def, "varname".$iidx
					, "input_".$stepnum.".".$pos);
				$input["id".$iidx]=$this->aod($def, "id".$iidx, 
					$input["varname".$iidx]);
				$input["ignore".$iidx] = $default_ignore;
				$iidx++;
			}
		} else {
			$input["varname"]=$this->aod($def, "varname", "input_".$stepnum."."
				.$pos);
			$input["id"]=$this->aod($def, "id", $input["varname"]);
		}
		$pos=$this->aod($def, "position", "append");
		if (!array_key_exists($stepnum, $this->steps)) {
			$this->steps[$stepnum]=array();
		}
		switch ($pos) {
			case "prepend": {
				$keys=sort(array_keys($this->steps[$stepnum]));
				$newkey=val($keys[0])-1;
				$this->steps[$stepnum][$newkey]=$input;
				break;
			}
			case "append": {
				$this->steps[$stepnum][]=$input;
				break;
			}
			default: {
				$this->steps[$stepnum][val($pos)]=$input;
			}
		}
	}
	
	function renderdemos($inputdef = array(), $options=array()) { // <<<2
		// purpose: to render (useless) demo versions of the
		//	available inputs. To be used in the workflow designer.
		$opts = array(
			"style"			=>	"border: 1px solid red; padding: 15px;",
			"allvisible"	=>	false,
		);
		$this->load_array($options, $opts);
		$r = "<div id=\"".$inputdef["id"]."\"";
		if (strpos($opts["style"], ":") === false) {
			$r.=" class=\"".$opts["style"]."\"";
		} else {
			$r.=" style=\"".$opts["style"]."\"";
		}
		$r.=">";
		$showdiv = true;
		$options = array("red", "yellow", "blue");
		if (!is_array($opts["widgets"])) {
			$opts["widgets"] = array();
		}
		foreach ($opts["widgets"] as $divname => $cname) {
			$r.="<div style=\"";
			if ($showdiv) { // allow for any non-zero result here.
			} else {
				$r.="display: none";
			}
			if ($opts["allvisible"]) {
				$showdiv = true;
			} else {
				$showdiv = false;
			}
			$r.="\" id=\"".$inputdef["id"]."_".$cname."\">";
			$dinputdef = array(
					"name"		=>	"",
					"id"		=>	"__demo__".$cname,
					"options"	=>	$options,
					"varname"	=>	"__demo__".$cname,
					"type"		=>	$cname,
				);
			switch ($cname) {
				case "checklabel": {
					$dinputdef["prompt"] = gettrans("msg_demo_checklabel", 
						"This is a demo checklabel");
					break;
				}
				case "infobox": {
					$dinputdef["value"] = gettrans("msg_demo_infobox", 
						str_repeat("This is a demo<br>", 5)."<br>".
						str_repeat("This is still a demo (:<br>", 8));
					break;
				}
				case "label": {
						$dinputdef["value"] = gettrans("msg_demo_label",
							"This is a demo label");
					break;
				}
				default: {
				}
			}
			$r.=$this->renderiinput($dinputdef);
			$r.="</div>";
		}
		$r.="</div>";
		return $r;
	}
	
	function renderiinput(&$inputdef, $idx="") { // <<<2
		switch ($inputdef["type".$idx]) {
			case "demo":
			case "widget_demo":
			case "democontrols": {/*<<<*/
				// compounding of demo controls not supported. It just won't
				//	owrk, eh!
				$extra = array(
					"widgets"	=>	array(
											"textbox",
											"select",
											"listbox",
											"datepicker",
											"modlist",
											"textarea",
											"helpertext",
											"memorytext",
											"checklabel",
											"infobox",
											"label",
											"radio",
											"checkbox",
											"spinner",
										),
					"style"			=> "border: 1px solid red",
					"allvisible"	=> false,
				);
				$this->load_array($extra, $inputdef["extra"]);
				$r = $this->renderdemos($inputdef, $extra);
				break;
			}
/*>>>*/
			case "modlist": { /*<<<*/
				// gives a list which the user can add to and remove from;
				//	the *entire* list is passed on to the post page as
				//	a comma-delimited list, as one would expect from an
				//	assortment of checkboxes...
				//	scripts are applied to the listbox
				$extra = array(
					"hash"		=> -1,
					"scripts"	=>	array(),
					"delimiter"	=>	";",
					"sortopts"	=>	0,
				);
				if (!is_array($inputdef["options".$idx])) {
					$inputdef["options".$idx] = array();
				}
				$this->load_array($inputdef["extra".$idx], $extra);
				if ($inputdef["required".$idx]) {
					if (!array_key_exists("onchange", $extra["scripts"])) {
						$extra["scripts"]["onchange"]="checkval(this);";
					} else {
						if (is_array($extra["scripts"])) {
							$extra["scripts"]["onchange"][]="checkval(this);";
						} else {
							$extra["scripts"]["onchange"].=";checkval(this);";
						}
					}
				}
				if (array_key_exists("onchange", $extra["scripts"])) {
					if (is_array($extra["scripts"]["onchange"])
						|| $extra["scripts"]["onchange"] != "") {
						$r.="<script language=\"Javascript\">\n"
							."	function ".$inputdef["id".$idx]
								."_onchange () {\n";
						if (is_array($extra["scripts"]["onchange"])) {
							foreach ($extra["scripts"]["onchange"] as $scr) {
								$r.=$scr."\n";
							}
						} else {
							$r.=$extra["scripts"]["onchange"];
						}
						$r.="}\n"
							."</script>";
					}
				}
				$r.="<table style=\"padding: 0px; margin: 0px;\" border=\"0\"><tr><td>";
				$r.="<select multiple name=\"".$inputdef["varname".$idx]
					."_list\" id=\"".$inputdef["id".$idx]."_list\"";
				if ($inputdef["style".$idx]!="") {
					if (strpos($inputdef["style".$idx], ":") === false) {
						$r.=" class=\"".$inputdef["style".$idx]."\"";
					} else {
						$r.=" style=\"".$inputdef["style".$idx]."\"";
					}
				} else {
					$r.=" class=\"wizard_modlist\"";
				}
				$r.=">"
					.$this->genopts($inputdef["options".$idx], $extra["hash"],
						$inputdef["value".$idx], $extra["sortopts"])
					."</select>";
				// the hidden field that contains the POST value...
				$r.="<input type=\"hidden\" name=\"".$inputdef["varname".$idx]
					."\" id=\"".$inputdef["id".$idx]."\" value=\""
					.str_replace(",", "", implode($extra["delimiter"], 
					$inputdef["options".$idx]))."\">";
				$r.="<input type=\"hidden\" id=\"".$inputdef["id".$idx]
					."_delimiter\" value=\"".$extra["delimiter"]."\"></td>";
				// the item option buttons
				
				$btnmsgs = array(
					"btn_addnew"	=>	gettrans("btn_addnew", "New"),
					"btn_del"		=>	gettrans("btn_del", "Del"),
					"btn_moveup"	=>	gettrans("btn_moveup", "up"),
					"btn_movedown"	=>	gettrans("btn_movedown", "down"),
					"btn_movetop"	=>	gettrans("btn_movetop", "top"),
					"btn_movebottom"=>	gettrans("btn_movebottom", "bottom"),
					"msg_move"		=>	gettrans("msg_move", "Move selection:"),
					"msg_item_options"=>
								gettrans("msg_item_options", "Item options:"),
				);
				if (array_key_exists("btnmsgs", $extra)) {
					$this->load_array($btnmsgs, $extra["btnmsgs"]);
				}
				$inc = wiz_find_file("toolbar.php");
				if ($inc != "") {
				include_once($inc);
				$r.="<td><table border=\"0\"><tr>";
				$r.="<td colspan=\"2\" style=\"font-size: smaller\">"
					.$btnmsgs["msg_item_options"]
					."</td></tr><tr><td>";
				$mltb = new Toolbar(array("look" => "modern", "bwidth" => "70"));
				$mltb->add_button(array(
					"caption"	=>	$btnmsgs["btn_addnew"],
					"id"		=>	$inputdef["id".$idx]."_btnaddnew",
					"img"		=>	"new",
					"imgpos"	=>	"left",
				));
				$mltb->add_button(array(
					"caption"	=>	$btnmsgs["btn_del"],
					"id"		=>	$inputdef["id".$idx]."_btndel",
					"img"		=>	"delete",
					"imgpos"	=>	"left",
					"disabled"	=>	true,
				));
				$r.=$mltb->render(false);
				$mltb = new Toolbar(array("look" => "modern", "bwidth" => "70"));
				$r.="</td></tr><tr><td colspan=\"2\" style=\"font-size: "
					."smaller\">".$btnmsgs["msg_move"]."</td></tr><tr><td>";
				$mltb->add_button(array(
					"caption"	=>	$btnmsgs["btn_moveup"],
					"id"		=>	$inputdef["id".$idx]."_btnmoveup",
					"img"		=>	"up",
					"imgpos"	=>	"left",
					"disabled"	=>	true,
				));
				$mltb->add_button(array(
					"caption"	=>	$btnmsgs["btn_movetop"],
					"id"		=>	$inputdef["id".$idx]."_btnmovetop",
					"img"		=>	"top",
					"imgpos"	=>	"left",
					"disabled"	=>	true
				));
				$r.=$mltb->render(false);
				$mltb = new Toolbar(array("look" => "modern", "bwidth" => "70"));
				$r.="</td></tr><tr><td>";
				$mltb->add_button(array(
					"caption"	=>	$btnmsgs["btn_movedown"],
					"id"		=>	$inputdef["id".$idx]."_btnmovedown",
					"img"		=>	"down",
					"imgpos"	=>	"left",
					"disabled"	=>	true
				));
				$mltb->add_button(array(
					"caption"	=>	$btnmsgs["btn_movebottom"],
					"id"		=>	$inputdef["id".$idx]."_btnmovebottom",
					"img"		=>	"bottom",
					"imgpos"	=>	"left",
					"disabled"	=>	true
				));
				$r.=$mltb->render(false);
				$r.="</td></tr></table>";
				$r.="</td></tr></table>";
				} else {
				$r.="<td><table border=\"0\"><tr>";
				$r.="<td colspan=\"2\" style=\"font-size: smaller\">"
					.$btnmsgs["msg_item_options"]
					."</td></tr><tr>";
				$r.="<td><input type=\"button\" value=\""
					.$btnmsgs["btn_addnew"]."\" id=\"".$inputdef["id".$idx]
					."_btnaddnew\" class=\"modlist_btn\"></td>";
				$r.="<td><input type=\"button\" value=\""
					.$btnmsgs["btn_del"]."\" id=\"".$inputdef["id".$idx]
					."_btndel\" class=\"modlist_btn\" disabled></td></tr>";
				$r.="<tr><td style=\"font-size: smaller;\" colspan=\"2\">"
					.$btnmsgs["msg_move"]."</td></tr>"; // spacer
				$r.="<tr><td><input disabled type=\"button\" value=\""
					.$btnmsgs["btn_moveup"]."\" id=\"".$inputdef["id".$idx]
					."_btnmoveup\" class=\"modlist_btn\"></td>";
				$r.="<td><input disabled type=\"button\" value=\""
					.$btnmsgs["btn_movetop"]."\" id=\"".$inputdef["id".$idx]
					."_btnmovetop\" class=\"modlist_btn\"></td></tr>";
				$r.="<tr><td><input disabled type=\"button\" value=\""
					.$btnmsgs["btn_movedown"]."\" id=\"".$inputdef["id".$idx]
					."_btnmovedown\" class=\"modlist_btn\"></td>";
				$r.="<td><input disabled type=\"button\" value=\""
					.$btnmsgs["btn_movebottom"]."\" id=\"".$inputdef["id".$idx]
					."_btnmovebottom\" class=\"modlist_btn\">"
					."</td></tr></table>";
				$r.="</td></tr></table>";
				}
				$this->add_event($inputdef["id".$idx]."_btnaddnew", "onclick", 
					"modlist_additem(\"".$inputdef["id".$idx]."\")");
				$this->add_event($inputdef["id".$idx]."_btndel", "onclick", 
					"modlist_delitem(\"".$inputdef["id".$idx]."\")");
				$this->add_event($inputdef["id".$idx]."_btnmoveup", "onclick", 
					"modlist_moveup(\"".$inputdef["id".$idx]."\")");
				$this->add_event($inputdef["id".$idx]."_btnmovedown", 
					"onclick", "modlist_movedown(\""
					.$inputdef["id".$idx]."\")");
				$this->add_event($inputdef["id".$idx]."_btnmovetop", "onclick",
					"modlist_movetop(\"".$inputdef["id".$idx]."\")");
				$this->add_event($inputdef["id".$idx]."_btnmovebottom", 
					"onclick",
					"modlist_movebottom(\"".$inputdef["id".$idx]."\")");
				$this->add_event($inputdef["id".$idx]."_list", "onclick",
					"modlist_checkbtns(\"".$inputdef["id".$idx]."\");");
				foreach ($extra["scripts"] as $ev => $script) {
					$this->add_event($inputdef["id".$idx], $ev, $script);
				}
				
				$extra["badchars"][] = $extra["delimiter"];
				$r.="<script language=\"Javascript\">\n";
				$r.="	if (typeof(modlist_badstrings) == \"undefined\") {\n"
					."		modlist_badstrings = new Array();\n"
					."	}\n";
				foreach ($extra["badchars"] as $chr) {
					$r.="		modlist_badstrings.push(\"".
						$this->jquote($chr)."\");\n";
				}
				$r.="</script>\n";
				break;
			}
/*>>>*/
			case "checklabel": {/*<<<*/
				/* this is a simple concept: a checkbox on the left, with
					a label on the right, which is not how other inputs work
					it's for a boolean value: only good values are 1 and 0
				*/
				$extra = array(
					"scripts"	=>	array(),
				);
				$this->load_array( $inputdef["extra"],$extra);
				$r="<input type=\"checkbox\" value=\"1\" id=\""
					.$inputdef["id".$idx]."\" name=\"".$inputdef["varname".$idx]
					."\"";
				if (val($inputdef["value".$idx])) {
					$r.=" checked";
				}
				if ($inputdef["required".$idx]) {
					$this->add_event($inputdef["id".$idx], "onclick",
						"checkval_radio("
						."this, '".str_replace("'", "\'", 
						$inputdef["required_val".$idx])."')");
					$docheck = "true";
				} else {
					$docheck = "false";
				}
				$r.="><input type=\"checkbox\" name=\""
					.$inputdef["varname".$idx]
					."\" id=\"__anti_".$inputdef["id".$idx]."\" value=\"0\"";
				if (val($inputdef["value".$idx])==0) {
					$r.=" checked";
				}
				if ($inputdef["required"]) {
					$docheck = "true";
				} else {
					$docheck = "false";
				}
				$r.=" style=\"display: none\">";
				$r.="<span id=\"__span_".$inputdef["id".$idx]
					."\" class=\"wizard_check\">"
					.$inputdef["prompt".$idx]."</span>";
				$this->add_event("__span_".$inputdef["id".$idx], "onclick",
					"togglechecklabel('".$inputdef["id".$idx]
					."', ".$docheck.")");
				if (strlen($inputdef["tooltip"])) {
					global $tooltips;
					if ($inputdef["tooltip_title"]=="") {
						$tt["title"]="Helpful tip:";
					} else {
						$tt["title"]=$this->jhesc($inputdef["tooltip_title"]);
					}
					$tt["text"]=$this->jhesc($inputdef["tooltip"]);
					$tt["id"]="__span_".$inputdef["id".$idx];
					$tooltips[]=$tt;
				}
				if (is_array($extra["scripts"])) {
					foreach ($extra["scripts"] as $ev => $script) {
						$this->add_event($inputdef["id".$idx], $ev, $script);
						$this->add_event("__span_".$inputdef["id".$idx], $ev,
							$script);
					}
				}
				
				break;
			}
/*>>>*/
			case "hidden": break; // handled elsewhere
			case "input":
			case "entry":
			case "password":
			case "lockedentry":
			case "textbox": {/*<<<*/
				$extra=array(
					"scripts"	=> array(),
				);
				$this->load_array($inputdef["extra".$idx], $extra);
				if ($inputdef["required".$idx]) {
					if (!array_key_exists("onkeyup", $extra["scripts"])) {
						$extra["scripts"]["onkeyup"] = "";
					}
					$extra["scripts"]["onkeyup"].=";checkval(this);";
					if (!array_key_exists("onchange", $extra["scripts"])) {
						$extra["scripts"]["onchange"] = "";
					}
					$extra["scripts"]["onchange"].=";checkval(this);";
				}
				if ($inputdef["type"] == "password") {
					$type=" type=\"password\" ";
				} else {
					$type = "";
				}
				$r.="<input $type name=\"".$inputdef["varname".$idx]."\" id=\""
					.$inputdef["id".$idx]."\""
					.$this->gentitle($inputdef["title"])." value=\""
					.$this->hesc($inputdef["value".$idx])."\"";
				if ($inputdef["style".$idx]!="") {
					if (strpos($inputdef["style".$idx], ":") === false) {
						$r.=" class=\"".$inputdef["style".$idx]."\"";
					} else {
						$r.=" style=\"".$inputdef["style".$idx]."\"";
					}
				} else {
					if ($inputdef["type".$idx] == "lockedentry") {
						$r.=" class=\"wizard_textbox_locked\"";
					} else {
						$r.=" class=\"wizard_textbox\"";
					}
				}
				$r.=">";
				foreach ($extra["scripts"] as $ev => $script) {
					$this->add_event($inputdef["id".$idx], $ev, $script);
				}
				if ($inputdef["type"] == "lockedentry") {
					$this->add_event($inputdef["id".$idx], "onfocus", "this.blur()");
				}
				break;
			}/*>>>*/
			case "text":
			case "textarea": { /*<<<*/
				$extra=array(
					"cols" 			=> "40",
					"rows" 			=> "4",
					"resizable" 	=> 1,
					"sizeup_img" 	=> wiz_find_graphic("zoomin"),
					"sizedown_img"	=> wiz_find_graphic("zoomout"),
					"scripts"		=> array(),
				);
				$this->load_array($inputdef["extra".$idx], $extra);
				$size_up_msg = gettrans("msg_textbox_incsize", "Click here"
					." to increase the size of this text box...");
				$size_down_msg = gettrans("msg_textbox_decsize", "Click here"
					." to decrease the size of this text box...");
				if ($inputdef["required".$idx]) {
					if (!array_key_exists("onkeyup", $extra["scripts"])) {
						$extra["scripts"]["onkeyup"] = "";
					}
					$extra["scripts"]["onkeyup"].=";checkval(this);";
				}
				if ($extra["resizable"]) {
					$r.="<table cellpadding=\"0\" cellspacing=\"0\">"
						."<tr><td valign=\"top\">";
				}
				$r.="<textarea name=\"".$inputdef["varname".$idx]."\" id=\""
					.$inputdef["id".$idx]."\"";
				$r.=" rows=\"".$extra["rows"]."\" cols=\"".$extra["cols"]."\"";
				if (strlen($inputdef["style"])) {
					if (strpos($inputdef["style".$idx], ":")===false) {
						$r.=" class=\"".$inputdef["style".$idx]."\"";
					} else {
						$r.=" style=\"".$inputdef["style".$idx]."\"";
					}
				} else {
					$r.=" class=\"wizard_textarea\" style=\"height: 100px;\"";
				}
				foreach ($extra["scripts"] as $ev => $script) {
					$this->add_event($inputdef["id".$idx], $ev, $script);
				}
				$r.=">".$inputdef["value".$idx]."</textarea>";
				if ($extra["resizable"]) {
					if (file_exists($extra["sizeup_img"])) {
						$r.="</td><td valign=\"top\"><img src=\""
							.$extra["sizeup_img"]."\" style=\"width:16;"
							."height:16\" alt=\"+\""
							.$this->gentitle($size_up_msg)
							." onclick=\"sizeinc('"
							.$inputdef["id".$idx]."', 20);\"><br>";
					} else {
						$r.="</td><td valign=\"top\"><input type=\"button\""
							." style=\"width:20;"
							."height:20\" value=\"+\""
							.$this->gentitle($size_up_msg)
							." onclick=\"sizeinc('"
							.$inputdef["id".$idx]."', 20);\"><br>";
					}
					if (file_exists($extra["sizedown_img"])) {
						$r.="<img src=\"".$extra["sizedown_img"]."\" "
							."style=\"width: 16; height: 16;\" alt=\"-\""
							.$this->gentitle($size_down_msg)
							." onclick=\"sizeinc('"
							.$inputdef["id".$idx]
							."', -20);\"></td></tr></table>";
					} else {
						$r.="<input type=\"button\" "
							."style=\"width: 20; height: 20;\" value=\"-\""
							.$this->gentitle($size_down_msg)
							." onclick=\"sizeinc('"
							.$inputdef["id".$idx]
							."', -20);\"></td></tr></table>";
					}
				}
				break;
			}/*>>>*/
			case "droplist":
			case "select": 	{/*<<<*/
				$extra=array(
					"hash"		=> -1,
					"scripts"	=> array(),
					"sortopts"	=> 0,
				);
				$this->load_array($inputdef["extra".$idx], $extra);
				if ($inputdef["required".$idx]) {
					if (!array_key_exists("onchange", $extra["scripts"])) {
						$extra["scripts"]["onchange"] = "";
					}
					$extra["scripts"]["onchange"].=";checkval(this);";
				}
				$r.="<select name=\"".$inputdef["varname".$idx]."\" id=\""
					.$inputdef["id".$idx]."\"";
				if ($inputdef["style".$idx] == "") {
					$r.=" class=\"wizard_select\"";
				} else {
					if (strpos($inputdef["style".$idx], ":")===false) {
						$r.=" class=\"".$inputdef["style".$idx]."\"";
					} else {
						$r.=" style=\"".$inputdef["style".$idx]."\"";
					}
				}
				$r.=">"
					.$this->genopts($inputdef["options".$idx], $extra["hash"],
						$inputdef["value".$idx], $extra["sortopts"])
					."</select>";
				foreach ($extra["scripts"] as $ev => $script) {
					$this->add_event($inputdef["id".$idx], $ev, $script);
				}
				break;
			}/*>>>*/
			case "cassel": {/*<<<*/
				// makes use of the Cassel class to create N-Tier cascading
				//	select inputs. Relies on the cassel.php include, and will
				//	generate a text output error if the class cannot be used.
				if (class_exists("Cassel")) {
					$extra = array(
						"scripts"	=>	array(),
					);
					$this->load_array($inputdef["extra".$idx], $extra);
					$cobj = new Cassel(array(
							"name"	=>	$inputdef["varname".$idx],
							"id"	=>	$inputdef["id".$idx],
						));
					if ($inputdef["required".$idx]) {
						$cobj->onchange_script = "checkval(this);";
					}
					if (strlen($extra["scripts"])) {
						$cobj->other_scripts = $extra["scripts"];
					}
					if (strlen($inputdef["style".$idx])) {
						$cobj->style = $inputdef["style".$idx];
					} else {
						$cobj->style="wizard_select";
					}
					$cobj->cascades = $inputdef["cascades"];
					$cobj->sel_idx = $inputdef["value"];
					$cobj->sel_val = $inputdef["value"];
					$r.=$cobj->render(false);
					if (array_key_exists("scripts", $extra) 
						&& is_array($extra["scripts"])) {
						foreach($extra["scripts"] as $ev => $script) {
							$this->register_event($ev, $script);
						}
					}
				} else {
					$r="Unable to render Cassel object: the Cassel class is"
						." not defined. Please make sure that the cassel.php"
						." file can be found in ".dirname(__FILE__);
				}
				break;
			}
/*>>>*/
			case "listbox":
			case "staticlist":
			case "list":	{/*<<<*/
				$extra=array(
					"hash"		=> -1,
					"scripts"	=> array(),
					"sortopts"	=> 0,
				);
				$this->load_array($inputdef["extra".$idx], $extra);
				if ($inputdef["required".$idx]) {
					if (!array_key_exists("onchange", $extra["scripts"])) {
						$extra["scripts"]["onchange"] = "";
					}
					$extra["scripts"]["onchange"].="checkval(this);";
				}
				//hack in some brackets for array gen at post
				if (strpos("[]", $inputdef["varname".$idx]) === false) {
					$hb = "[]";
				} else {
					$hb = "";
				}
				$r.="<select multiple name=\"".$inputdef["varname".$idx]
					.$hb."\" id=\"".$inputdef["id".$idx]."\"";
				if ($inputdef["style".$idx]!="") {
					if (strpos($inputdef["style".$idx], ":")===false) {
						$r.=" class=\"".$inputdef["style".$idx]."\"";
					} else {
						$r.=" style=\"".$inputdef["style".$idx]."\"";
					}
				} else {
					$r.=" class=\"wizard_list\"";
				}
				$r.=">".$this->genopts($inputdef["options".$idx], 
					$extra["hash"], $inputdef["value".$idx],
					$extra["sortopts"])."</select>";
				foreach ($extra["scripts"] as $ev => $script) {
					$this->add_event($inputdef["id".$idx], $ev, $script);
				}
				break;
			}/*>>>*/
			case "datepicker":
			case "dateselect":
			case "date":	{/*<<<*/
				// bad_day_list is a semi-colon-delimited list
				//	of non-allowed days, eg:
				//	2004-11-12;Nov-13;Dec-Sun;mon
				//	which disables:
				//		12 November 2004
				//		13 November, all years,
				//		All Sundays in December (all years)
				//		All mondays, all months, all years
				$extra=array(
					"scripts"		=> array(),
					"button_img"	=> wiz_find_file("datepickerbutton.gif"),
					"pop_page"		=> wiz_find_file("datepicker.php"),
					"title"			=> "Select date:",
					"dateformat"	=> "Y-m-d",
					"allowweekends"	=> 1,
					"allowholidays"	=> 1,
					"bad_day_list"	=> "",
					"show_time"		=> 0,
				);
				$this->load_array($inputdef["extra".$idx], $extra);
				if ($inputdef["required".$idx]) {
					if (!array_key_exists("onchange", $extra["scripts"])) {
						$extra["scripts"]["onchange"] = "";
					}
					$extra["scripts"]["onchange"].=";checkval(this);";
				}
				$r.="<input name=\"".$inputdef["varname".$idx]."\" id=\""
					.$inputdef["id"].$idx."\" value=\""
					.$this->hesc($inputdef["value".$idx])."\" onfocus=\""
					."this.blur();\">";
				if (file_exists($extra["button_img"])) {
					$r.="<img src=\""
						.$extra["button_img"]."\" style=\"border: 1px "
						."solid #FF0000;\" onmouseover=\"this.style."
						."background='#FF0000';\" onmouseout=\"this."
						."style.background='';\" id=\"".$inputdef["id".$idx]
						."_button\""; 
				} else {
					$r.="<input type=\"button\" value=\"...\" style=\"width:"
						."22px; height: 22px;\""
						." id=\"".$inputdef["id".$idx]
						."_button\""; 
				}
				foreach ($extra["scripts"] as $ev => $script) {
					$this->add_event($inputdef["id".$idx], $ev, $script);
				}
				switch ($this->settings["datepickstyle"]) {
					case "popup": {
						$r.=" ".$this->gentitle(gettrans("msg_click_select_date", "Click here to select a date.")).">";
						$this->add_event($inputdef["id".$idx]."_button",
							"onclick",
							"popdatepicker(\"".$extra["pop_page"]."\", \""
							.$inputdef["id".$idx]."\", \""
							.gettrans("msg_select_date", "Select a date:")."\", \""
							.$extra["dateformat"]."\", \""
							.$extra["allowweekends"]."\", \""
							.$extra["allowholidays"]."\", \""
							.$extra["bad_day_list"]."\")");
						break;
					}
					case "inline":
					case "dhtml": {
						// try to get the date format into the one the dhtml
						//	calendar likes
						$r.=">\n";
						$r.=<<<JS_FUNC
<script language="Javascript">
	function date_status_${inputdef["varname"]} (date, y, m, d) {
JS_FUNC;
// >>>
						if (!$extra["allowweekends"]) {
			$r.=<<<JS_FUNC
	var wday=date.getDay();
	if ((wday==0)||(wday==6)) {
		return true;
	}
JS_FUNC;
//>>>
						}
			$r.=<<<JS_FUNC
	if (m<10) {
		strm='0'+m;
	} else {
		strm=''+m;
	}
	if (d<10) {
		strd='0'+d;
	} else {
		strd=''+d;
	}
	if (SPECIAL_DAYS_${inputdef["varname"]}.indexOf(strd+'|')>-1) {
		if (SPECIAL_DAYS_${inputdef["varname"]}.indexOf('|-1/-1/'+strd+'|')>-1)
			return true;
		if (SPECIAL_DAYS_${inputdef["varname"]}.indexOf('/'+strm+'/')>-1) {
			if (SPECIAL_DAYS_${inputdef["varname"]}.indexOf('|-1/'+strm+'/-1')>-1) 
			return true;
			if (SPECIAL_DAYS_${inputdef["varname"]}.indexOf('|-1/'+strm+'/'+strd+'|')>-1)
				return true;
			if (SPECIAL_DAYS_${inputdef["varname"]}.indexOf('|'+y+'/'+strm+'/'+strd+'|')>-1) 
				return true;
		}
	}
}
JS_FUNC;
// >>>
						$r.=$this->genspecialdates($inputdef["varname".$idx],
							$extra["allowholidays"],	
							$extra["bad_day_list"]);
						$r.="\n</script>";
						$extra["dateformat"]=str_replace("Y", "%Y", 
							str_replace("y", "%y", str_replace("d", "%d",
							str_replace("m", "%m", $extra["dateformat"]))));
						$r.="\n<script language=\"Javascript\">Calendar.setup("
							."{inputField 	: \""
								.$inputdef["varname".$idx]."\","
							." ifFormat		: \"".$extra["dateformat"]."\","
							." button		: \""
								.$inputdef["varname".$idx]."_button\","
							." dateStatusFunc	: date_status_"
								.$inputdef["varname".$idx].","
							." showsTime	: ";
						if ($extra["show_time"]) {
							$r.="true";
						} else {
							$r.="false";
						}
						$r.="});\n</script>";
						$special="";
						if (strlen($extra["bad_day_list"])) {
							$b=explode(";", $extra["bad_day_list"]);
							foreach ($extra as $val) {
								$special.=$this->genspecialdates($val);
							}
						}
						}
						if (array_key_exists("scripts", $extra)
								&& is_array($extra["scripts"])) {
							foreach ($extra["scripts"] as $ev => $script) {
								$this->add_event($inputdef["id".$idx], $ev,
									$script);
							}
						}
					}
				break;
			}/*>>>*/
			case "spinint":
			case "spinselect":
			case "spinner":	{/*<<<*/
				$extra=array(
					"scripts" 	=> array(),
					"strict"	=> true,
					"up_img"	=> wiz_find_file("spinner_up.gif"),
					"down_img"	=> wiz_find_file("spinner_down.gif"),
					"up_title"	=> "Click here to select the next allowable"
										." item...",
					"down_title"=> "Click here to select the previous"
										." allowable item...",
					"input_title"=>"set me",
					"low_val"	=> "",
					"high_val"	=> "",
					"step"		=> "",
				);
				$this->load_array($inputdef["extra"], $extra);
				if ($inputdef["required".$idx]) {
					if (!array_key_exists("onchange", $extra["scripts"])) {
						$extra["scripts"]["onchange"] = "";
					}
					$extra["scripts"]["onchange"].=";checkval(this);";
					if (!array_key_exists("onkeyup", $extra["scripts"])) {
						$extra["scripts"]["onkeyup"].=";checkval(this);";
					}
				}
				if (($extra["low_val"] != "") && ($extra["high_val"] != "") 
					&& ($extra["step"] != "")) {
					// auto-gen option list from low, high & step values
					if ($extra["low_val"] > $extra["high_val"]) {
						$swp=$extra["low_val"];
						$extra["low_val"]=$extra["high_val"];
						$extra["high_val"]=$swp;
					}
					if ($extra["step"]<0) $extra["step"]=(-1)*$extra["step"];
					$i=$extra["low_val"];
					while ($i<$extra["high_val"]) {
						$inputdef["options".$idx][$i]=$i;
						$i+=$extra["step"];
					}
				}
				if ($extra["input_title"]=="set me") {
					if ($extra["strict"]) {
						$s_msg=gettrans("msg_spinner_strict", 
							"Enter a value here, or click on "
							."the buttons to the right to go through a "
							."list of suggested values.");
						$extra["input_title"]=$s_msg;
					} else {
						$s_msg=gettrans("msg_spinner_nostrict",
						"Click on the buttons to the "
						."right to go through a list of allowable values.");
						$extra["input_title"]=$s_msg;
					}
				}
				$r.="\n<script language=\"Javascript\">\nvar optionlist_"
					.$inputdef["varname".$idx]." = new Array();\n";
				if (is_array($inputdef["options".$idx])) {
					$oidx=0;
					foreach ($inputdef["options".$idx] as $val) {
						$r.="optionlist_".$inputdef["varname".$idx]
							."[".$oidx++."] = '".$this->hesc($val)."';\n";
					}
				} else {
					$r.="var optionlist_".$inputdef["varname".$idx]."[0]='"
						."no options defined.';";
				}
				$r.="</script>";
				$r.="<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\""
					."><tr><td>";
				$r.="<input name=\"".$inputdef["varname".$idx]."\" id=\""
					.$inputdef["id".$idx]."\"";
				if ($inputdef["style".$idx]!="") {
					if (strpos($inputdef["style".$idx], ":")===false) {
						$r.=" class=\"".$inputdef["style".$idx]."\"";
					} else {
						$r.=" style=\"".$inputdef["style".$idx]."\"";
					}
				} else {
					$r.=" class=\"wizard_spinner\"";
				}
				$r.=" value=\"".$this->hesc($inputdef["value".$idx])."\"";
				if ($extra["strict"]) {
					$r.=" onfocus=\"this.blur\"";
				}
				$r.=$this->gentitle($extra["input_title"]);
				if (file_exists($extra["up_img"]) 
					&& file_exists($extra["down_img"])) {
					$r.="></td><td><img src=\"".$extra["up_img"]."\""
						." onclick=\"change_val('"
						.$inputdef["varname".$idx]."', "
						."optionlist_".$inputdef["varname".$idx].", -1);\""
						.$this->gentitle($extra["up_title"])
						."><br><img src=\""
						.$extra["down_img"]."\" onclick=\"change_val('"
						.$inputdef["id".$idx]
						."', optionlist_".$inputdef["varname".$idx]
						.", 1);\"".$this->gentitle($extra["down_title"])
						."></td></tr></table>";
				} else {
					$r.="></td><td><input type=\"button\" value=\"&lt;\""
						." onclick=\"change_val('"
						.$inputdef["varname".$idx]."', "
						."optionlist_".$inputdef["varname".$idx].", -1);\""
						.$this->gentitle($extra["up_title"])
						."><input type=\"button\" value=\"&gt;\""
						." onclick=\"change_val('"
						.$inputdef["id".$idx]
						."', optionlist_".$inputdef["varname".$idx]
						.", 1);\"".$this->gentitle($extra["down_title"])
						."></td></tr></table>";
				}
				foreach ($extra["scripts"] as $ev => $script) {
					$this->add_event($inputdef["id".$idx], $ev, $script);
				}
				break;
			}/*>>>*/
			case "checkbutton": 
			case "checkbox": 
			case "radio": {/*<<<*/
				if (strpos($inputdef["type".$idx], "check")!==false) {
					$inputdef["type".$idx]="checkbox";
				} elseif (strpos($inputdef["type".$idx], "radio")!==false) {
					$inputdef["type".$idx]="radio";
				}
				$extra=array(
					"cols"	=> 1,
					"rows"	=> 0,
					"hash"	=> -1,
					"scripts" => array(),
					"sortopts" => 0,
				);
				$this->load_array($inputdef["extra".$idx], $extra);
				if ($inputdef["required".$idx]) {
					if (!array_key_exists("onclick", $extra["scripts"])) {
						$extra["scripts"]["onclick"] = "";
					}
					$extra["scripts"]["onclick"].=";checkval_radio(this, '"
						.str_replace("'", "\'", $inputdef["required_val".$idx])
						."');\" ";
				}
				if (is_array($inputdef["options"])) {
					$numopts=count($inputdef["options".$idx]);
				} else {
					$inputdef["options".$idx]=array(0 => "no options defined");
					$numopts=1;
				}
				// geometry
				//	if rows is specified, it's really only used to calculate
				//	the columns.
				if ($extra["cols"] == 0) {
					if ($extra["rows"] == 0) {
						$extra["cols"]=1;
					} else {
						$extra["cols"]=$numopts/$extra["rows"];
					}
				}
				$r.="<table border=\"0\" cellpadding=\"2\" cellspacing=\"2\"><tr>";
				$genopts=array();
				$this->gencheckradio($genopts, $inputdef["options".$idx], 
					$inputdef["value".$idx], $inputdef["varname".$idx], 
					$extra["hash"],	$inputdef["type".$idx], 
					$inputdef["id".$idx], $inputdef["required".$idx], 
					$extra["scripts"], $extra["sortopts"]);
				$currcol=0;
				foreach ($genopts as $val) {
					$r.="<td>".$val."</td>";
					$currcol++;
					if ($currcol % $extra["cols"] == 0) {
						$r.="</tr><tr>";
					}
				}
				$r.="</tr></table>";
				break;
			}/*>>>*/
			case "combobox":
			case "helpertext": 
			case "memorytext": {/*<<<*/
				/* <<< about memorytext:
					memory text relies on the new dhtml combobox control
					this control gives a drop-down list, but allows the user
					to enter her own selection. Whatever is chosen is
					forwarded on to the "save_page" (if set). This save page
					must decide whether or not to save the user's entry, and
					how.
					helpertext has the same functionality as a memorytext, 
					exept that new items are not added to the list.
				>>>*/
				$extra=array(
					"save_page"		=> "",
					"button_img"	=> "",
					"background"	=> "#ccccee",
					"color"			=> "#000015",
					"selbg"			=> "#4545ff",
					"selcolor"		=> "#000000",
					"scripts"		=> array(),
				);
				$this->load_array($inputdef["extra".$idx], $extra);
				$frmname = "frm".$inputdef["id".$idx];
				if ($inputdef["required".$idx]) {
					if (!array_key_exists("onchange", $extra["scripts"])) {
						$extra["scripts"]["onchange"] = "";
					}
					$extra["scripts"]["onchange"].=";checkval(this);";
					if ($inputdef["type".$idx] == "memorytext") {
						$extra["scripts"]["onchange"].=
						"if(frm=window.frames['".$frmname."'].document."
						."frm) {"
						."if (frm.newval) {frm.newval.value=this.value; "
						."frm.submit();}}";
					}
					if (!array_key_exists("onkeyup", $extra["scripts"])) {
						$extra["scripts"]["onkeyup"] = "";
					}
					$extra["scripts"]["onkeyup"].=";checkval(this);";
				}
				$tip=gettrans("msg_memorytext_tip",
					"Type in a value here or select an option"
					." from the list by clicking the button to the right");
				$r.="<input name=\"".$inputdef["varname".$idx]."\" id=\""
					.$inputdef["id".$idx]."\" value=\""
					.$inputdef["value".$idx]."\" "
					.$this->gentitle($tip);
				if ($inputdef["style".$idx]!="") {
					if (strpos($inputdef["style".$idx], ":")===false) {
						$r.=" class=\"".$inputdef["style".$idx]."\"";
					} else {
						$r.=" style=\"".$inputdef["style".$idx]."\"";
					}
				} else {
					$r.=" class=\"wizard_selbox\">";
				}
				if (file_exists("images/comboselect_down.png")) {
					$extra["button_down_img"] = "images/comboselect_down.png";
				}
				if (file_exists("images/comboselect_up.png")) {
					$extra["button_img"] = "images/comboselect_up.png";
				}
				if ($extra["button_img"]!="") {
					$r.="<img src=\"".$extra["button_img"]."\" name=\"button_"
						.$inputdef["varname".$idx]."\" id=\"button_"
						.$inputdef["id".$idx]."\">";
					if ($extra["button_down_img"] != "") {
						$swap_images = true;
					}
				} else {
					$r.="<input type=\"button\" value=\"V\" name=\"button_"
					.$inputdef["varname".$idx]."\" id=\"button_"
					.$inputdef["id".$idx]
					."\" style=\"width: 25px;\">";
				}
				if (strlen($extra["save_page"])) {
					$r.="<iframe src=\"".$extra["save_page"]
						."\" style=\"width: 0px; height: 0px; display: none\">"
						."</iframe>";
				}
				$r.="<script language=\"Javascript\">\nComboSelect.setup ({\n"
					."input	    : \"".$inputdef["varname"]."\",\n"
					."button    : \"button_".$inputdef["id".$idx]."\",\n"
					."options   : \"".implode(";", $inputdef["options"])."\",\n"
					."background: \"".$extra["background"]."\",\n"
					."color		: \"".$extra["color"]."\",\n"
					."selbg		: \"".$extra["selbg"]."\",\n"
					."selcolor	: \"".$extra["selcolor"]."\"\n"
					."});\n</script>";
					// passing this option to a save page to be handled at the
					//	time of wizard completion.
				foreach ($extra["scripts"] as $ev => $script) {
					$this->add_event($inputdef["id".$idx], $ev, $script);
				}
				if (isset($swap_images)) {
					$this->add_event("button_".$inputdef["id".$idx], 
						"onmousedown", "swapimg(this, '"
						.$extra["button_down_img"]."')");
					$this->add_event("button_".$inputdef["id".$idx],
						"onmouseup", "swapimg(this, '"
						.$extra["button_img"]."')");
				}
				break;
			}/*>>>*/
			case "largelabel":
			case "license":
			case "infobox": {/*<<<*/
				$substyle = "";
				if (array_key_exists("extra", $inputdef)) {
					if (array_key_exists("height", $inputdef["extra"])) {
						$substyle.="height: ".$inputdef["extra"]["height"].";";
					}
					if (array_key_exists("width", $inputdef["extra"])) {
						$substyle.="width: ".$inputdef["extra"]["height"].";";
					}
				}
				if ($inputdef["style"] != "") {
					if (strpos($inputdef["style".$idx], ":")===false) {
						$r.="<div class=\"".$inputdef["style"]."\" id=\""
							.$inputdef["id"]."\">"
							.$inputdef["value"]."</div>";
					} else {
						$r.="<div style=\"".$inputdef["style"]."\" id=\""
							.$inputdef["id"]."\">"
							.$inputdef["value"]."</div>";
					}
				} else {
					$r.="<div class=\"wizard_infobox\"";
					if (strlen($substyle)) {
						$r.=" style=\"".$substyle."\"";
					}
					$r.=" id=\""
						.$inputdef["id"]."\">".str_replace("\n", "<br>",
							$inputdef["value"])."</div>";
				}
				if (strlen($inputdef["tooltip"])) {
					global $tooltips;
					if ($inputdef["tooltip_title"]=="") {
						$tt["title"]="Helpful tip:";
					} else {
						$tt["title"]=$this->jhesc($inputdef["tooltip_title"]);
					}
					$tt["text"]=$this->jhesc($inputdef["tooltip"]);
					$tt["id"]=$inputdef["id".$idx];
					$tooltips[]=$tt;
				}
				break;
			}/*>>>*/
			case "label": {/*<<<*/
				$extra = array(
					"scripts"	=>	array(),
				);
				$this->load_array($inputdef["extra".$idx], $extra);
				if ($inputdef["style"] != "") {
					if (strpos($inputdef["style".$idx], ":")===false) {
						$r.="<div id=\"".$inputdef["id".$idx]
							."\" class=\"".$inputdef["style"]."\">"
							.$inputdef["value"]."</div>";
					} else {
						$r.="<div id=\"".$inputdef["id".$idx]
							."\" style=\"".$inputdef["style"]."\">"
							.$inputdef["value"]."</div>";
					}
				} else {
					$r.="<div class=\"wizard_label\" id=\""
						.$inputdef["id".$idx]."\">".$inputdef["value"]
						."</div>";
				}
				if (is_array($extra["scripts"])) {
					foreach ($extra["scripts"] as $ev => $script) {
						$this->add_event($inputdef["id".$idx], $ev, $script);
					}
				}
				break;
			}/*>>>*/
			case "tlc_select": {/*<<<*/
				$inc = wiz_find_file("toolbar.php");
				if (file_exists($inc)) {
					$r.="<table cellpadding=\"0\" cellspacing=\"0\" "
						."border=\"0\"><tr><td>";
					$use_toolbar = true;
				} else $use_toolbar = false;
				$btn_img="";
				// default is a user select
				$host="";
				$port="";
				$module="user";
				$cmd="list_users"; // default action is to list active users
				$data="";
				if (is_array($inputdef["extra".$idx])) {
					$extra=$inputdef["extra".$idx];
					if (array_key_exists("btn_img", $extra)
						&& $extra["btn_img"]!="") {
						$btn_img=$extra["btn_img"];
					}
					if (array_key_exists("host", $extra)
						&& $extra["host"]!="") {
						$host=$extra["host"];
					}
					if (!array_key_exists("port", $extra)
						&& $extra["port"]!="") {
						$port=$extra["port"];
					}
					if (array_key_exists("module", $extra)
						&& $extra["module"]!="") {
						$module=$extra["module"];
					}
					if (array_key_exists("cmd", $extra)
						&& $extra["cmd"]!="") {
						$cmd=$extra["cmd"];
					}
					if (array_key_exists("data", $extra)
						&& $extra["data"]!="") {
						$data=$extra["data"];
					}
					if (!array_key_exists("phpurl", $extra)
						&& $extra["phpurl"]=="") {
						$phpurl=wiz_find_file("tlc_select.php");
					} else {
						$phpurl=$extra["phpurl"];
					}
					if (!array_key_exists("multi", $extra)) {
						$extra["multi"] = 0;
					}
					if (!array_key_exists("hideid", $extra) 
						|| $extra["hideid"] == 0) {
						$extra["hideid"] = "";
					$r.="<input name=\"".$inputdef["varname"]."\" id=\""
						.$inputdef["id".$idx]."\" value=\""
						.$inputdef["value".$idx]
						."\" onfocus=\"this.blur();\"";
					if ($inputdef["style".$idx]!="") {
						if (strpos($inputdef["style".$idx], ":")===false) {
							$r.=" class=\"".$inputdef["style".$idx]."\"";
						} else {
							$r.=" style=\"".$inputdef["style".$idx]."\"";
						}
					} else {
						$r.=" class=\"wizard_textbox_med\"";
					}
					$r.=">";
					} else {
						if (!array_key_exists("desc", $extra)) {
							$extra["desc"] = $inputdef["value".$idx];
						}
					$r.="<input name=\"".$inputdef["varname".$idx]
						."_desc\" id=\""
						.$inputdef["id".$idx]."_desc\" value=\""
						.$extra["desc"]
						."\" onfocus=\"this.blur();\"";
					if ($inputdef["style".$idx]!="") {
						if (strpos($inputdef["style".$idx], ":")===false) {
							$r.=" class=\"".$inputdef["style".$idx]."\"";
						} else {
							$r.=" style=\"".$inputdef["style".$idx]."\"";
						}
					} else {
						$r.=" class=\"wizard_textbox_med\"";
					}
					$r.="><input type=\"hidden\" id=\"".$inputdef["id".$idx]
						."\" name=\"".$inputdef["varname".$idx]."\" value=\""
						.$inputdef["value".$idx]."\">";
					}
					$otherargs = array("cols","title","caption", "lang");
					foreach ($otherargs as $arg) {
						if (!array_key_exists($arg, $extra)) {
							$extra[$arg] = "";
						}
					}
					if (!array_key_exists("lang", $extra)) {
						global $lang;
						if (isset($lang)) {
							$extra["lang"] = $lang;
						}
					}
					if ($use_toolbar) {
						include_once($inc);
						$r.="<td>";
						$tb = new Toolbar(array("look"=>"modern"));
						if ($btn_img == "") {
							$caption = "...";
						} else {
							$caption = "";
						}
						$tb->add_button(array(
							"caption"	=>	$caption,
							"img"		=>	$btn_img,
							"imgpos"	=>	"left",
							"id"		=>	"__btn_".$inputdef["id".$idx]
						));
						$r.=$tb->render(false);
						$r.="</td></tr></table>";
					} else {
						if (!file_exists($btn_img)) {
							$r.="<input type=\"button\" style=\"width:"
								."25px;\" value=\"...\" id=\"__btn_"
								.$inputdef["id".$idx]."\">";
						} else {
							$r.="<img src=\"".$btn_img."\" id=\"__btn_"
								.$inputdef["id".$idx]."\">";
						}
					}
					tack_on_nonblank_url_arg($phpurl, "host", $host);
					tack_on_nonblank_url_arg($phpurl, "module", $module);
					tack_on_nonblank_url_arg($phpurl, "cmd", $cmd);
					tack_on_nonblank_url_arg($phpurl, "data", $data);
					tack_on_nonblank_url_arg($phpurl, "hideid", $extra["hideid"]);
					if (array_key_exists("hideid", $extra) 
						&& $extra["hideid"]) {
					tack_on_nonblank_url_arg($phpurl, "targetid",
						$inputdef["id".$idx]);
					tack_on_nonblank_url_arg($phpurl, "target", 
						$inputdef["id".$idx]."_desc");
					} else {
					tack_on_nonblank_url_arg($phpurl, "target",
						$inputdef["id".$idx]);
					}
					tack_on_nonblank_url_arg($phpurl, "multi", $extra["multi"]);
					tack_on_nonblank_url_arg($phpurl, "delimiter", 
						$extra["delimiter"]);
					tack_on_nonblank_url_arg($phpurl, "cols", $extra["cols"]);
					tack_on_nonblank_url_arg($phpurl, "title", $extra["title"]);
					tack_on_nonblank_url_arg($phpurl, "caption", 
														$extra["caption"]);
					tack_on_nonblank_url_arg($phpurl, "lang", $extra["lang"]);
					if (array_key_exists("insert", $extra)) {
						if (is_array($extra["insert"])) {
							$delim = "";
							foreach ($extra["insert"] as $iidx => $ival) {
								if (is_array($ival)) {
									$insertstr.="|";
									$delim = "";
									foreach ($ival as $aidx => $aval) {
										$insertstr.=$delim.$aval;
										$delim = ";";
									}
								} else {
									$insertstr.=$delim.$ival;
								}
								$delim=";";
							}
						} else {
							$insertstr = $extra["insert"];
						}
						tack_on_nonblank_url_arg($phpurl, "insert", $insertstr);
					}
					if ($inputdef["required".$idx])
						tack_on_nonblank_url_arg($phpurl, "jsaftersel", 
							"wizard_validate_".$inputdef["id"]."()");
					if ($extra["winargs"] == "") {
						$winargs="resize=0,status=0,toolbar=0,location=0,"
							."menubar=0,directories=0,scrollbars=0";
					} else {
						$winargs=$extra["winargs"];
					}
					$this->add_event("__btn_".$inputdef["id".$idx], "onclick",
						"tlc_select_popup('__btn_".$inputdef["id".$idx]
						."', '".$phpurl."', '".$winargs."')");
					if (array_key_exists("scripts", $extra)
						&& (is_array($extra["scripts"]))) {
						foreach ($extra["scripts"] as $ev => $script) {
							$this->add_event($inputdef["id".$idx], $ev, 
								$script);
						}
					}
				} else {
					$r.="<input type=\"button\" onclick=\"alert('Error: TLC "
						."selecter not properly defined: please check your "
						."wizard setup and ensure that there is an \'extra\' "
						."array');\" value=\"...\">";
				}
				if ($inputdef["required"]) {
				/*
				$r.="<script language=\"Javascript\">\nfunction "
					."wizard_validate_".$inputdef["id"]."() {\n\t"
					."if (obj=document.getElementById('"
						.$inputdef["id"]."')) {\n\t\t"
					."checkval (obj, '".str_replace("'", "\'", 
						$inputdef["required_val"])."');\n\t"
					."}\n"
					."}\n"
					."</script>";
					*/
					$this->add_event($inputdef["id".$idx], "onchange", 
						"checkval(this)");
				}
				break;
			}
/*>>>*/
			case "ellipsis": {/*<<<*/
				/* about the ellipsis: <<<
				the ellipsis control allows us to create a pop-up dialogue
					that does the actual work, and saves the return info
					in the named field. The TLC_Select is a specific 
					ellipsis, but I want to be able to do others "on a whim"
					here.
				 this input takes the standard settings, but the extra array
					can contain the follwoing as well (it's up to the caller
					to provide useful information, of course)

					url:	url to open in popup (default blank, gen. error)
					title:	window title (your popup document will overwrite
							this) -- default blank
					winopts: window options (check out the javascript spec
								on window.open for what you can and can't
								do here. The defaults open a nice pop-up 
								window with no statusbar, toolbar, location,
								menu, etc
					urlargs: array of arguments to tack on to the url for
								your popup page. It's up to your popup
								page to make sense of this and write back
								to the input on the form.
					descfield: name of field to create for description of
								selected item (if required). If this is set,
								then the actual field carrying the value to be
								saved will be a hidden field, allowing a 
								situation like the following:
								a user clicks the ellipsis button, selects
								a user name from the list, the name is 
								displayed in the wizard, and the user id that
								was selected is kept in the hidden field, to
								be handled by the post page
					>>>
				*/
				$r.="<table border=\"0\" cellpadding=\"1\"><tr><td nowrap>";
				if (array_key_exists("descfield", $inputdef["extra"])) {
					$r.="<input type=\"hidden\" name=\"".$inputdef["varname"]
						."\" value=\"".$this->jhesc($inputdef["value"])
						.">";
					$descfieldname = $inputdef["extra"]["descfield"];
				} else {
					$descfieldname = $inputdef["varname"];
				}
				$r.="<input name=\"".$descfieldname."\" id=\""
					.$inputdef["id"]."\" value=\"".$inputdef["value"]
					."\" onfocus=\"this.blur();\"";
				if ($inputdef["style"]!="") {
					if (strpos($inputdef["style".$idx], ":")===false) {
						$r.=" class=\"".$inputdef["style"]."\"";
					} else {
						$r.=" style=\"".$inputdef["style"]."\"";
					}
				}
				// get the popup options:
				if (array_key_exists("title", $inputdef["extra"])) {
					$title = $this->jhesc($inputdef["extra"]["title"]);
				} else {
					$title = "";
				}
				if (array_key_exists("url", $inputdef["extra"])) {
					$url = $this->jhesc($inputdef["extra"]["url"]);
				} else {
					$url = "";
				}
				if (array_key_exists("winopts", $inputdef["extra"])) {
					$winopts = $this->jhesc($inputdef["extra"]["winopts"]);
				} else {
					// some sane popup windowing options
					$winopts = "toolbar=no,status=no,menubar=no,location=no";
				}
				if (array_key_exists("urlargs", $def["extra"])) {
					if (is_array($inputdef["extra"]["urlargs"])) {
						foreach ($inputdef["extra"]["urlargs"] 
															as $arg => $val) {
							tack_on_nonblank_url_arg($url, $arg, $val);
						}
					}
				}
				$r.=">";
				if (file_exists("include/toolbar.php")) {
					$tmptb = new Toolbar();
					if (array_key_exists("button_image", 
						$inputdef["extra".$idx])) {
						$caption = "";
						$btnimg = $inputdef["extra".$idx]["button_image"];
					} else {
						$caption = "...";
						$btnimg = "";
					}
					$tmptb->add_button(array(
						"caption"	=>	$caption,
						"img"		=>	$btnimg,
						"code"		=>	"popwin('__btn_".$inputdef["id".$idx]
									."','".$url."', '".$title."', '".$winopts
									."')\"",
					));
					$tmptb->render();
				} else {
					$r.="<input type=\"button\" style=\"width: 24px; height:"
					."24px;\" onclick=\"popwin('__btn_".$inputdef["id".$idx]
						."','".$url."', '".$title."', '".$winopts
						."')\" id=\"__btn_".$inputdef["id".$idx].">";
				}
				$r.="</td></tr></table>";
				break;
			}
/*>>>*/
			case "customprompted":
			case "custom": {/*<<<*/
				// with a custom element, it's up to the element generator
				//	to make sure things are ok. Some tags may be left in the
				//	html code, which will be substituted, if corresponding
				//	values in the definition are found
				
				//	customprompted allows regular insertion of the prompt attr
				
				$html=$inputdef["html"];
				if ($inputdef["varname"]!="") {
					$html=str_replace("[INPUT_VARNAME]", $inputdef["varname"],
						$html);
				}
				if ($inputdef["id"]=="") $inputdef["id"]==$inputdef["varname"];
				$html=str_replace("[INPUT_ID]", $inputdef["id"], $html);
				$html=str_replace("[INPUT_PROMPT]", $inputdef["prompt"], $html);
				$r.=$html;
				break;
			}/*>>>*/
			default: {
				$r.="Unable to render input type: (".$inputdef["type"].")</td>";
			}
		}
		return $r;
	}
	
	function renderinput(&$inputdef, $tabulate=true) { // <<<2
		// expects a well-formed input definition.
		//	if tabulate is on, then the return is a table row, with 2 cols
		$acttime=$this->get_unique_time_val();
		if ($inputdef["required"]) {
			$star = "<span title=\"This information is required\" "
				."style=\"color:red\">*</span>";
		} else {
			$star == "";
		}
		if (is_array($inputdef) && is_array($inputdef["extra"])) {
			if (array_key_exists("tr_id", $inputdef["extra"])) {
				$trid = " id=\"".$inputdef["extra"]["tr_id"]."\"";
			} else {
				$trid = "";
			}
		}
		if ($tabulate) {
			switch ($inputdef["type"]) {
				case "hidden": {
					return "<tr".$trid
						." style=\"display: none\"><td colspan=\"2\">"
						."<input type=\"hidden\" name=\"".$inputdef["varname"]
						."\" id=\"".$inputdef["id"]."\" value=\""
						.$inputdef["value"]."\"></td></tr>";
					break;
				}
				case "label":
				case "infobox":
				case "checklabel":
				case "custom": {
					$r="<tr".$trid."><td valign=\"top\" colspan=2 id=\"wa_"
						.$acttime."\">";
					break;
				}
				default: {
					if (is_array($inputdef["extra"]) 
						&& array_key_exists("hide_tr", $inputdef["extra"])
						&& $inputdef["extra"]["hide_tr"]) {
						$styleextra = " style=\"display: none\"";
					} else {
						$styleextra = "";
					}
					$r="<tr".$trid.$styleextra."><td valign=\"top\" id=\"wa_"
						.$acttime
						."\" style=\"cursor: default\">"
						.$inputdef["prompt"].$star."</span>"
						."</td><td>";
				}
			}
		} else {
			if ($inputdef["type"] == "hidden") {
				return "<input type=\"hidden\" name=\"".$inputdef["varname"]
					."\" id=\"".$inputdef["id"]."\" value=\""
					.$inputdef["value"]."\">";
			} else {
				$r.="<span id=\"wa_".$acttime."\">".$inputdef["prompt"]
					."</span>";
			}
		}
		if ($inputdef["tooltip"]!="") {
			global $tooltips;
			if ($inputdef["tooltip_title"]=="") {
				$tt["title"]="Helpful tip:";
			} else {
				$tt["title"]=$this->jhesc($inputdef["tooltip_title"]);
			}
			$tt["text"]=$this->jhesc($inputdef["tooltip"]);
			$tt["id"]="wa_".$acttime;
			$tooltips[]=$tt;
		}
		switch ($inputdef["type"]) {
			case "compound": {
				$idx=1;
				while ($inputdef["varname".$idx] != "") {
					$r.=$this->renderiinput($inputdef, $idx);
					$idx++;
				}
				break;
			}
			default: {
				$r.=$this->renderiinput($inputdef);
			}
		}
		if ($tabulate) {
			switch ($inputdef["type"]) {
				case "label":
				case "infobox":
				case "checklabel":
				case "custom": {
					$r.="</td></tr>";
					break;
				}
				default: {
					$r.="</tr>";
				}
			}
		}
		return $r;
	}
	function gencheckradio(&$aout, &$aoptions, $selected, $name, $ishash, $type, $id_base="", $required, $scripts = array(), $sortopts = 0) { //<<<2
		if ($id_base=="") {
			$id_base=$name;
		}
		if ($is_hash == -1 || is_null($is_hash)) {
			$is_hash = 0;
			$check_keys=array_keys($aoptions);
			foreach ($check_keys as $key) {
				if (val($key) != $key) {
					// if the numeric value doesn't equal the actual key,
					//	chances are this is actually a hash array
					$is_hash=1;
					break;
				}
			}
		}
		if (!is_array($aout)) $aout=array();
		if (!is_array($aoptions)) {
			$aout[]="no options defined";
			return 0;
		}
		if (!is_array($selected)) {
			$asel=explode(",", $selected);
		} else {
			$asel = $selected;
		}
		$i=0;
		if ($required) {
			$docheck = "true";
		} else {
			$docheck = "false";
		}
		if (strpos("[]", $name) === false) {
		// we add on [] to the name, such that the posted page gets an array
		//	of values, instead of just the last value. I didn't realise
		//	that this was happening until recently -- too used to asp just
		//	making a comma-delimited list (which has it's own drawbacks)
			$name.="[]";
		}
		if ($ishash) {
			if ($sortopts) asort($aoptions);
			foreach ($aoptions as $idx => $val) {
				$tmp="<input type=\"".$type."\" value=\"".$val."\" name=\""
					.$name."\" id=\"".$id_base."_".$i."\"";
				if (in_array($val, $asel)) {
					$tmp.=" checked";
				}
				$tmp.="><span id=\"__span_".$id_base."_".$i."\""
					." class=\"wizard_check\">".$val."</span>";
				$this->add_event("__span_".$id_base."_".$i, "onclick",
					"togglecheck('".$id_base."_".$i."', ".$docheck
					.")");
				$aout[]=$tmp;
				if (is_array($scripts)) {
					foreach ($scripts as $ev => $script) {
						$this->add_event($id_base."_".$i, $ev, $script);
						$this->add_event("__span_".$id_base."_".$i, $ev, 
							$script);
					}
				}
				$i++;
			}
		} else {
			foreach ($aoptions as $val) {
				$tmp="<input type=\"".$type."\" value=\"".$val."\" name=\""
					.$name."\" id=\"".$id_base."_".$i."\"";
				if (in_array($val, $asel)) $tmp.=" checked";
				$tmp.="><span id=\"__span_".$id_base."_".$i."\" "
					." class=\"wizard_check\">".$val."</span>";
				$aout[]=$tmp;
				$this->add_event("__span_".$id_base."_".$i, "onclick",
					"togglecheck('".$id_base."_".$i."', ".$docheck
					.")");
				foreach ($scripts as $ev => $script) {
					$this->add_event($id_base."_".$i, $ev, $script);
					$this->add_event("__span_".$id_base."_".$i, $ev, $script);
				}
				$i++;
			}
		}
		return 1;
	}
	
	function add_event($objid, $evname, $script) {// <<<2
		if (trim($evname) == "") return;
		$this->js_events[$objid][$evname][] = $script;
	}

	function add_object_event($object, $evname, $script) { // <<<2
		if (trim($evname) == "") return;
		$this->js_obj_events[$object][$evname][]=$script;
	}

	function genspecialdates($inputname, $allowholidays, $baddaylist) { // <<<2
		// outputs a javascript multi-dimensional array generation script
		if (!$allowholidays) {
			$baddaylist.=";12-25;12-26;01-01;";
			$easter=easter_date();
			for($i=-2;$i<2;$i++) {
				$baddaylist.=date("m-d", dateadd("d", $i, $easter)).";";
			}
		}
		$months=array();
		$years=array();
		$bd=explode(";", $baddaylist);
		$vars=array();
		$allmonths=0;
		$allyears=0;
		$special=array();
		foreach ($bd as $bdval) {
			if ($bdval=="") continue;
			$thisdate=explode("-", $bdval);
			if (is_array($thisdate)) {
				switch (count($thisdate)) {
					case 1: {	// just a day setting: int values are monthday,
								//	strings are weekday names
						$year="-1";
						$month="-1";
						$day=$thisdate[0];
						break;
					}
					case 2: {	// month, day setting: weekday names allowed as well
						$year="-1";
						$month=$thisdate[0];
						$day=$thisdate[1];
						break;
					}
					case 3: {	// year, month, day. weekdays allowed
						$year=$thisdate[0];
						$month=$thisdate[1];
						$day=$thisdate[2];
						break;
					}
				}
			}
			// now, to compensate for the stupid desire of js and the dhtml
			//	calendar programmer to adhere to a zero-based month numbering,
			//	which is anything but clear to the caller, we must decrement
			//	the month here.
			$month=val($month)-1;
			if ($month<10) $month="0".$month;
			$dlist.="|".$year."/".$month."/".$day."|";
		}
		$r="\nvar SPECIAL_DAYS_".$inputname."='".$dlist."';";
		return $r;
	}
	
	function genopts(&$aopts, $is_hash=-1, $selected="", $sortopts = 0) { // <<<2
		if (is_array($aopts)) {
			$r="";
			// check if we have been given the hash field properly
			if ($is_hash == -1) {
				$check_keys=array_keys($aopts);
				foreach ($check_keys as $key) {
					$is_hash = 0;
					$val = val($key);
					//print("key is: $key; val is: $val<br>");
					// the check below has given me a lot of grief -- i don't
					//	understand why, but even when $val is 0 and $key is
					//	something like "checkbox" (clearly different), a !=
					//	check returns FALSE. wierd. So i'm trying a string
					//	function instead, for hash auto-detection
					if (strcmp($val, $key)) {
						//print("--> val != key; setting hash<br>");
						// if the numeric value doesn't equal the actual key,
						//	chances are this is actually a hash array
						$is_hash=1;
						break;
					}
				}
			}
			if ($sortbyname) asort($aopts);
			if ($is_hash) {
				foreach ($aopts as $idx => $val) {
					if ($idx == "" || $val == "") continue;
					if (is_array($selected)) {
						$sel=(in_array($idx, $selected))?" selected":"";
					} else {
						$sel=($idx == $selected)?" selected":"";
					}
					$r.="<option value=\"".$this->hesc($idx)."\"".$sel.">"
						.$this->hesc($val)."</option>";
				}
			} else {
				foreach ($aopts as $val) {
					if ($val == "") continue;
					if (is_array($selected_val)) {
						$sel=(in_array($val, $selected))?" selected":"";
					} else {
						$sel=($val == $selected)?" selected":"";
					}
					$v=$this->hesc($val);
					$r.="<option value=\"".$v."\"".$sel.">".$v."</option>";
				}
			}
			return $r;
		} else {
			return "";
		}
	}

	function load_array(&$source, &$dest) { // <<<2
		// loads existing keys in $source over those in $dest, like 
		//	array get in tcl; creates dest if necessary
		if (is_array($source)) {
			foreach ($source as $idx=>$val) {
				$dest[$idx]=$val;
			}
			return true;
		} else return false;
	}
	
	function hesc($str) { // <<<2
		// html string escaping sequences
		return str_replace("<", "&lt;", 
			str_replace(">", "&gt;", 
				str_replace("\"", "&quot;", $str)));
	}
	function jhesc ($str) { // <<<2
		return str_replace("\"", "\\\"", 
			str_replace("'", "\'", $this->hesc($str)));
	}
	function jquote($str) { // <<<2
		return str_replace("\"", "\\\"", str_replace("'", "\'", $str));
	}
	
	function gentitle($str) { // <<<2
		$str=trim($str);
		if ($str=="") return "";
		$str=str_replace("\"", "'", $str);
		return " title=\"".$str."\" onmouseover=\"window.status='"
			.$this->hesc($str)."';return true;\" onmouseout=\""
			."window.status='';return true;\"";
	}
	
	function removeinput($step, $pos) { // <<<2
		if (isset($this->steps[$step][$pos])) {
			unset($this->steps[$step][$pos]);
		}
	}
	function gen_summary_page ($def="") { // <<<2
	// the summary page is a step like most others, except that it provides
	//	a textural summary at no programming cost (nearly) for the entire
	//	wizard. What a winner!
		if (!is_array($def)) $def=array();
		foreach ($this->summarydef as $idx => $val) {
			if (!array_key_exists($idx, $def)) {
				$def[$idx]=$this->summarydef[$idx];
			}
		}
		$this->summarystepdata["title"]=$this->aod($def, "title", "Summary:");
		$this->summarystepdata["caption"]=$this->aod($def, "caption", 
			"Here follows a summary of your choices:");
		$this->summarystepdata["image"]=$this->aod($def, "image", "");
		
		$tmp["type"]="infobox";
		$tmp["id"]="summary_table_div";
		$tmp["value"]="";
		$tmp["name"]="summary_table_div";
		$this->summarystep[]=$tmp;
		
		$tmp["id"]="summary_label";
		$tmp["type"]="label";
		$tmp["value"]=$def["summary_label"];
		$tmp["name"]="summary_label";
		$this->summarystep[]=$tmp;
		// create some smart code to make this summary.
		$code="wizard_has_summary = true;\n";
		$code.="var sum='<table border=\"0\" width=\"97%\">';\n";
		$tcode="\nif (obj=document.getElementById('[OBJID]')) {\n"
			."	sum+='<tr><td class=\"wizard_summary_lcol\">[OBJCAPTION]</td>"
			."<td class=\"wizard_summary_rcol\">'+obj.value+'</td></tr>';\n"
			."}\n\n";
		// accomodate select items
		$tcode2="\nif (obj=document.getElementById('[OBJID]')) {\n"
			."	if ((obj.selectedIndex > 0) && (obj.selectedIndex <= obj.options.length)) {\n"
			."		objval = obj.options[obj.selectedIndex].text;\n"
			."	} else {\n"
			."		objval = '".gettrans("msg_nothing_selected", 
				"Nothing selected.")."';\n"
			."	}\n"
			."	sum+='<tr><td class=\"wizard_summary_lcol\">[OBJCAPTION]</td>"
			."<td class=\"wizard_summary_rcol\">'+objval+'</td></tr>';\n"
			."}\n";
		// accomodate list items
		$tcode3="\nif (obj=document.getElementById('[OBJID]')) {\n"
			."	sum+='<tr><td class=\"wizard_summary_lcol\">[OBJCAPTION]</td>"
			."<td class=\"wizard_summary_rcol\">';\n"
			."	needbr=false;\n"
			."	for (i=0; i< obj.options.length; i++) {\n"
			."		if (obj.options[i].selected) {\n"
			."			if (needbr) sum+='<br>';\n"
			."			needbr=true;\n"
			."			sum+=obj.options[i].text;\n"
			."		}"
			."	}\n"
			."	sum+='</td></tr>';\n"
			."}\n";
		// compound widget code
		$ccode1="\nif (obj=document.getElementById('[OBJID]')) {\n"
			."	sum+='<tr><td class=\"wizard_summary_lcol\">[OBJCAPTION]</td>"
			."<td class=\"wizard_summary_rcol\">';\n";
		$ccode2="\nif (obj=document.getElementById('[OBJID]')) {\n"
			."	sum+=obj.value+' ';\n"
			."}\n";
		$ccode3="\nsum+='</td></tr>';\n"
			."}\n";
		// accomodate select items in compound widgets
		$ccode4="\nif (obj=document.getElementById('[OBJID]')) {\n"
			."	sum+=obj.options[obj.selectedIndex].text+' ';\n"
			."}\n";
		// accomodate list items in compound widgets
		$ccode5="\nif (obj=document.getElementById('[OBJID]')) {\n"
			."	needbr=false;\n"
			."	for (i=0; i< obj.options.length; i++) {\n"
			."		if (obj.options[i].selected) {\n"
			."			if (needbr) sum+='<br>';\n"
			."			needbr=true;\n"
			."			sum+=obj.options[i].text;\n"
			."		}\n"
			."	}\n"
			."}\n";
		//checklabels
		$clabelcode="if (obj=document.getElementById('[OBJID]')) {\n"
					."	sum+='<tr><td class=\"wizard_summary_lcol\">"
					."[OBJCAPTION]</td><td class=\"wizard_summary_rcol\">';\n"
					."	if (obj.checked) {\n"
					."		sum+='yes';\n"
					."	} else {\n"
					."		sum+='no';\n"
					."	}\n"
					."	sum+='</td></tr>';\n"
					."}\n";
		//modlists
		$modlistcode="if (obj=document.getElementById('[OBJID]_list')) {\n"
					."	sum+='<tr><td class=\"wizard_summary_lcol\">"
					."[OBJCAPTION]</td><td class=\"wizard_summary_rcol\">';\n"
					."	addbr = false;\n"
					."	for (idx = 0; idx < obj.options.length; idx++) {\n"
					."		if (addbr) sum+='<br>';\n"
					."		sum+=obj.options[idx].text;\n"
					."		addbr = true;\n"
					."	}\n"
					."}\n";
		// passwords (if you don't set summary to 0 in the extra array)
		$pwdcode = 	"if (obj=document.getElementById('[OBJID]')) {\n"
					."	sum+='<tr><td class=\"wizard_summary_lcol\">"
					."[OBJCAPTION]</td><td class=\"wizard_summary_rcol\">';\n"
					."	for (i=0; i < obj.value.length; i++) {\n"
					."		sum+='*';\n"
					."	}\n"
					."}\n";
		// radio / checkbox
		$rccode = 	"sum+='<tr><td class=\"wizard_summary_lcol\">"
					."[OBJCAPTION]</td><td class=\"wizard_summary_rcol\">';\n"
					."needbr = false;\n"
					."for (i = 0; i < [OPTION_COUNT]; i++) {\n"
					."	if (obj = document.getElementById('[OBJID]_'+i)) {\n"
					."		if (obj.checked) {\n"
					."			if (needbr) {\n"
					."				sum+='<br>';\n"
					."			}\n"
					."			sum+=obj.value;\n"
					."			needbr=true;\n"
					."		}\n"
					."	}\n"
					."}\n";
		foreach ($this->steps as $stepnum => $inputs) {
			$code.="if ((typeof(skip_steps) == \"undefined\") "
				." || !in_array(".$stepnum.", skip_steps)) {\n";
			foreach ($inputs as $item) {
				if (array_key_exists("extra", $item) 
					&& is_array($item["extra"])) {
					if (array_key_exists("summary", $item["extra"])) {
						if ($item["extra"]["summary"]) {
							// we'll do the summary for any true value
						} else {
							continue;
						}
					}
				}
				switch ($item["type"]) {
					case "hidden":
					case "infobox":
					case "widget_demo":
					case "demo":
					case "democontrols":
					case "label": {
						break;
					}
					case "radio":
					case "checkbox": {
						if (!array_key_exists("caption", $item) 
							|| $item["caption"] == "") {
							$item["caption"] = $item["prompt"];
						}
						$code.=str_replace("[OBJCAPTION]", $item["caption"],
							str_replace("[OBJID]", $item["id"],
							str_replace("[OPTION_COUNT]", 
							count($item["options"]), $rccode)));
						break;
					}
					case "modlist": {
						if (!array_key_exists("caption", $item) 
							|| $item["caption"] == "") {
							$item["caption"] = $item["prompt"];
						}
						$code.=str_replace("[OBJID]", $item["id"],
							str_replace("[OBJCAPTION]", $item["caption"],
								$modlistcode));
						break;
					}
					case "checklabel": {
						if (!array_key_exists("caption", $item)
							|| $item["caption"] == "") {
							$item["caption"] = $item["prompt"];
						}
						$code.=str_replace("[OBJID]", $item["id"],
							str_replace("[OBJCAPTION]", $item["caption"],
								$clabelcode));
						break;
					}
					case "compound": {
						if (!array_key_exists("caption", $item)
							|| $item["caption"] == "") {
							$item["caption"] = $item["prompt"];
						}
						$iidx=1;
						$code.=str_replace("[OBJID]", $item["id1"],
							str_replace("[OBJCAPTION]", 
								$this->jhesc($item["caption"]), $ccode1));
						while($item["varname".$iidx]!="") {
							switch ($item["type".$iidx]) {
								case "hidden":
								case "infobox":
								case "label": {
									continue;
									break;
								}
								case "checklabel": {
									$code.=str_replace("[OBJID]", 
										$item["id".$iidx],
										str_replace("[OBJCAPTION]", 
										$item["caption"], $clabelcode));
									break;
								}
								case "droplist":
								case "select": {
									$code.=str_replace("[OBJID]", 
										$item["id".$iidx], 
										$ccode4);
									break;
								}
								case "listbox":
								case "staticlist":
								case "list": {
									$code.=str_replace("[OBJID]",
										$item["id".$iidx],
										$ccode5);
									break;
								}
								default: {
									$code.=str_replace("[OBJID]", 
										$item["id".$iidx],
										$ccode2);
								}
							}
							$iidx++;
						}
						$code.=$ccode3;
						break;
					}
					case "droplist":
					case "select": {
						if (!array_key_exists("caption", $item)
							|| $item["caption"] == "") {
							$item["caption"] = $item["prompt"];
						}
						$code.=str_replace("[OBJID]", $item["id"], 
							str_replace("[OBJCAPTION]", 
							$this->jhesc($item["caption"]), 
							$tcode2));
						break;
					}
					case "listbox":
					case "staticlist":
					case "list": {
						if (!array_key_exists("caption", $item)
							|| $item["caption"] == "") {
							$item["caption"] = $item["prompt"];
						}
						$code.=str_replace("[OBJID]", $item["id"],
							str_replace("[OBJCAPTION]", 
							$this->jhesc($item["caption"]),
							$tcode3));
						break;
					}
					default: {
						if (!array_key_exists("caption", $item)
							|| $item["caption"] == "") {
							$item["caption"] = $item["prompt"];
						}
						$code.=str_replace("[OBJID]", $item["id"], 
							str_replace("[OBJCAPTION]", 
							$this->jhesc($item["caption"]), 
							$tcode));
					}
				}
			}
			$code.="}\n";
		}
		$code.="if (obj=document.getElementById('summary_table_div')) {\n\t"
			."obj.innerHTML=sum;\n"
			."}";
		$this->summarystepdata["code"]=$code;
	}
	
	function get_unique_time_val() { // <<<2
		global $used_time_vals;
		if (is_array($used_time_vals)) {
			$thetime=mktime();
			while (in_array($thetime, $used_time_vals)) {
				$thetime++;
			}
			$used_time_vals[]=$thetime;
			return $thetime;
		} else {
			$used_time_vals=array();
			$used_time_vals[]=mktime();
			return $used_time_vals[0];
		}
	}
	// >>>2	
	function addhinput($varname, $varvalue, $ignore = 0) {/*<<<*/
		// shortcut to add a hidden field
		$this->addinput(array(
			"type"		=>	"hidden",
			"name"		=>	$varname,
			"value"		=>	htmlentities($varvalue),
			"ignore"	=>	$ignore,
		));
	}
/*>>>*/
	function getfields($delimiter = ";") {/*<<<*/
		foreach($this->steps as $inputs) {
			foreach ($inputs as $input) {
				if ($input["type"] == "compound") {
					$idx = 1;
					while(array_key_exists("varname".$idx, $input)) {
						if ($input["varname".$idx] != "") {
							if (array_key_exists("ignore".$idx, $input)
								&& $input["ignore".$idx]) continue;
							$flds[] = $input["varname".$idx];
						}
						$idx++;
					}
				} else {
					if (array_key_exists("ignore", $input)
						&& ($input["ignore"])) {
						continue;
					}
					if ($input["varname"] != "") {
						$flds[] = $input["varname"];
					}
				}
			}
		}
		if (is_array($flds)) {
			return implode($delimiter, $flds);
		} else {
			return "";
		}
	}
/*>>>*/
}
?>
<style type="text/css">@import url(<?=wiz_find_file("calendar-win2k-1.css")?>);</style>
<script type="text/javascript" src="<?=wiz_find_file("calendar.js")?>"></script>
<script type="text/javascript" src="<?=wiz_find_file("calendar-default-lang.js")?>">
</script>
<script type="text/javascript" src="<?=wiz_find_file("calendar-setup.js")?>"></script>
<script type="text/javascript" src="<?=wiz_find_file("comboselect.js")?>"></script>
<script type="text/javascript" src="<?=wiz_find_file("tooltips.js")?>"></script>
<script language="Javascript">
var __tabstopid = "";
var wizard_has_summary = 0;
var wizard_blocked = false;
var step_scripts=new Array();
createtipbox();
tipdelay = 8000;		// how long this tooltip stays alive for if there is no movement
function setnexttabstop(id) {/*<<<*/
}
/*>>>*/
function handlekeys(ev) {/*<<<*/
	alert(window.event.keyCode);
}
/*>>>*/
function sizeinc(obj_id, changeby) {/*<<<*/
// increments the height of an object -- useful for text areas that are allowed
//	to grow to suit the client.
	if (obj=document.getElementById(obj_id)) {
		var old_height=obj.style.height;
		if (old_height=='') old_height='100px';
		var units=old_height.substr(old_height.length-2, 2);
		old_height=parseInt(old_height.substr(0, old_height.length-2));
		old_height*=(1+(changeby/100));
		if (old_height>40) {
			obj.style.height=old_height+units;
		}
	} else {
		alert('unable to grab object:'+obj_id);
	}
}/*>>>*/
function wdaynum (str) {/*<<<*/
	str=str.toLowerCase();
	switch (str.substring(0,3)) {
		case "sun": {
			return 0;
		}
		case "mon": {
			return 1;
		}
		case "tue": {
			return 2;
		}
		case "wed": {
			return 3;
		}
		case "thu": {
			return 4;
		}
		case "fri": {
			return 5;
		}
		case "sat": {
			return 6;
		}
		default: {
			return 0;
		}
	}
}
/*>>>*/
function change_val(inputname, optionlist, changeby) {/*<<<*/
	var j=optionlist.length;
	var el=document.getElementById(inputname);
	var hitval=-1;
	if (el) {
		currval=el.value;
		for (var i=0; i < j; i++) {
			if (currval==optionlist[i]) {
				hitval=i;
				break;
			}
		}
		newidx=hitval+changeby;
		if (newidx>=0) {
			if (newidx<j) {
				newval=optionlist[newidx];
			} else {
				newval=optionlist[j-1];
			}
		} else {
			newval=optionlist[0];
		}
		el.value=newval;
	} else {
		alert('unable to grab element: '+inputname);
	}
}/*>>>*/
function showitem (itemid, show) {/*<<<*/
	if (obj=document.getElementById(itemid)) {
		if (show) {
			obj.style.display='';
		} else {
			obj.style.display='none';
		}
	} else {
		alert('Unable to grasp item with id: '+itemid+'; showitem fails');
	}
}/*>>>*/
function setstate(btnid, enabled) {/*<<<*/
	if (typeof(disable_toolbar_btn_byid) != "undefined") {
		if (enabled) {
			disable_toolbar_btn_byid(btnid, false);
		} else {
			disable_toolbar_btn_byid(btnid, true);
		}
	} else {
		if (btn=document.getElementById(btnid)) {
			btn.disabled=!enabled;
		}
	}
}/*>>>*/
function cancel_wizard (url) {/*<<<*/
	if (confirm(cancel_msg)) {
		window.location=url;
	}
}/*>>>*/
function in_array(arr, val) {/*<<<*/
	for (var idx in arr) {
		if (val == arr[idx]) {
			return true;
		}
	}
	return false;
}
/*>>>*/
function incstep (change_by, timestamp) {/*<<<*/
	// get the index of the current step
	for (i=0; i<allsteps.length; i++) {
		if (allsteps[i]==current_step) break;
	}
	step_idx=i;
	if (i==allsteps.length) {
		alert('End of all step array reached and step '+current_step+' not found!');
		return;
	}
	// get the step name of the requested step
	rstep=i+change_by;
	if (typeof(skip_steps) != "undefined") {
		while (in_array(skip_steps, rstep)) {
			if (change_by > 0) {
				rstep++;
			} else {
				rstep--;
			}
		}
	}
	
	if ((rstep >= allsteps.length) || (rstep < 0)) {
		alert('unable to select step: '+rstep+' -- out of bounds. Check button logic');
		return
	}
	// hide current step div
	showitem('wizard_step_'+current_step+"_"+timestamp, false);
	// unhide selected step div
	current_step=allsteps[rstep];
	showitem('wizard_step_'+current_step+"_"+timestamp, true);
	
	// wizard headings:
	if (obj=document.getElementById('wizard_head_image_'+timestamp)) {
		if (images[current_step]=='') {
			obj.style.display='none';
			if (obj=document.getElementById('imgtd_'+timestamp)) {
				obj.style.width='0px';
			}
		} else {
			obj.src=images[current_step];
			if (obj=document.getElementById('imgtd_'+timestamp)) {
				obj.style.width='10%';
			}
		}
	}
	if (obj=document.getElementById('wizard_head_title_'+timestamp)) {
		obj.innerHTML=titles[current_step];
		if (captions[current_step].length == 0) {
			obj.className = "supersize";
		} else {
			obj.className = "normal";
		}
	}
	if (obj=document.getElementById('wizard_head_caption_'+timestamp)) {
		if (captions[current_step].length == 0) {
			obj.className="hiddenCaption";
		} else {
			obj.className = "wizard_caption";
			obj.innerHTML=captions[current_step];
		}
	}
	if (obj=document.getElementById('wizard_head_stepnum_'+timestamp)) {
		idx=i+1+change_by;
		if (idx == allsteps.length) {
			obj.innerHTML='Final step';
		} else {
			obj.innerHTML='Step '+idx+' of '+allsteps.length;
		}
	}

	// buttons: check against required fields & prior openings
	state_prev=true;
	state_next=true;
	state_fin=true;
	if (numsteps>1) {
		// check first / last step
		if (rstep == (allsteps.length-1)) {
			state_next=false;
			state_fin=true;
		} else {
			state_next=true;
			state_fin=false;
		}
		if (rstep <= 0) {
			state_prev=false;
		}
	}
	dpos=dsteps.indexOf('|'+current_step+'|');
	if ((dpos > -1) && (osteps.indexOf('|'+current_step+'|') <0)) {
		state_next=false;
		state_fin=false;
	}
	setstate('wizard_btnprev', state_prev);
	setstate('wizard_btnnext', state_next);
	setstate('wizard_btnfinish', state_fin);
	check_step_vals();

	if (typeof(step_scripts[current_step]) != 'undefined') {
		if (step_scripts[current_step] != '') {
			try {
			  eval(step_scripts[current_step]);
			} catch (e) {
				alert('could not execute step change script: '+e.description+' :: this is most likely a bug in the gen_summary output. The best way to debug this is probably to save this page as a standalone html page, and debug the javascript from there.');
			}
		}
	}
}
/*>>>*/
function showallsteps(wizid) {/*<<<*/
	for (var idx in allsteps) {
		showitem("wizard_step_"+allsteps[idx]+"_"+wizid, true);
	}
}
/*>>>*/
function check_step_vals() {/*<<<*/
	var ret = true;
	if (wizard_flattened) {
		check_steps = allsteps;
	} else {
		check_steps = new Array(current_step);
	}
	for (var idx in check_steps)
	if ((dsteps.indexOf('|'+check_steps[idx]+'|') >= 0) && (osteps.indexOf('|'+check_steps[idx]+'|') < 0)) {
		for (var controlid in validation[check_steps[idx]]) {
			//alert("checking on "+controlid);
			if (obj = document.getElementById(controlid)) {
				ret = (ret & checkval(obj));
			}
		}
	}
	return ret;
}
/*>>>*/
function checkval_id(id) {/*<<<*/
	//alert("checkval_id called on "+id);
	if (obj = document.getElementById(id)) {
		return checkval(obj);
	}
}
/*>>>*/
function checkval (control) {/*<<<*/
	if (wizard_blocked) {
		return; // external blocking mechanism
	}
	if (wizard_flattened) {
		check_steps = allsteps;
	} else {
		check_steps = Array(current_step);
	}
	for (var cidx in check_steps) {
		if ((dsteps.indexOf('|'+check_steps[cidx]+'|') >= 0) && (osteps.indexOf('|'+check_steps[cidx]+'|') < 0)) {
			if (typeof(validation[check_steps[cidx]][control.id] != 'undefined')) {
				required = validation[current_step][control.id];
				//alert(control.id + " :: " + required + " :: " + control.value);
				if (control.value == required) {
					if ((required == '') 
						&& (control.value.replace(/ /, '') == '')) {
						// when the validation value is set to '', it means
						//	that *anything* must be entered -- but not nothing.
						validswitch[check_steps[cidx]][control.id] = 0;
					} else {
						validswitch[check_steps[cidx]][control.id] = 1;
					}
				} else {
					if (required == '') {
						validswitch[check_steps[cidx]][control.id] = 1;
					} else {
						validswitch[check_steps[cidx]][control.id] = 0;
					}
				}
				// check if we can open up the step
				if (cidx == 0)
					var first_time = true;
				for (var val in validation[check_steps[cidx]]) {
					if (first_time)
						allowed = validswitch[check_steps[cidx]][val];
					else
						allowed=(allowed & validswitch[check_steps[cidx]][val]);
					first_time = false;
				}
			} else {
				if (!wizard_flattened)
					alert('validation not set up correctly for: '+control.id);
			}
		}
	}
	if (typeof(allowed) != "undefined") {
		if (wizard_has_summary) {
			stepdiff = 1;
		} else {
			stepdiff = 2;
		}
		if (wizard_flattened || (step_idx > allsteps.length - stepdiff)) {
			setstate('wizard_btnfinish', allowed);
		} else {
			setstate('wizard_btnnext', allowed);
		}
		return allowed;;
	}
	return true;
}
/*>>>*/
function checkval_radio (control, required) {/*<<<*/
	if (wizard_flattened) {
		check_steps = allsteps;
	} else {
		check_steps = Array(current_step);
	}
	for (var cidx in check_steps) {
		if ((dsteps.indexOf('|'+check_steps[cidx]+'|') >= 0) && (osteps.indexOf('|'+check_steps[cidx]+'|') < 0)) {
			if (typeof(validation[check_steps[cidx]][control.id] != 'undefined')) {
				if (((control.value == required) && control.checked) || ((required == '') && (control.value.replace(/ /, '') != ''))) {
					validation[check_steps[cidx]][control.id] = 1;
				} else {
					validation[check_steps[cidx]][control.id] = 0;
				}
				// check if we can open up the step
				var first_time=true;
				for (var val in validation[check_steps[cidx]]) {
					if (first_time) {
						allowed = validation[check_steps[cidx]][val]
					} else {
						allowed=(allowed && validation[check_steps[cidx]][val]);
					}
					first_time = false;
				}
			} else {
				if (!wizard_flattened)
					alert('validation not set up correctly for: '+control.id);
			}
		}
	}
	if (typeof(allowed) != "undefined") {
		if (step_idx >= allsteps.length - 1) {
			setstate('wizard_btnnext', allowed);
		} else {
			setstate('wizard_btnfinish', allowed);
		}
	}
}/*>>>*/
function togglecheck (id, docheck) {/*<<<*/
	if (obj=document.getElementById(id)) {
		if (!obj.checked || (obj.type != "radio")) {
			obj.checked=!obj.checked;
		}
		if (docheck) {
			if (obj.type == "checkbox" || obj.type == "radio")
				checkval_radio(obj, obj.value);
			else
				checkval(obj);
		}
	}
}
/*>>>*/
function togglechecklabel(id, docheck) {/*<<<*/
	togglecheck(id, docheck);
	togglecheck('__anti_'+id, false);
}
/*>>>*/
function add_url_arg(url, arg, val) {/*<<<*/
	if (val == "") return url;
	if (url.indexOf("?")>0) {
		return url+"&"+idx+"="+val;
	} else {
		return url+"?"+idx+"="+val;
	}
}
/*>>>*/
function tlc_select_popup (btnid, phpurl, winargs) {/*<<<*/
	if (phpurl == "") {
		alert("Error:\n\nCould not spawn TLC selector, as the phpurl was not set");
		return;
	}
	
	if (cw=window.open(phpurl, "selecta", winargs)) {
		disable_toolbar_btn_byid(btnid);
		wait_for_close(cw, btnid);
	}
}
/*>>>*/
function wait_for_close(win, objid) {/*<<<*/
	if (win.document) {
		if (dobj=document.getElementById("debug_div")) {
			dobj.innerHTML="waiting";
		}
		i=wins.length;
		wins[i]=win;
		btns[i]=objid;
		window.setTimeout("watch_win("+i+")", 500);
	} else {
		disable_toolbar_btn_byid(objid, false);
		check_step_vals();
	}
}
/*>>>*/
function watch_win(i) {/*<<<*/
	if (wins[i].document) {
		if (obj=document.getElementById("debug_div")) {
			obj.innerHTML+=".";
		}
		window.setTimeout("watch_win("+i+")", 500);
	} else {
		disable_toolbar_btn_byid(btns[i], false);
		check_step_vals();
	}
}
/*>>>*/
function  setopts(selectid, optarray) {/*<<<*/
	
}
/*>>>*/
function popwin(btnid, url, title, winopts) {/*<<<*/
	if (url == "") {
		alert("Improperly configured popup window: no url specified.");
		return;
	}
	if (cw = window.open(url, title, winopts)) {
		btn.disabled = true;
		wait_for_close(cw, btnid);
	} else {
		alert("Unable to open client pop-up window -- perhaps you need to disable pop-up blocking for this site?");
	}
	
}
/*>>>*/
function addEvent(objid, evname, funcname) {/*<<<*/
/* I've checked out other methods 
	(http://www.scottandrew.com/weblog/jsjunk#events)
	for adding events -- but this is
	based on what I saw at 
	http://simon.incutio.com/archive/2004/05/26/addLoadEvent,
	taking into account the power of javascript's eval() function.
	
	drawbacks: 
		(i) can only use a function name with no arguments. But I think
				that the function gets given an event object as the first
				passed parameter anyways... (FIXED -- you can now!)
		(ii) there's no event remover. Not that I particularly want one...
*/
	if (obj = document.getElementById(objid)) {
		addEventToObject(obj, evname, funcname);
	}
}
/*>>>*/
function addEventToObject(obj, evname, funcname) { /*<<<*/
	if (typeof(obj) == "undefined") return;
	if (evname.substr(0,2) != "on") {
		evname = "on"+evname; // allow events to be passed by their names
	}
	if (funcname.indexOf(")")) {
		nfbr = "";
	} else {
		nfbr = "()"; // make sure that the new function is called as a fn
	}
	if (obj) {
		eval ("var oldfunc = obj."+evname);
		if (typeof oldfunc != 'function') {
			str = "obj."+evname+" = function () {"+funcname+nfbr+"}";
			try {
				eval(str);
			} catch (e) {
			}
		} else {
			try {
			eval("obj."+evname+" = function() {\noldfunc();\n"+funcname+";\n}");
			} catch (e) {
			}
		}
	}
}
/*>>>*/
// modlist functions <<<:
// note that the move functions work on multiple selections, however,
//	when there is a block of selections at the top or bottom of a list, a 
//	moveup and movedown may work not quite as expected... I'm not
//	sure if I want to fix it as the behaviour might actually be useful  (:
// >>>
function str_replace(find, replace, str) {/*<<<*/
	while (str.indexOf(find) > -1) {
		start = str.indexOf(find);
		end = start + find.length;
		str = str.substr(0, start) + replace + str.substr(end, str.length);
	}
	return str;
}
/*>>>*/
function modlist_additem(id) {/*<<<*/
	<?php
		$prompt = gettrans("msg_addnewitem", "Please enter a new item to add:");
		$blankitem = gettrans("msg_blankitem","You may not enter a blank item!");
		$itemexists = gettrans("msg_itemexists", "That item is already in the"
			." list.");
		$badcharsreplaced = gettrans("msg_badcharsreplaced", "The item you "
			."entered contained one or more characters which are not allowed."
			." These have been removed for you.");
	?>
	var newitem = prompt("<?=$prompt?>");
	if (newitem == null) return;
	if (newitem.replace(/ /, '') == '') {
		alert("<?=$blankitem?>");
		return;
	}
	if (obj = document.getElementById(id+"_list")) {
		for (var idx = 0; idx < obj.options.length; idx++) {
			if (obj.options[idx].value == newitem) {
				alert("<?=$itemexists?>");
				return;
			}
		}
		if ((typeof(modlist_badstrings) != "undefined")
			&& (typeof(modlist_badstrings[id]) != "undefined")) {
			for (var bidx in modlist_badstrings[id]) {
				newitem = str_replace(modlist_badstrings[id][bidx], "", newitem);
				warn_badchars = true;
			}
		}
		if (typeof(warn_badchars) != "undefined") {
			alert("<?=$badcharsreplaced?>");
		}
		obj.options[obj.options.length] = new Option(newitem, newitem);
		obj.options[obj.options.length - 1].selected = true;
	}
	modlist_checkbtns(id);
	modlist_updatelfield(id);
}
/*>>>*/
function modlist_delitem(id) {/*<<<*/
	<?php
		$prompt = gettrans("msg_confirmitemdel", "Are you sure you would"
			." like to delete these item(s)?");
	?>
	// get a list of items that are selected
	if (obj = document.getElementById(id+"_list")) {
		var itemlist = "";
		var bSomethingSelected = false;
		for (var idx = 0; idx < obj.options.length; idx++) {
			if (obj.options[idx].selected) {
				itemlist+="\n"+obj.options[idx].text;
				bSomethingSelected = true;
			}
		}
		if (bSomethingSelected) {
			if (confirm("<?=$prompt?>"+itemlist)) {
				var rvals = new Array();
				for (idx = 0; idx < obj.options.length; idx++) {
					if (obj.options[idx].selected) {
						rvals.push(obj.options[idx].value);
					}
				}
				if (rvals.length) {
					for (var ridx in rvals) {
						for (idx = 0; idx < obj.options.length; idx++) {
							if (obj.options[idx].value == rvals[ridx]) {
								obj.options[idx] = null;
								break;
							}
						}
					}
				}
			}
		}
		modlist_checkbtns(id);
	}
	modlist_updatelfield(id);
}
/*>>>*/
function modlist_moveup(id) {/*<<<*/
	if (obj = document.getElementById(id+"_list")) {
		var mvals = new Array();
		var toppos = 0;
		for (var idx = 0; idx < obj.options.length; idx++) {
			if (obj.options[idx].selected) {
				mvals.push(obj.options[idx].value);
			}
		}
		for (var midx in mvals) {
			for (idx = 0; idx < obj.options.length; idx++) {
				if (obj.options[idx].value == mvals[midx]) {
					if (idx == toppos) continue;
					previdx = idx-1;
					swap_list_items(obj, idx, previdx);
					toppos++;
					break;
				}
			}
		}
		// restore the selected items
		for (idx = 0; idx < obj.options.length; idx++) {
			obj.options[idx].selected = false;
		}
		for (var midx in mvals) {
			for (idx = 0; idx < obj.options.length; idx++) {
				if (mvals[midx] == obj.options[idx].value) {
					obj.options[idx].selected = true;
				}
			}
		}
		modlist_checkbtns(id);
		modlist_updatelfield(id);
	}
	modlist_updatelfield(id);
}
/*>>>*/
function modlist_movedown(id) {/*<<<*/
	if (obj = document.getElementById(id+"_list")) {
		var mvals = new Array();
		var toppos = obj.options.length - 1;
		for (var idx = 0; idx < obj.options.length; idx++) {
			if (obj.options[idx].selected) {
				mvals.push(obj.options[idx].value);
			}
		}
		for (var midx = mvals.length -1; midx >=0; midx--) {
			for (idx = 0; idx < obj.options.length; idx++) {
				if (obj.options[idx].value == mvals[midx]) {
					if (idx == toppos) continue;
					nextidx = parseInt(idx) + 1;
					swap_list_items(obj, idx, nextidx);
					toppos--;
					break;
				}
			}
		}
		// restore the selected items
		for (idx = 0; idx < obj.options.length; idx++) {
			obj.options[idx].selected = false;
		}
		for (var midx in mvals) {
			for (idx = 0; idx < obj.options.length; idx++) {
				if (mvals[midx] == obj.options[idx].value) {
					obj.options[idx].selected = true;
				}
			}
		}
		modlist_checkbtns(id);
		modlist_updatelfield(id);
	}
	modlist_updatelfield(id);
}
/*>>>*/
function modlist_checkbtns(id) {/*<<<*/
	if (listobj = document.getElementById(id+"_list")) {
		// add new button should always be enabled... make sure!
		setstate(id+"_btnaddnew", true);
		if (listobj.options) {
			if (listobj.options.length <= 1) {
				setstate(id+"_btnmoveup", false);
				setstate(id+"_btnmovedown", false);
				if (listobj.options.length == 1) {
					if (listobj.options[0].selected) {
						setstate(id+"_btndel", true);
					} else {
						setstate(id+"_btndel", false);
					}
				} else {
					setstate(id+"_btndel", false);
				}
			} else {
				var bSomethingSelected = false;
				for (var idx = 0; idx < listobj.options.length; idx++) {
					if (listobj.options[idx].selected) {
						bSomethingSelected = true;
						break;
					}
				}
				if (bSomethingSelected) {
					var bSomethingInMiddleSelected = false;
					for (idx = 0; idx < listobj.options.length; idx++) {
						if (listobj.options[idx].selected) {
							if ((idx > 0) && (idx < (listobj.options.length - 1))) {
								bSomethingInMiddleSelected = true;
								break;
							}
						}
					}
					if (bSomethingInMiddleSelected) {
						setstate(id+"_btnmoveup", true);
						setstate(id+"_btnmovedown", true);
						setstate(id+"_btnmovetop", true);
						setstate(id+"_btnmovebottom", true);
					} else {
						if (listobj.options[listobj.options.length-1].selected) {
							setstate(id+"_btnmoveup", true);
							setstate(id+"_btnmovetop", true);
						} else {
							setstate(id+"_btnmoveup", false);
							setstate(id+"_btnmovetop", false);
						}
						if (listobj.options[0].selected) {
							setstate(id+"_btnmovedown", true);
							setstate(id+"_btnmovebottom", true);
						} else {
							setstate(id+"_btnmovedown", false);
							setstate(id+"_btnmovebottom", false);
						}
					}
					setstate(id+"_btndel", true);
				} else {
					setstate(id+"_btndel", false);
					setstate(id+"_btnmoveup", false);
					setstate(id+"_btnmovedown", false);
				}
			}
		} else {
			setstate(id+"_btndel", false);
			setstate(id+"_btnmoveup", false);
			setstate(id+"_btnmovedown", false);
		}
	}
}
/*>>>*/
function modlist_updatelfield(id) {/*<<<*/
	if (listobj = document.getElementById(id+"_list")) {
		delimiter = "";
		if (delobj = document.getElementById(id+"_delimiter")) {
			delimiter = delobj.value;
		}
		if (delimiter == "") delimiter = ";";
		
		if (fldobj = document.getElementById(id)) {
			fldobj.value = "";
			needs_delimiter = false;
			for (var idx = 0; idx < listobj.options.length; idx++) {
				if (needs_delimiter) fldobj.value+=delimiter;
				fldobj.value+=listobj.options[idx].text;
				needs_delimiter = true;
			}
		}
	}
	if (typeof(id+"_onchange" == "function")) {
		eval (id+"_onchange()");
	}
}
/*>>>*/
function modlist_movetop(id) {/*<<<*/
	if (obj = document.getElementById(id+"_list")) {
		var toppos = 0;
		mvals = new Array();
		for (var idx = 0; idx < obj.options.length; idx++) {
			if (obj.options[idx].selected) {
				mvals.push(obj.options[idx].value);
			}
		}
		for (var midx in mvals) {
			for (idx in obj.options) {
				if (obj.options[idx].value == mvals[midx]) {
					cidx = idx;
					while (cidx > toppos) {
						pcidx = parseInt(cidx)-1;
						swap_list_items(obj, cidx, pcidx);
						cidx--;
					}
				}
			}
		}
		// restore the selected items
		for (idx = 0; idx < obj.options.length; idx++) {
			obj.options[idx].selected = false;
		}
		for (var midx in mvals) {
			for (idx = 0; idx < obj.options.length; idx++) {
				if (mvals[midx] == obj.options[idx].value) {
					obj.options[idx].selected = true;
				}
			}
		}
		modlist_updatelfield(id);
		modlist_checkbtns(id);
	}
}
/*>>>*/
function modlist_movebottom(id) {/*<<<*/
	if (obj = document.getElementById(id+"_list")) {
		var toppos = obj.options.length-1;
		mvals = new Array();
		for (var idx = 0; idx < obj.options.length; idx++) {
			if (obj.options[idx].selected) {
				mvals.push(obj.options[idx].value);
			}
		}
		for (var midx in mvals) {
			for (idx in obj.options) {
				if (obj.options[idx].value == mvals[midx]) {
					cidx = idx;
					while (cidx < toppos) {
						ncidx = parseInt(cidx)+1;
						swap_list_items(obj, cidx, ncidx);
						cidx++;
					}
				}
			}
		}
		// restore the selected items
		for (idx = 0; idx < obj.options.length; idx++) {
			obj.options[idx].selected = false;
		}
		for (var midx in mvals) {
			for (idx = 0; idx < obj.options.length; idx++) {
				if (mvals[midx] == obj.options[idx].value) {
					obj.options[idx].selected = true;
				}
			}
		}
		modlist_updatelfield(id);
		modlist_checkbtns(id);
	}
}
/*>>>*/
function modlist_disable_buttons(id) {/*<<<*/
	btnsub = new Array("_btnmovedown", "_btnmoveup", "_btnmovetop", "_btnmovebottom", "_btnaddnew", "_btndel");
	for (i = 0; i < btnsub.length; i++) {
		disable_toolbar_btn_byid(id+btnsub[i]);
	}
}
/*>>>*/
function swap_list_items(listobj, idx1, idx2) {/*<<<*/
	swpval = obj.options[idx1].value;
	swptext= obj.options[idx1].text;
	obj.options[idx1].value=obj.options[idx2].value;
	obj.options[idx1].text=obj.options[idx2].text;
	obj.options[idx2].value=swpval;
	obj.options[idx2].text=swptext;
}
/*>>>*/
// function for the demo widget <<<
//	which is really a collectin of 
//	non-functional widgets used for display purposes so that the wizard can
//	be used to gather data to create another widget, with the user aware
//	of what widgets are available for use.
// >>>
function demowidget(parentid, widgetname) {/*<<<*/
	var posswidgets = new Array();
	posswidgets.push("textbox");
	posswidgets.push("select");
	posswidgets.push("listbox");
	posswidgets.push("datepicker");
	posswidgets.push("modlist");
	posswidgets.push("textarea");
	posswidgets.push("helpertext");
	posswidgets.push("memorytext");
	posswidgets.push("checklabel");
	posswidgets.push("infobox");
	posswidgets.push("label");
	posswidgets.push("checkbox");
	posswidgets.push("radio");
	posswidgets.push("spinner");
	// widget name aliases
	switch (widgetname) {
		case "spinselect":
		case "spinint": {
			widgetname = "spinner";
			break;
		}
		case "list": {
			widgetname = "listbox";
			break;
		}
		case "date": {
				widgetname = "datepicker";
			break;
		}
		default: {
		}
	}
	// first, hide all other demos, if available
	for (var idx in posswidgets) {
		setDivVis(parentid+"_"+posswidgets[idx], false);
	}
	// now, show the required one
	setDivVis(parentid+"_"+widgetname, true);
}
/*>>>*/
function setDivVis(divid, state) {/*<<<*/
	if (obj = document.getElementById(divid)) {
		if (state) {
			obj.style.display = "";
		} else {
			obj.style.display = "none";
		}
	}
}
/*>>>*/
function popdatepicker(baseurl, targetid, title, dateformat, allowweekends, allowholidays, baddays) {/*<<<*/
	if (obj = document.getElementById(targetid)) {
		var startdate = obj.value;
		window.open(baseurl+"?title="+title+"&target="+targetid+"&startdate="+startdate+"&dateformat="+dateformat+"&allowweekends="+allowweekends+"&allowholidays="+allowholidays+"&baddays="+baddays, "", "toolbar=no,status=no,menubar=no,location=no");
	} else {
		alert("invalid target id set for popdatepicker");
	}
}
/*>>>*/
function focus_item(id) {/*<<<*/
	if (obj = document.getElementById(id)) {
		obj.focus();
	}
}
/*>>>*/
if (typeof(swapimg) == "undefined") {/*<<<*/
	function swapimg (img, newsrc, title) {
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
}
/*>>>*/
</script>
<?php
	if (file_exists("wizard.css")) {
?>
	<link rel="Stylesheet" type="text/css" href="wizard.css">
<?php
	} elseif (file_exists("../wizard.css")) {
?>
	<link rel="Stylesheet" type="text/css" href="../wizard.css">
<?php
	} else {
?>
<style>
div.wizard_form {
	border: 			1px solid #333333;
	padding:			10px;
	margin:				10px;
}
input.wizard_selbox {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#cccce5;
	font-size:			12px;
	width:				255px;
}
input.wizard_selbox:hover {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#dddde5;
	font-size:			12px;
	width:				255px;
}
input.wizard_selbox:focus	{
    color: 				#000000;
    font-family: 		Verdana, Helvetica;
    background-color: 	#eeeee5;
	font-size: 			12px;
	border:				solid 1px #333746;
	width:				255px;
}
input.wizard_textbox_locked {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #444444;
	color:				#333333;
	background-color: 	#aaaac2;
	font-size:			12px;
	width:				280px;
}
input.wizard_textbox {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#cccce5;
	font-size:			12px;
	width:				280px;
}
input.wizard_textbox:hover {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#dddde5;
	font-size:			12px;
	width:				280px;
}
input.wizard_textbox:focus	{
    color: 				#000000;
    font-family: 		Verdana, Helvetica;
    background-color: 	#eeeee5;
	font-size: 			12px;
	border:				solid 1px #333746;
	width:				280px;
}
input.wizard_textbox_med {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#cccce5;
	font-size:			12px;
	width:				240px;
}
input.wizard_textbox_med:hover {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#dddde5;
	font-size:			12px;
	width:				240px;
}
input.wizard_textbox_med:focus	{
    color: 				#000000;
    font-family: 		Verdana, Helvetica;
    background-color: 	#eeeee5;
	font-size: 			12px;
	border:				solid 1px #333746;
	width:				240px;
}
input.wizard_textbox_sml {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#cccce5;
	font-size:			12px;
	width:				140px;
}
input.wizard_textbox_sml:hover {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#dddde5;
	font-size:			12px;
	width:				140px;
}
input.wizard_textbox_sml:focus	{
    color: 				#000000;
    font-family: 		Verdana, Helvetica;
    background-color: 	#eeeee5;
	font-size: 			12px;
	border:				solid 1px #333746;
	width:				140px;
}
textarea.wizard_textarea {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#cccce5;
	font-size:			12px;
	width:				280px		
}
textarea.wizard_textarea:hover {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#dddde5;
	font-size:			12px;
	width:				280px;
}
textarea.wizard_textarea:focus {
    color: 				#000000;
    font-family: 		Verdana, Helvetica;
    background-color: 	#eeeee5;
	font-size: 			12px;
	border:				solid 1px #333746;
	width:				280px;
}
select.wizard_select {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#cccce5;
	font-size:			12px;
	width:				280px;
	height:				20px;
}
select.wizard_select:hover {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#dddde5;
	font-size:			12px;
	width:				280px;
}
select.wizard_select:focus {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#eeeee5;
	font-size:			12px;
	width:				280px;
}
select.wizard_list {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#cccce5;
	font-size:			12px;
	width:				280px;
	height:				150px;
}
select.wizard_list:hover {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#dddde5;
	font-size:			12px;
	width:				280px;
	height:				150px;
}
select.wizard_list:focus {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#eeeee5;
	font-size:			12px;
	width:				280px;
	height:				150px;
}
select.wizard_modlist {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#cccce5;
	font-size:			12px;
	width:				200px;
	height:				150px;
}
select.wizard_modlist:hover {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#dddde5;
	font-size:			12px;
	width:				200px;
	height:				150px;
}
select.wizard_modlist:focus {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#eeeee5;
	font-size:			12px;
	width:				200px;
	height:				150px;
}
select.wizard_select_sml {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#cccce5;
	font-size:			12px;
	width:				140px;
}
select.wizard_select_sml:hover {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#dddde5;
	font-size:			12px;
	width:				140px;
}
select.wizard_select_sml:focus {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#eeeee5;
	font-size:			12px;
	width:				140px;
}
input.wizard_spinner {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#cccce5;
	font-size:			12px;
	width:				150px;
}
input.wizard_spinner:hover {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#dddde5;
	font-size:			12px;
	width:				150px;
}
input.wizard_spinner:focus {
    font-family: 		Verdana, Helvetica;
	border:				1px solid #222635;
	color:				#000000;
	background-color: 	#eeeee5;
	font-size:			12px;
}
div.wizard_stepnum {
	border-bottom: 		1px solid #555555;
	font-family:		Verdana, Helvetica;
	font-size:			10px;
	padding-left:		5px;
	padding-right:		5px;
	padding-bottom:		2px;
	text-align:			center;
	width:				75px;
}
div.wizard_body {
	border:				2px groove #555555;
	height:				<?=$wizard_body_height?>px;
}
div.wizard_step {
	border:				none;
	position:			relative;
	padding-top:		0px;
	width:				95%;
	margin:				auto;
	padding-bottom:		10px;
	height:				<?=get_wizard_step_height()?>px;
	overflow:			auto;
	border-top:			1px solid #7a7a7a;
	border-bottom:		1px solid #7a7a7a;
}
div.wizard_step_flat {
	border:				none;
	position:			relative;
	padding-top:		0px;
	width:				95%;
	margin:				auto;
	padding-bottom:		0px;
}
hr.wizard_hr {
	border:				2px groove #555555;
	width:				95%;
}
div.wizard_caption {
	border:				1px solid #555555;
	padding:			5px;
	text-align:			center;
	height:				30px;
	overflow:			auto;
}
div.hiddenCaption {
	display: 			none;
}
div.wizard_head {
	margin-left:		auto;
	margin-right:		auto;
	margin-top:			10px;
	margin-bottom:		15px;
	width:				95%;
	height:				14%;
}
div.wizard_label {
	margin-top:			10px;
	width:				80%;
	padding:			5px;
	border:				1px solid #666666;
}
div.wizard_infobox {
	width:				80%;
	padding:			5px;
	border:				1px solid #666666;
	height:				200px;
	overflow:			auto;
}
span.wizard_check {
	cursor:				pointer;
}
div.summary_table_div {
	border:				inset;
	height:				75%;
	width:				75%;
	margin:				auto;
	overflow:			auto;
}
td.wizard_summary_lcol {
	width:	45%;
	border-top: 1px solid #777777;
	vertical-align: top;
}
td.wizard_summary_rcol {
	width:	55%;
	border-top: 1px solid #777777;
	padding-left: 8px;
	vertical-align: top;
}
h3.normal {
}
h3.supersize {
	font-size: 24px;
}
input.modlist_btn {
	width: 55px;
	font-family: arial, helvetica,
	font-size: smaller;
	padding: 0px;
}
.tiptable {
	border: 1px solid black;
	padding: 0px;
	margin: 0px;
}
.tiptitle {
	text-color: black;
	background-color: #4c7fb6;
	font-family: verdana, tomaha, helvetica;
	font-weight: bold
	text-align; center;
	text-decoration: underline;
	padding: 2px;
}
.tiptexttable {
	padding: 0px;
	margin: 0px;
}
.tiptext {
	border: 1px solid black;
	background-color: #ffffe1;
	font-family: verdana, tomaha, helvetica;
	text-align: justify;
	padding: 3px;
}
.tiptd {
	padding: 0px;
}
</style>
<?php } ?>
