<html>
<head>
<title>Wizard test page</title>
<link rel="Stylesheet" type="text/css" href="default.css">
</head>
<body>
<?	include_once ("include/wizard.php"); 
	$set = array (
		"postpage"		=> "./test_post.php",
		"cancelpage"	=> "./test_post.php",
		"summary"		=> 1,
		"datepickstyle"	=> "popup",
	);
	$w = new Wizard($set);
	// step 1
	$def=array(
		"title"		=>	"First step:",
		"caption"	=>	"This is the first step in the test wizard. You will"
						." have to &quot;Accept the license&quot; to "
						."continue...",
	);
	$stepnum = $w->addstep($def);

	$w->addinput(array(
		"step"		=>	$stepnum,
		"prompt"	=>	"How often do you use wizards?",
		"type"		=>	"compound",
		"type1"		=>	"textbox",
		"varname1"	=>	"name",
		"value1"	=>	"5",
		"style1"	=>	"wizard_textbox_sml",
		"type2"		=>	"select",
		"varname2"	=>	"name_select",
		"value2"	=>	"red",
		"style2"	=>	"wizard_select_sml",
		"options2"	=>	array(
								"day" => "times a day", 
								"week" => "times a week",
								"month" => "times a month"),
		"title"		=>	"Tell us how often you use wizard-style interfaces",
		"tooltip"	=>	"It would be appreciated if you could give us an"
						." indication of how often you use wizard interfaces"
						." so that we can step up to the challenge of "
						."supporting that requirement.",
	));
	$def=array(
		"step"		=>	$stepnum,
		"prompt"	=>	"Please enter a description of where you find wizards "
						."useful:",
		"type"		=>	"textarea",
		"varname"	=>	"usefulness",
		"value"		=>	"In Hogwarts, when there are bad things flying around.",
		"title"		=>	"Enter some uses for wizards here.",
		"tooltip"	=>	"What could be said about the wizards?!",
	);
	$w->addinput($def);
	$w->addinput(array(
		"step"		=> $stepnum,
		"type"		=> "infobox",
		"value"		=> "<h5>Licensing:</h5>"
						."The Wizard class is released under the BSD license"
						." scheme. You may do pretty-much anything with it,"
						." as long as you don't claim that it's your own work."
						." It would also be appreciated if you contribute "
						."back to the project any enhancements or bugfixes "
						."that you make. This project is a community-driven"
						." one, and your contributions, even if they are simply"
						." requests or reports, are valued.",
		"tooltip"	=> "An example of the infobox or license &quot;input&quot"
						." --  which isn't really an input at all!",
		"extra"		=>	array(
							"height"	=>	"100px",
						)
	));
	$w->addinput(array(
		"varname"	=> "accept",
		"prompt"	=> "Yes, I accept the terms of this license",
		"type"		=> "checklabel",
		"tooltip"	=> "Example of a checklabel -- best used for things like"
						." accepting a license or something. You can give this "
						."input a required value, and prevent the user from "
						."going forward with the next step of the wizard.",
		"required_val"	=> "1",
	));
	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Please select an option from the spinner:",
		"type"		=>	"spinner",
		"varname"	=>	"spinner",
		"value"		=>	"bobo the clown",
		"title"		=>	"Enter your name here",
		"tooltip"	=>	"Example of a spinner",
	);
	$options=array("minky the rat", "johnny bravo", "bobo the clown",);
	$def["options"] = $options;
	$w->addinput($def);
	
	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Please enter your birthday:",
		"type"		=>	"date",
		"varname"	=>	"name3",
		"value"		=>	"2004-11-12",
		"title"		=>	"Enter your name here",
		"tooltip"	=>	"Example of a date selector",
	);
	$w->addinput($def);
	
	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Select one from the drop-down list:",
		"type"		=>	"select",
		"varname"	=>	"name4",
		"value"		=>	"bobo the clown",
		"title"		=>	"Example of a select",
		"tooltip"	=>	"Example of a select",
	);
	$def["options"]=$options;
	$w->addinput($def);
	
	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Select one from the list:",
		"type"		=>	"list",
		"varname"	=>	"name5",
		"value"		=>	"bobo the clown",
		"title"		=>	"Listbox selection item",
		"tooltip"	=>	"This is an example of the usage of a listbox. Not"
							." the best control to use, in most circumstances,"
							." but supported nonetheless."
	);
	$def["options"]=$options;
	$w->addinput($def);
	
	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Please select one of the options",
		"type"		=>	"radio",
		"varname"	=>	"name6",
		"value"		=>	"bobo the clown",
		"title"		=>	"Example of radio button usage.",
		"tooltip"	=>	"This is an example of radio button usage -- a good "
						."way to get a user to select one option from a short"
						." list."
	);
	$def["options"]=$options;
	$w->addinput($def);
	
	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Please select one or more of the options:",
		"type"		=>	"checkbox",
		"varname"	=>	"name7",
		"value"		=>	"bobo the clown",
		"title"		=>	"Example of checkbox usage",
		"tooltip"	=>	"This is an example of checkbox usage. Checkboxes are "
						." an ideal way to allow the user to select one or more"
						." options from a short list."
	);
	$def["options"]=$options;
	$w->addinput($def);
	
	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Please select one from the list, or enter a value:",
		"type"		=>	"memorytext",
		"varname"	=>	"name8",
		"value"		=>	"bobo the clown",
		"title"		=>	"Select one from the list, or enter a value",
		"tooltip"	=>	"This is an example of the memorytext. The memorytext"
						." gives the user a list to choose from, but allows her"
						." to also enter any text value, which is passed on to"
						." the post page. The post page can then save the text"
						." and add it in to the list next time. In this way, "
						." lists of often-used strings can be built up to help"
						." your users be more productive."
	);
	$def["options"]=$options;
	$w->addinput($def);
	
	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Please select one from the list, or enter a value:",
		"type"		=>	"helpertext",
		"varname"	=>	"name9",
		"value"		=>	"bobo the clown",
		"title"		=>	"Example of a helpertext",
		"tooltip"	=>	"The helpertext provides the same functionality as a "
						." memory text, but without the save function. So you"
						." can populate a list of common text strings, and the"
						." user can also enter something free-form."
	);
	$def["options"]=$options;
	$w->addinput($def);

	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Cascading select: level 1:",
		"type"		=>	"cassel",
		"varname"	=>	"cassel1",
		"value"		=>	"red",
		"title"		=>	"Example of cascading select: level one",
		"cascades"	=>	array(
							"red"	=>	array ( "red" => array(
											"cassel2" => array (
														"red car" => "red car",
														"red boat" => "red boat",
														"red bike" => "red bike",
														),
											),
										),	
							"blue"	=>	array( "blue" => array(
											"cassel2" => array (
														"blue car" => "blue car",
														"blue boat" => "blue boat",
														"blue bike" => "blue bike",
														),
												),
										),
							"green"	=>	array( "green" => array(
											"cassel2" => array (
														"green car" => "green car",
														"green boat" => "green boat",
														"green bike" => "green bike",
														),
												),
										),
						),
					
	);
	$w->addinput($def);
	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Cascading select: level 2:",
		"type"		=>	"cassel",
		"varname"	=>	"cassel2",
		"value"		=>	"-- please select level 1 --",
		"title"		=>	"Enter your name here",
		"cascades"	=>	array(
							"--" => "-- please select level 1 --",
							"red boat" => array( "red boat" => array(
												"cassel3" => array(
															"1" => "1 props",
															"2" => "2 props",
															"3" => "3 props",
															),
												"cassel4" => array(
															"1" => "1 sail",
															"2" => "2 sails",
															),
												),
										),
							"blue boat" => array( "blue boat" => array(
												"cassel3" => array(
															"1" => "1 props",
															"2" => "2 props",
															"3" => "3 props",
															),
												"cassel4" => array(
															"1" => "1 sail",
															"2" => "2 sails",
															),
												),
										),
							"green boat" => array( "green boat" => array(
												"cassel3" => array(
															"1" => "1 props",
															"2" => "2 props",
															"3" => "3 props",
															),
												"cassel4" => array(
															"1" => "1 sail",
															"2" => "2 sails",
															),
												),
										),
							"__default__" => array(
												"cassel3" => array(
													"1"	=>	"1 wheel",
													"2"	=>	"2 wheel",
													"3"	=>	"3 wheel",
													"4"	=>	"4 wheel",
												),
												"cassel4" => array(
													"500" => "500cc",
													"1000" => "1 litre",
													"1500" => "1.5 litre",
													"2000" => "2.0 litre",
												),
											),
						),
	);
	$w->addinput($def);
	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Cascading select level 3",
		"type"		=>	"select",
		"varname"	=>	"cassel3",
		"value"		=>	"-- please select level 2 --",
		"title"		=>	"Cassel level 3",
		"options"	=> array("-- please select level 2 --"),
	);
	$w->addinput($def);
	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Cascading select level 3, branch2",
		"type"		=>	"select",
		"varname"	=>	"cassel4",
		"value"		=>	"bobo the clown",
		"title"		=>	"Enter your name here"
	);
	$w->addinput($def);
	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Please enter your namee:",
		"type"		=>	"textbox",
		"varname"	=>	"namee",
		"value"		=>	"bobo the clown",
		"title"		=>	"Enter your name here"
	);
	$w->addinput($def);
	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Please enter your namef:",
		"type"		=>	"textbox",
		"varname"	=>	"namef",
		"value"		=>	"bobo the clown",
		"title"		=>	"Enter your name here"
	);
	$w->addinput($def);
	$def=array(
		"step"		=>	0,
		"prompt"	=>	"Please enter your nameg:",
		"type"		=>	"textbox",
		"varname"	=>	"nameg",
		"value"		=>	"bobo the clown",
		"title"		=>	"Enter your name here"
	);
	$w->addinput($def);
	
	
	$def=array(
		"title"		=>	"second step:",
		"caption"	=>	"This is the second step in the test wizard. ",
	);
	$stepnum=$w->addstep($def);
	
	$def=array(
		"step"		=> $stepnum,
		"prompt"	=> "Please select your favourite colour:",
		"type"		=> "select",
		"varname"	=> "colour",
		"value"		=> "red",
	);
	$options=array(
		"red", "yellow", "blue", "green",
	);
	$def["options"]=$options;
	$w->addinput($def);
	$def=array(
		"step"		=> $stepnum,
		"prompt"	=> "Please select your favourite word:",
		"type"		=> "textbox",
		"varname"	=> "favword",
		"value"		=> "puppy",
	);
	$w->addinput($def);
	$def=array(
		"step"		=> $stepnum,
		"prompt"	=> "Please select your favourite car:",
		"type"		=> "radio",
		"varname"	=> "favcar",
		"value"		=> "mercedes",
	);
	$options=array(
		"bmw", "mercedes", "ford", "chevy",
	);
	$def["options"]=$options;
	$w->addinput($def);
	$def=array(
		"step"		=> $stepnum,
		"prompt"	=> "Please select your favourite people:",
		"type"		=> "checkbox",
		"varname"	=> "favpeople",
		"value"		=> "George Bush",
	);
	$options=array(
		"George Bush", "Al Gore", "Santa Claus", "Bob Saget",
	);
	$def["options"]=$options;
	$w->addinput($def);
	
	$def=array(
		"step"		=> $stepnum,
		"prompt"	=> "Please tell me about your childhood:",
		"type"		=> "textarea",
		"varname"	=> "childhood",
		"value"		=> "Well, mummy used to make the most terrible sandwiches...",
	);
	$w->addinput($def);
	$def=array(
		"step"		=> $stepnum,
		"prompt"	=> "Please select the date of your granny's birthday:",
		"type"		=> "date",
		"varname"	=> "granbday",
		"value"		=> "1914-08-03",
	);
	$w->addinput($def);
	
	$w->render();
?>
<!--<div id="debug" style="height: 100px; overflow: auto">debug output:<br></div>-->
</body>
</head>
