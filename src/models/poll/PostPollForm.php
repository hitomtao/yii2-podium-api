<?php

declare(strict_types=1);

namespace bizley\podium\api\models\poll;

use bizley\podium\api\enums\PollChoice;
use bizley\podium\api\enums\PostType;
use bizley\podium\api\events\ModelEvent;
use bizley\podium\api\interfaces\CategorisedFormInterface;
use bizley\podium\api\interfaces\MembershipInterface;
use bizley\podium\api\interfaces\ModelInterface;
use bizley\podium\api\repos\PostRepo;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\Exception;

/**
 * Class PostPollForm
 * @package bizley\podium\api\models\poll
 *
 * @property PollForm $poll
 * @property PollAnswerForm[] $pollAnswers
 */
class PostPollForm extends PostRepo implements CategorisedFormInterface
{
    public const EVENT_BEFORE_CREATING = 'podium.poll.creating.before';
    public const EVENT_AFTER_CREATING = 'podium.poll.creating.after';
    public const EVENT_BEFORE_EDITING = 'podium.poll.editing.before';
    public const EVENT_AFTER_EDITING = 'podium.poll.editing.after';

    /**
     * @var string
     */
    public $question;

    /**
     * @var bool
     */
    public $revealed;

    /**
     * @var string
     */
    public $choice_id;

    /**
     * @var int
     */
    public $expires_at;

    /**
     * @var array
     */
    public $answers = [];

    /**
     * @return ActiveQuery
     */
    public function getPoll(): ActiveQuery
    {
        return $this->hasOne(PollForm::class, ['post_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getPollAnswers(): ActiveQuery
    {
        return $this->hasMany(PollAnswerForm::class, ['poll_id' => 'id'])->via('poll');
    }

    private $_oldAnswers = [];

    /**
     * @return array
     */
    public function getOldAnswers(): array
    {
        return $this->_oldAnswers;
    }

    /**
     * @param string $oldAnswer
     */
    public function addOldAnswer(string $oldAnswer): void
    {
        $this->_oldAnswers[] = $oldAnswer;
    }

    public function afterFind(): void
    {
        $poll = $this->poll;
        if ($poll !== null) {
            $this->question = $poll->question;
            $this->revealed = $poll->revelead;
            $this->choice_id = $poll->choice_id;
            $this->expires_at = $poll->expires_at;

            $answers = $this->pollAnswers;
            foreach ($answers as $pollAnswer) {
                $this->addOldAnswer($pollAnswer->answer);
                $this->answers[] = $pollAnswer->answer;
            }
        }

        parent::afterFind();
    }

    /**
     * @param MembershipInterface $author
     */
    public function setAuthor(MembershipInterface $author): void
    {
        $this->author_id = $author->getId();
    }

    /**
     * @param ModelInterface $thread
     */
    public function setThread(ModelInterface $thread): void
    {
        $this->setThreadModel($thread);
        $this->setForumModel($thread->getParent());

        $this->thread_id = $thread->getId();
        $this->forum_id = $this->getForumModel()->getId();
        $this->category_id = $this->getForumModel()->getParent()->getId();
    }

    private $_thread;

    /**
     * @param ModelInterface $thread
     */
    public function setThreadModel(ModelInterface $thread): void
    {
        $this->_thread = $thread;
    }

    /**
     * @return ModelInterface
     */
    public function getThreadModel(): ModelInterface
    {
        return $this->_thread;
    }

    private $_forum;

    /**
     * @param ModelInterface $forum
     */
    public function setForumModel(ModelInterface $forum): void
    {
        $this->_forum = $forum;
    }

    /**
     * @return ModelInterface
     */
    public function getForumModel(): ModelInterface
    {
        return $this->_forum;
    }

    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [
            'timestamp' => TimestampBehavior::class,
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['revealed'], 'default', 'value' => true],
            [['choice_id'], 'default', 'value' => PollChoice::SINGLE],
            [['question', 'revealed', 'choice_id', 'expires_at', 'answers'], 'required'],
            [['question'], 'string', 'min' => 3],
            [['revealed'], 'boolean'],
            [['choice_id'], 'in', 'range' => PollChoice::keys()],
            [['expires_at'], 'integer'],
            [['answers'], 'each', 'rule' => ['string', 'min' => 3]],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels(): array
    {
        return [
            'revealed' => Yii::t('podium.label', 'poll.revealed'),
            'choice_id' => Yii::t('podium.label', 'poll.choice.type'),
            'question' => Yii::t('podium.label', 'poll.question'),
            'expires_at' => Yii::t('podium.label', 'poll.expires'),
            'answers' => Yii::t('podium.label', 'poll.answers'),
        ];
    }

    /**
     * @param array|null $data
     * @return bool
     */
    public function loadData(?array $data = null): bool
    {
        return $this->load($data, '');
    }

    /**
     * @return bool
     */
    public function beforeCreate(): bool
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_CREATING, $event);

        return $event->canCreate;
    }

    /**
     * @return bool
     */
    public function create(): bool
    {
        if (!$this->beforeCreate()) {
            return false;
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$this->getThreadModel()->updateCounters(['posts_count' => 1])) {
                throw new Exception('Error while updating thread counters!');
            }
            if (!$this->getForumModel()->updateCounters(['posts_count' => 1])) {
                throw new Exception('Error while updating forum counters!');
            }

            $this->type_id = PostType::POLL;

            if (!$this->save()) {
                Yii::error(['Error while creating post for poll', $this->errors], 'podium');
                throw new Exception('Error while creating post for poll!');
            }

            $poll = new PollForm([
                'post_id' => $this->id,
                'question' => $this->question,
                'revelead' => $this->revealed,
                'choice_id' => $this->choice_id,
                'expires_at' => $this->expires_at,
            ]);
            if (!$poll->create()) {
                throw new Exception('Error while creating poll!');
            }

            foreach ($this->answers as $answer) {
                $pollAnswer = new PollAnswerForm([
                    'poll_id' => $poll->id,
                    'answer' => $answer,
                ]);
                if (!$pollAnswer->create()) {
                    throw new Exception('Error while creating poll answer!');
                }
            }

            $this->afterCreate();

            $transaction->commit();
            return true;

        } catch (\Throwable $exc) {
            Yii::error(['Exception while creating poll', $exc->getMessage(), $exc->getTraceAsString()], 'podium');
            try {
                $transaction->rollBack();
            } catch (\Throwable $excTrans) {
                Yii::error(['Exception while poll creating transaction rollback', $excTrans->getMessage(), $excTrans->getTraceAsString()], 'podium');
            }
        }
        return false;
    }

