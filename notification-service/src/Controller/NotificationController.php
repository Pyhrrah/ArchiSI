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
use Psr\Log\LoggerInterface;

class NotificationController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/api/send-notification", name="send_notification", methods={"POST"})
     */
    public function sendNotification(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): JsonResponse
    {
        $this->logger->info('Entrée dans sendNotification');

        // Récupérer les données JSON de la requête
        $data = json_decode($request->getContent(), true);
        $this->logger->info('Données reçues : ' . json_encode($data));

        // Vérifier si les données nécessaires sont présentes
        if (!isset($data['email_recipient'], $data['sujet'], $data['message'])) {
            $this->logger->error('Manque des données requises');
            return new JsonResponse(['error' => 'Manque des données requises'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Créer une nouvelle instance de Notification
        $notification = new Notification();
        $notification->setEmailRecipient($data['email_recipient']);
        $notification->setSujet($data['sujet']);
        $notification->setMessage($data['message']);

        // Persister l'entité Notification en base de données
        try {
            $entityManager->persist($notification);
            $entityManager->flush();
            $this->logger->info('Notification persistée en base de données');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la persistance de la notification : ' . $e->getMessage());
            return new JsonResponse(['error' => 'Erreur lors de la persistance de la notification'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Envoyer l'email
        try {
            $email = (new Email())
                ->from('groupe5esgi3@gmail.com') // L'adresse email expéditeur
                ->to($data['email_recipient']) // L'adresse email destinataire
                ->subject($data['sujet']) // Le sujet de l'email
                ->text($data['message']); // Le contenu textuel de l'email

            $mailer->send($email);
            $this->logger->info('Email envoyé');

            // Retourner une réponse JSON
            return new JsonResponse(['status' => 'Notification créée et email envoyé'], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
            return new JsonResponse(['error' => 'Erreur lors de l\'envoi de l\'email. ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
