<?php
/**
 * @file
 * Contains Drupal\login_security\Tests\LoginSecurityTest.
 */

namespace Drupal\login_security\Tests;

use Drupal\Component\Utility\String;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\simpletest\WebTestBase;

/**
 * Basic integration tests for Login Security.
 *
 * @group login_security
 */
class LoginSecurityTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'login_security', 'dblog'];

  /**
   * @var \Drupal\user\UserInterface[]
   */
  protected $badUsers = [];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->badUsers[] = $this->drupalCreateUser();
    $this->badUsers[] = $this->drupalCreateUser();
  }

  /**
   * Test threshold notify functionality.
   */
  public function testThresholdNotify() {
    // Set notify threshold to 5, and user locking to 5.
    \Drupal::configFactory()->getEditable('login_security.settings')
      ->set('user_wrong_count', 5)
      ->set('activity_threshold', 5)
      ->save();

    // Attempt 10 bad logins. Since the user will be locked out after 5, only
    // a single log message should be set, and an attack should not be
    // detected.
    for ($i = 0; $i < 10; $i++) {
      $login = [
        'name' => $this->badUsers[0]->getUsername(),
        'pass' => 'bad_password_' . $i,
      ];
      $this->drupalPostForm('user', $login, t('Log in'));
    }

    // Ensure a log message has been set.
    $logs = $this->getLogMessages();
    $this->assertEqual(count($logs), 1, '1 event was logged.');
    $log = array_pop($logs);
    $this->assertBlockedUser($log, $this->badUsers[0]->getUsername());
    db_truncate('watchdog')->execute();

    // Run failed logins as second user to trigger an attack warning.
    for ($i = 0; $i < 10; $i++) {
      $login = [
        'name' => $this->badUsers[1]->getUsername(),
        'pass' => 'bad_password_' . $i,
      ];
      $this->drupalPostForm('user', $login, t('Log in'));
    }

    $logs = $this->getLogMessages();

    // 2 logs should be generated.
    $this->assertEqual(count($logs), 2, '2 events were logged.');

    // First log should be the ongoing attack, triggered on attempt after the
    // threshold.
    $log = array_shift($logs);
    $variables = ['@activity_threshold' => 5, '@tracking_current_count' => 6];
    $expected = String::format('Ongoing attack detected: Suspicious activity detected in login form submissions. Too many invalid login attempts threshold reached: currently @tracking_current_count events are tracked, and threshold is configured for @activity_threshold attempts.', $variables);
    $this->assertEqual(String::format($log->message, unserialize($log->variables)), $expected);
    $this->assertEqual($log->severity, RfcLogLevel::WARNING, 'The logged alert was of severity "Warning".');

    // Second log should be a blocked user.
    $log = array_shift($logs);
    $this->assertBlockedUser($log, $this->badUsers[1]->getUsername());
  }

  /**
   * Asserts a blocked user log was set.
   *
   * @param stdClass $log
   *   The raw log record from the database.
   * @param string $username
   *   The blocked username.
   */
  protected function assertBlockedUser($log, $username) {
    $variables = ['@username' => $username];
    $expected = String::format('Blocked user @username due to security configuration.', $variables);
    $this->assertEqual(String::format($log->message, unserialize($log->variables)), $expected, 'User blocked log was set.');
    $this->assertEqual($log->severity, RfcLogLevel::NOTICE, 'User blocked log was of severity "Notice".');
  }

  /**
   * Retrieve log records from the watchdog table.
   *
   * @return stdClass[]
   */
  protected function getLogMessages() {
    return db_select('watchdog', 'w')
      ->fields('w', ['wid', 'message', 'variables', 'severity'])
      ->condition('w.type', 'login_security')
      ->execute()
      ->fetchAllAssoc('wid');
  }

}
