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
    #[Route('/', name: 'app_default')]
    public function defaultAction(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PostType::class, new Post(), [
            'action' => $this->generateUrl('app_post_new'),
        ]);

        // Retrieves all posts sorted by creation date
        $posts = $em->getRepository(Post::class)->findBy([], ['created' => 'DESC']);

        return $this->render('post/index.html.twig', [
            'form' => $form->createView(),
            'posts' => $posts,
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
            $em->persist($post);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'post' => [
                    'title' => $post->getTitle(),
                    'created' => $post->getCreated()->format('d/m/Y H:i'),
                ]
            ]);
        }

        // Retrieves errors from the form
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new JsonResponse([
            'success' => false,
            'message' => implode(', ', $errors)
        ], 400);
    }
}