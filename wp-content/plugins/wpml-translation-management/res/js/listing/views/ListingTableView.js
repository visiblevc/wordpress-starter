/*jshint browser:true, devel:true */
/*global jQuery, Backbone, Translation_Jobs, head, ajaxurl, _, icl_ajxloaderimg */
(function () {
    "use strict";

    Translation_Jobs.listing.views.ListingTableView = Backbone.View.extend(
        {
            el: ".wpml-translation-management-jobs",
            tagName: "table",
            events: {
                "click .bulk-select-checkbox": "select_bulk_action",
                "change .js-selected-items": "updateButtons",
                "click #icl-tm-jobs-cancel-but": "cancelJobs"
            },
            initialize: function () {
                var self = this;
                self.ajaxLoader = null;

                self.model.bind(
                    'groups_ready', function () {
                        _.bindAll(self, 'render');
                        _.bindAll(self, 'renderGroups');
                        _.bindAll(self, 'renderNav');
                        _.bindAll(self, 'renderFilter');
                        self.model.bind('groups_ready', self.renderNav);
                        self.model.get('Filter').bind('change', self.renderFilter);
                        self.model.get('Navigator').bind('page_change', self.renderFilter);
                        self.model.get('Navigator').bind('per_page_change', self.renderFilter);
                        self.render();
                        self.$el.data('view', self);
                    }
                );

            },
            render: function () {
                var self = this;

                if (self.ajaxLoader) {
                    self.ajaxLoader.remove();
                    self.ajaxLoader = null;
                }

                if (self.model.get('loaded') === false) {
                    self.renderPlaceHolder();
                    return self;
                } else {
                    self.$el.siblings('#table-listing-no-jobs-wrapper').hide();
                    self.$el.show();
                }
                self.renderFilter();

            	self.$el.find('th :checkbox').removeAttr('checked');

                return self;
            },
            renderPlaceHolder: function(){
                var self = this;
                jQuery(".spinner").hide();
                if(!self.$el.siblings('#table-listing-no-jobs-wrapper').length) {
                    self.template = _.template(jQuery('#table-listing-no-jobs').html());
                    self.$el.after(self.template());
                } else {
                    self.$el.siblings('#table-listing-no-jobs-wrapper').show();
                }
                self.$el.hide();
                self.model.destroy();
                self.off();
            },
            renderGroups: function () {

                var self = this;
                self._cleanBeforeRender(self.$el);
                var groups = self.model.get('Groups');
                if (typeof groups !== 'undefined' && groups.length > 0) {
                    if (typeof self.groups_view === 'undefined') {
                        self.groups_view = new Translation_Jobs.listing.views.ListingGroupsView({models: groups.models});
                    }
                    self.groups_view.models = groups.models;
                }
                self.groups_view.render();
                jQuery(".spinner").hide();
                self.model.trigger('render_done');
                self.navigator_view.delegateEvents();
                self.groups_view.delegateEvents();

                return self;
            },
            renderFilter: function () {
                var spinner = jQuery('.waiting-2');
                spinner.show();
                var self = this;

                var navigator_options = self.model.get('Navigator');
                var filter_options = self.model.get('Filter');
                var old_filter = self.model.get('old_filter');
                var first_render_flag = false;
                var old_navigator = self.model.get('old_navigator');

                if (!old_navigator) {
                    old_navigator = {page: 1, per_page: navigator_options.get('per_page')};
                    first_render_flag = true;
                }

                if (!self.model.get('old_filter')) {
                    old_filter = {
                        translator_id: "",
                        job_status: "",
                        job_lang_from: "",
                        job_lang_to: ""
                    };
                    first_render_flag = true;
                }

                var filter_object = {
                    translator_id: filter_options.get('translator_id') || "",
                    job_status: filter_options.get('job_status') || "",
                    job_lang_from: filter_options.get('lang_from') || "",
                    job_lang_to: filter_options.get('lang_to') || ""
                };

                var navigator_object = {
                    per_page: navigator_options.get('per_page'),
                    page: navigator_options.get('page')
                };

                if (!first_render_flag && old_filter.job_lang_to === filter_object.job_lang_to && old_filter.job_lang_from === filter_object.job_lang_from && old_filter.job_status === filter_object.job_status && old_filter.translator_id === filter_object.translator_id && old_navigator.page === navigator_object.page && old_navigator.per_page === navigator_object.per_page) {
                    spinner.hide();
                    return self;
                } else {
                    self.model.set('old_filter', filter_object, {silent: true});
                    self.model.set('old_navigator', navigator_object, {silent: true});
                    spinner.show();
                    self.model.group_data(navigator_object.page, navigator_object.per_page, filter_object);
                    var filter_fragment = document.createDocumentFragment();
                    self.filter_view = typeof self.filter_view !== 'undefined' ? self.filter_view : new Translation_Jobs.listing.views.ListingFilterView({model: filter_options});
                    self.filter_view.render();
                    filter_fragment.appendChild(self.filter_view.el);
                    self.$el.first("tr:first").before(filter_fragment);
                }

                return self;
            },
            renderNav: function () {
                var self = this;
                var navigator_fragment = document.createDocumentFragment();
                self.navigator_view = typeof self.navigator_view !== 'undefined'
                    ? self.navigator_view
                    : new Translation_Jobs.listing.views.ListingNavigatorView({model: self.model.get('Navigator')});

                self.renderGroups();
                self.navigator_view.render();
                navigator_fragment.appendChild(self.navigator_view.el);
                self.$el.append(navigator_fragment);
            },
            _cleanBeforeRender: function (el) {
                var self = this;

                el.find('tbody').each(
                    function (i, v) {
                        if (jQuery(v).data('view')) {
                            self._cleanBeforeRender(jQuery(v));
                            jQuery(v).data('view').remove();
                        }

                    }
                );
            },
            select_bulk_action: function (e) {
                var self = this;
                var checkboxes = jQuery('.js-selected-items');
                var newStatus = jQuery(e.currentTarget).attr('checked');
                if (!newStatus) {
                    newStatus = false;
                }
                checkboxes.attr('checked', newStatus);
                jQuery('.bulk-select-checkbox').attr('checked', newStatus);

                self.updateButtons();
            },
            updateButtons: function () {
                var self               = this;
                var button             = jQuery('#icl-tm-jobs-cancel-but');
                var checkboxes         = self.$el.find('.js-selected-items');
                var checkboxes_checked = self.$el.find('.js-selected-items:checked');

                if (!checkboxes_checked.length) {
                    button.attr('disabled', 'disabled');
                } else {
                    button.removeAttr('disabled');
                }

                if (checkboxes.length) {
                    jQuery('.bulk-select-checkbox').attr('checked', checkboxes_checked.length == checkboxes.length);
                }
            },
            cancelJobs: function (e) {
                e.preventDefault();
                var self       = this;
                var jobIDs     = [];
                var checkboxes = self.$el.find('.js-selected-items:checked');
                var button     = jQuery('#icl-tm-jobs-cancel-but');
                jQuery.each(checkboxes, function(){
                    jobIDs.push(jQuery(this).val());
                });

                self.ajaxLoader = jQuery(icl_ajxloaderimg).insertBefore(button);

                this.model.cancelJobs(jobIDs);

                jQuery('.bulk-select-checkbox').attr('checked', false);
                button.attr('disabled', 'disabled');
            },
            highlight: function (view) {
                window.scrollTo(0, view.$el.offset().top - ( view.$el.height() * 1.5 ));
                view.$el.css(
                    {
                        background: "#FFFFE0",
                        opacity: 0.2
                    }
                );
                view.$el.animate(
                    {
                        opacity: 1,
                        specialEasing: {
                            background: "easeOutBounce"
                        }
                    }, 1600, function () {
                        view.$el.animate(
                            {
                                opacity: 0.9,
                                specialEasing: {
                                    background: "linear"
                                }
                            }, 400, function () {
                                view.$el.css(
                                    {
                                        background: "#f9f9f9",
                                        opacity: 1
                                    }
                                );
                            }
                        );
                        /** @namespace Translation_Jobs.listing_manager */
                        Translation_Jobs.listing_manager.listing_table_view.current = null;
                    }
                );
            }
        }
    );
}());
