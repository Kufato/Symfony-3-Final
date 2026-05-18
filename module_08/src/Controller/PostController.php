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
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PostController extends AbstractController
{
    // Renders the home page.
    // Creates an empty Post form and retrieves all posts sorted by date (newest first).
    #[Route('/', name: 'app_default')]
    public function defaultAction(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PostType::class, new Post(), [
            'action' => $this->generateUrl('app_post_new'),
        ]);

        // Retrieve all posts sorted by creation date in descending order
        $posts = $em->getRepository(Post::class)->findBy([], ['created' => 'DESC']);

        return $this->render('post/index.html.twig', [
            'form'  => $form->createView(),
            'posts' => $posts,
        ]);
    }

    // Handles Ajax POST form submission for creating a new post.
    // Only accepts POST requests.
    // #[IsGranted] returns a 403 response if the user is not authenticated.
    #[Route('/post/new', name: 'app_post_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function newAction(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);

        // Bind the request data to the form
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($post);
            $em->flush();

            // Return the new post data so the client can update the DOM
            return new JsonResponse([
                'success' => true,
                'post'    => [
                    'id'      => $post->getId(),
                    'title'   => $post->getTitle(),
                    'created' => $post->getCreated()->format('d/m/Y H:i'),
                ],
            ]);
        }

        // Collect all validation error messages from the form
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new JsonResponse([
            'success' => false,
            'message' => implode(', ', $errors),
        ], 400);
    }

    // Returns the full details of a single post as JSON.
    // Called via Ajax when the user clicks on a post title.
    // The "canDelete" flag tells the client whether to show the delete button.
    #[Route('/view/{id}', name: 'app_post_view', methods: ['GET'])]
    public function viewAction(Post $post): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'post'    => [
                'id'        => $post->getId(),
                'title'     => $post->getTitle(),
                'content'   => $post->getContent(),
                'created'   => $post->getCreated()->format('d/m/Y H:i'),
                // Only logged-in users are allowed to delete posts
                'canDelete' => $this->getUser() !== null,
            ],
        ]);
    }

    // Deletes a post and returns its id as JSON so the client can
    // remove it from the DOM.
    // Only accepts DELETE requests.
    // #[IsGranted] returns a 403 response if the user is not authenticated.
    #[Route('/delete/{id}', name: 'app_post_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteAction(Post $post, EntityManagerInterface $em): JsonResponse
    {
        // Store the id before removing the entity (it becomes null after flush)
        $id = $post->getId();

        $em->remove($post);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'id'      => $id,
        ]);
    }
}