<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Notification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function sendEmailNotification(Request $request, MailerInterface $mailer)
    {
        $data = json_decode($request->getContent(), true);

        $emailRecipient = $data['emailRecipient'] ?? null;
        $subject = $data['subject'] ?? 'Notification';
        $message = $data['message'] ?? 'This is a notification message.';
        $type = $data['type'] ?? 'email';

        if (!$emailRecipient) {
            return new JsonResponse(['error' => 'Email recipient is required.'], 400);
        }

        $email = (new Email())
            ->from('your_email@example.com') 
            ->to($emailRecipient)
            ->subject($subject)
            ->text($message);

        $mailer->send($email);

        $notification = new Notification();
        $notification->setType($type);
        $notification->setEmailRecipient($emailRecipient);
        $notification->setSubject($subject);
        $notification->setMessage($message);
        $notification->setCreatedAt(new \DateTime());

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Email sent and notification saved.']);
    }
}
