<?php
class SitePress_Setup {

	static function setup_complete() {
		global $sitepress;

		return $sitepress->get_setting('setup_complete');
	}
	
	static function languages_complete() {
		return self::active_languages_complete() && self::languages_table_is_complete();
	}

	private static function active_languages_complete() {
		static $result = null;

		if ( $result === null ) {
			global $sitepress;

			$result = isset( $sitepress ) && 1 < count( $sitepress->get_active_languages() );

		}

		return $result;
	}

	private static function get_languages_codes() {
		static $languages_codes = null;
		if ( $languages_codes == null ) {
			$languages_codes = icl_get_languages_codes();
		}

		return $languages_codes;
	}

	private static function get_languages_names() {
		static $languages_names = null;
		if ( $languages_names == null ) {
			$languages_names = icl_get_languages_names();
		}

		return $languages_names;
	}

	private static function get_languages_names_count() {
		return count( self::get_languages_names() );
	}

	static function get_charset_collate() {
		static $charset_collate = null;

		if ( $charset_collate == null ) {
			$charset_collate = '';
			global $wpdb;
			if ( method_exists( $wpdb, 'has_cap' ) && $wpdb->has_cap( 'collation' ) ) {
				if ( !empty( $wpdb->charset ) ) {
					$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
				}
				if ( !empty( $wpdb->collate ) ) {
					$charset_collate .= " COLLATE $wpdb->collate";
				}
			}
		}

		return $charset_collate;
	}

	private static function create_languages() {
		$sql = "( `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					  `code` VARCHAR( 7 ) NOT NULL ,
					  `english_name` VARCHAR( 128 ) NOT NULL ,
					  `major` TINYINT NOT NULL DEFAULT '0',
					  `active` TINYINT NOT NULL ,
					  `default_locale` VARCHAR( 35 ),
					  `tag` VARCHAR( 35 ),
					  `encode_url` TINYINT( 1 ) NOT NULL DEFAULT 0,
					  UNIQUE KEY `code` (`code`),
					  UNIQUE KEY `english_name` (`english_name`)
				  ) ";

