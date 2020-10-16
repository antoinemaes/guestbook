<?php

namespace App\Notification;

use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;

use App\Entity\Comment;

class CommentApprovedNotification extends Notification implements EmailNotificationInterface
{
    private $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;

        parent::__construct($comment->getConference().' : comment approved.');
        $this->importance(Notification::IMPORTANCE_MEDIUM);
    }

    public function asEmailMessage(Recipient $recipient, string $transport = null): ?EmailMessage
    {
        $message = EmailMessage::fromNotification($this, $recipient, $transport);
        switch($this->comment->getUserLocale())
        {
            case 'fr':
                $template = 'emails/approved/fr.html.twig';
                break;
            case 'en':
            default:
                $template = 'emails/approved/en.html.twig';
                break;
        }
        $message->getMessage()
            ->htmlTemplate($template)
            ->context(['comment' => $this->comment])
        ;

        return $message;
    }
}