    public function afterCreate(): void
    {
        $this->trigger(self::EVENT_AFTER_CREATING, new ModelEvent([
            'model' => $this
        ]));
    }

    /**
     * @return bool
     */
    public function beforeEdit(): bool
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_EDITING, $event);

        return $event->canEdit;
    }

    /**
     * @return bool
     */
    public function edit(): bool
    {
        if (!$this->beforeEdit()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $this->edited = true;
            $this->edited_at = time();

            if (!$this->save()) {
                Yii::error(['Error while editing post for poll', $this->errors], 'podium');
                return false;
            }

            $poll = $this->poll;
            if ($poll === null) {
                throw new Exception('Poll does not exist!');
            }

            foreach (['question', 'revelead', 'choice_id', 'expires_at'] as $property) {
                if ($this->$property !== $poll->$property) {
                    $poll->$property = $this->$property;
                }
            }

            if (!$poll->edit()) {
                throw new Exception('Error while editing poll!');
            }

            $answersToAdd = array_diff($this->answers, $this->getOldAnswers());
            $answersToRemove = array_diff($this->getOldAnswers(), $this->answers);

            foreach ($answersToAdd as $answer) {
                $pollAnswer = new PollAnswerForm([
                    'poll_id' => $poll->id,
                    'answer' => $answer,
                ]);
                if (!$pollAnswer->create()) {
                    throw new Exception('Error while creating poll answer!');
                }
            }
            foreach ($answersToRemove as $answer) {
                $pollAnswer = new PollAnswerRemover([
                    'poll_id' => $poll->id,
                    'answer' => $answer,
                ]);
                if (!$pollAnswer->remove()) {
                    throw new Exception('Error while removing poll answer!');
                }
            }

            $this->afterEdit();
            return true;

        } catch (\Throwable $exc) {
            Yii::error(['Exception while editing poll', $exc->getMessage(), $exc->getTraceAsString()], 'podium');
            try {
                $transaction->rollBack();
            } catch (\Throwable $excTrans) {
                Yii::error(['Exception while poll editing transaction rollback', $excTrans->getMessage(), $excTrans->getTraceAsString()], 'podium');
            }
        }
        return false;
    }

    public function afterEdit(): void
    {
        $this->trigger(self::EVENT_AFTER_EDITING, new ModelEvent([
            'model' => $this
        ]));
    }

    /**
     * @param ModelInterface $category
     * @throws NotSupportedException
     */
    public function setCategory(ModelInterface $category): void
    {
        throw new NotSupportedException('Poll category can not be set directly.');
    }

    /**
     * @param ModelInterface $forum
     * @throws NotSupportedException
     */
    public function setForum(ModelInterface $forum): void
    {
        throw new NotSupportedException('Poll forum can not be set directly.');
    }
}