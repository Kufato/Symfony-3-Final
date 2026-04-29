<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PostController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function defaultAction(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PostType::class, new Post(), [
            'action' => $this->generateUrl('app_post_new'),
        ]);

        return $this->render('post/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // This action handles the submission of the POST form via Ajax.
    // It only accepts POST requests (methods: ['POST'])
    // #[IsGranted] blocks access if the user is not logged in
    #[Route('/post/new', name: 'app_post_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function newAction(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // persist() tells Doctrine "prepare this object for the database"
            $em->persist($post);
            
            // flush() actually executes the INSERT SQL query on the database
            $em->flush();
            return new JsonResponse(['success' => true, 'message' => 'Post created!']);
        }

        return new JsonResponse(['success' => false, 'message' => 'Invalid data'], 400);
    }
}