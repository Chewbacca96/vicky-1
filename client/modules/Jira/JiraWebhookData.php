<?php
namespace Vicky\client\modules\Jira;

class JiraWebhookData
{
    private $rawData;
    
    private $number;
    private $URL;
    private $status;
    private $summary;
    private $assignee;
    private $lastCommenterID;
    private $lastComment;
    
    private $priority;
    private $issueType;
    private $webhookEvent;

    private $issueEvent;
    
    public static function parseWebhookData($data = null)
    {
        $webhookData = new self;
        
        if ($data === null) {
            return $webhookData;
        }
        
        $webhookData->setRawData($data);
        
        $issueFields = $data['issue']['fields'];

        $webhookData->setNumber($data['issue']['key']);
        $webhookData->setURL($data['issue']['self']);
        $webhookData->setStatus($issueFields['status']['name']);
        $webhookData->setSummary($issueFields['summary']);
        $webhookData->setAssignee($issueFields['assignee']['name']);
        
        $lastComment = array_pop($issueFields['comment']['comments']);
        
        $webhookData->setLastCommenterID($lastComment['author']['name']);
        $webhookData->setLastComment($lastComment['body']);

        $webhookData->setPriority($issueFields['priority']['name']);
        $webhookData->setIssueType($issueFields['issuetype']['name']);
        $webhookData->setWebhookEvent($data['webhookEvent']);

        $webhookData->setIssueEvent($data['issue_event_type_name']);

        return $webhookData;
    }
    
    public function isPriorityBlocker()
    {
        return $this->priority === 'Blocker';
    }
    
    public function isTypeOprations()
    {
        return $this->issueType === 'Operations';
    }

    public function isTypeUrgentBug()
    {
        return $this->issueType === 'Urgent bug';
    }
    
    public function isAssignee()
    {
        return ($this->assignee) ? true : false;
    }

    /**************************************************/

    public function setRawData($rawData)
    {
        $this->rawData = $rawData;
    }
    
    public function setNumber($number)
    {
        $this->number = $number;
    }

    public function setURL($URL)
    {
        $this->URL = $URL;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setSummary($summary)
    {
        $this->summary = $summary;
    }

    public function setAssignee($assignee)
    {
        $this->assignee = $assignee;
    }

    public function setLastCommenterID($lastCommenterID)
    {
        $this->lastCommenterID = $lastCommenterID;
    }

    public function setLastComment($lastComment)
    {
        $this->lastComment = $lastComment;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function setIssueType($issueType)
    {
        $this->issuetype = $issueType;
    }

    public function setWebhookEvent($webhookEvent)
    {
        $this->webhookEvent = $webhookEvent;
    }

    public function setIssueEvent($issueEvent)
    {
        $this->issueEvent = $issueEvent;
    }

    /**************************************************/

    public function getRawData()
    {
        return $this->rawData;
    }
    
    public function getNumber()
    {
        return $this->number;
    }

    public function getURL()
    {
        return $this->URL;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getSummary()
    {
        return $this->summary;
    }

    public function getAssignee()
    {
        return $this->assignee;
    }

    public function getLastCommenterID()
    {
        return $this->lastCommenterID;
    }

    public function getLastComment()
    {
        return $this->lastComment;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function getIssueType()
    {
        return $this->issueType;
    }

    public function getWebhookEvent()
    {
        return $this->webhookEvent;
    }
    
    public function getIssueEvent()
    {
        return $this->issueEvent;
    }
}