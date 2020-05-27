/*jslint browser: true, onevar: true, undef: true, nomen: false, eqeqeq: true, bitwise: true, regexp: true, newcap: true, immed: true */
/*global jQuery: false, window: false */

/*


A jQuery edit in place plugin

Fork of version 2.3.0

Authors:
	Dave Hauenstein
	Martin Häcker <spamfaenger [at] gmx [dot] de>

Project home:
	http://code.google.com/p/jquery-in-place-editor/

Patches with tests welcomed! For guidance see the tests  </spec/unit/>. To submit, attach them to the bug tracker.

License:
This source file is subject to the BSD license bundled with this package.
Available online: {@link http://www.opensource.org/licenses/bsd-license.php}
If you did not receive a copy of the license, and are unable to obtain it, 
learn to use a search engine.

*/

(function ($){

// Private helpers .......................................................

function assertMandatorySettingsArePresent(options) {
	// one of these needs to be non falsy
	if (options.url || options.callback) {
		return;
	}
	
	throw new Error("Need to set either url: or callback: option for the inline editor to work.");
}

/* preload the loading icon if it is configured */
function preloadImage(anImageURL) {
	if ('' === anImageURL) {
		return;
	}
	
	var loading_image = new Image();
	loading_image.src = anImageURL;
}

function trim(aString) {
	return aString
		.replace(/^\s+/, '')
		.replace(/\s+$/, '');
}

function hasContent(something) {
	if (undefined === something || null === something) {
		return false;
	}
	
	if (0 === something.length) {
		return false;
	}
	
	return true;
}


function InlineEditor(settings, dom) {
	this.settings = settings;
	this.dom = dom;
	this.originalValue = null;
	this.didInsertDefaultText = false;
	this.shouldDelayReinit = false;
	this.openingEditor = false;	
	this.processing = false;
}

InlineEditor.prototype = {
	
	init: function () {
		this.setDefaultTextIfNeccessary();
		this.connectOpeningEvents();
	},
	
	reinit: function () {
		if (this.shouldDelayReinit) {
			return;
		}
		
		this.triggerDelegateCall('didCloseEditInPlace');
		
		this.markEditorAsInactive();
		this.disconnectClosingEventsFromEditor();
		this.connectOpeningEvents();
		this.setDefaultTextIfNeccessary();
	},
	
	setDefaultTextIfNeccessary: function () {
		if ('' !== this.dom.html()) {
			return;
		}
		
		this.dom.html(this.settings.default_text);
		this.didInsertDefaultText = true;
	},
	
	getActivator: function () {
		if (null !== this.settings.activator) {
			var activator;
		
			if ('parent' === this.settings.activatorType) {
				activator = this.dom.closest(this.settings.activator);
			} else if ('sibling' === this.settings.activatorType) {
				activator = this.dom.siblings(this.settings.activator);			
			} else {
				activator = $(this.settings.activator);
			}
			
			if (activator.length > 0) {
				return activator;
			}
		}
		return false;
	},
	
	connectOpeningEvents: function () {
		var that = this, activator = this.getActivator();
		
		if (this.settings.auto_effects) {
			this.dom
				.bind('mouseenter.editInPlace', function (){ that.addHoverEffect(); })
				.bind('mouseleave.editInPlace', function (){ that.removeHoverEffect(); });
		}
			
		if (this.settings.activateOnEditorClick) {
			this.dom.bind('click.editInPlace', function (anEvent){ that.openEditor(anEvent); });
		}
		
						
		if (activator) {
			activator.bind('click.editInPlaceActivator', function (anEvent){ that.openEditor(anEvent); });
		}
	},
	
	disconnectOpeningEvents: function () {
		// prevent re-opening the editor when it is already open
		if (this.settings.activateOnEditorClick || this.settings.auto_effects) {
			this.dom.unbind('.editInPlace');
		}
		
		var activator = this.getActivator();
		if (activator) {
			activator.unbind('.editInPlaceActivator');
		}
	},
	
	addHoverEffect: function () {
		if (!this.settings.auto_effects) {
			return;
		}
		
		if (this.settings.hover_class) {
			this.dom.addClass(this.settings.hover_class);
		} else {
			this.dom.css("background-color", this.settings.bg_over);
		}
	},
	
	removeHoverEffect: function () {
		if (!this.settings.auto_effects) {
			return;
		}
		
		if (this.settings.hover_class) {
			this.dom.removeClass(this.settings.hover_class);
		} else {
			this.dom.css("background-color", this.settings.bg_out);
		}
	},
	
	openEditor: function (anEvent, value) {	
		if ( ! this.shouldOpenEditor(anEvent)) {
			return;
		}
		
		this.triggerDelegateCall('willStartEditing');		
		
		this.openingEditor = true;
		
		this.disconnectOpeningEvents();
		this.removeHoverEffect();
		this.removeInsertedDefaultTextIfNeccessary();

		if (typeof value === 'undefined') {
			this.saveOriginalValue();
		} 
		
		this.markEditorAsActive();
		this.replaceContentWithEditor();
		this.setInitialValue(value);
		this.workAroundMissingBlurBug();
		this.connectClosingEventsToEditor();
		this.triggerDelegateCall('didOpenEditInPlace');
		
		var that = this;		
		setTimeout(function () {
			that.openingEditor = false;		
		}, 50);
	},
	
	shouldOpenEditor: function (anEvent) {
		if (anEvent === true) {
			return true;
		}
		
		if (this.isClickedObjectCancelled(anEvent.target)) {
			return false;
		}
				
		if (false === this.triggerDelegateCall('shouldOpenEditInPlace', true, anEvent)) {
			return false;
		}
		
		return true;
	},
	
	removeInsertedDefaultTextIfNeccessary: function () {
		if (!this.didInsertDefaultText
	      || this.dom.html() !== this.settings.default_text
		) {
			return;
		}
		
		this.dom.html('');
		this.didInsertDefaultText = false;
	},
	
	isClickedObjectCancelled: function (eventTarget) {
		if ( ! this.settings.cancel) {
			return false;
		}
		
		var eventTargetAndParents = $(eventTarget).parents().andSelf(),
				elementsMatchingCancelSelector = eventTargetAndParents.filter(this.settings.cancel);
				
		return 0 !== elementsMatchingCancelSelector.length;
	},
	
	saveOriginalValue: function () {
		if (this.settings.use_html) {
			this.originalValue = this.dom.html();
		} else {
			this.originalValue = trim(this.dom.text());
		}
	},
	
	restoreOriginalValue: function () {
		this.setClosedEditorContent(this.originalValue);
	},
	
	setClosedEditorContent: function (aValue) {
		if (this.settings.use_html) {
			this.dom.html(aValue);
		} else {
			this.dom.text(aValue);
		}
	},
	
	workAroundMissingBlurBug: function () {
		// Strangely, all browser will forget to send a blur event to an input element
		// when another one is created and selected programmatically. (at least under some circumstances). 
		// This means that if another inline editor is opened, existing inline editors will _not_ close 
		// if they are configured to submit when blurred.
		
		// Using parents() instead document as base to workaround the fact that in the unittests
		// the editor is not a child of window.document but of a document fragment
		var ourInput = this.dom.find(':input');
		this.dom.parents(':last').find('.editInPlace-active :input').not(ourInput).blur();
	},
	
	replaceContentWithEditor: function () {
		var buttons_html  = (this.settings.show_buttons) ? this.settings.save_button + ' ' + this.settings.cancel_button : '',
		    editorElement = this.createEditorElement(), // needs to happen before anything is replaced
		    extra_html    = (this.settings.extraHtml) ? this.settings.extraHtml : '';
		/* insert the new in place form after the element they click, then empty out the original element */
		this.dom.html('<form class="inplace_form" style="display: inline; margin: 0; padding: 0;"></form>')
			.find('form')
				.append(editorElement)
				.append(buttons_html)
				.append(extra_html);
	},
	
	createEditorElement: function () {
		var editor;
		
		if ("select" === this.settings.field_type) {
			editor = this.createSelectEditor();
		} else if ("text" === this.settings.field_type) {
			editor = $('<input type="text" ' + this.inputNameAndClass()  
				+ (this.settings.text_size !== null ? ' size="' + this.settings.text_size + '"' : '')
				+ ' />');
		} else if ("textarea" === this.settings.field_type) {
			editor = $('<textarea ' + this.inputNameAndClass() 
				+ ' rows="' + this.settings.textarea_rows + '" '
				+ ' cols="' + this.settings.textarea_cols + '" />');
		} else {
			throw "Unknown field_type <" + this.settings.field_type + ">, supported are 'text', 'textarea' and 'select'";
		}
		
		return editor;
	},
	
	setInitialValue: function (value) {
		var initialValue,
		    editor = this.dom.find(':input');

		if (typeof value !== 'undefined') {
			initialValue = value;
		} else {
			initialValue = this.triggerDelegateCall('willOpenEditInPlace', this.originalValue);
		}
	
		editor.val(initialValue);
		
		// Workaround for select fields which don't contain the original value.
		// Somehow the browsers don't like to select the instructional choice (disabled) in that case
		if (editor.val() !== initialValue) {
			editor.val(''); // selects instructional choice
		}
	},
	
	inputNameAndClass: function () {
		return ' name="inplace_value" class="inplace_field" ';
	},
	
	createSelectEditor: function () {
		var i, currentTextAndValue, value, text, option,
		
		editor = $('<select' + this.inputNameAndClass() + '>'
			+	'<option disabled="true" value="">' + this.settings.select_text + '</option>'
			+ '</select>'),
		    
		optionsArray;
		
		if (typeof this.settings.select_options === 'object' && !$.isArray(optionsArray)) {
			optionsArray = [];
			for (i in this.settings.select_options) {
				if (this.settings.select_options.hasOwnProperty(i)) {
					optionsArray.push([this.settings.select_options[i], i]);				
				}
			}
		} else {
			optionsArray = this.settings.select_options;
			if (!$.isArray(optionsArray)) {
				optionsArray = optionsArray.split(',');
			}
		}		    
		
		for (i = 0; i < optionsArray.length; i++) {
			currentTextAndValue = optionsArray[i];
			if ( ! $.isArray(currentTextAndValue)) {
				currentTextAndValue = currentTextAndValue.split(':');
			}
			
			if (currentTextAndValue.length > 1) {
				value = trim(currentTextAndValue[1]);
			} else {
				value = trim(currentTextAndValue[0]);
			}
			
			text = trim(currentTextAndValue[0]);
			
			option = $('<option>').attr('value', value).text(text);
			editor.append(option);
		}
		
		return editor;
	},
	
	connectClosingEventsToEditor: function () {
		var that = this,
		cancelEditorAction = function (anEvent) {
			that.handleCancelEditor(anEvent);
			return false; // stop event bubbling
		},
		saveEditorAction = function (anEvent) {
			that.handleSaveEditor(anEvent);
			return false; // stop event bubbling
		},
		form = this.dom.find("form");
		
		form.find(".inplace_field").focus().select();
		form.find(".inplace_cancel").click(cancelEditorAction);
		form.find(".inplace_save").click(saveEditorAction);
		
		if ( ! this.settings.show_buttons) {
				// TODO: Firefox has a bug where blur is not reliably called when focus is lost 
				//       (for example by another editor appearing)
			if ("save" === this.settings.on_blur) {
				form.find(".inplace_field").blur(saveEditorAction);
			} else {
				form.find(".inplace_field").blur(cancelEditorAction);
			}
			
			// workaround for msie & firefox bug where it won't submit on enter if no button is shown
			if ($.browser.mozilla || $.browser.msie) {
				this.bindSubmitOnEnterInInput();
			}
		} else if ('cancel' === this.settings.on_outside_click) {
			$(document).bind('click.editInPlaceDeactivator-' + this.dom.attr("id"), function (e) {
				if (that.openingEditor) {
					return;
				}
				var target = jQuery(e.target), activator = that.getActivator();
					
				if (activator && (target.is(activator) || target.is(activator.children()))) {
					return;
				}
				
				if (target.is(that.dom) || target.is(that.dom.children())) {
					return;
				}
				
				if (target.is(form) ||  form.find(target).length > 0) {
					return;
				}				
				
				cancelEditorAction();
			});
		}
		
		form.keyup(function (anEvent) {
			// allow canceling with escape
			var escape = 27;
			if (escape === anEvent.which) {
				return cancelEditorAction();
			}
		});
		
		// workaround for webkit nightlies where they won't submit at all on enter
		// REFACT: find a way to just target the nightlies
		if ($.browser.safari) {
			this.bindSubmitOnEnterInInput();
		}
		
		
		form.submit(saveEditorAction);
	},
	
	disconnectClosingEventsFromEditor: function ()
	{
		if ('cancel' === this.settings.on_outside_click) {
			$(document).unbind('click.editInPlaceDeactivator-' + this.dom.attr("id"));
		}	
	},
	
	bindSubmitOnEnterInInput: function () {
		if ('textarea' === this.settings.field_type) {
			return; // can't enter newlines otherwise
		}
		
		var that = this;
		this.dom.find(':input').keyup(function (event) {
			var enter = 13;
			if (enter === event.which) {
				return that.dom.find('form').submit();
			}
		});
	},
	
	handleCancelEditor: function (anEvent) {
		if (this.processing) {
			return;
		}

		if (false === this.triggerDelegateCall('shouldCancelEditInPlace', true, anEvent)) {
			return;
		}	
		

		this.originalValue = this.triggerDelegateCall('willCancelEditInPlace', this.originalValue);

		
		this.triggerDelegateCall('willStopEditing');		
		this.restoreOriginalValue();
		this.reinit();
	},
	
	handleSaveEditor: function (anEvent) {
		if (false === this.triggerDelegateCall('shouldSaveEditInPlace', true, anEvent)) {
			return;
		}
		
		var enteredText = this.dom.find(':input').val();
		enteredText = this.triggerDelegateCall('willSaveEditInPlace', enteredText);
		
		if (this.isDisabledDefaultSelectChoice()
			  || this.isUnchangedInput(enteredText)
		) {
			this.handleCancelEditor(anEvent, false);
			return;
		}
		
		if (this.didForgetRequiredText(enteredText)) {
			this.handleCancelEditor(anEvent, false);
			this.reportError("Error: You must enter a value to save this field");
			return;
		}
		
		this.showSaving(enteredText);
		
		if (this.settings.callback) {
			this.handleSubmitToCallback(enteredText);
		} else {
			this.handleSubmitToServer(enteredText);
		}
	},
	
	didForgetRequiredText: function (enteredText) {
		return this.settings.value_required 
			&& ("" === enteredText 
				|| undefined === enteredText
				|| null === enteredText);
	},
	
	isDisabledDefaultSelectChoice: function () {
		return this.dom.find('option').eq(0).is(':selected:disabled');
	},
	
	isUnchangedInput: function (enteredText) {
		return ! this.settings.save_if_nothing_changed
			&& this.originalValue === enteredText;
	},
	
	showSaving: function (enteredText) {
		if (this.settings.callback && this.settings.callback_skip_dom_reset) {
			return;
		}
		
		var savingMessage = enteredText;
		
		if (hasContent(this.settings.saving_text)) {
			savingMessage = this.settings.saving_text;
		}
		
		if (hasContent(this.settings.saving_image)) {
			savingMessage = $('<img />').attr('src', this.settings.saving_image).attr('alt', savingMessage);
		}
		
		this.dom.html(savingMessage);
	},
	
	handleSubmitToCallback: function (enteredText) {
		if (this.processing) {
			return;
		}
		
		// REFACT: consider to encode enteredText and originalHTML before giving it to the callback
		this.enableOrDisableAnimationCallbacks(true, false);
		this.processing = true;
		var that = this, newHTML = this.triggerCallback(
			this.settings.callback, 
			enteredText, 
			this.originalValue, 
			this.settings.params, 
			{
				didStartSaving: function () { that.didStartSaving(); },
				didEndSaving:   function () { that.didEndSaving(); }
			}
		);
		
		this.reinit();
		
		if (!this.settings.callback_skip_dom_reset && undefined === newHTML) {
			// failure; put original back
			this.reportError("Error: Failed to save value: " + enteredText);
			this.restoreOriginalValue();
		} else if (!this.settings.callback_skip_dom_reset) {
			// REFACT: use setClosedEditorContent
			this.dom.html(newHTML);
		}
		
		this.triggerDelegateCall('willStopEditing');
		
		if (this.didCallNoCallbacks()) {
			this.enableOrDisableAnimationCallbacks(false, false);
			this.reinit();
		}
		this.processing = false;
	},
	
	handleSubmitToServer: function (enteredText) {
		if (this.processing) {
			return;
		}
		
		this.processing = true;
		
		var that = this, customParams = '', data, paramName,
		
		reopenEditorCallback = function () {
			that.didEndSaving();
			that.reinit();
			that.openEditor(true, enteredText);
		},
		cancelEditorCallback = function () {
			that.didEndSaving();
			that.handleCancelEditor(true);
		},
		finishCallback = function () {
			that.didEndSaving();
			that.triggerDelegateCall('willStopEditing');
			that.reinit();
		};

		if ($.isFunction(this.settings.params)) {
			data = this.settings.params(this.dom.attr("id"), enteredText, this.originalValue);
		} else {
			if ($.isPlainObject(this.settings.params))	 {
				for (paramName in this.settings.params) {
					if (this.settings.params.hasOwnProperty(paramName)) {
						customParams += '&' + paramName + '=' + encodeURIComponent(this.settings.params[paramName]); 
					}			
				}
			} else if (typeof this.settings.params === 'string') {
				customParams = '&' + this.settings.params;
			}
			
			data = this.settings.update_value     + '=' + encodeURIComponent(enteredText) 
				   + '&' + this.settings.element_id + '=' + encodeURIComponent(this.dom.attr("id"))
				   + customParams;
				   
			if (this.settings.send_original_values) {
				data += '&' + this.settings.original_value + '=' + encodeURIComponent(this.originalValue);
			}		
		}
		
		this.enableOrDisableAnimationCallbacks(true, false);
		this.didStartSaving();

		$.ajax({
			url: that.settings.url,
			type: "POST",
			data: data,
			dataType: that.settings.dataType,
			success: function (result) {			
				if (typeof that.settings.success === 'function' && 
				    that.triggerCallback(that.settings.success, result, reopenEditorCallback, cancelEditorCallback, finishCallback) !== false 
				) {
					if (that.settings.dataType === 'html') {
						that.dom.html(result || that.settings.default_text);
					}
					that.didEndSaving();
					that.triggerDelegateCall('willStopEditing');
					that.reinit();
				}
				that.processing = false;
			},
			error: function (request) {
				if (typeof that.settings.error === 'function' && 
						that.triggerCallback(that.settings.error, request, reopenEditorCallback, cancelEditorCallback) !== false
				) {
					that.didEndSaving();
					that.restoreOriginalValue();
					that.reportError("Failed to save value: " + request.responseText || 'Unspecified Error');
					that.triggerDelegateCall('willStopEditing');
					that.reinit();
				}
				that.processing = false;
			}
		});
	},
	
	// Utilities .........................................................
	
	triggerCallback: function (aCallback /*, arguments */) {
		if (typeof aCallback !== 'function') {
			return; // callback wasn't specified after all
		}
		
		var callbackArguments = Array.prototype.slice.call(arguments, 1);
		return aCallback.apply(this.dom[0], callbackArguments);
	},
	
	/// defaultReturnValue is only used if the delegate returns undefined
	triggerDelegateCall: function (aDelegateMethodName, defaultReturnValue, optionalEvent) {
		// REFACT: consider to trigger equivalent callbacks automatically via a mapping table?
		if ( ! this.settings.delegate
			|| ! $.isFunction (this.settings.delegate[aDelegateMethodName]))
			return defaultReturnValue;
		
		var delegateReturnValue =  this.settings.delegate[aDelegateMethodName](this.dom, this.settings, optionalEvent);
		return (undefined === delegateReturnValue)
			? defaultReturnValue
			: delegateReturnValue;
	},
	
	reportError: function (anErrorString) {
		this.triggerCallback(this.settings.error_sink, anErrorString);
	},
	
	// REFACT: this method should go, callbacks should get the dom node itself as an argument
	id: function () {
		return this.dom.attr('id');
	},
	
	markEditorAsActive: function () {
		this.dom.addClass('editInPlace-active');
	},
	
	markEditorAsInactive: function () {
		this.dom.removeClass('editInPlace-active');
	},
	
	enableOrDisableAnimationCallbacks: function (shouldEnableStart, shouldEnableEnd) {
		this.didStartSavingEnabled = shouldEnableStart;
		this.didEndSavingEnabled = shouldEnableEnd;
	},
	
	didCallNoCallbacks: function () {
		return this.didStartSavingEnabled && ! this.didEndSavingEnabled;
	},
	
	assertCanCall: function (methodName) {
		if ( ! this[methodName + 'Enabled']) {
			throw new Error('Cannot call ' + methodName + ' now. See documentation for details.');
		}
	},
	
	didStartSaving: function () {
		this.assertCanCall('didStartSaving');
		this.shouldDelayReinit = true;
		this.enableOrDisableAnimationCallbacks(false, true);
		
		this.startSavingAnimation();
	},
	
	didEndSaving: function () {
		this.assertCanCall('didEndSaving');
		this.shouldDelayReinit = false;
		this.enableOrDisableAnimationCallbacks(false, false);
		
		this.stopSavingAnimation();
	},
	
	startSavingAnimation: function () {
		if (this.settings.auto_effects) {
			var that = this;
			this.dom
				.animate({ backgroundColor: this.settings.saving_animation_color }, 400)
				.animate({ backgroundColor: 'transparent'}, 400, 'swing', function (){
					// In the tests animations are turned off - i.e they happen instantaneously.
					// Hence we need to prevent this from becomming an unbounded recursion.
					setTimeout(function (){ that.startSavingAnimation(); }, 10);
				});
		}
	},
	
	stopSavingAnimation: function () {
		if (this.settings.auto_effects) {
			this.dom
				.stop(true)
				.css({backgroundColor: ''});
		}
	},
	
	missingCommaErrorPreventer:''
};

$.fn.editInPlace = function (options) {
	
	var settings = $.extend({}, $.fn.editInPlace.defaults, options);
	assertMandatorySettingsArePresent(settings);
	preloadImage(settings.saving_image);
	
	return this.each(function () {
		var dom = $(this);
		// This won't work with live queries as there is no specific element to attach this
		// one way to deal with this could be to store a reference to self and then compare that in click?
		if (dom.data('editInPlace')) {
			return; // already an editor here
		}
		dom.data('editInPlace', true);
		
		new InlineEditor(settings, dom).init();
	});
};


/// Switch these through the dictionary argument to $(aSelector).editInPlace(overideOptions)
/// Required Options: Either url or callback, so the editor knows what to do with the edited values.
$.fn.editInPlace.defaults = {
	url:				  "", // string: POST URL to send edited content
	activator:     null,
	activatorType: 'absolute',
	activateOnEditorClick: true,
	on_outside_click: null,
	dataType:     "html",
	bg_over:			"#ffc", // string: background color of hover of unactivated editor
	bg_out:				"transparent", // string: background color on restore from hover
	auto_effects:   true,
	hover_class:		"",  // string: class added to root element during hover. Will override bg_over and bg_out
	show_buttons:		false, // boolean: will show the buttons: cancel or save; will automatically cancel out the onBlur functionality
	extraHtml:      false,
	save_button:		'<button class="inplace_save">Save</button>', // string: image button tag to use as Save button
	cancel_button:		'<button class="inplace_cancel">Cancel</button>', // string: image button tag to use as Cancel button
	params:					"", // string: example: first_name=dave&last_name=hauenstein extra paramters sent via the post request to the server
	field_type:			"text", // string: "text", "textarea", or "select";  The type of form field that will appear on instantiation
	default_text:		"(Click here to add text)", // string: text to show up if the element that has this functionality is empty
	use_html:			false, // boolean, set to true if the editor should use jQuery.fn.html() to extract the value to show from the dom node
	textarea_rows:		10, // integer: set rows attribute of textarea, if field_type is set to textarea. Use CSS if possible though
	textarea_cols:		25, // integer: set cols attribute of textarea, if field_type is set to textarea. Use CSS if possible though
	select_text:		"Choose new value", // string: default text to show up in select box
	select_options:		"", // string or array: Used if field_type is set to 'select'. Can be comma delimited list of options 'textandValue,text:value', Array of options ['textAndValue', 'text:value'] or array of arrays ['textAndValue', ['text', 'value']]. The last form is especially usefull if your labels or values contain colons)
	text_size:			null, // integer: set cols attribute of text input, if field_type is set to text. Use CSS if possible though
	
	// Specifying callback_skip_dom_reset will disable all saving_* options
	saving_text:		undefined, // string: text to be used when server is saving information. Example "Saving..."
	saving_image:		"", // string: uses saving text specify an image location instead of text while server is saving
	saving_animation_color: 'transparent', // hex color string, will be the color the pulsing animation during the save pulses to. Note: Only works if jquery-ui is loaded
	
	value_required:		false, // boolean: if set to true, the element will not be saved unless a value is entered
	element_id:			"element_id", // string: name of parameter holding the id or the editable
	update_value:		"update_value", // string: name of parameter holding the updated/edited value
	send_original_values: true, 
	original_value:		'original_value', // string: name of parameter holding the updated/edited value
	save_if_nothing_changed:	false,  // boolean: submit to function or server even if the user did not change anything
	on_blur:			"save", // string: "save" or null; what to do on blur; will be overridden if show_buttons is true
	cancel:				"", // string: if not empty, a jquery selector for elements that will not cause the editor to open even though they are clicked. E.g. if you have extra buttons inside editable fields
	
	// All callbacks will have this set to the DOM node of the editor that triggered the callback
	
	callback:			null, // function: function to be called when editing is complete; cancels ajax submission to the url param. Prototype: function (idOfEditor, enteredText, orinalHTMLContent, settingsParams, callbacks). The function needs to return the value that should be shown in the dom. Returning undefined means cancel and will restore the dom and trigger an error. callbacks is a dictionary with two functions didStartSaving and didEndSaving() that you can use to tell the inline editor that it should start and stop any saving animations it has configured.
	callback_skip_dom_reset: false, // boolean: set this to true if the callback should handle replacing the editor with the new value to show
	success:			null, // function: this function gets called if server responds with a success. Prototype: function (newEditorContentString)
	error:				null, // function: this function gets called if server responds with an error. Prototype: function (request)
	error_sink:			function (errorString) { console.log(errorString); }, // function: gets id of the editor and the error. Make sure the editor has an id, or it will just be undefined. If set to null, no error will be reported.
	delegate:			null // object: if it has methods with the name of the callbacks documented below in delegateExample these will be called. This means that you just need to impelment the callbacks you are interested in.
};

// Lifecycle events that the delegate can implement
// this will always be fixed to the delegate
/*
var delegateExample = {
	// called while opening the editor.
	// return false to prevent editor from opening
	shouldOpenEditInPlace: function (aDOMNode, aSettingsDict, triggeringEvent) {},
	// return content to show in inplace editor
	willOpenEditInPlace: function (aDOMNode, aSettingsDict) {},
	didOpenEditInPlace: function (aDOMNode, aSettingsDict) {},
	
	// called while closing the editor
	// return false to prevent the editor from closing
	shouldCloseEditInPlace: function (aDOMNode, aSettingsDict, triggeringEvent) {},
	// return value will be shown during saving
	willCloseEditInPlace: function (aDOMNode, aSettingsDict) {},
	didCloseEditInPlace: function (aDOMNode, aSettingsDict) {},
	
	missingCommaErrorPreventer:''
};
*/


}(jQuery));
