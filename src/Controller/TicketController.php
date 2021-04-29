<?php

namespace App\Controller;


use App\Entity\Ticket;
use App\Entity\Reponse;
use App\Form\TicketType;
use App\Form\ReponseType;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Route("/ticket")
 */
class TicketController extends AbstractController
{

    private $authorizationChecker = null;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }


    /**
     * @Route("/", name="ticket_index", methods={"GET"})
     */
    public function index(TicketRepository $ticketRepository): Response
    {
        if ($this->authorizationChecker->isGranted('ROLE_SUPPORT') || $this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->render('ticket/index.html.twig', [
                'tickets' => $ticketRepository->findAll(),

            ]);
        } else {
            return $this->render('ticket/index.html.twig', [
                'tickets' => $ticketRepository->findBy(['user_id' => $this->getUser()]),
            ]);
        }
    }

    /**
     * @Route("/new", name="ticket_new", methods={"GET","POST"})
     * @IsGranted("ROLE_USER")
     */
    public function new(Request $request): Response
    {
        $ticket = new Ticket();
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticket->setStatus("Ouvert");
            $ticket->setCreatedAt(new \DateTime());
            $ticket->setUserId($this->getUser());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($ticket);
            $entityManager->flush();

            return $this->redirectToRoute('ticket_index');
        }

        return $this->render('ticket/new.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="ticket_show", methods={"GET", "POST"})
     * @IsGranted("ROLE_USER")
     */
    public function show(Ticket $ticket, Request $request, EntityManagerInterface $manager): Response
    {
        $reponse = new Reponse();
        

        $form = $this->createForm(ReponseType::class, $reponse);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
            if (!$reponse->getId()) {
                $reponse->setCreatedAt(new \DateTime());
                $reponse->setUserId($this->getUser());
                $reponse->setTicketId($ticket);
            }
            $manager->persist($reponse);
            $manager->flush();

            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/edit", name="ticket_edit", methods={"GET","POST"})
     * @IsGranted("ROLE_SUPPORT")
     */
    public function edit(Request $request, Ticket $ticket): Response
    {
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('ticket_index');
        }

        return $this->render('ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="ticket_delete", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(Request $request, Ticket $ticket): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ticket->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($ticket);
            $entityManager->flush();
        }

        return $this->redirectToRoute('ticket_index');
    }
}
