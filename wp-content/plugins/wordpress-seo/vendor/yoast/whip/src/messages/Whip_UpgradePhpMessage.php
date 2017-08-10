<?php

/**
 * Class Whip_UpgradePhpMessage
 */
class Whip_UpgradePhpMessage implements Whip_Message {
	/**
	 * @var string
	 */
	private $textdomain;

	/**
	 * Whip_UpgradePhpMessage constructor.
	 *
	 * @param string $textdomain The text domain to use for the translations.
	 */
	public function __construct( $textdomain ) {
		$this->textdomain = $textdomain;
	}

	/**
	 * Composes the body of the message to display.
	 *
	 * @return string The message to display.
	 */
	public function body() {
		$textdomain = $this->textdomain;

		$message = array();

		$message[] = Whip_MessageFormatter::strongParagraph( __( 'Your site could be faster and more secure with a newer PHP version.', $textdomain ) ) . '<br />';
		$message[] = Whip_MessageFormatter::paragraph( __( 'Hey, we\'ve noticed that you\'re running an outdated version of PHP. PHP is the programming language that WordPress and Yoast SEO are built on. The version that is currently used for your site is no longer supported. Newer versions of PHP are both faster and more secure. In fact, your version of PHP no longer receives security updates, which is why we\'re sending you to this notice.', $textdomain ) );
		$message[] = Whip_MessageFormatter::paragraph( __( 'Hosts have the ability to update your PHP version, but sometimes they don\'t dare to do that because they\'re afraid they\'ll break your site.', $textdomain ) );
		$message[] = Whip_MessageFormatter::strongParagraph( __( 'To which version should I update?', $textdomain ) ) . '<br />';
		$message[] = Whip_MessageFormatter::paragraph( sprintf( __( 'You should update your PHP version to either 5.6 or to 7.0 or 7.1. On a normal WordPress site, switching to PHP 5.6 should never cause issues. We would however actually recommend you switch to PHP7. There are some plugins that are not ready for PHP7 though, so do some testing first. We have an article on how to test whether that\'s an option for you %1$shere%2$s. PHP7 is much faster than PHP 5.6. It\'s also the only PHP version still in active development and therefore the better option for your site in the long run.', $textdomain ), '<a href="https://yoa.st/wg" target="_blank">', '</a>' ) );

		if ( Whip_Host::name() !== '' ) {
			$hostMessage = new Whip_HostMessage( 'WHIP_MESSAGE_FROM_HOST_ABOUT_PHP', $textdomain );
			$message[] = $hostMessage->body();
		}

		$hostingPageUrl = Whip_Host::hostingPageUrl();

		$message[] = Whip_MessageFormatter::strongParagraph( __( 'Can\'t update? Ask your host!', $textdomain ) ) . '<br />';

		if ( function_exists( 'apply_filters' ) && apply_filters( Whip_Host::HOSTING_PAGE_FILTER_KEY, false ) ) {
			$message[] = Whip_MessageFormatter::paragraph( sprintf( __( 'If you cannot upgrade your PHP version yourself, you can send an email to your host. We have %1$sexamples here%2$s. If they don\'t want to upgrade your PHP version, we would suggest you switch hosts. Have a look at one of the recommended %3$sWordPress hosting partners%4$s.', $textdomain ), '<a href="https://yoa.st/wh" target="_blank">', '</a>', sprintf( '<a href="%1$s" target="_blank">', esc_url( $hostingPageUrl ) ), '</a>' ) );
		} else {
			$message[] = Whip_MessageFormatter::paragraph( sprintf( __( 'If you cannot upgrade your PHP version yourself, you can send an email to your host. We have %1$sexamples here%2$s. If they don\'t want to upgrade your PHP version, we would suggest you switch hosts. Have a look at one of our recommended %3$sWordPress hosting partners%4$s, they\'ve all been vetted by our Yoast support team and provide all the features a modern host should provide.', $textdomain ), '<a href="https://yoa.st/wh" target="_blank">', '</a>', sprintf( '<a href="%1$s" target="_blank">', esc_url( $hostingPageUrl ) ), '</a>' ) );
		}

		return implode( $message, "\n" );
	}

}
