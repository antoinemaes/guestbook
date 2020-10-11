<?php

namespace App\Controller;


use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use App\Service\SpamChecker;

class ConferenceController extends AbstractController
{

    private $twig;
    private $entityManager;
    private $bus;

    public function __construct(
        Environment $twig, EntityManagerInterface $entityManager, MessageBusInterface $bus)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->bus = $bus;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function index(ConferenceRepository $confRep)
    {
        return new Response(
            $this->twig->render(
                'conference/index.html.twig', 
                ['conferences' => $confRep->findAll()]
            )
        );
    }

    /**
    * @Route("/conference_header", name="conference_header")
    */
    public function conferenceHeader(ConferenceRepository $conferenceRepository)
    {
        return new Response($this->twig->render('conference/header.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]));
    }


    /**
     * @Route("/conference/{slug}", name="conference")
     */
    public function show(Request $request, Conference $conf, CommentRepository $commRep)
    {

        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);

        $form->handleRequest($request);
        if($form->isSubmitted() and $form->isValid()) {
        
            $comment->setConference($conf);
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];
            $this->bus->dispatch(new CommentMessage($comment->getId(), $context));

            return $this->redirectToRoute('conference', ['slug' => $conf->getSlug()]);
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commRep->getCommentPaginator($conf, $offset);

        return new Response(
            $this->twig->render(
                'conference/show.html.twig',
                [
                    'conference' => $conf,
                    'comments' => $paginator,
                    'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
                    'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
                    'comment_form' => $form->createView()
                ]
            )
        );
    }
}
