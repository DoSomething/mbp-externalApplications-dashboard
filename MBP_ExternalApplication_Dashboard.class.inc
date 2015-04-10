<?php

use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MBStatTracker\StatHat;

/**
 * MBP_ExternalApplication_Dashboard class - functionality related to the Message Broker
 * producer mbp-externalApplications-dashboard.
 */
class MBP_ExternalApplication_Dashboard
{

  /**
   * Access credentials settings
   *
   * @var object
   */
  private $credentials;

  /**
   * Service settings
   *
   * @var array
   */
  private $settings;

  /**
   * Setting from external services - StatHat.
   *
   * @var object
   */
  private $toolbox;

  /**
   * Setting from external services - StatHat.
   *
   * @var object
   */
  private $statHat;

  /**
   * Constructor for MBP_ExternalApplication_Dashboard
   *
   * @param array $credentials
   *   Connection credentials
   * @param array $settings
   *   Settings of additional services used by the class.
   */
  public function __construct($credentials, $settings) {

    $this->credentials = $credentials;
    $this->settings = $settings;

    $this->toolbox = new MB_Toolbox($settings);
    $this->statHat = new StatHat($settings['stathat_ez_key'], 'mbp-externalApplications-dashboard:');
    $this->statHat->setIsProduction($this->settings['use_stathat_tracking']);
  }

  /* 
   * Consumer entries in activityStatsQueue
   *
   * @param string $payload
   *   The contents of the message in a serial format
   */
  public function consumeQueue($payload) {
    echo '------- mbp-externalApplication-dashboard->consumeQueue() START: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

    $message = unserialize($payload->body);
    $activity = $message['activity'];

    if ($activity == 'user_register' || $activity == 'campaign_signup' || $activity == 'campaign_reportback') {
      $this->userEventBroadcast($message);
    }

    echo '------- mbc-externalApplication-users->consumeQueue() END: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
  }

  /**
   * Broadcast user events to dashboard application.
   *
   * @param array $message
   *   Details about the transaction for US based signups.
   */
  private function userEventBroadcast($jsonMessage) {

    $curlURL = $this->settings['ds_lobby_dashboard_host'] . '/services/message-broker';
    $this->toolbox->curlPOST($curlURL, $jsonMessage);

    $this->statHat->clearAddedStatNames();
    $this->statHat->addStatName('produceUserEvent');
    $this->statHat->reportCount(1);
  }

}
