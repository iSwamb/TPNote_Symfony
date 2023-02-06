<?php

namespace App\Controller;

use App\Entity\Candidate;
use App\Entity\Offer;
use App\Repository\CandidateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontofficeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('offer/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/offers', name: 'app_offers')]
    public function offers(EntityManagerInterface $entityManager, int $page = 1): Response
    {
        $repository = $entityManager->getRepository(Offer::class);
        $offers = $repository->findBy(
            [],
            [],
            50,
            50 * ($page - 1)
        );

        return $this->render('offer/index.html.twig', [
            'offers' => $offers,
        ]);
    }

    // Une page /offers/department-{code département} listant l’ensemble des offres
    //dans un département (87,93,2A par exemple)
    #[Route('/offers/department-{code}', name: 'app_offers_department')]
    public function offersByDepartment(EntityManagerInterface $entityManager, string $code, int $page = 1): Response
    {
        $repository = $entityManager->getRepository(Offer::class);
        $offers = $repository->findBy(
            ['department' => $code],
            [],
            50,
            50 * ($page - 1)
        );

        return $this->render('offer/department.html.twig', [
            'offers' => $offers,
        ]);
    }

    // Une page /offers/job-{ID JOB} listant l’ensemble des offres pour un job donné
    //(Identifiant du métier en BDD)
    #[Route('/offers/job-{id}', name: 'app_offers_job')]
    public function offersByJob(EntityManagerInterface $entityManager, int $id, int $page = 1): Response
    {
        $repository = $entityManager->getRepository(Offer::class);
        $offers = $repository->findBy(
            ['job' => $id],
            [],
            50,
            50 * ($page - 1)
        );

        return $this->render('offer/job.html.twig', [
            'offers' => $offers,
        ]);
    }

    // Une page /offer/{ID OFFER} affichant tout le détail d’une offre
    #[Route('/offer/{id}', name: 'app_offer')]
    public function offer(EntityManagerInterface $entityManager, int $id): Response
    {
        $repository = $entityManager->getRepository(Offer::class);
        $offer = $repository->find($id);

        return $this->render('offer/show.html.twig', [
            'offer' => $offer,
        ]);
    }

    // /offers/{ID OFFER}/candidate permettant à un candidat de postuler à une offre.

    #[Route('/offers/{id}/candidate', name: 'app_offers_candidate')]
    public function candidate(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, CandidateRepository $candidateRepository): Response
    {
        $candidate = new Candidate();

        $form = $this->createFormBuilder($candidate)
            ->add('user_name', TextType::class)
            ->add('user_email', TextType::class)
            ->add('user_motivation', TextType::class)
            ->add('offer_id', IntegerType::class)
            ->add('created_at', DateTimeType::class, [
                'data' => new \DateTime(),
            ])
            ->add('status', TextType::class)
            ->add('save', SubmitType::class, ['label' => 'Create Category'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $candidate = $form->getData();
            $entityManager->persist($candidate);
            $entityManager->flush();

            echo "Candidature créée !";
            return $this->redirectToRoute('app_offers');
        }

        return $this->render('offer/candidate.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}