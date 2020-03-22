<?php

namespace App\Manager;

use App\Entity\Answer;
use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Repository\MessageRepository;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MessageManager
{
    const DEPLOY_GRACE = 120; /* 2 mins */

    /**
     * @var AnswerManager
     */
    private $answerManager;

    /**
     * @var MessageRepository
     */
    private $messageRepository;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param AnswerManager         $answerManager
     * @param MessageRepository     $messageRepository
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(AnswerManager $answerManager,
        MessageRepository $messageRepository,
        TokenStorageInterface $tokenStorage)
    {
        $this->answerManager     = $answerManager;
        $this->messageRepository = $messageRepository;
        $this->tokenStorage      = $tokenStorage;
    }

    /**
     * @return string
     */
    public function generateCode(): string
    {
        return $this->messageRepository->generateCode();
    }

    /**
     * @param int $messageId
     *
     * @return Message|null
     */
    public function find(int $messageId): ?Message
    {
        return $this->messageRepository->find($messageId);
    }

    /**
     * @param Campaign $campaign
     *
     * @return int
     */
    public function getNumberOfSentMessages(Campaign $campaign): int
    {
        return $this->messageRepository->getNumberOfSentMessages($campaign);
    }

    /**
     * @param Volunteer $volunteer
     *
     * @return string
     */
    public function generatePrefix(Volunteer $volunteer): string
    {
        $prefix = 'A';

        do {
            $message = $this->messageRepository->getMessageFromVolunteer($volunteer, $prefix);
            if (!$message) {
                break;
            }

            $prefix++;
        } while (true);

        return $prefix;
    }

    /**
     * @param string $phoneNumber
     * @param string $body
     *
     * @return int|null
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function handleAnswer(string $phoneNumber, string $body): ?int
    {
        // In case of multiple calls, we should handle the "A1 B2" body case.
        $messages = [];
        foreach (explode(' ', $body) as $word) {
            $matches = [];
            preg_match('/^([a-zA-Z]+)(\d)/', $word, $matches);
            if (3 === count($matches)) {
                $message = $this->getMessageFromPhoneNumber($phoneNumber, $word);
                if (!array_key_exists($message->getId(), $messages)) {
                    $messages[$message->getId()] = $message;
                }
            }
        }

        // Answer is invalid, we seek for latest active campaign for the phone number
        if (!$messages) {
            $message = $this->getMessageFromPhoneNumber($phoneNumber, $word);
            if ($message) {
                $messages[] = $message;
            }
        }

        // A better way would be to add a @ManyToMany on Answer<->Message entities,
        // but answers are currently tied too much on their communications.
        foreach ($messages as $message) {
            if (Communication::TYPE_SMS === $message->getCommunication()->getType()) {
                $this->addAnswer($message, $body);
            }
        }

        if (!$messages) {
            return null;
        }

        return reset($messages)->getId();
    }

    /**
     * @param string $phoneNumber
     * @param string $body
     *
     * @return Message|null
     *
     * @throws NonUniqueResultException
     */
    public function getMessageFromPhoneNumber(string $phoneNumber, string $body): ?Message
    {
        $matches = [];
        preg_match('/^([a-zA-Z]+)(\d)/', $body, $matches);

        // Prefix not found, getting the latest message sent to volunteer on active campaigns
        if (3 === count($matches)) {
            $prefix = strtoupper($matches[1]);

            $message = $this->messageRepository->getMessageFromPhoneNumberAndPrefix($phoneNumber, $prefix);

            if ($message) {
                return $message;
            }
        }

        return $this->messageRepository->getMessageFromPhoneNumber($phoneNumber);
    }

    /**
     * @param Message $message
     * @param string  $body
     * @param bool    $byAdmin
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addAnswer(Message $message, string $body, bool $byAdmin = false): void
    {
        $choices = [];
        if (0 !== count($message->getCommunication()->getChoices())) {
            // Get all valid choices in message
            if ($multipleChoice = $message->getCommunication()->isMultipleAnswer()) {
                $choices = $message->getCommunication()->getAllChoicesInText($message->getPrefix(), $body);
            } else {
                $choices = [];
                if ($choice = $message->getCommunication()->getChoiceByCode($message->getPrefix(), $body)) {
                    $choices[] = $choice;
                }
            }

            if (!$multipleChoice) {
                // If no multiple answers are allowed, clearing up all previous answers
                $this->answerManager->clearAnswers($message);
            } else {
                // If mulitple answers allowed, we'll only keep the last duplicate
                $this->answerManager->clearChoices($message, $choices);
            }
        }

        // Storing the new answer
        $answer = new Answer();
        $message->addAnswser($answer);
        $answer->setMessage($message);
        $answer->setRaw($body);
        $answer->setReceivedAt(new DateTime());
        $answer->setUnclear($message->getCommunication()->isUnclear($message->getPrefix(), $body));

        if ($byAdmin) {
            $answer->setByAdmin($this->tokenStorage->getToken()->getUsername());
        }

        foreach ($choices as $choice) {
            $answer->addChoice($choice);
        }

        $this->answerManager->save($answer);
        $this->messageRepository->save($message);
    }

    /**
     * @param Message $message
     * @param Choice  $choice
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function toggleAnswer(Message $message, Choice $choice)
    {
        // If choice currently selected, remove it
        if ($answer = $message->getAnswerByChoice($choice)) {
            $answer->getChoices()->removeElement($choice);
            $this->answerManager->save($answer);

            return;
        }

        $this->addAnswer($message, sprintf('%s%d', $message->getPrefix(), $choice->getCode()), true);
    }

    /**
     * @param Message $message
     * @param Choice  $choice
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function cancelAnswerByChoice(Message $message, Choice $choice): void
    {
        $this->messageRepository->cancelAnswerByChoice($message, $choice);
    }

    /**
     * @param array $volunteersTakenPrefixes
     *
     * @return bool
     */
    public function canUsePrefixesForEveryone(array $volunteersTakenPrefixes): bool
    {
        return $this->messageRepository->canUsePrefixesForEveryone($volunteersTakenPrefixes);
    }

    /**
     * @param Message $message
     */
    public function save(Message $message)
    {
        $this->messageRepository->save($message);
    }

    /**
     * Returns true whether is it possible to deploy, if
     * last message was sent less than N seconds ago,
     * we consider that the activity is too high.
     *
     * This method is subject to race conditions, if
     * a user launches a trigger during the deployment
     * time.
     *
     * @return int
     */
    public function getDeployGreenlight(): int
    {
        /** @var Message $message */
        $message = $this->messageRepository->getLatestMessageUpdated();

        if (!$message) {
            return true;
        }

        $diff = time() - $message->getUpdatedAt()->getTimestamp();
        if ($diff > self::DEPLOY_GRACE) {
            return 0;
        }

        return self::DEPLOY_GRACE - $diff;
    }
}