<?php
/**
 * mbc-user-subscriptions.php
 *
 * Consume queue entries in ?? Queue to process messages from MailChimp webhooks
 * triggered by user subscriptions.
 */

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MB_Toolbox\MB_Configuration;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/messagebroker-config/mb-secure-config.inc';
require_once __DIR__ . '/MBP_ExternalApplication_Dashboard.class.inc';

// Settings
$credentials = array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
);
$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
  'use_stathat_tracking' => getenv('USE_STAT_TRACKING'),
  'ds_lobby_dashboard_host' => getenv('DS_LOBBY_DASHBOARD_HOST'),
);

$config = array();
$source = __DIR__ . '/messagebroker-config/mb_config.json';
$mb_config = new MB_Configuration($source, $settings);
$transactionalExchange = $mb_config->exchangeSettings('transactionalExchange');

$config['exchange'] = array(
  'name' => $transactionalExchange->name,
  'type' => $transactionalExchange->type,
  'passive' => $transactionalExchange->passive,
  'durable' => $transactionalExchange->durable,
  'auto_delete' => $transactionalExchange->auto_delete,
);
foreach($transactionalExchange->queues->activityStatsQueue->binding_patterns as $bindingPattern) {
  $config['queue'][] = array(
   'name' => $transactionalExchange->queues->activityStatsQueue->name,
   'passive' => $transactionalExchange->queues->activityStatsQueue->passive,
   'durable' => $transactionalExchange->queues->activityStatsQueue->durable,
   'exclusive' => $transactionalExchange->queues->activityStatsQueue->exclusive,
   'auto_delete' => $transactionalExchange->queues->activityStatsQueue->auto_delete,
   'bindingKey' => $bindingPattern,
 );
}


echo '------- mbp-externalApplications-dashboard START: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBP_ExternalApplication_Dashboard($mb, $settings), 'consumeQueue'));

echo '------- mbp-externalApplications-dashboard END: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
