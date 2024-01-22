<?php

namespace App\Controller;

use App\Entity\Food;
use App\Entity\FoodRecipeInRefrigerator;
use App\Entity\FoodRecipeNotInRefrigerator;
use App\Entity\FreshUser;
use App\Entity\Refrigerator;
use App\Form\FoodFormType;
use App\Form\RefrigeratorFormType;
use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RefrigeratorController extends AbstractController
{

    #[Route('/refrigerator/', name: 'app_refrigerator_0_404')]
    public function fourZeroFour0(): Response
    {
        return $this->redirectToRoute("app_main");
    }

    #[Route('/refrigerator/want/', name: 'app_refrigerator_404')]
    public function fourZeroFour(): Response
    {
        return $this->redirectToRoute("app_main");
    }

    #[Route('/refrigerator/want/{number}', name: 'app_refrigerator')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function index(Request $request, EntityManagerInterface $entityManager, $number): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $refrigerators = $entityManager->getRepository(Refrigerator::class)->findBy(['owner' => $user->getId()]);
        if (!empty($refrigerators)) {
            if($number < 1 || $number > 2){
                return $this->redirectToRoute("app_main");
            }
            if(!key_exists($number-1,$refrigerators)) return $this->redirectToRoute("app_main");
            $refrigerator = $refrigerators[$number - 1];
            if ($refrigerator == null) {
                return $this->redirectToRoute("app_refrigerator", ["number" => 1]);
            }
            $foodFormArr = array();
            $legacyFoodFormArr = array();
            foreach ($refrigerator->getFoods() as $food) {
                $foodForm = $this->createForm(FoodFormType::class, $food);
                $foodForm->handleRequest($request);
                $foodFormArr[$food->getId()] = $foodForm;
                $legacyFoodFormArr[$food->getId()] = $foodForm;
            }

            $legacyName = $refrigerator->getName();
            $refrigeratorForm = $this->createForm(RefrigeratorFormType::class, $refrigerator);
            $refrigeratorForm->handleRequest($request);


            if ($refrigeratorForm->isSubmitted() && $refrigeratorForm->isValid()) {
                $entityManager->persist($refrigerator);
                $entityManager->flush();
                $this->addFlash('success', 'Vous avez modifié votre Frigo (' . $refrigerator->getName() . ' anciennment ' . $legacyName . ')');
                return $this->redirectToRoute("app_refrigerator", ['number' => $number]);
            }

            // if ($request->query->has('force_delete') && $request->query->get('force_delete') == "true" &&
            //     $request->query->has('foodId')) {
            //     $food = $entityManager->getRepository(Food::class)->find($request->query->get('foodId'));
            //     if ($food != null) {
            //         $name = $food->getName();
            //         foreach ($entityManager->getRepository(FoodRecipeInRefrigerator::class)->findBy(['food'=>$food]) as $foodInRefrigerator) {
            //             $foodNotInRefrigerator = new FoodRecipeNotInRefrigerator();
            //             $foodNotInRefrigerator->setName($food->getName());
            //             $foodNotInRefrigerator->setQuantity($foodInRefrigerator->getQuantity());
            //             $foodNotInRefrigerator->setUnit($foodInRefrigerator->getUnit());
            //             $foodNotInRefrigerator->setRecipe($foodInRefrigerator->getRecipe());
            //             $entityManager->persist($foodNotInRefrigerator);
            //             $entityManager->remove($foodInRefrigerator);
            //             $entityManager->flush();
            //         }
            //         $entityManager->remove($food);
            //         $entityManager->flush();
            //         $this->addFlash('success', 'L\'aliment ' . $name . ' a été consommé ou supprimé !');
            //         return $this->redirectToRoute("app_refrigerator", ['number' => $number]);
            //     }
            // }

            return $this->render('refrigerator/index.html.twig', [
                'refrigerator' => $refrigerator,
                'number' => $number,
                'user' => $user,
                'foodFormArr' => $foodFormArr,
                'refrigeratorForm' => $refrigeratorForm
            ]);
        } else {
            $this->addFlash("error", "Une erreur est survenue");
            return $this->redirectToRoute("app_main");
        }
    }


    /**
     * @throws Exception
     */
    #[Route('/refrigerator/{number}/food/add', name: 'app_refrigerator_food_add')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function addFoodInRefrigirator(Request $request, EntityManagerInterface $entityManager, $number): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $refrigerators = $entityManager->getRepository(Refrigerator::class)->findBy(['owner' => $user->getId()]);
        if($number < 1 || $number > 2){
            return $this->redirectToRoute("app_main");
        }
        if(!key_exists($number-1,$refrigerators)) return $this->redirectToRoute("app_refrigerator",['number'=>$number-2]);
        $refrigerator = $refrigerators[$number - 1];
        if ($refrigerator == null) {
            return $this->redirectToRoute("app_refrigerator", ["number" => 1]);
        }
        $foods = $refrigerator->getFoods();
        if (count($foods) < 100) {
            if ($request->query->has('force_regroup') &&
                $request->query->get('force_regroup') == "true" &&
                $request->query->has('foodId') &&
                $request->query->has('withQuantity')) {
                $food = $entityManager->getRepository(Food::class)->find($request->query->get('foodId'));
                if ($food == null) {
                    $this->addFlash('error', 'Une erreur est survenue...');
                    return $this->redirectToRoute("app_refrigerator_food_add", ['number' => $number]);
                }
                if (floatval($request->query->get('withQuantity')) < 1 && floatval($request->query->get('withQuantity')) > 200) {
                    $this->addFlash('error', 'Une erreur est survenue...');
                    return $this->redirectToRoute("app_refrigerator_food_add", ['number' => $number]);
                }
                $food->setQuantity($request->query->get('withQuantity'));
                $entityManager->persist($food);
                $entityManager->flush();
                $this->addFlash('success', 'Vous avez regroupé 2 aliments ensemble ! (' . $food->getName() . ')');
                return $this->redirectToRoute("app_refrigerator", ['number' => $number]);
            }
            $food = new Food();
            $food->setRefrigerator($refrigerator);
            $foodForm = $this->createForm(FoodFormType::class, $food);
            $foodForm->handleRequest($request);
            if ($foodForm->isSubmitted() && $foodForm->isValid()) {
                $food->setName(strtoupper($food->getName()));
                $food->setName(ucfirst(strtolower($food->getName())));
                $foodsCanBe = $entityManager->getConnection()->prepare("CALL getFoodAlreadyExistForUser(:foodName,DATE(:expireDate),:userId)");
                $expireDate = $food->getExpireDate()->format('Y-m-d 00:00:00');
                $foodsCanBe = $foodsCanBe->executeQuery(['foodName' => $food->getName(), 'expireDate' => $expireDate, 'userId' => $user->getId()])->fetchAllAssociative();
                if (!in_array(0, $foodsCanBe[0]) && $request->request->get("food_add_force") == "false") {
                    $this->addFlash('warning', "Un aliment déjà existant ressemble à ce que vous voulez ajouter.<br>Voulez-vous les regrouper?");
                    return $this->render('refrigerator/food/add.html.twig', [
                        'form' => $foodForm,
                        'number' => $number,
                        'foodsCanBe' => $foodsCanBe,
                        'refrigerator' => $refrigerator,
                        'user' => $user,
                        'legacyFood' => $food
                    ]);
                }
                if ($food->getQuantity() <= 0) {
                    $this->addFlash('error', "La quantité d'un aliment ne doit pas être inférieure ou égale à 0");
                    return $this->redirectToRoute("app_refrigerator_food_add", ['number' => $number]);
                }
                $dateTimeNow = new DateTime("now");
                if ($food->getExpireDate()->format("d-m-Y") != $dateTimeNow->format("d-m-Y") && $food->getExpireDate()->diff($dateTimeNow)->invert == 0) {
                    $this->addFlash('error', "La date d'expiration d'un aliment ne doit pas être inférieure à la date d'aujourd'hui");
                    return $this->redirectToRoute("app_refrigerator_food_add", ['number' => $number]);
                }
                $entityManager->persist($food);
                $entityManager->flush();
                $this->addFlash("success", "Vous avez ajouté un nouvel aliment !");
                return $this->redirectToRoute("app_refrigerator", ["number" => $number]);
            }
            return $this->render('refrigerator/food/add.html.twig', [
                'form' => $foodForm,
                'number' => $number,
                'refrigerator' => $refrigerator,
                'user' => $user
            ]);
        } else {
            $this->addFlash("error", "Vous ne pouvez pas ajouter plus de 100 aliments dans un frigo !");
            return $this->redirectToRoute("app_main");
        }
    }

    #[Route('/refrigerator/{number}/food/remove/{id}', name: 'app_refrigerator_food_remove')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function removeFoodInRefrigirator(Request $request, EntityManagerInterface $entityManager, $number, $id): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $refrigerators = $entityManager->getRepository(Refrigerator::class)->findBy(['owner' => $user->getId()]);
        if($number < 1 || $number > 2){
            return $this->redirectToRoute("app_main");
        }
        if(!key_exists($number-1,$refrigerators)) return $this->redirectToRoute("app_refrigerator",['number'=>$number-2]);
        $refrigerator = $refrigerators[$number - 1];
        if ($refrigerator == null) {
            return $this->redirectToRoute("app_refrigerator", ["number" => 1]);
        }
        $food = $entityManager->getRepository(Food::class)->find($id);
        if ($food == null) {
            return $this->redirectToRoute("app_refrigerator", ["number" => 1]);
        }

        if ($food->getRefrigerator()->getId() != $refrigerator->getId()) {
            return $this->redirectToRoute("app_refrigerator", ["number" => 1]);
        }

        if ($request->request->has('_remove_' . $id . '_token') && $this->isCsrfTokenValid('_remove_food_refrigerator_token_value', $request->request->get('_remove_' . $id . '_token'))) {
            $name = $food->getName();
            foreach ($entityManager->getRepository(FoodRecipeInRefrigerator::class)->findBy(['food'=>$food]) as $foodInRefrigerator) {
                $foodNotInRefrigerator = new FoodRecipeNotInRefrigerator();
                $foodNotInRefrigerator->setName($food->getName());
                $foodNotInRefrigerator->setQuantity($foodInRefrigerator->getQuantity());
                $foodNotInRefrigerator->setUnit($foodInRefrigerator->getUnit());
                $foodNotInRefrigerator->setRecipe($foodInRefrigerator->getRecipe());
                $entityManager->persist($foodNotInRefrigerator);
                $entityManager->remove($foodInRefrigerator);
                $entityManager->flush();
            }

            $entityManager->remove($food);
            $entityManager->flush();
            $this->addFlash('success', "L'aliment " . $name . " a été consommé ou supprimé !");
        } else {
            $this->addFlash('error', "Une erreur est survenue, merci de re-essayer...");
        }
        return $this->redirectToRoute("app_refrigerator", ['number' => $number]);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/refrigerator/{number}/food/modify/{id}', name: 'app_refrigerator_food_modify')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function modifyFoodInRefrigirator(Request $request, EntityManagerInterface $entityManager, $number, $id): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $refrigerators = $entityManager->getRepository(Refrigerator::class)->findBy(['owner' => $user->getId()]);
        if($number < 1 || $number > 2){
            return $this->redirectToRoute("app_main");
        }
        if(!key_exists($number-1,$refrigerators)) return $this->redirectToRoute("app_refrigerator",['number'=>$number-2]);
        $refrigerator = $refrigerators[$number - 1];
        if ($refrigerator == null) {
            return $this->redirectToRoute("app_refrigerator", ["number" => 1]);
        }
        $foods = $refrigerator->getFoods();
        if (count($foods) < 100) {

            $food = $entityManager->getRepository(Food::class)->find($id);

            if ($food == null) {
                $this->addFlash('error', 'Une erreur est survenue...');
                return $this->redirectToRoute("app_refrigerator", ['number' => $number]);
            }
            if($food->getRefrigerator()->getId() != $refrigerator->getId()){
                $this->addFlash('error', 'Une erreur est survenue...');
                return $this->redirectToRoute("app_refrigerator", ['number' => $number]);
            }
            $legacyFoodName = $food->getName();
            if ($request->query->has('force_regroup') &&
                $request->query->get('force_regroup') == "true" &&
                $request->query->has('foodId') &&
                $request->query->has('withQuantity')) {

                $newFood = $entityManager->getRepository(Food::class)->find($request->query->get('foodId'));
                if ($newFood == null) {
                    $this->addFlash('error', 'Une erreur est survenue...');
                    return $this->redirectToRoute("app_refrigerator_food_add", ['number' => $number]);
                }
                if($newFood->getRefrigerator()->getId() != $refrigerator->getId()){
                    $this->addFlash('error', 'Une erreur est survenue...');
                    return $this->redirectToRoute("app_refrigerator", ['number' => $number]);
                }
                $entityManager->remove($food);
                $entityManager->flush();
                if (floatval($request->query->get('withQuantity')) < 1 && floatval($request->query->get('withQuantity')) > 200) {
                    $this->addFlash('error', 'Une erreur est survenue...');
                    return $this->redirectToRoute("app_refrigerator_food_add", ['number' => $number]);
                }
                $newFood->setQuantity($request->query->get('withQuantity'));
                $entityManager->persist($newFood);
                $entityManager->flush();
                $this->addFlash('success', 'Vous avez regroupé 2 aliments ensemble ! (' . $newFood->getName() . ' et '.$legacyFoodName.')');
                return $this->redirectToRoute("app_refrigerator", ['number' => $number]);
            }
            if (!$request->request->has("_modify_" . $id . "_token") && !$request->request->has("_regroup_".$id."_token") && !$request->request->has('_valid_modify_'.$id.'_token')) {
                $this->addFlash('error','Une erreur est survenue, merci de ré-essayer...');
                return $this->redirectToRoute("app_refrigerator",['number'=>$number]);
            }
            $foodFormArr = $request->request->all()['food_form'];
            $food->setName(strtoupper($foodFormArr['name']));
            $food->setName(ucfirst(strtolower($food->getName())));
            $food->setQuantity($foodFormArr['quantity']);
            if ($food->getQuantity() <= 0) {
                $this->addFlash('error', "La quantité d'un aliment ne doit pas être inférieure ou égale à 0");
                return $this->redirectToRoute("app_refrigerator", ['number' => $number]);
            }
            $food->setExpireDate(new DateTime($foodFormArr['expireDate']));
            $dateTimeNow = new DateTime("now");
            if ($food->getExpireDate()->format("d-m-Y") != $dateTimeNow->format("d-m-Y") && $food->getExpireDate()->diff($dateTimeNow)->invert == 0) {
                $this->addFlash('error', "La date d'expiration d'un aliment ne doit pas être inférieure à la date d'aujourd'hui");
                return $this->redirectToRoute("app_refrigerator", ['number' => $number]);
            }
            $foodForm = $this->createForm(FoodFormType::class, $food);
            $foodForm->handleRequest($request);
            if(!$request->request->has('_valid_modify_'.$id.'_token')) {
                $foodsCanBe = $entityManager->getConnection()->prepare("CALL getFoodAlreadyExistForUser(:foodName,DATE(:expireDate),:userId)");
                $expireDate = $food->getExpireDate()->format('Y-m-d 00:00:00');
                $foodsCanBe = $foodsCanBe->executeQuery(['foodName' => $food->getName(), 'expireDate' => $expireDate, 'userId' => $user->getId()])->fetchAllAssociative();
                $legacyFoodsCanBe = $foodsCanBe;
                $filteredFoodsCanBe = array_filter($legacyFoodsCanBe, function ($foodCanBe) use ($food) {
                    return isset($foodCanBe['id']) && !is_null($foodCanBe['id']) && $foodCanBe['id'] !== $food->getId();
                });

                $foodsCanBe = array_values($filteredFoodsCanBe);
                if (!empty($foodsCanBe) && !in_array(0, $foodsCanBe[0])) {
                    $this->addFlash('warning', "Un aliment déjà existant ressemble à ce que vous voulez modifier.<br>Voulez-vous les regrouper?");
                    return $this->render('refrigerator/food/regroup.html.twig', [
                        'number' => $number,
                        'form' => $foodForm,
                        'foodsCanBe' => $foodsCanBe,
                        'refrigerator' => $refrigerator,
                        'user' => $user,
                        'legacyFood' => $food
                    ]);
                }
            }else if($this->isCsrfTokenValid('_valid_modify_token_value',$request->request->get('_valid_modify_'.$id.'_token'))) {
                if($foodForm->isSubmitted() && $foodForm->isValid()) {
                    $entityManager->persist($food);
                    $entityManager->flush();
                    $this->addFlash("success", "Vous avez modifié l'aliment " . $food->getName() . " (anciennement " . $legacyFoodName . ")");
                    return $this->redirectToRoute("app_refrigerator", ["number" => $number]);
                }
                $this->addFlash('error','Une erreur est survenue, merci de re-essayer...');
                return $this->redirectToRoute("app_refrigerator", ["number" => $number]);
            }
            if($request->request->has('_modify_'.$id.'_token') &&
                $this->isCsrfTokenValid('_modify_food_refrigerator_token_value',$request->request->get('_modify_'.$id.'_token'))){
                if($foodForm->isSubmitted() && $foodForm->isValid()){
                    $entityManager->persist($food);
                    $entityManager->flush();
                    $this->addFlash("success", "Vous avez modifié l'aliment " . $food->getName() . " (anciennement " . $legacyFoodName . ")");
                    return $this->redirectToRoute("app_refrigerator", ["number" => $number]);
                }
            }
            $this->addFlash('error','Une erreur est survenue');
            return $this->redirectToRoute("app_refrigerator", ["number" => $number]);
        } else {
            $this->addFlash("error", "Vous ne pouvez pas ajouter plus de 100 aliments dans un frigo !");
            return $this->redirectToRoute("app_refrigerator", ['number' => $number]);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route('/refrigerator/{number}/delete', name: 'app_refrigerator_remove')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function deleteRefrigirator(Request $request, EntityManagerInterface $entityManager, $number): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $refrigerators = $entityManager->getRepository(Refrigerator::class)->findBy(['owner' => $user->getId()]);
        if($number < 1 || $number > 2){
            return $this->redirectToRoute("app_main");
        }
        if(!key_exists($number-1,$refrigerators)) return $this->redirectToRoute("app_refrigerator",['number'=>$number-2]);
        $refrigerator = $refrigerators[$number - 1];
        if ($refrigerator == null) {
            return $this->redirectToRoute("app_refrigerator", ["number" => 1]);
        }
        $legacyName = $refrigerator->getName();
        if ($request->query->has('token') && $this->isCsrfTokenValid('manual-delete', $request->query->get('token'))) {
            $entityManager->remove($refrigerator);
            $entityManager->flush();
            $this->addFlash('success', 'Votre frigo ' . $legacyName . ' a été supprimé !');
        }

        return $this->redirectToRoute("app_main");
    }

    #[Route('/refrigerator/add', name: 'app_refrigerator_add')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function addRefrigerator(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $refrigerators = $entityManager->getRepository(Refrigerator::class)->findBy(['owner' => $user->getId()]);
        if (count($refrigerators) < 2) {
            $refrigerator = new Refrigerator();
            $refrigerator->setOwner($user);
            $refrigeratorForm = $this->createForm(RefrigeratorFormType::class, $refrigerator);
            $refrigeratorForm->handleRequest($request);
            if ($refrigeratorForm->isSubmitted() && $refrigeratorForm->isValid()) {
                foreach ($refrigerators as $legacyRefrigerator) {
                    if ($legacyRefrigerator->getName() == $refrigerator->getName()) {
                        $this->addFlash('error', "Vous avez déjà un frigo portant ce nom :)");
                        return $this->redirectToRoute("app_main");
                    }
                }
                $entityManager->persist($refrigerator);
                $entityManager->flush();
                $this->addFlash("success", "Vous avez ajouté un nouveau frigo !");
                return $this->redirectToRoute("app_refrigerator", ["number" => count($refrigerators) + 1]);
            }
            return $this->render('refrigerator/add.html.twig', [
                'form' => $refrigeratorForm,
                'user' => $user
            ]);
        } else {
            $this->addFlash("error", "Vous ne pouvez pas ajouter plus de 2 frigos !");
            return $this->redirectToRoute("app_main");
        }
    }
}
