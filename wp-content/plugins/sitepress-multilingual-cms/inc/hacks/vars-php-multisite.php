<?php
            global $PHP_SELF;
            if ( is_admin() ) {
                // wp-admin pages are checked more carefully
                if ( is_network_admin() )
                    preg_match('#/wp-admin/network/?(.*?)$#i', $PHP_SELF, $self_matches);
                elseif ( is_user_admin() )
                    preg_match('#/wp-admin/user/?(.*?)$#i', $PHP_SELF, $self_matches);
                else
                    preg_match('#/wp-admin/?(.*?)$#i', $PHP_SELF, $self_matches);
                $pagenow = $self_matches[1];
                $pagenow = trim($pagenow, '/');
                $pagenow = preg_replace('#\?.*?$#', '', $pagenow);
                if ( '' === $pagenow || 'index' === $pagenow || 'index.php' === $pagenow ) {
                    $pagenow = 'index.php';
                } else {
                    preg_match('#(.*?)(/|$)#', $pagenow, $self_matches);
                    $pagenow = strtolower($self_matches[1]);
                    if ( '.php' !== substr($pagenow, -4, 4) )
                        $pagenow .= '.php'; // for Options +Multiviews: /wp-admin/themes/index.php (themes.php is queried)
                }
            } else {
                if ( preg_match('#([^/]+\.php)([?/].*?)?$#i', $PHP_SELF, $self_matches) )
                    $pagenow = strtolower($self_matches[1]);
                else
                    $pagenow = 'index.php';
            }
            unset($self_matches);
  