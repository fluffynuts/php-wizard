/* 
ComboSelect script by Daf

Purpose: to proved the same funcitonality as a combo select -- ie a drop-down
	list as well as the ability to edit the text in the box. This is 
	accomplished with a dhtml div, an input and a button (or an image, if you
	want to use that)

Usage: create your input and button/image in your document, and then insert
	some script like:
	<script language="Javascript">
	ComboSelect.setup ({
		input		: 	"<id of input>",
		button		:	"<id of button>",
		options		:	"option1;option2;option3"
	});
	</script>
	*after* you have created the input and button. You may also try allowing
	this script to create the two controls for you: just specify a container
	element for the mega-control, and we'll try to make it all up  (:

	other parameters available are:
		option_ids:			allows you to have a list with selectable values
			different from the passed id value. Basically the same purpose as
			a "value" tag in a <select> <option>. If ids aren't sent, the
			options are used as the ids
		id_field: usually a hidden field, this will take the value of the
			selected id. Only used if a different id list is requested. 
			(what would be the point otherwise?)
		position: position for the list to open at, relative to the input.
			this position is modified to take into accout the button as well.
			This position should be left alone to emulate a true comboselect,
			but you can mess with it if you please. I'm not sure how the 
			positioning parameters work (haven't spent much time with it), but
			it's the same as the DHTML date selector, which I credit below.
		background:	css-style background setting for the optionlist
		border:	css-style border for the optionlist. If not specified, 
			the optionlist attempts to get it's styling from the input
			field, defaulting to a sort of off-white background with
			recessed border.
		selcolor:	color of selected item text. Defaults to black
		selbg:		color of selected item background. Defaults to a light blue
		typeahead:	boolean enabling or disabling typeahead. Typeahead is
			incomplete, so this does nothing for now
		editable:	boolean enabling or disabling typing in the input. Disabling
			editable basically gives you the same functionality as a <select>
Credits: Mihai Bazon, for his fantastic dhtml calendar. Not only did I learn
	a lot from his code (this is the first js object I have ever coded), but
	I also was saved a lot of hassle by having some of his functions available
	to me. Check out his awesome work at: http://dynarch.com/mishoo/
	
TODO: 
	* Type-Ahead. If you peruse the code, you see I'm incomplete on it, and it's
		just disabled for now. If you feel like completing it, do so, and let me
		know (:
	* Scrolling on the option div, when a user uses the up/down keys to change 
		options. Haven't figured out how to do that yet.
	* Small focus stuff: the up-down behaviour in the option list requires that
		the button or input be focussed. So it's not perfect, but it works 
		nearly the same as a regular select.
*/
ComboSelect = function () {/*<<<*/
this.params=new Array();
this.aopts=new Array();
this.aids=new Array();
this.hash=new Array();
this.mustgenerate=true;
this.selectShown=false;
this.button_selected=false;
this.div_selected=false;
this.input_selected=false;
}/*>>>*/
ComboSelect.prototype.gengui = function () {/*<<<*/
	// generate the options div
	var i = document.getElementById(this.params['input']);
	if (i) {
		// colors
		if ((this.params["color"] == "") || (this.params["background"] == "")) {
			var b=document.getElementsByTagName("body")[0];
			if (b.style.background == "") {
				this.params["background"]="TextBackground";
			} else {
				this.params["background"]=b.style.background;
			}
			if (b.style.color == "") {
				this.params["color"]="TextColor";
			} else {
				this.params["color"]=b.style.color;
			}
		}
		cmb_width=i.style.width.substring(0, i.style.width.length-2);
		b=document.getElementById(this.params['button']);
		if (b) {
			btn_width=b.style.width.substring(0, b.style.width.length-2);
		} else {
			btn_width=30;
		}
		if (cmb_width == '') {
			cmb_width = "280px";
		} else {
			cmb_width=parseInt(cmb_width)+parseInt(btn_width)-(this.params['border_width']*3);
			cmb_width = cmb_width+"px";
		}
		var parent=document.getElementsByTagName('body')[0];
		var d=ComboSelect.createElement('div', parent);
		var j=0;
		while (tmp=document.getElementById('optionlist'+j)) {
			j++;
		}
		d.id='optionlist'+j;
		ComboSelect.watched_divs.push(d.id);
		ComboSelect.div_inactive[d.id]=0;
		ComboSelect.combos[d.id]=this;
		//d.style.visibility='none';
		this.did=d.id;
		this.optiondiv=d;
		d.style.border=this.params['border'];
		d.style.background=this.params['background'];
		d.style.color=this.params['color'];
		d.style.height=this.params['height'];
		d.style.position='absolute';
		d.style.overflow='auto';
		d.style.width=cmb_width;
		d.style.height=this.params['height'];
		this.genoptions();
		var self=this;
		d["onmousemove"] = function () {
			self.div_selected=true;
		};
		d["onmouseout"] = function () {
			self.div_selected=false;
		};
	} else {
		return;
	}
	ComboSelect.showAtElement(d.id, this.params['button'], this.params['position']);
	this.mustgenerate=false;
};
/*>>>*/
ComboSelect.prototype.setActive = function () {/*<<<*/
	ComboSelect.div_inactive[this.did]=0;
}
/*>>>*/
ComboSelect.prototype.setInactive = function() {/*<<<*/
	alert('setting '+this.did+' inactive');
	ComboSelect.div_inactive[this.did]=1;
}
/*>>>*/
ComboSelect.prototype.typeAhead = function () {/*<<<*/
	var testval=this.input.value;
	for (var opt in this.aopts) {
		if (opt.toLower == testval) {
			
		}
	}
}
/*>>>*/
ComboSelect.prototype.hilite = function (trid) {/*<<<*/
	j=this.hash.length;
	for (var i=0; i<j; i++) {
		if (this.hash[i]==trid) break;
	}
	if (i<j) {
		this.current_idx=i;
	}
	if (tr=document.getElementById(trid)) {
		tr.style.background=this.params["selbg"];
		tr.style.color=this.params["selcolor"];
	}
	for(x=0; x<j; x++) {
		if (x==i) continue;
		if (tr=document.getElementById(this.hash[x])) {
			tr.style.background=this.params["background"];
			tr.style.color=this.params["color"];
		}
	}
}
/*>>>*/
ComboSelect.prototype.genoptions = function () {/*<<<*/
	
	i=0;
	var parentdiv = document.getElementById(this.did);
	var t = ComboSelect.createElement("table", parentdiv);
	t.style.width='100%';
	t.style.borderspacing='0px';
	t.style.border="0px";
	did=this.did;
	optlist=this.params["options"];
	optids=this.params["option_ids"];
	if (optids == "") optids=optlist;
	optioncount = 0;
	while (optlist.length) {
		x = optlist.indexOf(this.params["separator"]);
		if (x == -1) x = optlist.length;
		thisopt = optlist.substring(0, x);
		optlist = optlist.substring(x+1, optlist.length);
		x=optids.indexOf(this.params["separator"]);
		if (x == -1) x = optids.length;
		thisid = optids.substring(0, x);
		optids = optids.substring(x+1, optids.length);
		
		var tr=ComboSelect.createElement("tr", t);
		tr.id=did+'_'+thisid;
		this.hash[i]=tr.id;
		this.aopts[i]=thisopt;
		this.aids[i++]=thisid;
		
		var self=this;
		tr["onmouseover"]=function () {
			self.hilite(this.id);
		}
		tr["onclick"]=function() {
			self.setSelection();
		}
		
		var td=ComboSelect.createElement("td", tr);
		td.innerHTML=thisopt;
		optioncount++;
	}
	droplisth = parentdiv.style.height;
	droplisth = droplisth.substring(0, droplisth.length - 2);
	// assuming 20px per option, we can size this optionlist div down for a
	//	small list...
	optheight = 20 * optioncount;
	maxheight = this.params["height"].substring(0, this.params["height"].length-2);
	if (optheight < maxheight) {
		parentdiv.style.height = optheight+"px";
	} else {
		parentdiv.style.height = maxheight+"px";
	}
}
/*>>>*/
ComboSelect.createElement = function(type, parent) {/*<<<*/
	var el = null;
	if (document.createElementNS) {
		// use the XHTML namespace; IE won't normally get here unless
		// _they_ "fix" the DOM2 implementation.
		el = document.createElementNS("http://www.w3.org/1999/xhtml", type);
	} else {
		el = document.createElement(type);
	}
	if (typeof parent != "undefined") {
		parent.appendChild(el);
	}
	return el;
};/*>>>*/
ComboSelect.prototype.showSelect = function (state) {/*<<<*/
	if (this.mustgenerate) {
		this.gengui();
	}
	obj=document.getElementById(this.did);
	if (obj) {
		if (typeof(state) == "undefined") {
			switch(obj.style.display) {
				case '': {
					obj.style.display='none';
					this.selectShown=false;
					break;
				}
				default: {
					obj.style.display='';;
					this.selectShown=true;
				}
			}
		} else {
			obj.style.display=(state)?'':'none';
		}
		if (ComboSelect.is_ie || ComboSelect.is_ie5)
			ComboSelect.hideShowCovered(this.did);
	}
	if (this.current_idx>-1) {
		this.hilite(this.hash[this.current_idx]);
	}
}
/*>>>*/
ComboSelect.prototype.setSelection = function (hide) {/*<<<*/
	if (this.optiondiv) {
		if (typeof(hide) == "undefined") hide=true;
		if (hide) {
			this.optiondiv.style.display='none';
			if (ComboSelect.is_ie || ComboSelect.is_ie5) {
				ComboSelect.hideShowCovered(divid);
			}
		}
	}
	if (this.input) {
		this.input.value=this.aopts[this.current_idx];
	} else {
		alert('no input!');
	}
	if (this.id_field) {
		this.id_field.value=this.aids[this.current_idx];
	}
}
/*>>>*/
ComboSelect.prototype.hiliteTrav = function (traverseby) {/*<<<*/
	var swp=this.current_idx;
	swp+=traverseby;
	if (swp<0) swp=0;
	if (swp>=this.aopts.length) swp=this.aopts.length-1;
	this.hilite(this.did+'_'+this.aids[swp]);
	if (this.input_selected) {
		this.setSelection(false);
	}
}
/*>>>*/
ComboSelect.prototype._keyEvent = function(ev) {/*<<<*/
	(ComboSelect.is_ie) && (ev = window.event);
	var act = (this.is_ie || ev.type == "keypress");
	if (ev.ctrlKey) {
		switch (ev.keyCode) {
		    case 37: // KEY left
				break;
		    case 38: // KEY up
				break;
		    case 39: // KEY right
				break;
		    case 40: // KEY down
				break;
		    default:
			return false;
		}
	} else switch (ev.keyCode) {
	    case 32: // KEY space (now)
			break;
	    case 27: // KEY esc
			this.showSelect(false);
			break;
		case 38: // KEY up
			this.hiliteTrav(-1);
			break;
	    case 40: // KEY down
			this.hiliteTrav(1);
			break;
	    case 13: // KEY enter
			if (this.div_selected || this.button_selected) {
				this.setSelection();
			}
			break;
	    default:
			/*
			if (this.input_selected) {
				this.typeAhead();
			}
			*/
		return false;
	}
	return ComboSelect.stopEvent(ev);
}
/*>>>*/
ComboSelect.hideShowCovered = function (objid) {/*<<<*/
	function continuation (objid) {

		var tags = new Array("applet", "iframe", "select");
		var obj=document.getElementById(objid);
		var el = obj;
		var obj_is_hidden;
		var p = ComboSelect.getAbsolutePos(el);
		var EX1 = p.x;
		var EX2 = el.offsetWidth + EX1;
		var EY1 = p.y;
		var EY2 = el.offsetHeight + EY1;
		
		if (obj.style.visibility=='hidden') 
			obj_is_hidden = true;
		else
			obj_is_hidden=false;

		for (var k = tags.length; k > 0; ) {
			var ar = document.getElementsByTagName(tags[--k]);
			var cc = null;

			for (var i = ar.length; i > 0;) {
				cc = ar[--i];

				p = ComboSelect.getAbsolutePos(cc);
				var CX1 = p.x;
				var CX2 = cc.offsetWidth + CX1;
				var CY1 = p.y;
				var CY2 = cc.offsetHeight + CY1;

				if (obj_is_hidden || (CX1 > EX2) || (CX2 < EX1) || (CY1 > EY2) || (CY2 < EY1)) {
					if (!cc.__msh_save_visibility) {
						cc.__msh_save_visibility = ComboSelect.getVisible(cc);
					}
					cc.style.visibility = cc.__msh_save_visibility;
				} else {
					if (!cc.__msh_save_visibility) {
						cc.__msh_save_visibility = ComboSelect.getVisible(cc);
					}
					cc.style.visibility = "hidden";
				}
			}
		}
	};
	if (ComboSelect.is_khtml)
		setTimeout("continuation("+objid+")", 10);
	else
		continuation(objid);
};
/*>>>*/
ComboSelect.getAbsolutePos = function(el) {/*<<<*/
	var SL = 0, ST = 0;
	var is_div = /^div$/i.test(el.tagName);
	if (is_div && el.scrollLeft)
		SL = el.scrollLeft;
	if (is_div && el.scrollTop)
		ST = el.scrollTop;
	var r = { x: el.offsetLeft - SL, y: el.offsetTop - ST };
	if (el.offsetParent) {
		var tmp = ComboSelect.getAbsolutePos(el.offsetParent);
		r.x += tmp.x;
		r.y += tmp.y;
	}
	return r;
};
/*>>>*/
ComboSelect.addEvent = function(el, evname, func) {/*<<<*/
	if (el.attachEvent) { // IE
		el.attachEvent("on" + evname, func);
	} else if (el.addEventListener) { // Gecko / W3C
		el.addEventListener(evname, func, true);
	} else {
		el["on" + evname] = func;
	}
}
/*>>>*/
ComboSelect.stopEvent = function(ev) {/*<<<*/
	ev || (ev = window.event);
	if (ComboSelect.is_ie) {
		ev.cancelBubble = true;
		ev.returnValue = false;
	} else {
		ev.preventDefault();
		ev.stopPropagation();
	}
	return false;
};
/*>>>*/
ComboSelect.checkInactive = function () {/*<<<*/
	for (var div in ComboSelect.watched_divs) {
		if (ComboSelect.div_inactive[div]==1) {
			ComboSelect.combos[div].showSelect(false);
		}
	}
	window.setTimeout('ComboSelect.checkInactive', 1000);
}
/*>>>*/
ComboSelect.showAt = function (objid, x, y) {/*<<<*/
	var obj=document.getElementById(objid);
	if (obj) {
		var s = obj.style;
		s.left = x + "px";
		s.top = y + "px";
	}
}/*>>>*/
ComboSelect.showAtElement = function (objid, elid, opts) {/*<<<*/
	var obj=document.getElementById(objid);
	var el=document.getElementById(elid);
	if (!(el && obj)) return;
	var p = ComboSelect.getAbsolutePos(el);
	if (!opts || typeof opts != "string") {
		alert("arf");
		ComboSelect.showAt(p.x, p.y + el.offsetHeight);
		return true;
	}
	fixPosition=function (box) {
		if (box.x < 0)
			box.x = 0;
		if (box.y < 0)
			box.y = 0;
		var cp = document.createElement("div");
		var s = cp.style;
		s.position = "absolute";
		s.right = s.bottom = s.width = s.height = "0px";
		document.body.appendChild(cp);
		var br = ComboSelect.getAbsolutePos(cp);
		document.body.removeChild(cp);
		if (ComboSelect.is_ie) {
			br.y += document.body.scrollTop;
			br.x += document.body.scrollLeft;
		} else {
			br.y += window.scrollY;
			br.x += window.scrollX;
		}
		var tmp = box.x + box.width - br.x;
		//if (tmp > 0) box.x -= tmp;
		tmp = box.y + box.height - br.y;
		//if (tmp > 0) box.y -= tmp;
	};
	obj.style.display = "block";
	continuation = function() {
		var w = obj.offsetWidth;
		var h = obj.offsetHeight;
		obj.style.display = "none";
		var valign = opts.substr(0, 1);
		var halign = "l";
		if (opts.length > 1) {
			halign = opts.substr(1, 1);
		}
		// vertical alignment
		switch (valign) {
		    case "T": p.y -= h; break;
		    case "B": p.y += el.offsetHeight;break;
		    case "C": p.y += (el.offsetHeight - h) / 2; break;
		    case "t": p.y += el.offsetHeight - h; break;
		    case "b": break; // already there
		}
		// horizontal alignment
		switch (halign) {
		    case "L": p.x -= w; break;
		    case "R": p.x += el.offsetWidth; break;
		    case "C": p.x += (el.offsetWidth - w) / 2; break;
		    case "r": p.x += el.offsetWidth - w; break;
		    case "l": break; // already there
		}
		p.width = w;
		p.height = h + 40;
		fixPosition(p);
		ComboSelect.showAt(objid, p.x, p.y);
	};
	if (ComboSelect.is_khtml)
		setTimeout("continuation()", 10);
	else
		continuation();
}
/*>>>*/
ComboSelect.getVisible = function (obj){/*<<<*/
	var value = obj.style.visibility;
	if (!value) {
		if (document.defaultView && typeof (document.defaultView.getComputedStyle) == "function") { // Gecko, W3C
			if (!ComboSelect.is_khtml)
				value = document.defaultView.
					getComputedStyle(obj, "").getPropertyValue("visibility");
			else
				value = '';
		} else if (obj.currentStyle) { // IE
			value = obj.currentStyle.visibility;
		} else
			value = '';
	}
	return value;
};
/*>>>*/
ComboSelect.setup = function (params) {/*<<<*/
	function param_default(pname, def) { 
		if (typeof params[pname] == "undefined") { 
			params[pname] = def; 
		} 
	};
	param_default('input', null);
	param_default('button', null);
	param_default('options', '');
	param_default('option_ids', '');
	param_default('id_field', '');
	param_default('position', 'Br');
	param_default('container', '');
	if (params['container'] != '') {
		// assume we have to do *all* the work -- not tested yet
		if (container=document.getElementById(params['container'])) {
			ComboSelect.gencombo(container, params['input'], params['button']);
		}
	}
	def_border='';
	if (i=document.getElementById(params['input'])) {
		def_border=i.style.border;
		border_width=i.style.border.width;
	}
	if (def_border == '') {
		def_border='inset';
		border_width=2;
	}
	param_default('border', def_border);
	params['border_width']=border_width;
	param_default('background', 'Background');
	param_default('color', "");
	param_default('height', '75px');
	param_default('separator', ';');
	param_default('selbg', 'Highlight');
	param_default('selcolor', 'HighlightText');
	param_default('typeahead', true);
	param_default('editable', true);
	
	var cmb = new ComboSelect();
	
	cmb.params=params;
	cmb.current_idx=-1;
	if (b = document.getElementById(params['button'])) {
		b["onclick"]=function () {
			cmb.showSelect();
		}
	}
	if (i=document.getElementById(params['input'])) {
		if (!params["editable"]) {
			i["onfocus"]=function() {
				this.blur();
			}
		}
	}
	cmb.input=i;
	cmb.button=b;
	if (obj = document.getElementById(params['id_field'])) {
		cmb.id_field=obj;
	}
	if (i) {
		i["onblur"] = function () {
			cmb.input_selected=false;
		}
		i['onkeypress'] = function (ev) {
			cmb.input_selected=true;
			cmb._keyEvent(ev);
		};
		i['onkeydown'] = function () {
			cmb.input_selected=true;
			cmb._keyEvent;
		};
		i['onclick'] = function () {
			cmb.input_selected=true;
			cmb.showSelect(false);
		};
	}
	if (b) {
		b['onkeypress'] = function (ev) {
			cmb.button_selected=true;
			cmb._keyEvent(ev);
		};
		b['onkeydown'] = function () {
			cmb.button_selected=true;
			cmb._keyEvent;
		};
		b['onblur'] = function () {
			if (!cmb.div_selected) {
				cmb.showSelect(false);
			}
		}
	}
}
/*>>>*/
ComboSelect.gencombo = function (container, inputname, buttonname) {/*<<<*/
// given a container element, this generates the combo controls
	if (container) {
		var i=ComboSelect.createElement('input', container);
		i.name=inputname;
		i.id=inputname;
		var b=ComboSelect.createElement('input', container);
		b.type='button';
		b.value='V';
		b.name=buttonname;
		b.id=buttonname;
	} else {
		alert('could not generate ComboSelect: container could not be found!');
		return false;
	}
}
/*>>>*/

ComboSelect.is_ie = ( /msie/i.test(navigator.userAgent) &&
		   !/opera/i.test(navigator.userAgent) );

ComboSelect.is_ie5 = ( ComboSelect.is_ie && /msie 5\.0/i.test(navigator.userAgent) );

/// detect Opera browser
ComboSelect.is_opera = /opera/i.test(navigator.userAgent);

/// detect KHTML-based browsers
ComboSelect.is_khtml = /Konqueror|Safari|KHTML/i.test(navigator.userAgent);
ComboSelect.activeDivs=new Array;
ComboSelect.inactiveTimerSet=false;
ComboSelect.watched_divs=new Array();
ComboSelect.div_inactive=new Array();
ComboSelect.combos=new Array();
