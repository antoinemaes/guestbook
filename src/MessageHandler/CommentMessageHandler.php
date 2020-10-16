<?php

namespace App\MessageHandler;


use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Workflow\Registry;

use App\Message\CommentMessage;
use App\Notification\CommentApprovedNotification;
use App\Notification\CommentReviewNotification;
use App\Repository\CommentRepository;
use App\Service\ImageResizer;
use App\Service\SpamChecker;


class CommentMessageHandler implements MessageHandlerInterface
{
    private $spamChecker;
    private $entityManager;
    private $commentRepository;
    private $bus;
    private $workflowRegistry;
    private $notifier;
    private $resizer;
    private $logger;


    public function __construct(
        EntityManagerInterface $entityManager,
        SpamChecker $spamChecker,
        CommentRepository $commentRepository,
        MessageBusInterface $bus,
        Registry $workflowRegistry,
        NotifierInterface $notifier,
        ImageResizer $resizer,
        LoggerInterface $logger = null)
    {
        $this->entityManager = $entityManager;
        $this->spamChecker = $spamChecker;
        $this->commentRepository = $commentRepository;
        $this->bus = $bus;
        $this->workflowRegistry = $workflowRegistry;
        $this->notifier = $notifier;
        $this->resizer = $resizer;
        $this->logger = $logger;
    }

    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());
        if (!$comment) {
            return;
        }
        $workflow = $this->workflowRegistry->get($comment);

        if ($workflow->can($comment, 'accept')) {

            $score = $this->spamChecker->getSpamScore($comment, $message->getContext());

            switch($score) {
                case 0: $transition = 'accept'; break;
                case 1: $transition = 'might_be_spam'; break;
                case 2: $transition = 'reject_spam'; break;
            }

            $workflow->apply($comment, $transition);
            $this->entityManager->flush();
            $this->bus->dispatch($message);

        } elseif ($workflow->can($comment, 'publish')
            || $workflow->can($comment, 'publish_ham')) {

            $this->notifier->send(
                new CommentReviewNotification($comment),
                ...$this->notifier->getAdminRecipients());

        } elseif ($workflow->can($comment, 'optimize')) {

            if($photo = $comment->getPhotofileName())
                $this->resizer->resize($photo);

            $workflow->apply($comment, 'optimize');
            $this->entityManager->flush();
            $this->bus->dispatch($message);

            $this->notifier->send(
                new CommentApprovedNotification($comment), 
                new Recipient($comment->getEmail()));

        } elseif ($this->logger) {

            $this->logger->debug('Dropping comment message',
                ['comment' => $comment->getId(), 'state' => $comment->getState()]);

        }
    }
}
