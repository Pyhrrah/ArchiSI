<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FactureController extends AbstractController
{

    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/facture', name: 'app_facture')]
    public function index(): Response
    {
        return $this->render('facture/index.html.twig', [
            'controller_name' => 'FactureController',
        ]);
    }

    // Endpoint pour lire un Post par son ID
    #[Route('/post/read', name: 'read_post', methods: ['POST'])]
    // Définition de la route /post/read avec la méthode HTTP POST
    public function read(Request $request, EntityManagerInterface $entityManager)
    {
        // Fonction qui sera exécutée lors de l'appel de l'endpoint
        $data = json_decode($request->getContent(), true);
        // Décoder les données JSON de la requête
        $post = $entityManager->getRepository(Post::class)->find($data['id']);
        // Trouver le Post par son ID
        if (!$post) {
            return new JsonResponse(['status' => 'Post not found'], JsonResponse::HTTP_NOT_FOUND);
            // Si le Post n'existe pas, retourner une erreur 404
        }
        return new JsonResponse([
            'id' => $post->getId(),
            'amount' => $post->getAmount(),
            'due_date' => $post->getDueDate(),
            'customer_email' => $post->getCustomerEmail(),
        ], JsonResponse::HTTP_OK);
        // Retourner les détails du Post sous forme de réponse JSON
    }

    // Endpoint pour mettre à jour un Post par son ID
    #[Route('/post/update', name: 'update_post', methods: ['POST'])]
    // Définition de la route /post/update avec la méthode HTTP POST
    public function update(Request $request, EntityManagerInterface $entityManager)
    {
        // Fonction qui sera exécutée lors de l'appel de l'endpoint
        $data = json_decode($request->getContent(), true);
        // Décoder les données JSON de la requête
        $post = $entityManager->getRepository(Post::class)->find($data['id']);
        // Trouver le Post par son ID
        if (!$post) {
            return new JsonResponse(['status' => 'Post not found'], JsonResponse::HTTP_NOT_FOUND);
            // Si le Post n'existe pas, retourner une erreur 404
        }
        $post->setAmount($data['amount']);
        // Mettre à jour le titre du Post
        $post->setDueDate($data['due_date']);
        // Mettre à jour le contenu du Post
        $post->setCustomerEmail($data['customer_email']);
        $entityManager->flush();
        // Sauvegarder les changements dans la base de données

        $notificationResponse = $this->client->request('POST', 'http://notification-service/notifications',[
            'json' => [
                'sujet' => 'Billing',
                'recipient' => 'customer@example.com',
                'message' => 'Your invoice has been created.'
            ]
        ]);

        return new JsonResponse(['status' => 'Post updated!'], JsonResponse::HTTP_OK);
        // Retourner une réponse JSON indiquant le succès de l'opération
    }

    // Endpoint pour supprimer un Post par son ID
    #[Route('/post/delete', name: 'delete_post', methods: ['POST'])]
    // Définition de la route /post/delete avec la méthode HTTP POST
    public function delete(Request $request, EntityManagerInterface $entityManager)
    {
        // Fonction qui sera exécutée lors de l'appel de l'endpoint
        $data = json_decode($request->getContent(), true);
        // Décoder les données JSON de la requête
        $post = $entityManager->getRepository(Post::class)->find($data['id']);
        // Trouver le Post par son ID
        if (!$post) {
            return new JsonResponse(['status' => 'Post not found'], JsonResponse::HTTP_NOT_FOUND);
            // Si le Post n'existe pas, retourner une erreur 404
        }
        $entityManager->remove($post);
        // Supprimer le Post de la base de données
        $entityManager->flush();
        // Sauvegarder les changements dans la base de données
        return new JsonResponse(['status' => 'Post deleted!'], JsonResponse::HTTP_OK);
        // Retourner une réponse JSON indiquant le succès de l'opération
    }

}
