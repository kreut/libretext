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

'use strict';
var nanospell = {
	ckeditor: function(selector, settings) {
		if (typeof(window.CKEDITOR) == undefined) {
			setTimeout(function() {
				window.nanospell.ckeditor(selector, settings), 300
			})
		}
		if (!selector) {
			selector = 'all'
		};
		if (!settings) {
			settings = {}
		};
		nanospell.wysiwyg.cke.inject_plugin(selector, settings);
	},
	spell_ajax_folder_path: null,
	base_path: function() {
		if (typeof(this.spell_ajax_folder_path) !== 'undefined' && this.spell_ajax_folder_path !== null) {
			return this.spell_ajax_folder_path;
		}
		this.spell_ajax_folder_path = ''
		var scriptname = 'autoload.js';
		var scripts = document.getElementsByTagName('script');
		for (var i = scripts.length - 1; i >= 0; i--) {
			var script = scripts[i];
			if (script.src) {
				var src = script.src;
				src = src.split("?")[0];
				src = src.split("#")[0];
				if (src.lastIndexOf(scriptname) == src.length - scriptname.length) {
					this.spell_ajax_folder_path = src.substring(0, src.lastIndexOf(scriptname))
				}
			}
		}
		return this.spell_ajax_folder_path;
	}
};
nanospell.wysiwyg = {};
nanospell.wysiwyg.cke = {
	list: {},
	inject_plugin: function(selector, settings) {
		var plugin_name = 'nanospell'
		var plugin_url = nanospell.base_path() + "nanospell.ckeditor/plugin.js"
		if (typeof(CKEDITOR) == 'undefined') {
			return;
		}
		if (CKEDITOR.version < "4") {
			console.log("nanospell can not work with old CKEditor instances with versions less than 4.  Your version is " + CKEDITOR.version + " !")
			return;
		}
		for (var i in CKEDITOR.instances) {
			if (selector.toLowerCase() === 'all' || CKEDITOR.instances[i].element.$.id === selector) {
				nanospell.wysiwyg.cke.list[i] = true;
			}
		}
		CKEDITOR.plugins.addExternal(plugin_name, plugin_url, '');
		CKEDITOR.plugins.load(plugin_name, function(plugins) {
			for (var i in nanospell.wysiwyg.cke.list) {
				var editor = CKEDITOR.instances[i];
				editor.config.nanospell = settings;
				editor.config.removePlugins += ',wsc,scayt';
				plugins[plugin_name].init(editor)
			 
			}
		});
	}
}