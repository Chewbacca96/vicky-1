<?php
/**
 * An example index file for the main module of vicky project, that receives data from JIRA webhook
 * https://developer.atlassian.com/jiradev/jira-apis/webhooks), contains jiraWebhook listeners for events, that sends
 * messages to slack by slack client and contains converters declaration.
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that is distributed with this source code.
 */
namespace Vicky;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Maknz\Slack\Client;

use Vicky\src\modules\Jira\JiraDefaultToSlackBotConverter;
use Vicky\src\modules\Slack\SlackBotSender;
use Vicky\src\modules\Slack\SlackMessageSender;
use Vicky\src\modules\Vicky;

use JiraWebhook\JiraWebhook;
use JiraWebhook\Models\JiraWebhookData;
use JiraWebhook\Exceptions\JiraWebhookException;

require __DIR__ . '/vendor/autoload.php';
$config = require '/etc/vicky/config.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
date_default_timezone_set($config['timeZone']);

$log = new Logger('vicky');
$log->pushHandler(
    new StreamHandler(
        $config['error_log'],
        $config['loggerDebugLevel'] ? Logger::DEBUG : Logger::ERROR
    )
);
if ($config['environment'] === 'local'){
    $log->pushHandler(new StreamHandler('php://output', Logger::DEBUG)); // <<< uses a stream
}

$start = microtime(true);

$log->info("The script ".__FILE__." started.");

SlackBotSender::getInstance(
    $config['slackBot']['url'],
    $config['slackBot']['auth'],
    $config['slackBot']['timeout']
);

new Vicky($config);
$jiraWebhook = new JiraWebhook();

/**
 * Set the converters Vicky will use to "translate" JIRA webhook
 * payload into formatted, human readable Slack messages
 */
JiraWebhook::setConverter('JiraDefaultToSlack', new JiraDefaultToSlackBotConverter());

/*
|--------------------------------------------------------------------------
| Register default listeners
|--------------------------------------------------------------------------
|
| These are just a few default listeners that would make sense for most teams.
| To add your own you should follow instructions in the README.md file
|
*/

/**
 * Send a message to the project's channel when a blocker issue is created or updated
 */
$jiraWebhook->addListener('*', function($e, JiraWebhookData $data)
{
    // Catch all events
});

/**
 * Send message to user if a newly created issue
 * was assigned to them
 */
$jiraWebhook->addListener('jira:issue_created', function ($e, JiraWebhookData $data)
{
    $assignee = $data->getIssue()->getAssignee();

    if ($assignee->getName()) {
        $slackClientMessage = SlackMessageSender::getMessage();
        JiraWebhook::convert('JiraDefaultToSlack', $data, $slackClientMessage);
        $slackClientMessage->to('@' . $assignee->getName());
        $slackClientMessage->send();
    }
});

/**
 * Send message to user's channel if an issue gets assigned to them
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, JiraWebhookData $data)
{
    $issue = $data->getIssue();

    if ($data->isIssueAssigned()) {
        $data->overrideIssueEventDescription("An issue has been assigned to you");
        $slackClientMessage = SlackMessageSender::getMessage();
        JiraWebhook::convert('JiraDefaultToSlack', $data, $slackClientMessage);
        $slackClientMessage->to('@' . $issue->getAssignee()->getName());
        $slackClientMessage->send();
    }
});

/**
 * Send message to user's channel if someone comments on an issue
 * assigned to them
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, JiraWebhookData $data)
{
    $issue = $data->getIssue();

    if ($data->isIssueCommented()) {
        $data->overrideIssueEventDescription("A new comment has been posted on your issue");
        $slackClientMessage = SlackMessageSender::getMessage();
        JiraWebhook::convert('JiraDefaultToSlack', $data, $slackClientMessage);
        $slackClientMessage->to('@' . $issue->getAssignee()->getName());
        $slackClientMessage->send();
    }
});

/**
 * Send message to user's channel if someone mentions them in a new comment
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, JiraWebhookData $data)
{
    $issue = $data->getIssue();

    if ($data->isIssueCommented()) {
        $users = $issue->getIssueComments()->getLastComment()->getMentionedUsersNicknames();
        $data->overrideIssueEventDescription("You've been mentioned in a comment");

        foreach ($users as $user) {
            $slackClientMessage = SlackMessageSender::getMessage();
            JiraWebhook::convert('JiraDefaultToSlack', $data, $slackClientMessage);
            $slackClientMessage->to('@' . $user);
            $slackClientMessage->send();
        }
    }
});


/*
|--------------------------------------------------------------------------
| Custom listeners
|--------------------------------------------------------------------------
| ADD YOUR CUSTOM LISTENERS HERE
|
*/


/*
 * ------------------------------------------------------------------------
 * ------------------------------------------------------------------------
 */



try {
    /**
     * Get raw data from JIRA webhook
     */
    $f = fopen('php://input', 'r');
    $data = stream_get_contents($f);

    if (!$data) {
        $log->error('There is no data in the Jira webhook');
        throw new JiraWebhookException('There is no data in the Jira webhook');
    }

    $jiraWebhook->run($data);
} catch (\Exception $e) {
    $log->error($e->getMessage());
    $log->error($e->getLine());
    $log->error($e->getFile());
    $log->error($e->getCode());
}

$log->info("Script finished in ".(microtime(true) - $start)." sec.");
