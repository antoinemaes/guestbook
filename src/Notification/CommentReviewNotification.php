<?php

namespace App\Notification;

use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;

use App\Entity\Comment;


class CommentReviewNotification extends Notification implements EmailNotificationInterface
{
    private $comment;
    private $logger;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;

        parent::__construct('New comment posted');
        $this->importance(Notification::IMPORTANCE_MEDIUM);
    }

    public function asEmailMessage(Recipient $recipient, string $transport = null): ?EmailMessage
    {
        $message = EmailMessage::fromNotification($this, $recipient, $transport);
        $message->getMessage()
            ->htmlTemplate('emails/review_notification.html.twig')
            ->context([
                'comment' => $this->comment,
                ])
        ;

        return $message;
    }
}