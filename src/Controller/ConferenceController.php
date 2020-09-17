<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

use App\Entity\Conference;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;

class ConferenceController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index(Environment $twig, ConferenceRepository $confRep)
    {
        return new Response(
            $twig->render(
                'conference/index.html.twig', 
                ['conferences' => $confRep->findAll()]
            )
        );
    }


    /**
     * @Route("/conference/{id}", name="conference")
     */
    public function show(Environment $twig, Conference $conf, CommentRepository $commRep) 
    {
        return new Response(
            $twig->render(
                'conference/show.html.twig',
                [
                    'conference' => $conf,
                    'comments' => $commRep->findBy(
                        ['conference' => $conf], 
                        ['createdAt' => 'DESC'])
                ]
            )
        );
    }
}
