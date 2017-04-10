<?php
/**
 * JiraWebhookData converter of assigned issues into a Slack Client Message Object
 *
 * @credits https://github.com/kommuna
 * @author  Chewbacca chewbacca@devadmin.com
 * @author  Miss Lv lv@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vicky\src\modules\Jira;

use JiraWebhook\JiraWebhookDataConverter;
use JiraWebhook\Models\JiraWebhookData;
use Maknz\Slack\Message;

class JiraToAssigneeConverter implements JiraWebhookDataConverter
{
    /**
     * Converts $data into a formatted Slack Client Message Object
     *
     * @param JiraWebhookData $data          parsed data from JIRA
     * @param Message         $clientMessage slack Client Message Object
     *
     * @return Message
     */
    public function convert(JiraWebhookData $data, Message $clientMessage)
    {
        $issue        = $data->getIssue();
        $comment      = $issue->getIssueComments()->getLastComment();
        $typeIcon     = '';

        if ($issue->isPriorityBlocker()) {
            $typeIcon = ":no_entry_sign:";
        } elseif ($issue->isTypeOperations()) {
            $typeIcon = "⚙";
        } elseif ($issue->isTypeUrgentBug()) {
            $typeIcon = "⚡";
        }

        /**
         * Issue doesn't have any comments, but is assigned
         */
        if (!$comment) {
            $message = vsprintf(
                "Assigned to you: {$typeIcon} <%s|%s> %s: %s",
                [
                    $issue->getUrl(),
                    $issue->getKey(),
                    $issue->getStatus(),
                    $issue->getSummary()
                ]
            );

        /**
         * Default message
         */
        } else {
            $message = vsprintf(
                "Assigned to you: {$typeIcon} <%s|%s> %s: %s\n@%s ➠ %s",
                [
                    $issue->getUrl(),
                    $issue->getKey(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $comment->getAuthor()->getName(),
                    $comment->getBody()
                ]
            );
        }

        $clientMessage->attach($message);

        return $clientMessage;
    }
}