		return self::create_table ( 'icl_languages', $sql );
	}

	static function languages_table_is_complete() {
		global $wpdb;
		$table_name    = $wpdb->prefix . 'icl_languages';
		$sql           = "SELECT count(id) FROM {$table_name}";
		$records_count = $wpdb->get_var( $sql );

		$languages_names_count = self::get_languages_names_count();

		if( $records_count < $languages_names_count) return false;

		$languages_codes = self::get_languages_codes();

		$table_name    = $wpdb->prefix . 'icl_languages_translations';
		foreach ( self::get_languages_names() as $lang => $val ) {
			if ( strpos( $lang, 'Norwegian Bokm' ) === 0 ) {
				$lang                     = 'Norwegian Bokmål';
				$languages_codes[ $lang ] = 'nb';
			}
			foreach ( $val[ 'tr' ] as $k => $display ) {
				if ( strpos( $k, 'Norwegian Bokm' ) === 0 ) {
					$k = 'Norwegian Bokmål';
				}
				$sql = $wpdb->prepare( "SELECT id FROM {$table_name} WHERE language_code=%s AND display_language_code=%s", array( $languages_codes[ $lang ], $languages_codes[ $k ] ) );
				if ( !( $wpdb->get_var( $sql ) ) ) {
					return false;
				}
			}
		}
		return true;
	}

	static function fill_languages() {
		global $wpdb, $sitepress, $sitepress_settings;

		$languages_codes = icl_get_languages_codes();
        $lang_locales = icl_get_languages_locales();

		$table_name = $wpdb->prefix . 'icl_languages';
		if ( !self::create_languages() ) {
			return false;
		}

		if ( !self::languages_table_is_complete() ) {
			//First truncate the table
			$active_languages = ( $sitepress !== null
			                      && $sitepress_settings !== null
			                      && icl_get_setting( 'setup_complete' ) ) ? $sitepress->get_active_languages() : array();

			$wpdb->hide_errors();

			$sql = "TRUNCATE " . $table_name;

			$truncate_result = $wpdb->query( $sql );

			$wpdb->show_errors();

			if ( false !== $truncate_result ) {
				foreach ( self::get_languages_names()  as $key => $val ) {
					$language_code     = $languages_codes[ $key ];
					if ( strpos( $key, 'Norwegian Bokm' ) === 0 ) {
						$key                     = 'Norwegian Bokmål';
						$language_code = 'nb';
					} // exception for norwegian
					$default_locale = isset( $lang_locales[ $language_code ] ) ? $lang_locales[ $language_code ] : '';

					$args = array(
						'english_name'   => $key,
						'code'           => $language_code,
						'major'          => $val[ 'major' ],
						'active'         => isset($active_languages[ $language_code ]) ? 1 : 0,
						'default_locale' => $default_locale,
						'tag'            => $language_code
					);
					if ( $wpdb->insert( $table_name, $args )  === false) {
						return false;
					}                  
				}
			}
		}

		return true;
	}

	private static function create_languages_translations() {

		$sql = "(`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                `language_code`  VARCHAR( 7 ) NOT NULL ,
                `display_language_code` VARCHAR( 7 ) NOT NULL ,
                `name` VARCHAR( 255 ) CHARACTER SET utf8 NOT NULL,
                UNIQUE(`language_code`, `display_language_code`)
	            )";

		return self::create_table ( 'icl_languages_translations', $sql );
	}

	static function fill_languages_translations() {
		global $wpdb;

		$languages_codes = icl_get_languages_codes();

		$table_name = $wpdb->prefix . 'icl_languages_translations';

		if ( !self::create_languages_translations() ) {
			return false;
		}

		if ( !self::languages_table_is_complete() ) {

			//First truncate the table
			$wpdb->hide_errors();

			$sql =  "TRUNCATE " . $table_name;

			$truncate_result = $wpdb->query( $sql );

			$wpdb->show_errors();

			if ( false !== $truncate_result ) {
				$index = 1;

				$insert_sql_parts = array();
				$languages = self::get_languages_names();
				if($languages) {
					foreach ( $languages as $lang => $val ) {
						if ( strpos( $lang, 'Norwegian Bokm' ) === 0 ) {
							$lang                     = 'Norwegian Bokmål';
							$languages_codes[ $lang ] = 'nb';
						}
						foreach ( $val[ 'tr' ] as $k => $display ) {
							if ( strpos( $k, 'Norwegian Bokm' ) === 0 ) {
								$k = 'Norwegian Bokmål';
							}
							if ( ! trim( $display ) ) {
								$display = $lang;
							}

							$inserts_language_data = array(
								'id'                    => $index,
								'language_code'         => $languages_codes[ $lang ],
								'display_language_code' => $languages_codes[ $k ],
								'name'                  => $display
							);

							$insert_sql_parts[] = $wpdb->prepare('(%d, %s, %s, %s)', $inserts_language_data);
							$index ++;
						}
					}

					$insert_sql = implode(",\n", $insert_sql_parts);
					$insert_sql = "INSERT INTO {$table_name} (id, language_code, display_language_code, name) VALUES "
								. $insert_sql;

					if ( $wpdb->query( $insert_sql ) === false ) {
						return false;
					}
				}
			}
		}

		return true;
	}

	private static function create_table($name, $table_sql){
		global $wpdb;

		$table_name = $wpdb->prefix . $name;

		return 0 === strcasecmp( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ), $table_name )
			|| ( $wpdb->query(
				sprintf("CREATE TABLE IF NOT EXISTS `%s` ", $table_name )
				. $table_sql . " "
				. self::get_charset_collate()
			) !== false );
	}

    private static function create_flags(){

	    $sql = "(`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                `lang_code` VARCHAR( 10 ) NOT NULL ,
                `flag` VARCHAR( 32 ) NOT NULL ,
                `from_template` TINYINT NOT NULL DEFAULT '0',
                UNIQUE (`lang_code`)
                )";

        return self::create_table('icl_flags', $sql);
    }

    public static function fill_flags() {
        global $wpdb;

        if ( self::create_flags () === false ) {
            return false;
        }

        $codes = $wpdb->get_col ( "SELECT code FROM {$wpdb->prefix}icl_languages" );
        foreach ( $codes as $code ) {
            if ( !$code || $wpdb->get_var (
                    $wpdb->prepare (
                        "SELECT lang_code
                         FROM {$wpdb->prefix}icl_flags
                         WHERE lang_code = %s ",
                        $code
                    )
                )
            ) {
                continue;
            }
            if ( !file_exists ( ICL_PLUGIN_PATH . '/res/flags/' . $code . '.png' ) ) {
                $file = 'nil.png';
            } else {
                $file = $code . '.png';
            }
            $wpdb->insert (
                $wpdb->prefix . 'icl_flags',
                array( 'lang_code' => $code, 'flag' => $file, 'from_template' => 0 )
            );
        }
    }

    public static function insert_default_category( $lang_code ) {
        global $sitepress;

        $default_language = $sitepress->get_default_language();
        if ( $lang_code === $default_language ) {
            return;
        }

	    // Get default categories.
	    $default_categories = $sitepress->get_setting ( 'default_categories', array() );
	    if ( isset( $default_categories[ $lang_code ] ) ) {
		    return;
	    }

        $sitepress->switch_locale ( $lang_code );
	    $tr_cat = __ ( 'Uncategorized', 'sitepress' );
	    $tr_cat = $tr_cat === 'Uncategorized' && $lang_code !== 'en' ? 'Uncategorized @' . $lang_code : $tr_cat;
        $tr_term = term_exists ( $tr_cat, 'category' );
        $sitepress->switch_locale ();
		
        // check if the term already exists
        if ( $tr_term !== 0 && $tr_term !== null ) {
            $tmp = get_term ( $tr_term[ 'term_taxonomy_id' ], 'category', ARRAY_A );
        } else {
            $tmp = wp_insert_term ( $tr_cat, 'category' );
        }

        // add it to settings['default_categories']
        $default_categories[ $lang_code ] = $tmp[ 'term_taxonomy_id' ];

        $sitepress->set_default_categories ( $default_categories );

        //update translations table
        $default_category_trid = $sitepress->get_element_trid (
            get_option('default_category'),
            'tax_category'
        );
        $sitepress->set_element_language_details (
            $tmp[ 'term_taxonomy_id' ],
            'tax_category',
            $default_category_trid,
            $lang_code,
            $default_language
        );
    }
}
