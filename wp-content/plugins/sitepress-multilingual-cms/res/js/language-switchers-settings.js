/*jshint browser:true, devel:true */
/*global jQuery, wp, document, wpml_language_switcher_admin, _, iclSaveForm_success_cb, iclSaveForm */
var WPML_core = WPML_core || {};

WPML_core.languageSwitcher = (function( $, wpml_ls ) {
	"use strict";

	/** @namespace wpml_ls.strings */
	/** @namespace wpml_ls.strings.confirmation_item_remove */
	/** @namespace wpml_ls.color_schemes */
	/** @namespace wpml_ls.strings.table_no_item */
	/** @namespace wpml_ls.templates */

	var form,
		dialogBox,
		formAndDialogBox,
		additionalCssStyleId = 'wpml-ls-inline-styles-additional-css',
		dialogInlineStyleId  = 'wpml-ls-inline-styles-dialog',
		currentItemSlug,
		slotInlineStylesBackup;

	var init = function () {
		form         	 = $('#wpml-ls-settings-form');
		dialogBox        = $('#wpml-ls-dialog');
		formAndDialogBox = form.add(dialogBox);

		maybeInitAdditionalCssStyle();
		initDialogNode();
		initColorPicker();
		initLanguageSortable();
		attachEvents();
		openDialogFromHash();
		$(document).trigger('wpml_ls_admin_loaded');
	};

	var attachEvents = function () {
		attachAutoSaveEvents();
		attachToggleEvents();
		attachTooltipEvents();
		attachDialogEvents();
		attachRowActionEvents();
		attachSelectedSlotChangeEvents();
		attachPresetColorsEvent();
		attachTemplateChangeEvents();
		attachMenuHierarchicalEvents();
		attachUpdatePreviewEvents();
		attachSaveClickEvents();
		fixSelectedOption();
		forceRefreshOnBrowserBackButton();
		setupWizardNextEvent();
		preventClickOnPreviewLinks();
	};

	var maybeInitAdditionalCssStyle = function() {
		if ($('#' + additionalCssStyleId).length < 1) {
			var style = $('<style>');
			style.attr('id', additionalCssStyleId);
			style.appendTo($('head'));
		}
	};

	var attachAutoSaveEvents = function() {
		formAndDialogBox.on('change', '.js-wpml-ls-trigger-save', function() {
			updateSettings($(this));
		});

		formAndDialogBox.on('keyup', '.js-wpml-ls-trigger-need-save', function() {
			var triggerNode = $(this),
				messageWrapper = triggerNode.closest('.js-wpml-ls-option').find('.js-wpml-ls-messages');

			showUpdatedContent(messageWrapper, wpml_ls.strings.leave_text_box_to_save , 0, 'notice');
		});
	};
	
	var attachToggleEvents = function() {
		formAndDialogBox
			.on('click', '.js-wpml-ls-toggle-slot', function() {
				var triggerNode = $(this);
				var targetNode  = $(triggerNode.data('target'));

				targetNode.slideToggle({
					complete: function(){
						if(targetNode.is(':visible')) {
							triggerNode.addClass('open');
							targetNode.find('.js-wpml-ls-row-edit').trigger('click');
						} else {
							triggerNode.removeClass('open');
						}
						repositionDialog();

					}
				});
			})
			.on('click', '.js-wpml-ls-toggle-once', function() {
				var targetNode = $(this).nextAll('.js-wpml-ls-toggle-target');
				$(this).find('label').unwrap().find('.js-arrow-toggle').remove();
				targetNode.slideToggle();
				return false;
			});
	};

	var attachTooltipEvents = function() {
		formAndDialogBox.on('click.tooltip', '.js-wpml-ls-tooltip-open', function(e) {
			e.preventDefault();
			openTooltip($(this));
		});
	};

	var initLanguageSortable = function() {
		$('#wpml-ls-languages-order').sortable({
			stop: function() {
				updateSettings($(this));
			}
		});
	};

	var initDialogNode = function() {
		dialogBox.dialog({
				dialogClass: 'dialog-fixed otgs-ui-dialog wpml-ls-dialog',
				width: '90%',
				modal:       true,
				autoOpen:    false,
				draggable:   true,
				resizable:   false
			})
			.on('dialogopen', function () {
				$('body').css('overflow', 'hidden');
				$('.js-wpml-ls-active-tooltip').pointer('close');
				repositionDialog();
				attachDialogScrollEvent();
			})
			.on('dialogclose', function () {
				$('body').css('overflow', '');
				$('.js-wpml-ls-active-tooltip').pointer('close');
				updateDialogInlineStyle();
			});

		$(window).resize(resizeWindowEvent);
	};

	var openDialogFromHash = function() {
		var item = getItemFromHash();

		if (item) {
			var section = $('#wpml-language-switcher-' + item.type),
				row     = getItemRow(item);

			$('html, body').animate({
				scrollTop: section.offset().top
			}, 1, function() {
				var subform;

				if (row) {
					subform = row.find('.js-wpml-ls-subform');
				} else {
					var targetNode = $('#wpml-ls-new-' + item.type + '-template');
					subform = prepareTemplateSubform(targetNode);
					currentItemSlug = item.slug;
				}

				cloneSubformIntoDialog(subform);
			});
		}
	};

	var getItemFromHash = function() {
		var hashParts = window.location.hash.substring(1).split('/'),
			item = null,
			type = hashParts[0] || '',
			slug = hashParts[1] || '';

		if (0 <= $.inArray(type, ['menus', 'sidebars', 'statics'])) {
			item = {
				'type': type,
				'slug': slug
			};

			parent.location.hash = '';
		}

		return item;
	};

	var getItemRow = function(item) {
		var itemTypeSelector = '[data-item-type=' + item.type + ']',
			itemSlugSelector = item.slug ? '[data-item-slug=' + item.slug + ']' : '',
			row              = $('tr' + itemTypeSelector + itemSlugSelector);

		return 1 === row.length ? row : null;
	};

	var resizeWindowEvent = _.debounce(function() {
		repositionDialog();
		attachDialogScrollEvent();
	}, 200);

	var repositionDialog = function () {
		var winH = $(window).height() - 180;
		dialogBox.dialog("option", "maxHeight", winH);

		dialogBox.dialog("option", "position", {
			my: "center",
			at: "center",
			of: window
		});
	};

	var attachDialogScrollEvent = function() {
		var preview = dialogBox.find('.js-wpml-ls-preview-wrapper'),
			has_two_columns = dialogBox.width() > 900,
			has_minimal_height = (preview.height() + 200) < dialogBox.height();

		has_minimal_height = has_minimal_height || (has_two_columns && preview.height() < dialogBox.height());

		if (has_minimal_height) {
			dialogBox.on('scroll.preview', function(){
				dialogBox.find('.js-wpml-ls-preview-wrapper').css({
					position: 'relative',
					top: dialogBox.scrollTop()
				});
			});
		} else {
			dialogBox
				.off('scroll.preview')
				.find('.js-wpml-ls-preview-wrapper').css({
					position: 'relative',
					top: 0
				});
		}
	};

	var initColorPicker = function(node) {
		node = node || form;
		node.find('.js-wpml-ls-colorpicker').wpColorPicker({
			change: function(e){
				var subform = $(e.target).parents('.js-wpml-ls-subform');
				updatePreview(subform);
			},
			clear: function(e){
				var subform = $(e.target).parents('.js-wpml-ls-subform');
				updatePreview(subform);
			}
		});
	};

	var attachDialogEvents = function() {
		$('.js-wpml-ls-dialog-close').on('click', function(e) {
			e.preventDefault();
			restoreInlineStyles();
			dialogBox.dialog('close');
		});

		$('.js-wpml-ls-dialog-save').on('click', function(e) {
			e.preventDefault();
			$(this).prop('disabled', true);
			var subform = dialogBox.find('.js-wpml-ls-subform');
			replaceDialogSubformIntoOrigin(subform);
		});

		$('.js-wpml-ls-open-dialog').on('click', function(e) {
			e.preventDefault();

			var subform,
				targetNode = $($(e.target).data('target'));

			if (targetNode.hasClass('js-wpml-ls-template')) {
				subform = prepareTemplateSubform(targetNode);
			} else {
				subform = targetNode.find('.js-wpml-ls-subform');
			}

			cloneSubformIntoDialog(subform);
		});
	};

	var attachRowActionEvents = function() {
		$('.js-wpml-ls-slot-list')
			.on('click', '.js-wpml-ls-row-edit', function(e) {
				e.preventDefault();

				var targetNode = $($(e.target).parents('tr.js-wpml-ls-row')),
					subform    = targetNode.find('.js-wpml-ls-subform');

				cloneSubformIntoDialog(subform);
			})
			.on('click', '.js-wpml-ls-row-remove', function(e) {
				e.preventDefault();
				if (confirm(wpml_ls.strings.confirmation_item_remove)) {
					var rowNode  = $(this).parents('tr.js-wpml-ls-row'),
						itemType = rowNode.data('item-type');

					rowNode.find('.js-wpml-ls-subform input, .js-wpml-ls-subform select').remove();

					updateSettings(rowNode, function(){
						rowNode.fadeOut(800, function(){
							rowNode.remove();
							updateSlotSectionStatus(itemType);
						});
					});
				}
			});
	};

	var attachSelectedSlotChangeEvents = function() {
		formAndDialogBox.on('change', '.js-wpml-ls-available-slots', function(){
			var newSlug = $(this).val(),
				subform = $(this).closest('.js-wpml-ls-subform'),
				itemType = subform.data('item-type');

			$(this).removeClass('wpml-ls-required');

			if (isSlotAllowed(newSlug, itemType)) {
				replaceSubformElementsAttributes(subform, newSlug);
			}
		});
	};

	var resetColorpickers = function(node) {
		node.find('.js-wpml-ls-colorpicker').each( function() {
			var nodeClone  = $(this).clone(),
				parentNode = $(this).parents('.js-wpml-ls-colorpicker-wrapper');

			parentNode.empty().html(nodeClone);
		});

		initColorPicker(node);
	};

	var preselectSlot = function(subform) {
		if (currentItemSlug) {
			var selector = '.js-wpml-ls-available-slots option[value="' + currentItemSlug + '"]';
			subform.find(selector).attr('selected', 'selected');
			currentItemSlug = null;
		}
	};

	var attachPresetColorsEvent = function() {
		formAndDialogBox.on('change', '.js-wpml-ls-colorpicker-preset', function(){
			var slug = $(this).val();

			if (slug) {
				var pickerSets = $(this).parents('.js-wpml-ls-panel-colors'),
					colors     = wpml_ls.color_schemes[ slug ].values;

				$.each(colors, function(k, v){
					var inputTags = pickerSets.find('.js-wpml-ls-color-' + k);
					inputTags.attr('value', v);
					inputTags.parents('.wp-picker-container').find('.wp-color-result').css('background-color', v);
				});

				pickerSets.find('.js-wpml-ls-colorpicker').trigger('change');
			}
		});
	};

	var fixSelectedOption = function() {
		// Prevent loosing selected after replacing in original id
		formAndDialogBox.on('change', 'select', function () {
			var selectedVal = $(this).val();
			$('option', this).removeAttr('selected');
			$('option[value="' + selectedVal + '"]', this).attr('selected', 'selected');
		});
	};

	/**
	 * @see http://stackoverflow.com/a/19196020
	 */
	var forceRefreshOnBrowserBackButton = function() {
		var input = $('#wpml-ls-refresh-on-browser-back-button');

		if(input.val() === 'yes') {
			location.reload(true);
		} else {
			input.val('yes');
		}
	};

	var setupWizardNextEvent = function() {
		form.submit(function(e){
			e.preventDefault();

			form.find('input[name="submit_setup_wizard"]').val(1);

			updateSettings(form, function(){
				location.href = location.href.replace(/#.*/,'');
			});
		});
	};

	var preventClickOnPreviewLinks = function () {
		$('.js-wpml-ls-preview').on('click', function(e) {
			e.preventDefault();
		});
	};

	var openTooltip = function(triggerNode) {
		var content = triggerNode.data('content');
		var link_text = triggerNode.data('link-text');
		var link_url = triggerNode.data('link-url');
		var link_target = triggerNode.data('link-target');

		if (link_text.length > 0) {
			if (link_url.length === 0) {
				link_url = '#';
			}
			var content_link_target = 'target="' + link_target + '"';
			content += '<br><br><a href="' + link_url + '" ' + content_link_target + '>';
			content += link_text;
			content += '</a>';
		}

		$('.js-wpml-ls-active-tooltip').pointer('close');

		if(triggerNode.length && content) {
			triggerNode.addClass('js-wpml-ls-active-tooltip');
			triggerNode.pointer({
				pointerClass : 'js-wpml-ls-tooltip wpml-ls-tooltip',
				content:       content,
				position: {
					edge:  'bottom',
					align: 'left'
				},
				show: function(event, t){
					t.pointer.css('marginLeft', '-54px');
				},
				close: function(event, t){
					t.pointer.css('marginLeft', '0');
				},
				buttons: function( event, t ) {
					var button = $('<a class="close" href="#">&nbsp;</a>');

					return button.bind( 'click.pointer', function(e) {
						e.preventDefault();
						t.element.pointer('close');
					});
				},

			}).pointer('open');
		}
	};

	var cloneSubformIntoDialog = function(subform) {
		var subformClone = subform.clone(true);

		dialogBox.find('.js-wpml-ls-dialog-inner').empty().append(subformClone);

		if('' === subformClone.find('.js-wpml-ls-preview').html()) {
			updatePreview(subformClone);
		}

		prepareSlotInlineStyles(subform);
		updateAvailableSlotsSelector(subformClone);
		resetColorpickers(subformClone);
		preselectSlot(subformClone);

		dialogBox.dialog('option', 'title', subformClone.data('title'))
			.dialog('open');
	};

	var prepareSlotInlineStyles = function(subform) {
		var type = subform.data('item-type');
		var slug = subform.data('item-slug');

		slug = '%id%' === slug ? '__id__' : slug;
		var inlineStyles = $('#wpml-ls-inline-styles-' + type + '-' + slug);
		slotInlineStylesBackup = inlineStyles.detach();
		updateDialogInlineStyle(slotInlineStylesBackup.clone());
	};

	var restoreInlineStyles = function() {
		if ( slotInlineStylesBackup instanceof jQuery && slotInlineStylesBackup.length > 0) {
			$('#' + additionalCssStyleId).before(slotInlineStylesBackup);
		}
	};

	var replaceDialogSubformIntoOrigin = function(subform) {
		var subformClone = subform.clone(true),
			itemType     = subform.data('item-type');

		subformClone.find('.js-wpml-ls-preview-wrapper').css('top', 0);

		if(0 <= $.inArray(itemType, ["menus", "sidebars"] )) {
			var selectedSlot = subform.find('.js-wpml-ls-available-slots').val();

			if (isSlotAllowed(selectedSlot, itemType)) {
				if (typeof subformClone.data('origin-id') === 'undefined') {
					appendNewRowAndSubform(subformClone);
				} else {
					updateRowAndSubform(subformClone);
				}

				updateSlotSectionStatus(itemType);
			} else {
				missingSlotWarning(subform);
			}
		} else if(subformClone.data('item-type') === 'statics'){
			updateStaticSlot(subformClone);
		}
	};

	var updateSlotSectionStatus = function(itemType) {
		var section    = $('#wpml-language-switcher-' + itemType),
			numItems   = section.find('.js-wpml-ls-row').length,
			numAllowed = $.map(wpml_ls[ itemType ], function(n, i) { return i; }).length;

		if (numItems === 0) {
			section.find('.js-wpml-ls-slot-list').fadeOut();
		} else {
			section.find('.js-wpml-ls-slot-list').fadeIn();
		}

		if (numItems === numAllowed) {
			section.find('button.js-wpml-ls-open-dialog').attr('disabled', 'disabled')
				.siblings('.js-wpml-ls-tooltip-wrapper').removeClass('hidden');
		} else {
			section.find('button.js-wpml-ls-open-dialog').removeAttr('disabled')
				.siblings('.js-wpml-ls-tooltip-wrapper').addClass('hidden');
		}
	};

	var updateStaticSlot = function(subform) {
		var wrapper = $('#' + subform.data('origin-id')),
			slug    = subform.data('item-slug');

		wrapper.find('.js-wpml-ls-subform').replaceWith(subform);

		copyDialogInlineStyleToSlot('statics', slug);

		updateSettings(wrapper, function() {
			dialogBox.find('.js-wpml-ls-dialog-inner').empty();
			dialogBox.dialog('close');
		});
	};

	var updateRowAndSubform = function(subform) {
		var row      = $('#' + subform.data('origin-id')),
			itemType = subform.data('item-type'),
			slug     = subform.find('.js-wpml-ls-available-slots').val();

		row.find('.js-wpml-ls-subform').replaceWith(subform);

		if(row.attr('id') !== 'wpml-ls-' + itemType +'-row-' + slug){
			updateRowOnItemSlotChange(row, itemType, slug);
		}

		copyDialogInlineStyleToSlot(itemType, slug);

		updateSettings(row, function() {
			dialogBox.find('.js-wpml-ls-dialog-inner').empty();
			dialogBox.dialog('close');
		});
	};

	var appendNewRowAndSubform = function(subform) {
		var itemType = subform.data('item-type'),
			slug     = subform.find('.js-wpml-ls-available-' + itemType).val();

		replaceSubformElementsAttributes(subform, slug);

		var tplString = $.trim($('#wpml-ls-new-' + itemType + '-row-template').html()),
			newRow    = $($.parseHTML(tplString));

		newRow.find('.js-wpml-ls-subform').replaceWith(subform);
		updateRowOnItemSlotChange(newRow, itemType, slug);
		newRow.hide().appendTo(form.find('#wpml-ls-slot-list-' + itemType + ' > tbody')).show(800);

		copyDialogInlineStyleToSlot(itemType, slug);

		updateSettings(newRow, function() {
			dialogBox.find('.js-wpml-ls-dialog-inner').empty();
			dialogBox.dialog('close');
		});
	};

	var missingSlotWarning = function(subform) {
		subform.find('.js-wpml-ls-available-slots').addClass('wpml-ls-required');
		dialogBox.animate({scrollTop:0}, 300);
		$('.js-wpml-ls-dialog-save').prop('disabled', false);
	};

	var updateRowOnItemSlotChange = function(row, itemType, slug) {
		var newRowId = 'wpml-ls-' + itemType +'-row-' + slug,
			newTitle = wpml_ls[ itemType ][ slug ].name;

		row.attr('id', newRowId);
		row.data('item-type', itemType);
		row.find('.js-wpml-ls-subform').addBack().data('item-slug', slug);
		row.find('.js-wpml-ls-subform').data('origin-id', newRowId);
		row.find('.js-wpml-ls-row-title').html(newTitle);
	};

	var replaceSubformElementsAttributes = function(subform, newSlug) {
		var attr     = '',
			itemType = subform.data('item-type'),
			oldSlug  = subform.data('item-slug');

		subform.find('input, select').each(function(i, el){
			attr = $(el).attr('name');
			if( typeof attr === 'string') {
				$(el).attr('name', attr.replace(itemType + '[' + oldSlug +']', itemType + '[' + newSlug +']') );
			}

			attr = $(el).attr('id');
			if( typeof attr === 'string') {
				$(el).attr('id', attr.replace(oldSlug, newSlug) );
			}
		});

		subform.find('label').each(function(i, el){
			attr = $(el).attr('for');
			if( typeof attr === 'string') {
				$(el).attr('for', attr.replace(oldSlug, newSlug) );
			}
		});
	};

	var prepareTemplateSubform = function(targetNode) {
		var tplString = $.trim(targetNode.html()),
			subform   = $($.parseHTML(tplString));

		updateAvailableSlotsSelector(subform);

		return subform;
	};

	var getUsedSlots = function( type ) {
		var slotSlugs = [];
		$('#wpml-ls-slot-list-' + type + ' .js-wpml-ls-row').each( function() {
			slotSlugs.push($(this).data('item-slug'));
		});

		return slotSlugs;
	};

	var isSlotAllowed = function(slot, itemType) {
		var allowedSlots = $.map(wpml_ls[ itemType ], function(el) {
			return itemType === 'menus' ? el.term_id.toString() : el.id;
		});

		return 0 <= $.inArray(slot, allowedSlots);
	};

	var updateAvailableSlotsSelector = function(subform) {
		var itemType            = subform.data('item-type'),
			alreadyUsed         = getUsedSlots(itemType),
			selectedOption      = subform.data('item-slug'),
			selectorOptionsNode = subform.find('.js-wpml-ls-available-slots option');

		selectorOptionsNode.each(function(){
			var optionNode    = $(this),
				usedOption    = $.inArray(optionNode.val(), alreadyUsed) >= 0,
				currentOption = optionNode.val();

			if (usedOption && selectedOption !== currentOption) {
				optionNode.attr('disabled', 'disabled');
			} else {
				optionNode.removeAttr('disabled');
			}
		});
	};

	var attachUpdatePreviewEvents = function() {
		formAndDialogBox
			.on('change', '[type!="text"].js-wpml-ls-trigger-update', function(){
				var subform = $(this).parents('.js-wpml-ls-subform');
				updatePreview(subform);
			})
			.on('keyup', '[type="text"].js-wpml-ls-trigger-update', function(){
				var subform = $(this).parents('.js-wpml-ls-subform');
				updatePreview(subform);
			})
			.on('keyup', '.js-wpml-ls-additional-css', function(){
				var styleId      = 'wpml-ls-inline-styles-additional-css',
					newStyleNode = $('<style id="' + styleId + '" type="text/css">' + $(this).val() + '</style>');

				$('#' + styleId).replaceWith(newStyleNode);
			});
	};

	var attachTemplateChangeEvents = function() {
		formAndDialogBox.on('change', '.js-wpml-ls-template-selector', function() {
			var selected = $(this).val(),
				subform  = $(this).closest('.js-wpml-ls-subform'),
				force    = wpml_ls.templates[ selected ].force_settings;

			subform.find('.js-wpml-ls-to-include input').prop('disabled', false);

			jQuery.each(force, function(k, v){
				if(v === 1) {
					subform.find('.js-wpml-ls-setting-' + k).attr('checked', 'checked').prop('disabled', true);
				} else {
					subform.find('.js-wpml-ls-setting-' + k).removeAttr('checked').prop('disabled', true);
				}
			});
		});
	};

	var attachMenuHierarchicalEvents = function() {
		formAndDialogBox.on('change', '.js-wpml-ls-menu-is-hierarchical', function() {
			var isHierarchical          = parseInt($(this).val()),
				subform                 = $(this).closest('.js-wpml-ls-subform'),
				includeCurrentLangInput = subform.find('.js-wpml-ls-setting-display_link_for_current_lang');

			includeCurrentLangInput.prop('disabled', false);

			if(isHierarchical === 1) {
				includeCurrentLangInput.attr('checked', 'checked').prop('disabled', true);
			}
		});
	};

	var updatePreview = _.debounce( function(subform) {
		$('.js-wpml-ls-dialog-save').prop('disabled', true);
		var preview = subform.find('.js-wpml-ls-preview');

		if(preview.length > 0) {
			showSpinner(preview);
			var previewData = getSerializedSettings(subform);

			wp.ajax.send({
				data: {
					action:        'wpml-ls-update-preview',
					nonce:         wpml_ls.nonce,
					slot_settings: previewData
				},
				success:  function (data) {
					preview.empty();
					updateDialogInlineStyle(data.styles);
					buildCSSLinks(data.css);
					buildJSScripts(data.js);
					showUpdatedContent(preview, data.html);
				},
				error: function (data) {
					showUpdatedContent(preview, data, 0, 'error');
				}
			});
		}
	}, 500);

	var attachSaveClickEvents = function() {
		form.on('click', '.js-wpml-ls-save-settings', function(e){
			e.preventDefault();
			updateSettings($(this));
		});
	};

	var updateSettings = _.debounce(function (wrapper, successCallback) {
		var messageWrapper = wrapper.closest('.js-wpml-ls-option').find('.js-wpml-ls-messages'),
			settings       = getSerializedSettings(form);

		if(messageWrapper.length === 0) {
			messageWrapper = wrapper.closest('.js-wpml-ls-section').find('.js-wpml-ls-messages');
		}

		messageWrapper.empty();
		showSpinner(messageWrapper);

		wp.ajax.send({
			data: {
				action:   'wpml-ls-save-settings',
				nonce:    wpml_ls.nonce,
				settings: settings
			},
			success:  function (data) {
				showUpdatedContent(messageWrapper, data, 2500, 'updated');

				if(typeof successCallback === 'function') {
					successCallback();
				}
			},
			error: function (data) {
				showUpdatedContent(messageWrapper, data, 2500, 'error');
			}
		});
	}, 500);

	var getSerializedSettings = function(form) {
		var disabled = form.find(':input:disabled').removeAttr('disabled'),
			settings = form.find('input, select, textarea').serialize();

		disabled.attr('disabled','disabled');

		return settings;
	};

	var showSpinner = function(wrapper) {
		$('.js-wpml-ls-messages').removeClass('success error').hide().empty();
		wrapper.siblings('.spinner').addClass('is-active');
	};

	var showUpdatedContent = function(wrapper, content, delay, type) {
		wrapper.siblings('.spinner').removeClass('is-active');

		wrapper.removeClass('updated notice error');

		if(type) {
			wrapper.addClass(type);
		}

		wrapper.html(content).fadeIn();

		if(delay) {
			wrapper.delay(delay).fadeOut();
		}

		$('.js-wpml-ls-dialog-save').prop('disabled', false);
	};

	var updateDialogInlineStyle = function (styles) {
		 $('#' + dialogInlineStyleId).remove();

		if(styles) {
			var newDialogStyle;

			if (styles instanceof jQuery) {
				newDialogStyle = styles;
			} else {
				newDialogStyle = $($.parseHTML(styles)).first();
			}

			newDialogStyle.attr('id', dialogInlineStyleId);
			$('#' + additionalCssStyleId).before(newDialogStyle);
		}
	};

	var copyDialogInlineStyleToSlot = function (itemType, slug) {
		var dialogStyleClone = $('#' + dialogInlineStyleId).clone(),
			targetId         = 'wpml-ls-inline-styles-' + itemType + '-' + slug,
			targetStyle      = $('#' + targetId);

		dialogStyleClone.attr('id', targetId);

		if (targetStyle.length) {
			targetStyle.replaceWith(dialogStyleClone);
		} else {
			$('#' + additionalCssStyleId).before(dialogStyleClone);
		}
	};

	var buildCSSLinks = function (css) {
		var i,
			linkTag;

		if (typeof css !== typeof undefined && null !== css) {
			for (i = 0; i < css.length; i++) {
				linkTag = $('link[href="' + css[i] + '"]');

				if (0 === linkTag.length) {
					linkTag = $('<link>');
					linkTag.attr('rel', 'stylesheet');
					linkTag.attr('type', 'text/css');
					linkTag.attr('media', 'all');
					linkTag.attr('href', css[i]);
					linkTag.prependTo('head');
				}
			}
		}
	};

	var buildJSScripts = function (js) {
		var i,
			jsTag;

		if (typeof js !== typeof undefined && null !== js) {
			for (i = 0; i < js.length; i++) {
				jsTag = $('script[src="' + js[i] + '"]');

				if (0 === jsTag.length) {
					jsTag = $('<script></script>');
					jsTag.attr('type', 'text/javascript');
					jsTag.attr('src', js[i]);
					jsTag.prependTo('head');
				}
			}
		}
	};

	return {
		'init': init
	};

})( jQuery, wpml_language_switcher_admin );

jQuery(document).ready(function () {
	"use strict";

	WPML_core.languageSwitcher.init();
});