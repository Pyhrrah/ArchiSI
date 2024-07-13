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

    /**
     * @Route("/api/notification/send-email", name="send_email_notification", methods={"POST"})
     */
    public function sendEmailNotification(Request $request, MailerInterface $mailer)
    {
        // Récupérer les données du corps de la requête JSON
        $data = json_decode($request->getContent(), true);

        // Valider et récupérer les détails de l'email
        $emailRecipient = $data['emailRecipient'] ?? null;
        $subject = $data['subject'] ?? 'Notification';
        $message = $data['message'] ?? 'This is a notification message.';
        $type = $data['type'] ?? 'email'; // Vous pouvez définir une valeur par défaut ou gérer différemment

        // Vérifier si l'email du destinataire est présent
        if (!$emailRecipient) {
            return new JsonResponse(['error' => 'Email recipient is required.'], 400);
        }

        // Envoyer l'email
        $email = (new Email())
            ->from('your_email@example.com') // Remplacer par votre adresse email
            ->to($emailRecipient)
            ->subject($subject)
            ->text($message);

        $mailer->send($email);

        // Enregistrer les détails de l'email dans la base de données
        $notification = new Notification();
        $notification->setType($type);
        $notification->setEmailRecipient($emailRecipient);
        $notification->setSubject($subject);
        $notification->setMessage($message);
        $notification->setCreatedAt(new \DateTime());

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        // Réponse JSON en cas de succès
        return new JsonResponse(['message' => 'Email sent and notification saved.']);
    }
}
