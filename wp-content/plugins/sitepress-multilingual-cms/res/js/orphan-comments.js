/*jslint browser: true, nomen: true, laxbreak: true*/
/*global ajaxurl */

"use strict";

jQuery(document).ready(
	function ($) {

		var deleted = 0;
		var deleting = false;
		var initial_orphans_count = 0;

		var orphansCount = $('#wpml_orphans_count');
		var orphansCheckCount = orphansCount.find('.check-orphans');
		var orphansCountResults = orphansCount.find('.orphans-check-results');
		var orphansCountProgress = orphansCount.find('.count-in-progress');
		var deletingProgress = orphansCount.find('.delete-in-progress');
		var deletedOrphans = orphansCount.find('.deleted');
		var cleanOrphans = orphansCount.find('.clean-orphans');
		var noOrphans = orphansCount.find('.no_orphans');
		var orphansCheckLoader = orphansCount.find('.check_loader');

		deletedOrphans.hide();
		noOrphans.hide();
		orphansCountProgress.hide();
		orphansCountResults.hide();
		orphansCheckLoader.hide();

		orphansCount.show();

		orphansCheckCount.on('click', count_orphans);
		cleanOrphans.on('click', delete_orphans);

		var nonce = orphansCount.find('#wpml_orphan_comment_nonce').val();

		function resetDeletion() {
			deletingProgress.fadeOut();
			deleted = 0;
			deleting = 0;
		}

		function count_orphans() {
			if(!deleting) {
				orphansCheckCount.fadeOut();
				orphansCountProgress.fadeIn();
			}
			var data = {
				action    : 'wpml_count_orphans',
				_icl_nonce: nonce
			};

			$.post(
				ajaxurl, data, function (res) {
					orphansCountProgress.fadeOut();
					var orphansCountResult = res.success ? res.data : 0;
					orphansCheckLoader.fadeOut();
					orphansCountResults.find('.count').html(orphansCountResult);
					if (orphansCountResult > 0) {
						if (initial_orphans_count == 0) {
							initial_orphans_count = orphansCountResult;
							cleanOrphans.fadeIn();
						}
						noOrphans.fadeOut();
						orphansCountResults.fadeIn();

						if (deleting) {
							delete_orphans();
						} else {
							resetDeletion();
						}
					} else {
						resetDeletion();
						noOrphans.fadeIn();
						orphansCountResults.fadeOut();
						orphansCheckCount.fadeIn();
					}
				}
			);
		}

		function delete_orphans() {

			cleanOrphans.fadeOut();
			deletingProgress.fadeIn();
			deleting = true;

			deletedOrphans.fadeIn();

			var data = {
				'action'  : 'wpml_delete_orphans',
				'data'    : {how_many: Math.max(10, initial_orphans_count / 10)},
				_icl_nonce: nonce
			};
			$.post(
				ajaxurl, data, function (res) {
					var deletedComments = res.success ? res.data : 0;
					deleted += parseInt(deletedComments);
					deletedOrphans.html(deleted);
					count_orphans();
				}
			);
		}
	}
);