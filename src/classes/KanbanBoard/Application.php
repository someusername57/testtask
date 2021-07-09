<?php
namespace KanbanBoard;

use Github\Client;
use vierbergenlars\SemVer\version;

use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\SemVerException;
use \Michelf\Markdown;

class Application {

    public function __construct($github, $repositories, $paused_labels = array())
    {
        $this->github = $github;
        $this->repositories = $repositories;
        $this->paused_labels = $paused_labels;
    }

    public function board()
    {
        $milestones_github = array();
        $milestones = array();
        foreach ($this->repositories as $repository) {
            foreach ($this->github->milestones($repository) as $data) {
                $data['repository'] = $repository;
                $milestones_github[$data['title']] = $data;
            }
        }
        ksort($milestones_github);
        foreach ($milestones_github as $name => $data) {
            $issues = $this->issues($data['repository'], $data['number']);
            $percent = self::percent($data['closed_issues'], $data['open_issues']);
            if ($percent) {
                $milestones[] = array(
                    'milestone' => $name,
                    'url'       => $data['html_url'],
                    'progress'  => $percent,
                    'queued'    => $issues['queued'],
                    'active'    => $issues['active'],
                    'completed' => $issues['completed']
                );
            }
        }
        return $milestones;
    }

    private function issues($repository, $milestone_id)
    {
        $issues = array(
            'active'    => array(),
            'queued'    => array(),
            'completed' => array()
        );
        $issues_github = $this->github->issues($repository, $milestone_id);
        
        foreach ($issues_github as $issue_item) {
            if (isset($issue_item['pull_request'])) {
                continue;
            }
            
            $issues[self::state($issue_item)][] = array(
                'id'        => $issue_item['id'],
                'number'    => $issue_item['number'],
                'title'     => $issue_item['title'],
                'body'      => Markdown::defaultTransform($issue_item['body']),
                'url'       => $issue_item['html_url'],
                'assignee'  => self::assignee($issue_item),
                'paused'    => self::labels_match($issue_item, $this->paused_labels),
                'progress'  => self::percent(
                                    substr_count(strtolower($issue_item['body']), '[x]'),
                                    substr_count(strtolower($issue_item['body']), '[ ]')
                                ),
                'closed'    => $issue_item['closed_at']
            );
        }
        
        usort($issues['active'], function ($a, $b) {
            return (count($a['paused']) - count($b['paused']) === 0) ? strcmp($a['title'], $b['title']) : count($a['paused']) - count($b['paused']);
        });
        return $issues;
    }

    private static function state($issue)
    {
        if ($issue['state'] === 'closed') {
            return 'completed';
        } elseif (Utilities::hasValue($issue, 'assignee') && count($issue['assignee']) > 0){
            return 'active';
        } else {
            return 'queued';
        }
    }
    
    private static function assignee($issue)
    {
        if (is_array($issue)
            && array_key_exists('assignee', $issue)
            && !empty($issue['assignee'])
        ) {
            return $issue['assignee']['avatar_url'].'?s=16';
        } else {
            return null;
        }
    }
    
    private static function labels_match($issue, $needles)
    {
        if (Utilities::hasValue($issue, 'labels')) {
            foreach ($issue['labels'] as $label) {
                if (in_array($label['name'], $needles)) {
                    return array($label['name']);
                }
            }
        }
        return array();
    }

    private static function percent($complete, $remaining)
    {
        $total = $complete + $remaining;
        if($total > 0)
        {
            $percent = ($complete OR $remaining) ? round($complete / $total * 100) : 0;
            return array(
                'total' => $total,
                'complete' => $complete,
                'remaining' => $remaining,
                'percent' => $percent
            );
        }
        return array();
    }
}
