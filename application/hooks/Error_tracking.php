<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Sentry\Event;

/**
 * Reports PHP errors and uncaught exceptions to Sentry when SENTRY_DSN is
 * set. As a pre_system hook it runs after CodeIgniter installs its error
 * handlers, so the SDK passes every error on to them and pages are unchanged.
 */
class Error_tracking
{
    public function start()
    {
        $options = self::options();
        if ($options !== NULL && function_exists('Sentry\init')) {
            \Sentry\init($options);
        }
    }

    /** SDK options from the environment, or NULL when reporting is off. */
    public static function options()
    {
        $dsn = getenv('SENTRY_DSN');
        if (!is_string($dsn) || trim($dsn) === '') {
            return NULL;
        }
        return [
            'dsn' => trim($dsn),
            'environment' => getenv('SENTRY_ENVIRONMENT') ?: ENVIRONMENT,
            'release' => getenv('SENTRY_RELEASE') ?: NULL,
            'traces_sample_rate' => (float) (getenv('SENTRY_TRACES_SAMPLE_RATE') ?: 0),
            // Never send cookies, IP addresses or request bodies, which carry
            // passwords and tokens.
            'send_default_pii' => FALSE,
            'max_request_body_size' => 'never',
            'before_send' => [self::class, 'scrub'],
        ];
    }

    /** Removes the API key header, which the SDK does not treat as sensitive. */
    public static function scrub(Event $event)
    {
        $request = $event->getRequest();
        if (isset($request['headers']) && is_array($request['headers'])) {
            foreach (array_keys($request['headers']) as $name) {
                if (strcasecmp((string) $name, 'X-API-Key') === 0) {
                    unset($request['headers'][$name]);
                }
            }
            $event->setRequest($request);
        }
        return $event;
    }
}
