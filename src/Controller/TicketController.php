<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Ticket;
use App\Form\TicketType;
use App\Form\ActivityType;
use App\Repository\ActivityRepository;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

//#[Route('/ticket')]
class TicketController extends AbstractController
{
    #[Route('/', name: 'app_ticket_index', methods: ['GET'])]
    public function index(TicketRepository $ticketRepository): Response
    {
        return $this->render('ticket/index.html.twig', [
            'tickets' => $ticketRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_ticket_new', methods: ['GET', 'POST'])]
    public function new(Request $request, TicketRepository $ticketRepository, activityRepository $activityRepository): Response
    {
        $ticket = new Ticket();
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // insertion du status pour un nouveau ticket
            $ticket->setStatus('Ouvert');

            // insertion de la date de création du ticket
            $ticket->setCreatedAt(New \DateTimeImmutable());
            $ticket->setSupport('aucun');
            // insertion de l'utilisateur en dur, qui devrait être en temps normal un objet user
            $ticket->setUser('Client1');
            $ticketRepository->save($ticket, true);

            // On instancie la class Activity
            $activity = new Activity();

            // En commentaire j'intègre l'action réalisée
            $activity->setComment('a ouvert un ticket');

            // Insertion de la date de création du ticket
            $activity->setCreatedAt(New \DateTimeImmutable());

            // On insert l'objet ticket juste créé
            $activity->setTicket($ticket);

            // On enregistre
            $activityRepository->save($activity, true);

            return $this->redirectToRoute('app_ticket_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('ticket/new.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ticket_show', methods: ['GET','POST'])]
    public function show(Ticket $ticket, ActivityRepository $activityRepository, Request $request): Response
    {
        // Instanttiation de la class activity
        $a = new Activity();
        // réation du formulaire pour la class activity
        $form = $this->createForm(ActivityType::class, $a);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $a->setTicket($ticket);
            $a->setCreatedAt(new \DateTimeImmutable());
            $activityRepository->save($a, true);

            return $this->redirectToRoute('app_ticket_show', ['id'=>$ticket->getId()]);
        }
        // récupération de l'activité du ticket
        $activity = $activityRepository->findBy(['ticket'=>$ticket], ['created_at'=>'ASC']);
        return $this->renderForm('ticket/show.html.twig', [
            'ticket' => $ticket,
            'activities' => $activity,
            'form' => $form
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ticket_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ticket $ticket, TicketRepository $ticketRepository): Response
    {
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticketRepository->save($ticket, true);

            return $this->redirectToRoute('app_ticket_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('ticket/{id}/support', name: 'supported', methods: ['POST'])]
    public function supported(Ticket $ticket, TicketRepository $ticketRepository, Request $request, ActivityRepository $activityRepository) {

        if ($this->isCsrfTokenValid('supported'.$ticket->getId(), $request->request->get('_token'))) {
            // modification du status du ticket
            $ticket->setStatus('Pris en charge');
            // modification du nom du technicien 
            $ticket->setSupport('technicien1');
            // enregistrement des modifications du ticket
            $ticketRepository->save($ticket, true);

            // instantition de la class activity
            $activity = new Activity();
            // insertino du commentaire de prise en charge du ticket
            $activity->setComment('a pris en charge la demande');
            // récupération de l'objet ticket
            $activity->setTicket($ticket);
            // insertion de la date
            $activity->setCreatedAt(new \DateTimeImmutable());
            $activityRepository->save($activity, true);
        }

        return $this->redirectToRoute('app_ticket_show', ['id'=>$ticket->getId()]);
    }

    #[Route('/{id}', name: 'app_ticket_delete', methods: ['POST'])]
    public function delete(Request $request, Ticket $ticket, TicketRepository $ticketRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ticket->getId(), $request->request->get('_token'))) {
            $ticketRepository->remove($ticket, true);
        }

        return $this->redirectToRoute('app_ticket_index', [], Response::HTTP_SEE_OTHER);
    }
}
