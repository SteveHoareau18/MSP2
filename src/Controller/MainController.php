<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Entity\Food;
use App\Entity\FreshUser;
use App\Entity\Refrigerator;
use App\Form\FreshUserFormType;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MainController extends AbstractController
{

    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier){
        $this->emailVerifier = $emailVerifier;
    }
    #[Route('/', name: 'app_main')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(["email"=>$this->getUser()->getUserIdentifier()]);
        $today = new \DateTime();
        $user->setLastConnection($today);
        $entityManager->persist($user);
        $entityManager->flush();
        $today->setTime(0, 0, 0);
        $dateString = $today->format('Y-m-d');

        //on récupère les alertes dejà genérée, si vide alors on genere des alertes
        $alerts = $this->getAlertsByUser($entityManager,$user);
        if(empty($alerts)){
            $legacyAlerts = $entityManager->getConnection()->prepare("CALL genereAlertForUser(:userId)");
            $legacyAlerts->executeQuery(['userId'=>$user->getId()]);
            $alerts = $this->getAlertsByUser($entityManager,$user);
        }

        return $this->render('index.html.twig', [
            'user' => $user,
            'alerts'=>$alerts,
            'dateString'=>$dateString
        ]);
    }

    #[Route('/account', name: 'app_fresh_account')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function account(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(["email"=>$this->getUser()->getUserIdentifier()]);

        $formUser = $this->createForm(FreshUserFormType::class, $user);
        if($request->request->has('modify') && $request->request->get('modify')){
            return $this->render('account.html.twig', [
                'user' => $user,
                'formUser'=>$formUser,
                'modify'=>true
            ]);
        }
        $formUser->handleRequest($request);

        if($formUser->isSubmitted() && $formUser->isValid()){
            $user->setName(strtoupper($user->getName()));
            $user->setFirstname(strtoupper($user->getFirstname()));
            $user->setFirstname(ucfirst(strtolower($user->getFirstname())));
            $user->setPassword($passwordHasher->hashPassword($user,$request->request->all()['fresh_user_form']['plainPassword']));
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été sauvegardées !');
            return $this->redirectToRoute('app_fresh_account');
        }

        return $this->render('account.html.twig', [
            'user' => $user,
            'formUser'=>$formUser
        ]);
    }

    #[Route('/recovery-password', name: 'app_recovery_password')]
    public function recoveryPassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        if($request->query->has('email')){
            if(!$request->query->has('_send_email_token')) return $this->render("recovery-password.html.twig",['email'=>$request->query->get('email')]);
            if($this->isCsrfTokenValid('_send_email_token_value',$request->query->get('_send_email_token'))){
                if($entityManager->getRepository(FreshUser::class)->findOneBy(['email'=>$request->query->get('email')]) != null){
                    $freshUser = $entityManager->getRepository(FreshUser::class)->findOneBy(['email'=>$request->query->get('email')]);
                    $this->emailVerifier->send("app_recovery_password",$freshUser,);
                }else{
                    $this->addFlash('error', 'Cette email est introuvable dans notre base de données!');
                    return $this->redirectToRoute('app_register');
                }
            }else{
                $this->addFlash('error', 'Une erreur est survenue, merci de re-essayer...');
                return $this->redirectToRoute('app_login');
            }
        }
        return $this->redirectToRoute("app_main");
    }

    private function getAlertsByUser(EntityManagerInterface $entityManager, FreshUser $user){
        $legacyAlerts = $entityManager->getConnection()->prepare("CALL getTodayAlertForUser(:recipientId)");
        $legacyAlerts = $legacyAlerts->executeQuery(['recipientId'=>$user->getId()])->fetchAllAssociative();
        $alerts = array();
        foreach ($legacyAlerts as $alert) {
            array_push($alerts, $entityManager->getRepository(Alert::class)->find($alert['alert_id']));
        }
        return $alerts;
    }
}
