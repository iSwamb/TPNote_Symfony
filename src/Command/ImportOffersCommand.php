<?php

namespace App\Command;

use App\Entity\Job;
use App\Entity\Offer;
use App\Services\PoleEmploiService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportOffersCommand extends Command
{
    protected static $defaultName = 'app:import-offers';

    private $poleEmploiService;

    public function __construct(PoleEmploiService $poleEmploiService)
    {
        $this->poleEmploiService = $poleEmploiService;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Import all offers from the API');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $jobRepo = $em->getRepository('App:Job');
        $offerRepo = $em->getRepository('App:Offer');
        $offers = $this->poleEmploiService->getOffers();
        $output->writeln([
            '============',
            ' Import jobs and offers in DB ...',
            '============',
        ]);
        $count = 0;
        foreach($offers as $company_ref => $company_jobs)
        {
            $output->writeln([
                '============',
                ' Company : '.$company_ref,
                '============',
            ]);
            foreach($company_jobs as $ref => $offerArr)
            {
                $count++;
                if(isset($offerArr['title']) && isset($offerArr['location'])){
                    $offer = $offerRepo->findOneBy(array('reference'=>$ref));
                    if(!$offer)$offer=new Offer();
                    $job = $jobRepo->findOneBy(array('name'=>$offerArr['title']));
                    if(!$job){
                        $job=new Job();
                        $job->setCode($offerArr['romeCode']);
                        $job->setName($offerArr['title']);
                        $today = new \DateTime();
                        $job->setCreatedAt($today);
// RANDOM : 1/1000 est sticky
                        $job->setSticky($count%1000==0);
                        $em->persist($job);
                    }
                    $offer->setJob($job);
                    $offer->setReference($ref);
                    /* AUTRES CHAMPS */
                    $offer->setZipcode($offerArr['location']['codePostal']);
                    $offer->setCompany($offerArr['company']);
                    $offer->setDepartment(substr(0,2,$offerArr['location']['codePostal']));
                    $offer->setCity($offerArr['location']['libelle']);
                    if(isset($offerArr['location']['latitude'])) {
                        $offer->setLatitude($offerArr['location']['latitude']);
                        $offer->setLongitude($offerArr['location']['longitude']);
                    }
                    $offer->setDescription($offerArr['description']);
                    $offer->setCreatedAt(new \DateTime($offerArr['date']));
// Précaution : les emails sont réels, remplacez ici par un email factice
                    $offer->setContactEmail('fake-email-'.$company_ref.'@lebonjob.com');
                    $em->persist($offer);
                    if($count%50==0)
                    {
                        $em->flush();
                        $output->writeln([
                            $count.' ...'
                        ]);
                    }
                }
            }
        }
        $em->flush();
        $output->writeln([
            '============',
            ' Done ! ',
            '============',
        ]);
    }
}