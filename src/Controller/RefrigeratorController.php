<?php

namespace App\Controller;

use App\Entity\Food;
use App\Entity\FreshUser;
use App\Entity\Refrigerator;
use App\Form\FoodFormType;
use App\Form\RefrigeratorFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RefrigeratorController extends AbstractController
{
    #[Route('/refrigerator/want/{number}', name: 'app_refrigerator')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function index(EntityManagerInterface $entityManager, $number): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email'=>$this->getUser()->getUserIdentifier()]);
        $refrigerators = $entityManager->getRepository(Refrigerator::class)->findBy(['owner'=>$user->getId()]);
        if(!empty($refrigerators)){
            $refrigerator = $refrigerators[$number-1];
            if($refrigerator == null){
                return $this->redirectToRoute("app_refrigerator", ["number"=>1]);
            }
            return $this->render('refrigerator/index.html.twig', [
                'refrigerator'=>$refrigerator,
                'number'=>$number
            ]);
        }else{
            $this->addFlash("error","Une erreur est survenue");
            return $this->redirectToRoute("app_main");
        }
    }

    #[Route('/refrigerator/{number}/food/add', name: 'app_refrigerator_food_add')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function addFoodInRefrigirator(Request $request, EntityManagerInterface $entityManager, $number): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email'=>$this->getUser()->getUserIdentifier()]);
        $refrigerators = $entityManager->getRepository(Refrigerator::class)->findBy(['owner'=>$user->getId()]);
        $refrigerator = $refrigerators[$number-1];
        if($refrigerator == null){
            return $this->redirectToRoute("app_refrigerator", ["number"=>1]);
        }
        $foods = $refrigerator->getFoods();
        if(count($foods) < 100){
            $food = new Food();
            $food->setRefrigerator($refrigerator);
            $foodForm = $this->createForm(FoodFormType::class, $food);
            $foodForm->handleRequest($request);
            if($foodForm->isSubmitted() && $foodForm->isValid()){
                $entityManager->persist($food);
                $entityManager->flush();
                $this->addFlash("success","Vous avez ajouté un nouvel aliment !");
                return $this->redirectToRoute("app_refrigerator", ["number"=>$number]);
            }
            return $this->render('refrigerator/add.html.twig',[
                'form'=>$foodForm,
                'number'=>$number
            ]);
        }else{
            $this->addFlash("error","Vous ne pouvez pas ajouter plus de 100 aliments dans un frigo !");
            return $this->redirectToRoute("app_main");
        }
    }

    #[Route('/refrigerator/add', name: 'app_refrigerator_add')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function addRefrigerator(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email'=>$this->getUser()->getUserIdentifier()]);
        $refrigerators = $entityManager->getRepository(Refrigerator::class)->findBy(['owner'=>$user->getId()]);
        if(count($refrigerators) < 2){
            $refrigerator = new Refrigerator();
            $refrigerator->setOwner($user);
            $refrigeratorForm = $this->createForm(RefrigeratorFormType::class, $refrigerator);
            $refrigeratorForm->handleRequest($request);
            if($refrigeratorForm->isSubmitted() && $refrigeratorForm->isValid()){
                $entityManager->persist($refrigerator);
                $entityManager->flush();
                $this->addFlash("success","Vous avez ajouté un nouveau frigo !");
                return $this->redirectToRoute("app_refrigerator", ["number"=>count($refrigerators)]);
            }
            return $this->render('refrigerator/add.html.twig',[
                'form'=>$refrigeratorForm
            ]);
        }else{
            $this->addFlash("error","Vous ne pouvez pas ajouter plus de 2 frigos !");
            return $this->redirectToRoute("app_main");
        }
    }
}
