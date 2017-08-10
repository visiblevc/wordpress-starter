<?php

function update_string_statuses() {
    global $wpdb;

    $wpdb->update(
        $wpdb->prefix . 'icl_string_translations',
        array( 'status' => ICL_TM_COMPLETE, 'batch_id' => 0 ),
        array( 'batch_id' => -1, 'status' => 1 )
    );

    $wpdb->update(
        $wpdb->prefix . 'icl_string_translations',
        array( 'status' => ICL_TM_NEEDS_UPDATE, 'batch_id' => 0 ),
        array( 'batch_id' => -1, 'status' => 2 )
    );

    $wpdb->update(
        $wpdb->prefix . 'icl_string_translations',
        array( 'status' => ICL_TM_WAITING_FOR_TRANSLATOR, 'batch_id' => 0 ),
        array( 'batch_id' => -1, 'status' => 11 )
    );
    $sql = "ALTER TABLE `{$wpdb->prefix}icl_string_translations` CHANGE batch_id batch_id int DEFAULT 0 NOT NULL;";
    $wpdb->query( $sql );

}

function fix_icl_string_status() {
    global $wpdb;

    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->prefix}icl_strings s
                                 SET s.status = %d
                                 WHERE EXISTS ( SELECT 1
                                                FROM {$wpdb->prefix}icl_string_translations st
                                                WHERE string_id = s.id AND st.status = %d )",
            ICL_TM_NEEDS_UPDATE,
            ICL_TM_NEEDS_UPDATE
        )
    );

    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->prefix}icl_strings s
                                 SET s.status = %d
                                 WHERE  ( SELECT COUNT(string_id)
                                                FROM {$wpdb->prefix}icl_string_translations st
                                                WHERE st.string_id = s.id AND st.status = %d ) = (( SELECT COUNT(*)
                                                                                         FROM {$wpdb->prefix}icl_languages
                                                                                         WHERE active = 1) - 1)",
            ICL_TM_COMPLETE,
            ICL_TM_COMPLETE
        )
    );

    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->prefix}icl_strings s
                                 SET s.status = %d
                                 WHERE  ( SELECT COUNT(string_id)
                                                FROM {$wpdb->prefix}icl_string_translations st
                                                WHERE st.string_id = s.id AND st.status = %d ) < (( SELECT COUNT(*)
                                                                                         FROM {$wpdb->prefix}icl_languages
                                                                                         WHERE active = 1) - 1)
                                                AND ( SELECT COUNT(string_id)
                                                      FROM {$wpdb->prefix}icl_string_translations st2
                                                      WHERE st2.string_id = s.id AND st2.status = %d ) > 0 ",
            2,
            ICL_TM_COMPLETE,
            ICL_TM_COMPLETE
        )
    );
}