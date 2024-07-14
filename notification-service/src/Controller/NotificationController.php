<?php

// src/Controller/NotificationController.php

namespace App\Controller;

use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationController extends AbstractController
{
    /**
     * @Route("/api/send-notification", name="send_notification", methods={"POST"})
     */
    public function sendNotification(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): JsonResponse
    {
        // Récupérer les données JSON de la requête
        $data = json_decode($request->getContent(), true);

        // Vérifier si les données nécessaires sont présentes
        if (!isset($data['email_recipient'], $data['sujet'], $data['message'])) {
            return new JsonResponse(['error' => 'Manque des données requises'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Créer une nouvelle instance de Notification
        $notification = new Notification();
        $notification->setEmailRecipient($data['email_recipient']);
        $notification->setSujet($data['sujet']);
        $notification->setMessage($data['message']);

        // Persister l'entité Notification en base de données
        $entityManager->persist($notification);
        $entityManager->flush();

        // Envoyer l'email
        try {
            $email = (new Email())
                ->from('groupe5esgi3@gmail.com') // L'adresse email expéditeur
                ->to($data['email_recipient']) // L'adresse email destinataire
                ->subject($data['sujet']) // Le sujet de l'email
                ->text($data['message']); // Le contenu textuel de l'email

            $mailer->send($email);

            // Retourner une réponse JSON
            return new JsonResponse(['status' => 'Notification crée et email envoyé'], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'non envoi de l\'email. ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

