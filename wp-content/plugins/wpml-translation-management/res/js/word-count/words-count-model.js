/*jshint browser:true, devel:true */
/*global jQuery, ajaxurl, wpml_tm_words_count_data */

var WPMLWordsCount = function () {
	"use strict";

	var self = this;

	self.dialogPosition = {
		my: "center",
		at: "center",
		of: window
	};

	self.centerDialog = function () {
		if (self.hasOwnProperty('toolDialog') && self.toolDialog.hasClass('ui-dialog-content')) {
			self.toolDialog.dialog('option', 'position', self.dialogPosition);
		}
        self.updateTotals();
	};

    self.init = function () {
        self.box = jQuery('.wpml-accordion');
        self.toolDialog = self.box.find('.inside').find('.dialog');
        self.languageSelector = self.box.find('#source-language-selector');
        self.summary = self.box.find('.summary');
        self.cachedRows = {};

        if (self.box) {
            self.box.accordion({
                active: 0,
                collapsible: true,
                heightStyle: "content"
            });
            self.box.find('.button-primary').on('click', self.openTool);
            if ('#words-count' === window.location.hash) {
                self.openDialog();
            }
        }
    };

	self.openTool = function (event) {
		event.preventDefault();
		self.openDialog();
	};

	self.openDialog = function () {
		self.toolDialog.dialog({
			resizable:     false,
			modal:         true,
			width:         'auto',
			autoResize:    true,
			position:      self.dialogPosition,
			closeOnEscape: true,
			open:          function () {
				self.selectSourceLanguage();
			}
		});
		self.languageSelector.on('change', self.selectSourceLanguage);
	};

	self.bindCheckBoxes = function () {
        self.summary.find('.report tbody').find(':checkbox').on('click', self.updateCheckAllStatus);
        self.summary.find('[name="check-all"]').on('click', function () {
			self.updateCheckItemStatus(this);
		});
	};

	self.updateCheckAllStatus= function() {
		var tbody = self.summary.find('.report tbody');
		var checkAll = self.summary.find('[name="check-all"]');
		if (tbody.find(':checkbox').length === tbody.find(':checkbox:checked').length) {
			checkAll.attr('checked', 'checked');
		} else {
			checkAll.removeAttr('checked');
		}
		self.updateTotals();
	};

	self.updateCheckItemStatus= function(element) {
		var tbody = self.summary.find('.report tbody');
		var checkAll = self.summary.find('[name="check-all"]');

		if (jQuery(element).is(':checked')) {
			checkAll.attr('checked', 'checked');
			tbody.find(':checkbox').attr('checked', 'checked');
		} else {
			checkAll.removeAttr('checked');
			tbody.find(':checkbox').removeAttr('checked');
		}
		self.updateTotals();
	};

	self.updateTotals = function () {
		var table = self.summary.find('.report');
		var foot = table.find('tfoot tr');
		foot.find('td.num').each(function (c, td) {
			var total = jQuery(td);
			total.data('value', 0);
			total.text(0);
		});

		table.find('tbody tr').each(function (r, tr) {
			var row = jQuery(tr);
			row.find('td').each(function (c, td) {
				var cell, grandTotalCellElement, selectedValue, totalCellValue;
				cell = jQuery(td);
				if (cell.hasClass('num')) {
					selectedValue = cell.text();
					if (jQuery.isNumeric(selectedValue)) {
						selectedValue = row.find(':checkbox').is(':checked') ? parseInt(selectedValue) : 0;
						grandTotalCellElement = jQuery(foot.find('td')[cell.index() - 1]);
						totalCellValue = grandTotalCellElement.data('value');
						if (isNaN(totalCellValue) || '' === totalCellValue) {
							totalCellValue = 0;
						}
						grandTotalCellElement.data('value', (parseInt(totalCellValue) + selectedValue));
					}
				}
			});
		});

		foot.find('td.num').each(function (c, td) {
			var total = jQuery(td);
			total.text(total.data('value').toLocaleString());
		});
	};

	self.showSummaryElement = function (selector) {
		if (self.summary.is(":visible")) {
			self.summary.find(selector).fadeIn(function () {
				self.centerDialog();
			});
		} else {
			self.summary.find(selector).show();
			self.summary.fadeIn(function () {
				self.centerDialog();
			});
		}
	};

	self.hideSummaryElement = function (selector) {
		if (self.summary.is(":visible")) {
			self.summary.find(selector).fadeOut(function () {
				self.centerDialog();
			});
		} else {
			self.summary.find(selector).hide();
			self.centerDialog();
		}
	};

	self.showNoResults = function () {
		self.hideReport();
		self.showSummaryElement('.no-results');
        self.hideSpinner();
    };

	self.hideNoResults = function () {
		self.hideSummaryElement('.no-results');
	};

	self.hideReport = function () {
		self.hideSummaryElement('.report');
		self.centerDialog();
	};

	self.getSpinner = function () {
		return self.toolDialog.find('.spinner');
	};

	self.showSpinner = function () {
		self.getSpinner().addClass('is-active');
	};

	self.hideSpinner = function () {
		self.getSpinner().removeClass('is-active');
        self.hideRatio();
    };

    self.counts = {};

    self.mergeTables = function (tableA, tableB) {
        tableA = jQuery(tableA);
        tableB = jQuery(tableB);
        var cbs = tableB.find("tbody > tr > th > input[type='checkbox']");
        cbs = cbs.length > 0 ? cbs : [];
        jQuery.each(cbs, function (index, cb) {
            cb = jQuery(cb);
            var cb_in_a = tableA.find('tbody').find('#' + cb.attr('id'));
            if (cb_in_a.length > 0) {
                var counts_in_a = jQuery(cb_in_a).closest('tr').find('.num');
                var counts_in_b = cb.closest('tr').find('.num');
                [0, 1].forEach(function (index) {
                    counts_in_a[index] = jQuery(counts_in_a[index]);
                    counts_in_b[index] = jQuery(counts_in_b[index]);
                    counts_in_a[index].data('value', counts_in_a[index].data('value') + counts_in_b[index].data('value'));
                });
            } else {
                jQuery(cb.closest('tr')).appendTo(jQuery(tableA.find('tbody')));
            }
        });

        cbs = tableA.find("tbody > tr > th > input[type='checkbox']");
        cbs = cbs.length > 0 ? cbs : [];
        jQuery.each(cbs, function (index, cb) {
            cb = jQuery(cb);
            var postType = cb.attr('id');
            var counts_in_a = jQuery(cb).closest('tr').find('.num');
            [0, 1].forEach(function (index) {
                counts_in_a[index] = jQuery(counts_in_a[index]);
                self.counts[postType] = self.counts[postType] || [0, 0];
                self.counts[postType][index] = counts_in_a[index].data('value');
            });
        });

        return tableA;
    };

	self.selectSourceLanguage = function () {
		var sourceLanguage;

		self.showSpinner();
		sourceLanguage = self.languageSelector.val();
		self.hideNoResults();

		if (sourceLanguage in self.cachedRows) {
			self.renderData(self.cachedRows[sourceLanguage]);
        } else {
            self.fetchRows(sourceLanguage, 0);
		}
	};

    self.fetchRows = function (sourceLanguage, offset) {
        jQuery.ajax({
            url: ajaxurl,
            data: {
                'action': 'wpml_words_count_summary',
                'offset': offset,
                'nonce': jQuery('[name=wpml_words_count_source_language_nonce]').val(),
                'source_language': sourceLanguage
            },
            success: function (response) {
                if (response.success) {
                    /**
                     * @namespace response.data
                     * @type string
                     * */
                    if ('undefined' !== response.data && response.data.length) {
                        self.hideRatio();
                        self.getSpinner().before(jQuery(response.data).last('#wpml_tm_wc_post_ratio').show());
                        self.cachedRows[sourceLanguage] = self.cachedRows[sourceLanguage] ? self.mergeTables(self.cachedRows[sourceLanguage], response.data) : self.mergeTables(response.data, '');
                        self.fetchRows(sourceLanguage, offset + parseInt(jQuery('[name=wpml_words_count_chunk_size]').val(), 10), self.cachedRows[sourceLanguage]);
                    }
                } else {
                    if (self.cachedRows[sourceLanguage]) {
                        self.cachedRows[sourceLanguage] = self.syncCounts(self.cachedRows[sourceLanguage]);
                        self.renderData(self.cachedRows[sourceLanguage])
                    } else {
                        self.showNoResults()
                    }
                    self.updateCheckAllStatus();
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                self.showNoResults();
                console.log(xhr);
                console.log(ajaxOptions);
                console.log(thrownError);
            }
        });
    };

    self.hideRatio = function () {
        var ratio;
        [self.summary, self.toolDialog].forEach(function(element){
            ratio = jQuery(element).find('#wpml_tm_wc_post_ratio');
            if (ratio.length) {
                ratio.remove();
            }
        });
    };

    self.syncCounts = function (countData) {
        for (var elementType in self.counts) {
            if (self.counts.hasOwnProperty(elementType)) {
                [0, 1].forEach(function (index) {
                    jQuery(
                        countData.find('tbody')
                            .find('#' + elementType)
                            .closest('tr')
                            .find('.num')[index]
                    ).data('value', self.counts[elementType][index]).text(self.counts[elementType][index]);
                });
            }
        }

        return countData;
    };

    self.renderData = function (countData) {
        jQuery(countData).appendTo(self.summary.empty());
        self.summary.find('.no-results').hide();
        self.showSummaryElement('.report');
        self.centerDialog();
        self.bindCheckBoxes();
        self.updateTotals();
        self.hideSpinner();
        self.updateCheckAllStatus();
    };
};

jQuery(document).ready(function () {
	"use strict";

	var wpmlWordsCount = new WPMLWordsCount();
	wpmlWordsCount.init();
});