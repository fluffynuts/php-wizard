<html>
<head>
<title>Post test page</title>
<link rel="Stylesheet" type="text/css" href="default.css">
</head>
<body>
<?
	if (is_array($_POST)) {
		?><p>dumping post array:</p><?
		foreach ($_POST as $idx => $val) {
			print("[".$idx."] => {".$val."}<br>");
		}
	} else {
		?><p>nothing sent in post array</p><?
	}
?>
</body>
</html>
