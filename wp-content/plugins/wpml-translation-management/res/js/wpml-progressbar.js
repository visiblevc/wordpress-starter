/* global icl_ajxloaderimg */

var ProgressBar = Backbone.View.extend({
    dom: false,
    overall_count: 0,
    done_count: 0,
    ajax_loader_img: false,
    progress_label_value: false,
    progress_label: false,
    actionText : '',
    initialize: function () {
        var self = this;
        self.ajax_loader_img = jQuery(icl_ajxloaderimg);
        self.dom = jQuery('<div class="progressbar"><div class="progress-label"><span class="value"></span></div></div>');
        self.progress_label = self.dom.find(".progress-label");
        self.progress_label_value = self.dom.find(".value");

        return self;
    },
    start: function () {
        var self = this;
        self.ajax_loader_img.appendTo(self.progress_label);
        self.getDomElement().progressbar({value: false, max: 100});
        self.getDomElement().fadeIn();
		self.progress_label_value.text('');

        return self;
    },
    change: function (change) {
        var self = this;
        self.done_count += change;
        var value = Math.min(Math.round(self.done_count / self.overall_count * 100), 100);
        self.progress_label_value.text(self.actionText + " " + value + "%");
        self.getDomElement().progressbar('value', value);
    },
    complete: function (text, callback) {
        var self = this;
        self.ajax_loader_img.remove();
        self.progress_label_value.text(text);
        if (typeof callback === 'function') {
            callback();
        }
    },
    getRemainingCount: function () {
        var self = this;

        return self.overall_count - self.done_count;
    },
    getDomElement: function () {
        var self = this;

        return self.dom;
    },
    stop: function () {
        var self = this;
        self.getDomElement().hide();

        return self;
    }
});
