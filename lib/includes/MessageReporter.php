<?php

// NOTE: This copy is here in WB now so I am not blocked for months
// while people complain about whitespace on CR. This file will
// either go if it finally gets merged into core or will be changed
// to be in WB NS is people are to ignorant to see the point of it and not merge.

/**
 * Interface for objects that can report messages.
 *
 * @since 1.21
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface MessageReporter {

	/**
	 * Report the provided message.
	 *
	 * @since 1.21
	 *
	 * @param string $message
	 */
	public function reportMessage( $message );

}

/**
 * Message reporter that reports messages by passing them along to all
 * registered handlers.
 *
 * @since 1.21
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ObservableMessageReporter implements MessageReporter {

	/**
	 * @since 1.21
	 *
	 * @var MessageReporter[]
	 */
	protected $reporters = array();

	/**
	 * @since 1.21
	 *
	 * @var callable[]
	 */
	protected $callbacks = array();

	/**
	 * @see MessageReporter::report
	 *
	 * @since 1.21
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		foreach ( $this->reporters as $reporter ) {
			$reporter->reportMessage( $message );
		}

		foreach ( $this->callbacks as $callback ) {
			call_user_func( $callback, $message );
		}
	}

	/**
	 * Register a new message reporter.
	 *
	 * @since 1.21
	 *
	 * @param MessageReporter $reporter
	 */
	public function registerMessageReporter( MessageReporter $reporter ) {
		$this->reporters[] = $reporter;
	}

	/**
	 * Register a callback as message reporter.
	 *
	 * @since 1.21
	 *
	 * @param callable $handler
	 */
	public function registerReporterCallback( $handler ) {
		$this->callbacks[] = $handler;
	}

}

/**
 * Mock implementation of the MessageReporter interface that
 * does nothing with messages it receives.
 *
 * @since 1.21
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class NullMessageReporter implements MessageReporter {

	/**
	 * @see MessageReporter::reportMessage
	 *
	 * @since 1.21
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		// no-op
	}

}
