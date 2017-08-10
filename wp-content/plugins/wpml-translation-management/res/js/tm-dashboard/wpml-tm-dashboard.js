/*global wpml_tm_strings, jQuery, Backbone, icl_ajxloaderimg, ajaxurl */
/*jslint laxbreak: true */

(function () {
	'use strict';

var WPMLTMDashboard = Backbone.View.extend({
    events: {
        "click td :checkbox": 'update_td',
        "click th :checkbox": 'icl_tm_select_all_documents',
        "change #icl_tm_languages :radio": 'change_radio',
        "change #icl_parent_filter_control": 'iclTmPopulateParentFilter',
        "change #icl_language_selector": 'iclTmPopulateParentFilter',
        "click #duplicate-all": 'icl_tm_bulk_batch_selection',
        "click #translate-all": 'icl_tm_bulk_batch_selection',
        "click #update-none": 'icl_tm_bulk_batch_selection',
        "submit #icl_tm_dashboard_form": 'submit'
    },
    counts: {
        all: 0,
        duplicate: 0,
        translate: 0
    },
    init: function () {
        var self = this;
        self.counts.all =
            self.setElement('.icl_tm_wrap');
        self.iclTmPopulateParentFilter();
        self.change_radio();
    },
    submit: function (e) {
			var self = this;
			self.recount();
			if (self.counts.duplicate > 0) {
				e.preventDefault();
				var post_ids = [];
				var langs = [];
				var radios = jQuery('#icl_tm_languages').find('tbody').find(':radio:checked').filter('[value=2]');
				radios.each(function () {
					langs.push(jQuery(this).attr('name').replace('tr_action[', '').replace(']', ''));
				});

				var languagesCount = langs.length;
				if (0 < languagesCount) {
					var post_id_boxes = self.$el.find('td :checkbox:checked');
					var post_ids_count = post_id_boxes.length;

					for (var p = 0; p < post_ids_count; p++) {
						for (var l = 0; l < languagesCount; l++) {
							post_ids.push({
															postId:       jQuery(post_id_boxes[p]).val(),
															languageCode: langs[l]
														});
						}
					}
					//post_id_boxes.each(function () {
					//		post_ids.push(jQuery(this).val());
					//});
					var duplication_ui = new PostDuplication(post_ids, jQuery('#icl_dup_ovr_warn'));
					duplication_ui.sendBatch();
				}
			}
    },
    iclTmUpdateDashboardSelection: function () {
        var self = this;
        if (self.$el.find(':checkbox:checked').length > 0) {
            var checked_items = self.$el.find('th :checkbox');
            if (self.$el.find('td :checkbox:checked').length === self.$el.find('td :checkbox').length) {
                checked_items.attr('checked', 'checked');
            } else {
                checked_items.removeAttr('checked');
            }
        }
    },
    recount: function(){
        var self = this;
        var radios = jQuery('#icl_tm_languages').find('tbody').find(':radio:checked');
        self.counts.duplicate = radios.filter('[value=2]').length;
        self.counts.translate = radios.filter('[value=1]').length;
        self.counts.all = radios.length;

        return self;
    },
    change_radio: function () {
        var bulk_select_radio, bulk_select_val, self;
        self = this;
        self.recount();
        self.icl_tm_enable_submit();
        self.icl_tm_dup_warn();
        bulk_select_val = self.counts.duplicate === self.counts.all ? "2" : false;
        bulk_select_val = self.counts.translate === self.counts.all ? "1" : bulk_select_val;
        bulk_select_val = self.counts.translate === 0 && self.counts.duplicate === 0 ? "0" : bulk_select_val;
        bulk_select_radio = bulk_select_val !== false
            ? self.$el.find('[name="radio-action-all"]').filter('[value=' + bulk_select_val + ']')
            : self.$el.find('[name="radio-action-all"]');
        bulk_select_radio.attr('checked', !!bulk_select_val);
    },
    update_td: function () {
        var self = this;
        self.icl_tm_update_word_count_estimate();
        self.iclTmUpdateDashboardSelection();
    },
    icl_tm_select_all_documents: function (e) {
        var self = this;
        self.$el.find('#icl-tm-translation-dashboard').find(':checkbox').attr('checked', !!jQuery(e.target).attr('checked'));
        self.icl_tm_update_word_count_estimate();
        self.icl_tm_update_doc_count();
        self.icl_tm_enable_submit();
    },
    icl_tm_update_word_count_estimate: function () {
        var self = this;
        self.icl_tm_enable_submit();
        var element_rows = self.$el.find('tbody').find('tr');
        var current_overall_word_count = 0;
        var icl_tm_estimated_words_count = jQuery('#icl-tm-estimated-words-count');
        jQuery.each(element_rows, function () {
            var row = jQuery(this);
            if (row.find(':checkbox').attr('checked')) {
                var item_word_count = row.data('word_count');
                var val = parseInt(item_word_count);
                val = isNaN(val) ? 0 : val;
                current_overall_word_count += val;
            }
        });
        icl_tm_estimated_words_count.html(current_overall_word_count);
        self.icl_tm_update_doc_count();
    },
    setup_parent_filter_drop: function () {
        var icl_parent_filter_drop = jQuery('#icl_parent_filter_drop');
        if (icl_parent_filter_drop.length === 0) {
            icl_parent_filter_drop = jQuery('<select name="filter[parent_id]" id="icl_parent_filter_drop"></select>');
            icl_parent_filter_drop.appendTo(jQuery('#icl_parent_filter'));
        }
        icl_parent_filter_drop.html(icl_ajxloaderimg);
        icl_parent_filter_drop.hide();

        return icl_parent_filter_drop;
    },
    iclTmPopulateParentFilter: function () {
        var self = this;
        var icl_parent_filter_control = jQuery('#icl_parent_filter_control');
        var icl_parent_filter_drop = self.setup_parent_filter_drop();
        var icl_parent_filter_label = jQuery('#icl_parent_filter_label');
        icl_parent_filter_label.hide();
        var val = icl_parent_filter_control.val();
        if (val) {
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                dataType: 'json',
                data: 'action=icl_tm_parent_filter&type=' + val + '&lang=' + jQuery('form[name="translation-dashboard-filter"]').find('select[name="filter[from_lang]"]').val() + '&parent_id=' + jQuery('#icl_tm_parent_id').val() + '&parent_all=' + jQuery('#icl_tm_parent_all').val(),
                success: function (msg) {
                    icl_parent_filter_label.html(icl_parent_filter_control.children(":selected").text());
                    icl_parent_filter_drop.html(msg.html);
                    icl_parent_filter_drop.val(jQuery('#icl_tm_parent_id').val());
                    icl_parent_filter_drop.show();
                    icl_parent_filter_label.show();
                }
            });
        }
    },
    icl_update_button_label: function (dupl_count, trans_count) {
        var button_label;
        if (dupl_count > 0 && trans_count === 0) {
            button_label = wpml_tm_strings.BB_duplicate_all;
        } else if (dupl_count > 0 && trans_count > 0) {
            button_label = wpml_tm_strings.BB_mixed_actions;
        } else if (dupl_count === 0 && trans_count > 0) {
            button_label = wpml_tm_strings.BB_default;
        } else {
            button_label = wpml_tm_strings.BB_no_actions;
        }

        jQuery('#icl_tm_jobs_submit').val(button_label);
    },
    icl_tm_dup_warn: function () {
        var self = this;
        if (self.counts.duplicate > 0 !== self.$el.find('[id="icl_dup_ovr_warn"]:visible').length > 0) {
            self.$el.find('#icl_dup_ovr_warn').fadeToggle(400);
        }
        self.icl_update_button_label(self.counts.duplicate, self.counts.translate);
    },
    icl_tm_bulk_batch_selection: function (e) {
        var self = this;
        var element = jQuery(e.target);
        var value = element.val();
        element.attr('checked', 'checked');
        self.$el.find('#icl_tm_languages').find('tbody input:radio[value=' + value + ']').attr('checked', 'checked');
        self.change_radio();
        return self;
    },
    icl_tm_enable_submit: function () {
        var self = this;
        if ((self.counts.duplicate > 0 || self.counts.translate > 0)
            && jQuery('#icl-tm-translation-dashboard').find('td :checkbox:checked').length > 0) {
            jQuery('#icl_tm_jobs_submit').removeAttr('disabled');
        } else {
            jQuery('#icl_tm_jobs_submit').attr('disabled', 'disabled');
        }
    },
    icl_tm_update_doc_count: function () {
        var self = this;
        var dox = self.$el.find('td :checkbox:checked').length;
        jQuery('#icl-tm-sel-doc-count').html(dox);
        if (dox) {
            jQuery('#icl-tm-doc-wrap').fadeIn();
        } else {
            jQuery('#icl-tm-doc-wrap').fadeOut();
        }
    }
});

