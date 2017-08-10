jQuery(document).ready(function() {
    WPML_String_Translation.ExcludeContexts.init(jQuery('.wpml-st-exclude-contexts'));
});

var WPML_String_Translation = WPML_String_Translation || {};

WPML_String_Translation.ExcludeContexts = {

    init: function(box) {
        this.form = box.find('form');
        this.box = box;
        this.dialog = this.form.parent();

        this.update_excluded_preview();
        this.create_dialog();

        this.form.find('input[name = "select_all"]').on('click', {'form': this.form}, this.select_all);
        this.form.find('input[name = "search"]').on('keyup', {'form': this.form}, this.filter);
        this.form.find('.contexts input:checkbox').on('change', {'form': this.form}, this.toggle_context);
    },

    create_dialog: function () {
        var that  = this;

        this.dialog.dialog({
            autoOpen: false,
            width: 1150,
            height: 600,
            modal: true,
            buttons: [
                {
                    class: 'wpml-st-cancel-button',
                    text: 'Cancel',
                    click: function() {
                        jQuery( this ).dialog( 'close' );
                    }
                },
                {
                    text: 'Apply',
                    class: 'button-primary js-wpml-st-apply-button',
                    click: function() {
                        that.save();
                    }
                }
            ]
        });

        this.box.find('.js-wpml-autoregister-edit-contexts').on( 'click', {'dialog' : this.dialog}, function(event) {
            event.preventDefault();
            event.data.dialog.dialog( 'open' );
        });
    },

    save: function() {
        var that = this;
        var apply_button = this.dialog.parent().find('.js-wpml-st-apply-button');
        apply_button.attr('disabled', 'disabled');

        var data = {
            action: 'wpml_st_exclude_contexts',
            wpml_st_auto_reg_excluded_contexts: this.get_excluded_contexts(),
            nonce: this.form.data('nonce')
        };

        jQuery.ajax({
            url:      ajaxurl,
            type:     'POST',
            data:     data,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    that.update_excluded_preview();
                    that.dialog.dialog('close');
                } else {
                    that.display_error_msg('Error: ' + response.data);
                }

                apply_button.removeAttr('disabled');
            }
        });
    },

    update_excluded_preview: function() {
        var container = this.box.find('.wpml-st-excluded-info');

        var excluded = this.get_excluded_contexts();
        var included = this.get_included_contexts();

        if (excluded.length == 0) {
            var text = container.data('all-included');
            container.html(text);
        } else if(included.length == 0) {
            var text = container.data('all-excluded');
            container.html(text);
        } else {
            var limit = 4;

            var elements = included.length > excluded.length ? excluded : included;
            var text = included.length > excluded.length ? container.data('excluded-preview') : container.data('included-preview');

            text += ' ' + elements.slice(0, limit).join(', ');
            if (elements.length > limit) {
                text += ' ' + container.data('preview-suffix');
            }

            container.html(text);
        }
    },

    get_excluded_contexts: function () {
        var excluded = [];
        this.form.find('.contexts input:checkbox').not(':checked').each(function() {
            excluded.push(jQuery(this).val());
        });

        return excluded;
    },

    get_included_contexts: function () {
        var included = [];
        this.form.find('.contexts input:checkbox:checked').each(function() {
            included.push(jQuery(this).val());
        });

        return included;
    },

    display_error_msg: function (msg) {
        alert(msg);
    },

    toggle_context: function(event) {
        if (jQuery(this).is(':checked')) {
            if (event.data.form.find('.contexts input:checkbox:not(:checked)').length == 0) {
                event.data.form.find('input[name = "select_all"]').prop( 'checked', true );
            }
        } else {
            event.data.form.find('input[name = "select_all"]').prop( 'checked', false );
        }
    },

    select_all: function(event) {
        if (jQuery(this).is(':checked')) {
            event.data.form.find('.contexts input').prop( 'checked', true );
        } else {
            event.data.form.find('.contexts input').prop( 'checked', false );
        }
    },

    filter: function(event) {
        var text = jQuery(this).val();
        if (text.length < 2) {
            event.data.form.find('.contexts p').show();
            return;
        }

        event.data.form.find('.contexts p span').each(function () {
            if (jQuery(this).text().search(text) != -1) {
                jQuery(this).parents('p').show();
            } else {
                jQuery(this).parents('p').hide();
            }
        })
    }
}