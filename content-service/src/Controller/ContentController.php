<?php

namespace App\Controller;

use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ContentController extends AbstractController
{
    private $client;

    // Le constructeur injecte le service HttpClientInterface
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    // Endpoint pour créer une nouvelle commande
    #[Route('/commande/create', name: 'create_commande', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Décoder les données JSON de la requête
        $data = json_decode($request->getContent(), true);

        // Créer une nouvelle instance de l'entité Commande
        $commande = new Commande();
        $commande->setProductId($data['product_id']);
        $commande->setCustomerEmail($data['customer_email']);
        $commande->setQuantity($data['quantity']);
        $commande->setTotalPrice($data['total_price']);
        $commande->setPrice($data['price']); // Ajout du champ price

        // Persister l'entité et sauvegarder en base de données
        $entityManager->persist($commande);
        $entityManager->flush();

        // Envoyer une requête au Billing Service pour créer une facture
        $response = $this->client->request('POST', 'http://billing-service.local/create-invoice', [
            'json' => [
                'amount' => $data['total_price'],
                'due_date' => (new \DateTime('+30 days'))->format('Y-m-d'),
                'customer_email' => $data['customer_email']
            ],
        ]);

        // Vérifier si la requête au Billing Service a réussi
        if ($response->getStatusCode() !== 201) {
            // Si la création de la facture échoue, retourner une erreur
            return new JsonResponse(['status' => 'Failed to create invoice'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Retourner une réponse JSON indiquant le succès de l'opération
        return new JsonResponse(['status' => 'Commande created and invoice generated!'], JsonResponse::HTTP_CREATED);
    }

    // Endpoint pour lire une commande par son ID
    #[Route('/commande/read', name: 'read_commande', methods: ['POST'])]
    public function read(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $commande = $entityManager->getRepository(Commande::class)->find($data['id']);

        if (!$commande) {
            return new JsonResponse(['status' => 'Commande not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $commande->getId(),
            'product_id' => $commande->getProductId(),
            'customer_email' => $commande->getCustomerEmail(),
            'quantity' => $commande->getQuantity(),
            'total_price' => $commande->getTotalPrice(),
            'price' => $commande->getPrice()
        ], JsonResponse::HTTP_OK);
    }

    // Endpoint pour mettre à jour une commande par son ID
    #[Route('/commande/update', name: 'update_commande', methods: ['POST'])]
    public function update(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $commande = $entityManager->getRepository(Commande::class)->find($data['id']);

        if (!$commande) {
            return new JsonResponse(['status' => 'Commande not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $commande->setProductId($data['product_id']);
        $commande->setCustomerEmail($data['customer_email']);
        $commande->setQuantity($data['quantity']);
        $commande->setTotalPrice($data['total_price']);
        $commande->setPrice($data['price']); // Mise à jour du champ price
        $entityManager->flush();

        return new JsonResponse(['status' => 'Commande updated!'], JsonResponse::HTTP_OK);
    }

    // Endpoint pour supprimer une commande par son ID
    #[Route('/commande/delete', name: 'delete_commande', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $commande = $entityManager->getRepository(Commande::class)->find($data['id']);

        if (!$commande) {
            return new JsonResponse(['status' => 'Commande not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($commande);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Commande deleted!'], JsonResponse::HTTP_OK);
    }

    // Endpoint pour lister toutes les commandes
    #[Route('/commande/list', name: 'list_commande', methods: ['GET'])]
    public function list(EntityManagerInterface $entityManager): JsonResponse
    {
        $commandes = $entityManager->getRepository(Commande::class)->findAll();

        $response = [];
        foreach ($commandes as $commande) {
            $response[] = [
                'id' => $commande->getId(),
                'product_id' => $commande->getProductId(),
                'customer_email' => $commande->getCustomerEmail(),
                'quantity' => $commande->getQuantity(),
                'total_price' => $commande->getTotalPrice(),
                'price' => $commande->getPrice()
            ];
        }

        return new JsonResponse($response, JsonResponse::HTTP_OK);
    }
}