var PostDuplication = Backbone.View.extend({
    ui:                          {},
    posts:                       [],
    duplicatedIDs:               [],
    langs:                       '',
    initialize:                  function (posts, element) {
        var self = this;
        self.posts = posts;
        self.ui = new ProgressBar();
        self.ui.overall_count = posts.length;
        self.ui.actionText = wpml_tm_strings.duplicating;
        element.replaceWith(self.ui.getDomElement());
        self.ui.start();
    },
    sendBatch:                   function () {
			var nonce;
			var p;
			var postsToSend;
			var languages;
			var self = this;
			var postsDataToSend = self.posts.splice(0, 5);
			var postsDataToSendCount = postsDataToSend.length;

			if(0 < postsDataToSendCount) {
				postsToSend = [];
				languages = [];
				for (p = 0; p < postsDataToSendCount; p++) {
					if (-1 === jQuery.inArray(postsDataToSend[p].postId, postsToSend)) {
						postsToSend.push(postsDataToSend[p].postId);
					}
					if (-1 === jQuery.inArray(postsDataToSend[p].languageCode, languages)) {
						languages.push(postsDataToSend[p].languageCode);
					}
				}

				if(0 < postsToSend.length && 0 < languages.length) {
					nonce = wpml_tm_strings.wpml_duplicate_dashboard_nonce;
					jQuery.ajax({
												type:     "POST",
												url:      ajaxurl,
												dataType: 'json',
												data:     {
													action:                     'wpml_duplicate_dashboard',
													duplicate_post_ids:         postsToSend,
													duplicate_target_languages: languages,
													_icl_nonce:                 nonce
												},
												success:  function () {
													self.ui.change(postsToSend.length);
													self.duplicatedIDs = self.duplicatedIDs.concat(postsToSend);
													if (0 < self.posts.length) {
														self.sendBatch();
													} else {
														self.ui.complete(wpml_tm_strings.duplication_complete, false);
														jQuery('#icl_tm_languages').find('tbody').find(':radio:checked').filter('[value=2]').attr('checked', false);
														self.setHierarchyNoticeAndSubmit();
													}
												}
											});
				}
			}
    },
    setHierarchyNoticeAndSubmit: function () {
        var self = this;

        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: 'json',
            data: {
                action: 'wpml_need_sync_message',
                duplicated_post_ids: self.duplicatedIDs.join(','),
                _icl_nonce: wpml_tm_strings.wpml_need_sync_message_nonce

            },
            success: function () {
                jQuery('#icl_tm_dashboard_form').submit();
            }
        });
    }
});

jQuery(document).ready(function () {
    var tmDashboard = new WPMLTMDashboard();
    tmDashboard.init();
});

}());