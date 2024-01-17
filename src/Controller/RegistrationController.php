<?php

namespace App\Controller;

use App\Entity\EmailToken;
use App\Entity\FreshUser;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use App\Security\MainAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;
    private RouterInterface $router;

    public function __construct(EmailVerifier $emailVerifier, RouterInterface $router)
    {
        $this->emailVerifier = $emailVerifier;
        $this->router = $router;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, MainAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new FreshUser();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setName(strtoupper($user->getName()));
            $user->setFirstname(strtoupper($user->getFirstname()));
            $user->setFirstname(ucfirst(strtolower($user->getFirstname())));
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $this->sendEmailVerification($entityManager, $user);
            // do anything else you need here, like send an email

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    private function sendEmailVerification(EntityManagerInterface $entityManager, FreshUser $user)
    {
        $emailToken = new EmailToken();
        $legacyUser = $entityManager->getRepository(FreshUser::class)->findOneBy(['email' => $user->getEmail()]);
        //DISABLE ALL VALID TOKENS
        $legacyToken = $entityManager->getConnection()->prepare("CALL disableAllTokenForUser(:userId)");//return one result
        $legacyToken->executeQuery(["userId" => $legacyUser->getId()]);
        $entityManager->getConnection()->close();
        //
        $emailToken->setFreshUser($legacyUser);
        $entityManager->persist($emailToken);
        $entityManager->flush();

        $legacyToken = $entityManager->getConnection()->prepare("CALL getLastTokenForUser(:userId)");//return one result
        $legacyToken = $legacyToken->executeQuery(["userId" => $legacyUser->getId()])->fetchAllAssociative()[0]['token'];
        $entityManager->getConnection()->close();

        // generate a signed url and email it to the user
        $loader = new FilesystemLoader('email-template');
        $twigEnv = new Environment($loader);
        $twigBodyRenderer = new BodyRenderer($twigEnv);
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@fresh.app', 'Fresh Support !'))
            ->to($user->getEmail())
            ->subject('Activer votre compte Fresh !')
            ->htmlTemplate('confirmation_email.html.twig')
            ->context(['user'=>$user,'url' => $this->generateUrl('app_verify_email', [], UrlGeneratorInterface::ABSOLUTE_URL), 'token' => $legacyToken]);
        $twigBodyRenderer->render($email);
        $this->emailVerifier->send('app_verify_email', $user,
            $email
        );
    }

    #[Route('/resend-email/registration', name: 'app_resend_registration_confirmation_email')]
    public function resendEmailConfirmation(EntityManagerInterface $entityManager)
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $this->sendEmailVerification($entityManager, $user);
        $this->addFlash("success", "Un mail vous a été envoyé pour activé votre compte");
        return $this->redirectToRoute("app_main");
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        $id = $request->query->get('token');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $emailToken = $entityManager->getRepository(EmailToken::class)->findOneBy(['token' => $id]);

        // validate email confirmation link, sets User::isVerified=true and persists
        $isValidToken = $entityManager->getConnection()->prepare("SELECT isTokenValid(:token)");
        $isValidToken = $isValidToken->executeQuery(["token" => $id])->fetchAllAssociative();
        $entityManager->getConnection()->close();
        if ($isValidToken) {
            $user = $emailToken->getFreshUser();
            // generate a signed url and email it to the user
            $loader = new FilesystemLoader('email-template');
            $twigEnv = new Environment($loader);
            $twigBodyRenderer = new BodyRenderer($twigEnv);
            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@fresh.app', 'Fresh Support !'))
                ->to($user->getEmail())
                ->subject('Votre compte Fresh est maintenant actif !')
                ->htmlTemplate('success_confirmation_email.html.twig');
            $twigBodyRenderer->render($email);
            $this->emailVerifier->send('app_verify_email', $user,
                $email
            );
            $user->setIsVerified(true);
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Votre compte a été activé !');

            return $this->redirectToRoute('app_main');
        } else {
            $this->addFlash('verify_email_error', "Lien invalide, redemandez un nouveau lien !");

            return $this->redirectToRoute('app_register');
        }
    }
}
