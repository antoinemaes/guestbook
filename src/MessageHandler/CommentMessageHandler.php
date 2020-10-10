<?php

namespace App\MessageHandler;


use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Workflow\Registry;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Service\SpamChecker;


class CommentMessageHandler implements MessageHandlerInterface
{
    private $spamChecker;
    private $entityManager;
    private $commentRepository;
    private $bus;
    private $workflowRegistry;
    private $mailer;
    private $adminEmail;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager, 
        SpamChecker $spamChecker, 
        CommentRepository $commentRepository,
        MessageBusInterface $bus,
        Registry $workflowRegistry,
        MailerInterface $mailer,
        string $adminEmail,
        LoggerInterface $logger = null)
    {
        $this->entityManager = $entityManager;
        $this->spamChecker = $spamChecker;
        $this->commentRepository = $commentRepository;
        $this->bus = $bus;
        $this->workflowRegistry = $workflowRegistry;
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
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
        
            $this->mailer->send(
                (new NotificationEmail())
                    ->subject('New comment posted')
                    ->htmlTemplate('emails/comment_notification.html.twig')
                    ->from($this->adminEmail)
                    ->to($this->adminEmail)
                    ->context(['comment' => $comment]));

        } elseif ($this->logger) {

            $this->logger->debug('Dropping comment message', 
                ['comment' => $comment->getId(), 'state' => $comment->getState()]);
       
        }
    }
}