/*
 *  # NanoSpell Spell Check Plugin for CKEditor #
 *
 *  (C) Copyright nanospell.com (all rights reserverd)
 *  License:  http://ckeditor-spellcheck.nanospell.com/license
 *
 *
 *	# Resources #
 *
 *	Getting Started - http://ckeditor-spellcheck.nanospell.com
 *	Installation 	- http://ckeditor-spellcheck.nanospell.com/how-to-install
 *	Settings 		- http://ckeditor-spellcheck.nanospell.com/plugin-settings
 *	Dictionaries 	- http://ckeditor-spellcheck.nanospell.com/ckeditor-spellchecking-dictionaries
 *
 */
/*
 * A huge thanks To Frederico Knabben and all contributirs to CKEditor for releasing and maintaining a world class javascript HTML Editor.
 * FCK and CKE have enabled a new generation of online software , without your excelent work this project would be pointless.
 */
(function() {
	'use strict';


	var maxRequest = 200;
	var editorHasFocus = false;
	var spell_delay = 250;
	var spell_fast_after_spacebar = true;
	var settings;
	var settings_path;
	var state = false;
	var lang = "en";
	var locale = {
			ignore: "Ignore",
			learn: "Add To Personal Dictionary",
			nosuggestions: "( No Spelling Suggestions )",
		}
		/* #0 plugin init layer */
	CKEDITOR.plugins.add('nanospell', {
		icons: 'nanospell',
		init: function(editor) {



			/* #1 menu layer */

		//== 
			var afterInit= function(editor) {

			var wiggles_filter = {

                root:function(r){

                    var funky = document.createElement('div');
                    funky.innerHTML = r.getHtml();
                    var bogus = funky.querySelectorAll('span[data-cke-bogus]');

                   for(var i =0; i<bogus.length;i++){
                       var bogusnode =bogus[i];
                       unwrapbogus(bogusnode);
                   }
                    r.setHtml(funky.innerHTML);
                }

                /*,
                elements: {

					span: function(el) {
						var isNano = !!el.attributes && (el.attributes['class'] == "nanospell-typo" || el.attributes['class'] == "nanospell-typo-disabled")
						if (isNano) {
							return el.children[0];
						}
					}
				}*/
			};
			var dataProcessor = editor.dataProcessor,
				htmlFilter = dataProcessor && dataProcessor.htmlFilter;
			if (htmlFilter) {
				htmlFilter.addRules(wiggles_filter);
			}

			clearInterval(editor.wiggles_filter_handle);
		};

			editor.wiggles_filter_handle =  setInterval(function(){if(editor.instanceReady){afterInit(editor)}},33);

		  

            //function(){this.instanceReadyHelper(editor, this);

			settings_path = this.path;
			if (editor && !editor.config.nanospell) {
				editor.config.nanospell = {}
			}
			editor.getWin = function() {
				return editor.window.$
			}
			editor.getDoc = function() {
				return editor.document.$;
			}
			editor.getBody = function() {
				return   editor.editable().$ || editor.document.$.body;
			}
			settings = editor.config.nanospell;
			if (!settings) {
				settings = {};
			}
			lang = settings.dictionary || lang
			editor.addCommand('nanospell', {
				exec: function(editor) {
					if (!state) {
						start()
					} else {
						stop()
					};
				},
				editorFocus: true
			});
			editor.ui.addButton('nanospell', {
				label: 'Spell Checking by Nanospell',
				command: 'nanospell',
				toolbar: 'nanospell',
				icon: this.path + 'icons/nanospell.png'
			});
			editor.ui.addButton('Nanospell', {
				label: 'Spell Checking by Nanospell',
				command: 'nanospell',
				toolbar: 'Nanospell',
				icon: this.path + 'icons/nanospell.png'
			});
			editor.on("key", function(k) {
				keyHandler(k.data.keyCode)
			})
			editor.on("focus", function() {
				editorHasFocus = true;
			});
			editor.on("blur", function() {
				editorHasFocus = false;
			});
			editor.on("instanceReady", function() {
				if (settings.autostart !== false) {
					start()
				}
			})
			editor.on('mode', function() {
				if (editor.mode == 'wysiwyg' && state) {
					start()
				}
				return true;
			})

			setUpContextMenu(editor, this.path);







			function setUpContextMenu(editor, path) {
			var iconpath = path + 'icons/nanospell.png';
			if (!editor.contextMenu) {
				setTimeout(function(){setUpContextMenu(editor, path)},100)
				return
			}
			var generateSuggestionMenuItem = function(suggestion, icon, typo, element) {
				return {
					label: suggestion,
					icon: icon ? iconpath : null,
					group: 'nano',
					onClick: function() {
						if (suggestion.indexOf(String.fromCharCode(160)) > -1) {
							return window.open('http://ckeditor-spellcheck.nanospell.com/license?discount=developer_max');
						}
 
						    var txt = document.createElement("textarea");
						    txt.innerHTML = suggestion;
						    
						 

						editor.insertText(txt.value);
				

						element.$.className = "nanospell-typo-disabled"
					}
				}
			}
			var currentTypoText = function() {
				var anchor = editor.getSelection().getStartElement();
				var range = editor.createRange();
				//Fixes FF and IE highlighting of selected word
				range.selectNodeContents(anchor)
				range.enlarge();
				range.optimize();
				range.select()
					// end fix
				return anchor.getText();
			}

			editor.addMenuGroup('nano', -10 * 3); /*at the top*/
			editor.addMenuGroup('nanotools', -10 * 3 + 1);

			if(!editor.contextMenu.hasNanoListener){

                editor.contextMenu.addListener(function(element) {
                    if (!element.$ || !element.$.className || element.$.nodeName.toLowerCase() != 'span' || element.$.className !== "nanospell-typo") {
                        return;
                    }
                    var typo = currentTypoText();
                    var retobj = {};
                    var suggestions = getSuggestions(typo);
                    if (!suggestions) {
                        return;
                    }
                    if (suggestions.length == 0) {

                        editor.addMenuItem('nanopell_nosug', {
                            label: locale.nosuggestions,
                            icon: iconpath,
                            group: 'nano',
                        });
                        retobj["nanopell_nosug"] = CKEDITOR.TRISTATE_DISABLED
                    } else {
                        for (var i = 0; i < suggestions.length; i++) {
                            var word = suggestions[i]
                            if (word.replace(/^\s+|\s+$/g, '').length < 1) {
                                continue;
                            }
                            editor.addMenuItem('nanopell_sug_' + i, generateSuggestionMenuItem(word, !!!i, typo, element));
                            retobj["nanopell_sug_" + i] = CKEDITOR.TRISTATE_OFF
                        }
                    }

                    editor.addMenuItem('nanopell_ignore', {
                        label: locale.ignore,
                        group: 'nanotools',
                        onClick: function() {
                            ignoreWord(element.$, typo, true);
                        }
                    });

                    retobj["nanopell_ignore"] = CKEDITOR.TRISTATE_OFF
                        //
                    if (localStorage ) {
                        editor.addMenuItem('nanopell_learn', {
                            label: locale.learn,
                            group: 'nanotools',
                            onClick: function() {
                                addPersonal(typo);
                                ignoreWord(element.$, typo, true);
                            }
                        });
                        retobj["nanopell_learn"] = CKEDITOR.TRISTATE_OFF
                    }
                    return retobj
                });

                editor.contextMenu.hasNanoListener = true;
			}
		}
		/* #2 setup layer */
		/* #3 nanospell util layer */




	var start = function() {

		editor.getCommand('nanospell').setState(CKEDITOR.TRISTATE_ON);
		state = true;
		appendCustomStyles(settings_path)



		var words = getWords(editor.document.$.body,maxRequest);

		if (words.length == 0) {
			render();
		} else {
			send(words);
		}
	}
	var stop = function() {
		editor.getCommand('nanospell').setState(CKEDITOR.TRISTATE_OFF);
		state = false;
		clearAllSpellCheckingSpans(editor.getBody());
	}

		editor.stopNanospell = function(){
		    stop();
		}
		editor.startNanospell = function(){
		    start();
		}

	function checkNow() {
		if (!selectionCollapsed()) {

			return;
		}
		if (state) {
			start();
		}
	}

	function elementAtCursor() {
		if (!editor.getSelection()) {
			return null;
		}
		return editor.getSelection().getStartElement();
	}

	function keyHandler(ch8r) {
		editorHasFocus = true;
		//recheck after typing activity
		if (ch8r >= 16 && ch8r <= 31) {
			return;
		}
		if (ch8r >= 37 && ch8r <= 40) {
			return;
		}
		var target = elementAtCursor();
		if (!elementAtCursor) {
			return;
		}
		//if! user is typing on a typo remove its underline
		if (target.$.className == "nanospell-typo") {
			target.$.className = 'nanospell-typo-disabled';
		}
		triggerSpelling((spell_fast_after_spacebar && (ch8r === 32 || ch8r === 10 || ch8r === 13)))
	}

	function send(words) {
		var url = resolveAjaxHandler();
		var callback = function(data) {
			parseRpc(data, words);
			if (words.length >= maxRequest) {
				checkNow()
			}
		}
		var data = wordsToRPC(words, lang)
		rpc(url, data, callback);
	}

	function wordsToRPC(words, lang) {
		return '{"id":"c0","method":"spellcheck","params":{"lang":"' + lang + '","words":["' + words.join('","') + '"]}}'
	}

	function rpc(url, data, callback) {
		var xhr = new XMLHttpRequest();
		if (!xhr) {
			return null;
		}
		xhr.open('POST', url, true);
		xhr.onreadystatechange = function() {
			if ((xhr.readyState == 4 && ((xhr.status >= 200 && xhr.status < 300) || xhr.status == 304 || xhr.status === 0 || xhr.status == 1223))) {
				
				callback(xhr.responseText);
				xhr = null;
			}
		};
		xhr.send(data);
		return true;
	}

	function parseRpc(data, words) {
		try{
		var json = JSON.parse(data);
	 }catch(e){
	
			var msg = ("Nanospell need to be installed correctly before use (server:"+settings.server+").\n\nPlease run nanospell/getstarted.html. ");
		 
			if(window.location.href.indexOf('nanospell/')<0){
				console.log(msg)
			}else{
				if(confirm(msg)){
					window.location = settings_path+"../getstarted.html"
				}
			}
	 }
 
		var result = json.result
		for (var i in words) {
			var word = words[i];
			if (result[word]) {
				suggestionscache[word] = result[word];
				spellcache[word] = false;
			} else {
				spellcache[word] = true;
			}
		}
		render();
	}

	function resolveAjaxHandler() {
		var svr = settings.server;
		var url = settings_path;
		
		url +="../";

		if (typeof(svr) == "undefined") {
			svr = "php"
		}
		svr = svr.toLowerCase();
		switch (svr) {
			case ".net":
				return url + "server/ajax/asp.net/tinyspell.aspx"
				break;
			case "asp.net":
				return url + "server/ajax/asp.net/tinyspell.aspx"
				break;
			case "net":
				return url + "server/ajax/asp.net/tinyspell.aspx"
				break;
			case "asp":
				return url + "server/ajax/asp/tinyspell.asp"
				break;
			default:
				/*php*/
				return url + "server/ajax/php/tinyspell.php"
				break
		}
	}

	function render() {


		if(window.getSelection && editor.getWin().getSelection().toString()){
			return;
		}

	    if(!state){
	        return;
	    }

		putCursor();
		var IEcaret = getCaretIE()
		clearAllSpellCheckingSpans(editor.getBody());
		normalizeTextNodes(editor.getBody())
		var caret = getCaret();
		MarkAllTypos(editor.getBody())
		setCaret(caret);
		setCaretIE(IEcaret)
		editor.fire('SpellcheckStart');
		editor.nanospellstarted = true;
	}

	function clearAllSpellCheckingSpans(base) {
		var i, node, nodes;
		var finished = false;
		while (!finished) {
			finished = true;
			nodes = editor.getDoc().getElementsByTagName("span")
			var i = nodes.length;
			while (i--) {
				node = nodes[i];
				if (node.className == ('nanospell-typo') || node.className == ('nanospell-typo-disabled')) {
					unwrapbogus(node);
					finished = false;
				}
			}
		}
	}

	function unwrapbogus(node) {
		node.outerHTML = node.innerHTML.replace("&#8203;",'');
	}

	function normalizeTextNodes(elem) {
		if (!isIE()) {
			elem.normalize();
			return;
		}
		/*IE normalize function is not stable, even in IE 11*/
		var child = elem.firstChild,
			nextChild;
		while (child) {
			if (child.nodeType == 3) {
				while ((nextChild = child.nextSibling) && nextChild.nodeType == 3) {
					child.appendData(nextChild.data);
					elem.removeChild(nextChild);
				}
			} else {
				normalizeTextNodes(child);
			}
			child = child.nextSibling;
		}
	}

	function isIE() {
		/*Why can Microsoft just use a stable javascript engine like V8*/
		var au = navigator.userAgent.toLowerCase();
		return (au.indexOf("msie") > -1 || au.indexOf("trident") > -1 || au.indexOf(".net clr") > -1)
	}

	function appendCustomStyles(path) {
		if (!editor.getDoc().getElementById('nanospell_theme')) {
			var head = editor.getDoc().getElementsByTagName("head")[0];
			var element = editor.getDoc().createElement("link");
			element.setAttribute("rel", "stylesheet");
			element.setAttribute("type", "text/css");
			element.setAttribute("href", path + "/theme/nanospell.css");
			element.setAttribute("id", 'nanospell_theme');
			head.insertBefore(element, head.firstChild);
		}
	}
	var __memtok = null;
	var __memtoks = null;

	function wordTokenizer(singleton) {
		if (!singleton && !!__memtok) {
			return __memtok
		};
		if (singleton && !!__memtoks) {
			return __memtoks
		};
		var email = "\\b[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,4}\\b"
		var protocol = "\\bhttp[s]?://[a-z0-9#\\._/]{5,}\\b"
		var domain = "\\bwww\.[a-z0-9#\._/]{8,128}[a-z0-9/]\\b"
		var invalidchar = "\\s!\"#$%&()*+,-.â€¦/:;<=>?@[\\]^_{|}`\u200b\u00a7\u00a9\u00ab\u00ae\u00b1\u00b6\u00b7\u00b8\u00bb\u00bc\u00bd\u00be\u00bf\u00d7\u00f7\u00a4\u201d\u201c\u201e\u201f" + String.fromCharCode(160)
		var validword = "[^" + invalidchar + "'\u2018\u2019][^" + invalidchar + "]+[^" + invalidchar + "'\u2018\u2019]";
		var result = new RegExp("(" + email + ")|(" + protocol + ")|(" + domain + ")|(&#\d+;)|(" + validword + ")", singleton ? "" : "g");
		


		if (singleton) {
			__memtoks = result
		} else {
			__memtok = result
		}
		return result;
	}

	function getWords(corpus, max) {
		var fullTextContext = "";
		var allTextNodes = FindTextNodes(corpus)
		for (var i = 0; i < allTextNodes.length; i++) {
			fullTextContext += allTextNodes[i].data
			if (allTextNodes[i].parentNode && allTextNodes[i].parentNode.className && allTextNodes[i].parentNode.className == ("nanospell-typo")) {
				fullTextContext += "";
			} else {
				fullTextContext += " ";
			}
		}
		var matches = fullTextContext.match(wordTokenizer())
		var uniqueWords = [];
		var words = [];
		if (!matches) {
			return words;
		}
		for (var i = 0; i < matches.length; i++) {
			var word = cleanQuotes(matches[i]);
			if (!uniqueWords[word] && validWordToken(word) && (typeof(spellcache[word]) === 'undefined')) {
				words.push(word);
				uniqueWords[word] = true;
				if (words.length >= max) {
					return words;
				}
			}
		}
		return words;
	}

	function isCDATA(elem) {
		var n = elem.nodeName.toLowerCase();
		if (n == "script") {
			return true;
		}
		if (n == "style") {
			return true;
		}
		if (n == "textarea") {
			return true;
		}
		return false;
	}

	function FindTextNodes(elem) {
		// recursive but asynchronous so it can not choke
		var textNodes = [];
		FindTextNodes_r(elem)

		function FindTextNodes_r(elem) {
			for (var i = 0; i < elem.childNodes.length; i++) {
				var child = elem.childNodes[i];
				if (child.nodeType == 3) {
					textNodes.push(child)
				} else if (!isCDATA(child) && child.childNodes) {
					FindTextNodes_r(child);
				}
			}
		}
		return textNodes;
	}

	function cleanQuotes(word) {
		return word.replace(/[\u2018\u2019]/g, "'");
	}
	var spellcache = [];
	var suggestionscache = [];
	var ignorecache = [];

	function validWordToken(word) {
		if (!word) {
			return false;
		}
		if (/\s/.test(word)) {
			return false;
		}
		if (/[\:\.\@\/\\]/.test(word)) {
			return false;
		}
		if (/^\d+$/.test(word) || word.length == 1) {
			return false;
		}
		var ingnoreAllCaps = (settings.ignore_block_caps === true);
		var ignoreNumeric = (settings.ignore_non_alpha !== false);
		if (ingnoreAllCaps && word.toUpperCase() == word) {
			return false;
		}
		if (ignoreNumeric && /\d/.test(word)) {
			return false;
		}
		if (ignorecache[word.toLowerCase()]) {
			return false;
		}
		if (hasPersonal(word)) {
			return false
		}
		return true;
	}

	function addPersonal(word) {
		var value = localStorage.getItem('nano_spellchecker_personal');
		if (value !== null && value !== "") {
			value += String.fromCharCode(127);
		} else {
			value = "";
		}
		value += word.toLowerCase();
		localStorage.setItem('nano_spellchecker_personal', value);
	}

	function hasPersonal(word) {
			var value = localStorage.getItem('nano_spellchecker_personal');
			if (value === null || value == "") {
				return false;
			}
			var records = value.split(String.fromCharCode(127));
			word = word.toLowerCase();
			for (var i = 0; i < records.length; i++) {
				if (records[i] === word) {
					return true;
				}
			}
			return false;
		}
		//#  SECTION CURSOR  #//
	function setCaretIE(pos) {
		if (editor.getWin().getSelection || pos.x === 0 || pos.y === 0 /*thanks Nathan*/ ) {
			return null;
		}
		var doc = editor.getDoc();
		var clickx, clicky
		clickx = pos.x;
		clicky = pos.y;
		var cursorPos = doc.body.createTextRange();
		cursorPos.moveToPoint(clickx, clicky)
		cursorPos.select();
	}

	function getCaretIE() {
		if (editor.getWin().getSelection) {
			return null;
		}
		var doc = editor.getDoc();
		var clickx, clicky
		var cursorPos = doc.selection.createRange().duplicate();
		clickx = cursorPos.boundingLeft;
		clicky = cursorPos.boundingTop;
		var pos = {
			x: clickx,
			y: clicky
		};
		return pos;
	}

	function getCaret() {
		if (!editor.getWin().getSelection) {
			return null
		}
		if (!editorHasFocus) {
			return;
		}
		var allTextNodes = FindTextNodes(editor.getBody())
		var caretpos = null
		var caretnode = null
		for (var i = 0; i < allTextNodes.length; i++) {
			if (allTextNodes[i].data.indexOf(caret_marker) > -1) {
				caretnode = allTextNodes[i]
				caretpos = allTextNodes[i].data.indexOf(caret_marker);
				allTextNodes[i].data = allTextNodes[i].data.replace(caret_marker, "")
				return {
					node: caretnode,
					offset: caretpos
				}
			}
		}
	}

	function setCaret(bookmark) {

		if (!editor.getWin().getSelection) {
			return null
		}
		if (!editorHasFocus) {
			return;
		}
		if (!bookmark) {
			return;
		}
		var nodeIndex = null;
		var allTextNodes = FindTextNodes(editor.getBody())
		var caretnode = bookmark.node
		var caretpos = bookmark.offset
		for (var i = 0; i < allTextNodes.length; i++) {
			if (allTextNodes[i] == caretnode) {
				var nodeIndex = i;
			}
		}
		if (nodeIndex === null) {
			return;
		}
		for (var i = nodeIndex; i < allTextNodes.length - 1; i++) {
			if (caretpos <= allTextNodes[i].data.length) {
				break;
			}
			caretpos -= allTextNodes[i].data.length
			caretnode = allTextNodes[i + 1]
		}
		var textNode = caretnode
		var sel = editor.getWin().getSelection();
		if (sel.getRangeAt && sel.rangeCount) {
			var range = sel.getRangeAt(0);
			range.collapse(true);
			range.setStart(textNode, caretpos);
			range.setEnd(textNode, caretpos);
			sel.removeAllRanges();
			sel.addRange(range);
		}
	}
	var caret_marker = String.fromCharCode(8) + String.fromCharCode(127) + String.fromCharCode(1);

	function putCursor() {
			if (!window.getSelection) {
				return null /*IE <=8*/
			}
			if (!editorHasFocus) {
				return;
			}
			var sel = editor.getWin().getSelection();
			var range = sel.getRangeAt(0);
			range.deleteContents();
			range.insertNode(editor.getDoc().createTextNode(caret_marker));
		}
		//# SECTION MARKUP #//
	function MarkAllTypos(body) {
		var allTextNodes = FindTextNodes(body)
		for (var i = 0; i < allTextNodes.length; i++) {
			MarkTypos(allTextNodes[i]);
		}
	}

	function getSuggestions(word) {
		word = cleanQuotes(word)
		if (suggestionscache[word] && suggestionscache[word][0]) {
			if (suggestionscache[word][0].indexOf("*") == 0) {
				return Array("nanospell\xA0plugin\xA0developer\xA0trial ", "ckeditor-spellcheck.nanospell.com/license\xA0")
			}
		}
		return suggestionscache[word];
	}

	function MarkTypos(textNode) {
		var regex = wordTokenizer();
		"".match(regex); /*the magic reset button*/
		var currentNode = textNode
		var match
		var caretpos = -1
		var newNodes = [textNode];
		while ((match = regex.exec(currentNode.data)) != null) {

			var matchtext = match[0].replace("&#8203;",'');;
			if (!validWordToken(matchtext)) {
				continue;
			}
			if (typeof(suggestionscache[cleanQuotes(matchtext)]) !== 'object') {
				continue;
			}
			var pos = match.index
			var matchlength = matchtext.length
			var matchlength = matchtext.length
			var newNode = currentNode.splitText(pos)
			var span = editor.getDoc().createElement('span');
			span.className = "nanospell-typo"
			span.setAttribute('data-cke-bogus', true)
			var middle = editor.getDoc().createTextNode(matchtext);
			span.appendChild(middle);
			currentNode.parentNode.insertBefore(span, newNode);
			newNode.data = newNode.data.substr(matchlength)
			currentNode = newNode;
			newNodes.push(middle)
			newNodes.push(newNode)
			"".match(regex); /*the magic reset button*/
		}
	}

	function selectionCollapsed() {
		if (!editor.getSelection()) {

			return true;
		}

		return editor.getSelection().getSelectedText().length == 0;

	}
	var spell_ticker = null;

	function triggerSpelling(immediate) {
		//only recheck when the user pauses typing

		if (selectionCollapsed()) {
		    clearTimeout(spell_ticker);
			spell_ticker = setTimeout(checkNow, immediate ? 50 : spell_delay);
		}
	}

	function ignoreWord(target, word, all) {
		if (all) {
			ignorecache[word.toLowerCase()] == true;
			for (var i in suggestionscache) {
				if (i.toLowerCase() == word.toLowerCase()) {
					delete suggestionscache[i];
				}
			}
			var allInstances = editor.document.find('span.nanospell-typo').$
			for (var i = 0; i < allInstances.length; i++) {
				var item = allInstances[i];
				var text = item.innerText || item.textContent;
				if (text == word) {
					unwrap(item);
				}
			}
		} else {
			unwrap(target);
		}
	}

	function unwrap(node) {
		var text = node.innerText || node.textContent;
		if (isIE()) {
			text = text.replace(/  /g, " " + String.fromCharCode(160));
		}
		var content = editor.getDoc().createTextNode(text);
		node.parentNode.insertBefore(content, node);
		node.parentNode.removeChild(node);
	}


			if(!editor.nanospell){
				editor.nanospell = {
					getLanguage: function(){
						return lang;
					},
					setLanguage: function(langcode){
						lang = langcode;
						checkNow();
					}
				};



			}



		}
		
		
	});
	
})();