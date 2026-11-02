<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Sentry\Event;

defined('ENVIRONMENT') || define('ENVIRONMENT', 'testing');
require_once APPPATH . 'hooks/Error_tracking.php';

final class ErrorTrackingTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('SENTRY_DSN');
        putenv('SENTRY_RELEASE');
    }

    public function testReportingIsOffWithoutADsn(): void
    {
        putenv('SENTRY_DSN');

        self::assertNull(Error_tracking::options());
    }

    public function testOptionsNeverSendPersonalDataOrBodies(): void
    {
        putenv('SENTRY_DSN=https://public@sentry.example.test/1');
        putenv('SENTRY_RELEASE=1.2.3');

        $options = Error_tracking::options();

        self::assertSame('https://public@sentry.example.test/1', $options['dsn']);
        self::assertSame('testing', $options['environment']);
        self::assertSame('1.2.3', $options['release']);
        self::assertFalse($options['send_default_pii']);
        self::assertSame('never', $options['max_request_body_size']);
        self::assertSame(0.0, $options['traces_sample_rate']);
        self::assertIsCallable($options['before_send']);
    }

    public function testApiKeysAreRemovedFromReportedRequests(): void
    {
        $event = Event::createEvent();
        $event->setRequest(['url' => 'https://app.example.test/api/me', 'headers' => [
            'X-Api-Key' => ['ak_secret'], 'Accept' => ['application/json'],
        ]]);

        $scrubbed = Error_tracking::scrub($event)->getRequest();

        self::assertArrayNotHasKey('X-Api-Key', $scrubbed['headers']);
        self::assertSame(['application/json'], $scrubbed['headers']['Accept']);
    }
}
