<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Entity\Food;
use App\Entity\FreshUser;
use App\Entity\Refrigerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(["email"=>$this->getUser()->getUserIdentifier()]);
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $dateString = $today->format('Y-m-d');

        $legacyAlerts = $entityManager->getConnection()->prepare("CALL getTodayAlertForUser(:recipientId)");
        $legacyAlerts = $legacyAlerts->executeQuery(['recipientId'=>$user->getId()])->fetchAllAssociative();

        $alerts = array();
        foreach ($legacyAlerts as $alert){
            array_push($alerts,$entityManager->getRepository(Alert::class)->find($alert['alert_id']));
        }


        return $this->render('index.html.twig', [
            'user' => $user,
            'alerts'=>$alerts,
            'dateString'=>$dateString
        ]);
    }
}
