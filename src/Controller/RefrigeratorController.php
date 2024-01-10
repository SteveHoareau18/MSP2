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
                'number'=>$number,
                'user'=>$user
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
                $food->setName(strtoupper($food->getName()));
                $food->setName(ucfirst(strtolower($food->getName())));
                $foodsCanBe = $entityManager->getConnection()->prepare("CALL getFoodAlreadyExistForUser(:foodName,DATE(:expireDate),:userId)");
                $expireDate = $food->getExpireDate()->format('Y-m-d 00:00:00');
                $foodsCanBe = $foodsCanBe->executeQuery(['foodName'=>$food->getName(),'expireDate'=>$expireDate,'userId'=>$user->getId()])->fetchAllAssociative();
                if(!in_array(0,$foodsCanBe[0]) && $request->request->get("food_add_force")=="false"){
                    $this->addFlash('warning',"Un aliment déjà existant ressemble à ce que vous voulez ajouter.<br>Voulez-vous les regrouper?");
                    return $this->render('refrigerator/food/add.html.twig',[
                        'form'=>$foodForm,
                        'number'=>$number,
                        'foodsCanBe'=>$foodsCanBe,
                        'refrigerator'=>$refrigerator,
                        'user'=>$user
                    ]);
                }
                $entityManager->persist($food);
                $entityManager->flush();
                $this->addFlash("success","Vous avez ajouté un nouvel aliment !");
                return $this->redirectToRoute("app_refrigerator", ["number"=>$number]);
            }
            return $this->render('refrigerator/food/add.html.twig',[
                'form'=>$foodForm,
                'number'=>$number,
                'refrigerator'=>$refrigerator,
                'user'=>$user
            ]);
        }else{
            $this->addFlash("error","Vous ne pouvez pas ajouter plus de 100 aliments dans un frigo !");
            return $this->redirectToRoute("app_main");
        }
    }

    #[Route('/refrigerator/{number}/food/remove/{id}', name: 'app_refrigerator_food_remove')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function removeFoodInRefrigirator(Request $request, EntityManagerInterface $entityManager, $number,$id): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email'=>$this->getUser()->getUserIdentifier()]);
        $refrigerators = $entityManager->getRepository(Refrigerator::class)->findBy(['owner'=>$user->getId()]);
        $refrigerator = $refrigerators[$number-1];
        if($refrigerator == null){
            return $this->redirectToRoute("app_refrigerator", ["number"=>1]);
        }
        $food = $entityManager->getRepository(Food::class)->find($id);
        if($food == null){
            return $this->redirectToRoute("app_refrigerator", ["number"=>1]);
        }
        return $this->render('refrigerator/food/remove.html.twig',[
            'food'=>$food,
            'number'=>$number,
            'refrigerator'=>$refrigerator,
            'user'=>$user
        ]);
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
                foreach ($refrigerators as $legacyRefrigerator){
                    if($legacyRefrigerator->getName() == $refrigerator->getName()){
                        $this->addFlash('error',"Vous avez déjà un frigo portant se nom :)");
                        return $this->redirectToRoute("app_main");
                    }
                }
                $entityManager->persist($refrigerator);
                $entityManager->flush();
                $this->addFlash("success","Vous avez ajouté un nouveau frigo !");
                return $this->redirectToRoute("app_refrigerator", ["number"=>count($refrigerators)+1]);
            }
            return $this->render('refrigerator/add.html.twig',[
                'form'=>$refrigeratorForm,
                'user'=>$user
            ]);
        }else{
            $this->addFlash("error","Vous ne pouvez pas ajouter plus de 2 frigos !");
            return $this->redirectToRoute("app_main");
        }
    }
}
