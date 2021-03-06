<?php namespace ShahiemSeymor\Poll\Components;

use App;
use DB;
use Cms\Classes\CmsPropertyHelper;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Validator;
use Symfony\Component\HttpFoundation\Request;
//use October\Rain\Support\ValidationException;
use October\Rain\Exception\ValidationException;
use ShahiemSeymor\Poll\Models\Polls as Poll;
use ShahiemSeymor\Poll\Models\Vote as Votes;
use ShahiemSeymor\Poll\Models\Settings as Settings;

class Vote extends ComponentBase
{
    public $lastestPoll;
    public $lastestPollAnswers;
    public $checkIfVote;
    public $lastVote;
    public $showResult;
    public $request;
    public $vote;
    public $property;
    public $poll_id;

    public function componentDetails()
    {
        return [
            'name'        => 'Poll',
            'description' => 'Adds a poll form.'
        ];
    }

    public function defineProperties()
    {
        return [
            'poll' => [
                'title'       => 'Poll',
                'description' => 'Poll question to display',
                'type'        => 'dropdown'
            ],
        ];
    }

    public function getPollOptions()
    {
        return array_add(Poll::all()->lists('question', 'id'), '', '-none-');
    }

    public function onRun()
    {
        $this->addCss('/plugins/shahiemseymor/poll/assets/css/poll.css');
        $this->addJs('/plugins/shahiemseymor/poll/assets/js/poll.js');

        $this->request = Request::createFromGlobals();
        $this->vote = $this->page['vote'] = new Votes;
        $this->vote = $this->page['barColor'] = Settings::get('poll_settings');
        $this->vote = $this->page['showResult'] = Settings::get('show_result');
        $this->vote = $this->page['alertText'] = Settings::get('alert_text');
    }

    public function onRender()
    {
        $this->lastestPoll = $this->page['lastestPoll'] = Poll::getLatestPoll(($this->property('poll') == 0 ? Poll::getLatestPollId() : $this->property('poll')));
        $this->lastestPollAnswers = $this->page['lastestPollAnswers'] = Poll::getLatestPollAnswers(($this->property('poll') == 0 ? Poll::getLatestPollId() : $this->property('poll')));
        $this->checkIfVote = $this->page['checkIfVote'] = Votes::checkIfVote($this->request->getClientIp(), ($this->property('poll') == 0 ? Poll::getLatestPollId() : $this->property('poll')));
        $this->lastVote = $this->page['lastVote'] = Votes::lastVote($this->request->getClientIp(), ($this->property('poll') == 0 ? Poll::getLatestPollId() : $this->property('poll')));
    }

    public function onPoll()
    {
        $this->request = Request::createFromGlobals();
        $rules = ['vote_answer' => 'required'];
        $validation = Validator::make(post(), $rules);

        if ($validation->fails())
        {
            throw new ValidationException($validation);
        }
        else
        {   
            $poll_id = (post('id') == 0 ? Poll::getLatestPollId() : post('id'));
            if( Settings::get('allow_duplicate_ip') ) {
                $addVote = new Votes;
            }else{
                Votes::unguard();
                $addVote = Votes::firstOrCreate( ['ip' => $this->request->getClientIp(), 'poll_id' => $poll_id ] );
            }
            $addVote->ip =  $this->request->getClientIp();
            $addVote->poll_id = $poll_id;
            $addVote->answer_id = \Input::get('vote_answer');
            $addVote->save();

            $this->page['vote'] = new Votes;
            $this->lastestPoll = $this->page['lastestPoll'] = Poll::getLatestPoll($poll_id);
            $this->lastestPollAnswers = $this->page['lastestPollAnswers'] = Poll::getLatestPollAnswers($poll_id);
            $this->vote = $this->page['barColor'] = Settings::get('poll_settings');
        }
    }
}