<?php
/**
 * JiraWebhookData converter of issues with priority 'Blocker' into a Slack Client Message Object
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

class JiraBlockerToSlackBotConverter implements JiraWebhookDataConverter
{
    /**
     * Converts $data into a formatted string message
     *
     * @param JiraWebhookData $data parsed data from JIRA
     *
     * @return string
     */
    public function convert(JiraWebhookData $data)
    {
        $issue        = $data->getIssue();
        $assigneeName = $issue->getAssignee()->getName();
        $comment      = $issue->getIssueComments()->getLastComment();

        /**
         * Issue doesn't have comments and is not assigned to a user
         */
        if (!$comment && !$assigneeName) {
            $message = vsprintf(
                ":no_entry_sign: <%s|%s> %s: %s ➠ Unassigned",
                [
                    $issue->getUrl(),
                    $issue->getKey(),
                    $issue->getStatus(),
                    $issue->getSummary()
                ]
            );

        /**
         * Issue is not assigned to a user, but has comments
         */    
        } elseif (!$assigneeName) {
            $message = vsprintf(
                ":no_entry_sign: <%s|%s> %s: %s ➠ Unassigned\n@%s ➠ %s",
                [
                    $issue->getUrl(),
                    $issue->getKey(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $comment->getAuthor()->getName(),
                    $comment->getBody()
                ]
            );

        /**
         * Issue doesn't have any comments, but is assigned
         */    
        } elseif (!$comment) {
            $message = vsprintf(
                ":no_entry_sign: <%s|%s> %s: %s ➠ @%s",
                [
                    $issue->getUrl(),
                    $issue->getKey(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $assigneeName
                ]
            );

        /**
         * Default message
         */    
        } else {
            $message = vsprintf(
                ":no_entry_sign: <%s|%s> %s: %s ➠ @%s\n@%s ➠ %s",
                [
                    $issue->getUrl(),
                    $issue->getKey(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $assigneeName,
                    $comment->getAuthor()->getName(),
                    $comment->getBody()
                ]
            );
        }

        return $message;
    }
}