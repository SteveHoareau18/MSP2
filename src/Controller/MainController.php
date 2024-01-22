<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Entity\EmailToken;
use App\Entity\Food;
use App\Entity\FreshUser;
use App\Entity\Refrigerator;
use App\Form\FreshUserFormType;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Ulid;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

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
        $legacyEmail = $user->getEmail();
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
            if($legacyEmail != $user->getEmail()){
                $user->setIsVerified(false);
            }
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
        if(!$request->request->has('token') && !$request->request->has('_send_email_token')) {
            return $this->render("recovery-password.html.twig",['email'=>$request->query->get('email')]);
        }//SEND EMAIL TO GET A TOKEN
        //TODO dev

        // if($request->request->has('email') && $this->isCsrfTokenValid('_send_email_token_value',$request->request->get('_send_email_token'))){
        //     if($entityManager->getRepository(FreshUser::class)->findOneBy(['email'=>$request->request->get('email')]) != null){
        //         $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email'=>$request->request->get('email')]);
        //         $loader = new FilesystemLoader('email-template');
        //         $twigEnv = new Environment($loader);
        //         $twigBodyRenderer = new BodyRenderer($twigEnv);
        //         $legacyToken = new EmailToken();
        //         $legacyToken->setFreshUser($user);
        //         $legacyToken->setSendDate(new \DateTime("now"));
        //         $expireDate = $legacyToken->getSendDate();
        //         $expireDate->add(new \DateInterval('PT1H')); // PT1H represents a period of 1 hour
        //         $legacyToken->setExpireDate($expireDate);
        //         $legacyToken->setToken(Ulid::generate($legacyToken->getSendDate()));
        //         $entityManager->persist($legacyToken);
        //         $entityManager->flush();

        //         $timestamp = $legacyToken->getSendDate()->format('dmYHis').$user->getId().$user->getRegisterDate()->format("dmYHis");
        //         $email = (new TemplatedEmail())
        //             ->from(new Address('no-reply@fresh.app', 'Fresh Support !'))
        //             ->to($user->getEmail())
        //             ->subject('Votre demande de changement de mot de passe')
        //             ->htmlTemplate('recovery_password_email.html.twig')
        //             ->context(['user'=>$user,'url' => $this->generateUrl('app_change_password', [], UrlGeneratorInterface::ABSOLUTE_URL), 'timestamp' => $timestamp, 'key'=>$legacyToken->getId()]);
        //         $twigBodyRenderer->render($email);
        //         $this->emailVerifier->send('app_recovery_password', $user,
        //             $email
        //         );
        //         $entityManager->persist($user);
        //         $entityManager->flush();
        //         $this->addFlash('success', 'Un email contenant un lien (de no-reply@fresh.app) vous a été envoyé pour que vous modifiez votre mot de passe');
        //     }else{
        //         $this->addFlash('error', 'Cette email est introuvable dans notre base de données!');
        //         return $this->redirectToRoute('app_register');
        //     }
        // }else{
        //     $this->addFlash('error', 'Une erreur est survenue, merci de re-essayer...');
        //     return $this->redirectToRoute('app_recovery_password',['email'=>$request->request->get('email')]);
        // }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/change-password', name:'app_change_password')]
    public function changePassword(Request $request, EntityManagerInterface $entityManager):Response{
        if($request->query->has('timestamp') && $request->query->has('key')) {
            $emailToken = $entityManager->getRepository(EmailToken::class)->find($request->query->get('key'));
            if($emailToken != null){
                $timestamp = $emailToken->getSendDate()->format('dmYHis').$emailToken->getFreshUser()->getId().$emailToken->getFreshUser()->getRegisterDate()->format("dmYHis");

                if($request->query->get('timestamp') == $timestamp){
                    return $this->render('recovery-password.html.twig',['token'=>$timestamp]);
                }
            }
        }
        dd($request);
        return $this->redirectToRoute("app_login");
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
