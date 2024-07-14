<?php
namespace App\Controller;

use App\Entity\Facture;
use App\Entity\Post; // Ajout de cette ligne
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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

    #[Route('/post/read', name: 'read_post', methods: ['POST'])]
    public function read(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $post = $entityManager->getRepository(Post::class)->find($data['id']);

        if (!$post) {
            return new JsonResponse(['status' => 'Post not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $post->getId(),
            'amount' => $post->getAmount(),
            'due_date' => $post->getDueDate(),
            'customer_email' => $post->getCustomerEmail(),
        ], JsonResponse::HTTP_OK);
    }

    #[Route('/post/update', name: 'update_post', methods: ['POST'])]
    public function update(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $post = $entityManager->getRepository(Post::class)->find($data['id']);

        if (!$post) {
            return new JsonResponse(['status' => 'Post not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $post->setAmount($data['amount']);
        $post->setDueDate($data['due_date']);
        $post->setCustomerEmail($data['customer_email']);
        $entityManager->flush();

        $this->client->request('POST', 'http://notification-service/notifications', [
            'json' => [
                'sujet' => 'Billing',
                'recipient' => 'customer@example.com',
                'message' => 'Your invoice has been created.'
            ]
        ]);

        return new JsonResponse(['status' => 'Post updated!'], JsonResponse::HTTP_OK);
    }

    #[Route('/post/delete', name: 'delete_post', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $post = $entityManager->getRepository(Post::class)->find($data['id']);

        if (!$post) {
            return new JsonResponse(['status' => 'Post not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($post);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Post deleted!'], JsonResponse::HTTP_OK);
    }
}
