var WPML_TM = WPML_TM || {};

WPML_TM.fieldViewFactory = {
	create: function (field, args) {
		var view = null;
		if (field.field_style === '0') {
			view = new WPML_TM.editorSingleLineFieldView(args);
		} else if (field.field_style === '1') {
			view = new WPML_TM.editorTextareaFieldView(args);
		} else if (field.field_style === '2') {
			view = new WPML_TM.editorWysiwygFieldView(args);
		}
		return view;
	}

};

