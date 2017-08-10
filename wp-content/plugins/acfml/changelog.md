# 0.4
* Fixed problem with returned wrong data type after conversion (one-item arrays retruned as strings)
* Fixed fields dissapearance when translating field groups
* Added support for Gallery field

# 0.3

* added support for ACF Pro
* convert() method now returns original object id if translation is missing
* fixed not working repeater field

# 0.2

* Moved fix about xliff support from WPML Translation Management to this plugin. If you use xliff files to send documents 
to translation, define WPML_ACF_XLIFF_SUPPORT to be true in wp-config.php file.  

# 0.1

* Initial release
* Fixes issues during post translation with field of types: Post Object, Page Link, Relationship, Taxonomy, Repeater