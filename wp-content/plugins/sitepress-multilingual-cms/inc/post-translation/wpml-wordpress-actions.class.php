<?php

/**
 * Class WPML_WordPress_Actions
 * @package    wpml-core
 * @subpackage post-translation
 */
class WPML_WordPress_Actions {

    /**
     * @param int $post_id
     *
     * @return bool
     */
    public static function is_bulk_trash( $post_id ) {
        if ( self::is_trash_action() && self::post_id_in_bulk( $post_id ) ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $post_id
     *
     * @return bool
     */
    public static function is_bulk_untrash( $post_id ) {
        if ( self::is_untrash_action() && self::post_id_in_bulk( $post_id, true ) ) {
            return true;
        } else {
            return false;
        }
    }
	
	public static function is_heartbeat( ) {
        return self::is_action( 'heartbeat', 'post' );
	}

    protected static function is_trash_action() {
        return self::is_action( 'trash' );
    }

    protected static function is_untrash_action() {
        return self::is_action( 'untrash' );
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    protected static function is_action( $action, $type = 'get' ) {
		if ( $type == 'get' ) {
			return ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == $action ) || ( isset( $_GET[ 'action2' ] ) && $_GET[ 'action2' ] == $action );
		} elseif ( $type == 'post' ) {
			return ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == $action ) || ( isset( $_POST[ 'action2' ] ) && $_POST[ 'action2' ] == $action );
		} else {
			return false;
		}
    }

    /**
     * @param int $post_id
     * @param bool $check_ids
     *
     * @return bool
     */
    protected static function post_id_in_bulk( $post_id, $check_ids = false ) {
        if ( isset( $_GET[ 'post' ] ) && is_array( $_GET[ 'post' ] ) && in_array( $post_id, $_GET[ 'post' ] ) ) {
			return true;
		} elseif ( $check_ids ) {
			// We need to check the ids parameter when user clicks on 'undo' after trashing.
			return isset( $_GET[ 'ids' ] ) && is_string( $_GET[ 'ids' ] ) && in_array( $post_id, explode( ',', $_GET[ 'ids' ] ) );
        } else {
			return false;
		}
    }
}