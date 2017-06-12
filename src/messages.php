<?php
function setmsg($idx, $val) {
	$_SESSION["lang_".$idx]=$val;
}
function msg($idx) {
	if (is_array($_SESSION)) {
		if (array_key_exists("lang_".$idx, $_SESSION)) {
			return $_SESSION["lang_".$idx];
		} else {
			return "{".$idx."}";
		}
	}
}
function msg_exists($idx) {
	if (is_array($_SESSION)) {
		if (array_key_exists("lang_".$idx, $_SESSION)) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}
?>
