<?php

namespace App\Controller;

use App\Entity\Food;
use App\Entity\FoodRecipeInRefrigerator;
use App\Entity\FoodRecipeNotInRefrigerator;
use App\Entity\FreshUser;
use App\Entity\Recipe;
use App\Entity\Refrigerator;
use App\Form\RecipeFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RecipeController extends AbstractController
{
    #[Route('/recipe/want/{number}', name: 'app_recipe')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function index(Request $request, EntityManagerInterface $entityManager, $number): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $recipes = $entityManager->getRepository(Recipe::class)->findBy(['owner' => $user->getId()]);
        if (!empty($recipes)) {
            $recipe = $recipes[$number - 1];
            if ($recipe == null) {
                return $this->redirectToRoute("app_recipe", ["number" => 1]);
            }

            $ingredients = array();
            foreach ($recipe->getFoodRecipeNotInRefrigerators() as $foodNotInRefrigerator){
                array_push($ingredients,$foodNotInRefrigerator);
            }
            foreach ($recipe->getFoodRecipeInRefrigerators() as $food){
                array_push($ingredients,$food);
            }
            $recipeForm = $this->createForm(RecipeFormType::class,$recipe);
            $recipeForm->handleRequest($request);
            return $this->render('recipe/index.html.twig', [
                'recipe' => $recipe,
                'number' => $number,
                'user' => $user,
                'ingredients'=>$ingredients,
                'recipeForm'=>$recipeForm
            ]);
        } else {
            $this->addFlash("error", "Une erreur est survenue");
            return $this->redirectToRoute("app_main");
        }
    }
    #[Route('/recipe/{number}/food/remove/{id}', name: 'app_recipe_food_remove')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function removeFoodInRefrigirator(Request $request, EntityManagerInterface $entityManager, $number, $id): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $recipes = $entityManager->getRepository(Recipe::class)->findBy(['owner' => $user->getId()]);
        if($number < 1 || $number > 2){
            return $this->redirectToRoute("app_main");
        }
        $recipe = $recipes[$number - 1];
        if ($recipe == null) {
            return $this->redirectToRoute("app_recipe", ["number" => 1]);
        }
        $food = $entityManager->getRepository(Food::class)->find($id);
        if ($food == null) {
            return $this->redirectToRoute("app_recipe", ["number" => 1]);
        }

        if ($food->getRecipe()->getId() != $recipe->getId()) {
            return $this->redirectToRoute("app_recipe", ["number" => 1]);
        }

        if ($request->request->has('_remove_' . $id . '_token') && $this->isCsrfTokenValid('_remove_food_recipe_token_value', $request->request->get('_remove_' . $id . '_token'))) {
            $name = $food->getName();
            foreach ($food->getRecipe()->getAlerts() as $alert) {
                if ($alert->getFood()->getId() === $food->getId()) {
                    $entityManager->remove($alert);
                    $entityManager->flush();
                }
            }
            $recipes = $entityManager->getRepository(Recipe::class)->findAll();
            foreach ($recipes as $recipe){
                $recipe->removeFood($food);
            }
            $entityManager->remove($food);
            $entityManager->flush();
            $this->addFlash('success', "L'aliment " . $name . " a été consommé ou supprimé !");
        } else {
            $this->addFlash('error', "Une erreur est survenue, merci de re-essayer...");
        }
        return $this->redirectToRoute("app_recipe", ['number' => $number]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/recipe/{number}/delete', name: 'app_recipe_remove')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function deleteRecipe(Request $request, EntityManagerInterface $entityManager, $number): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $recipes = $entityManager->getRepository(Recipe::class)->findBy(['owner' => $user->getId()]);
        if($number < 1 || $number > 2){
            return $this->redirectToRoute("app_main");
        }
        $recipe = $recipes[$number - 1];
        if ($recipe == null) {
            return $this->redirectToRoute("app_recipe", ["number" => 1]);
        }
        $legacyName = $recipe->getName();
        if ($request->query->has('token') && $this->isCsrfTokenValid('manual-delete', $request->query->get('token'))) {
            foreach ($recipe->getFoodRecipeInRefrigerators() as $foodRecipeInRefrigerator){
                $entityManager->remove($foodRecipeInRefrigerator);
                $entityManager->flush();
            }
            foreach ($recipe->getFoodRecipeNotInRefrigerators() as $foodRecipeNotInRefrigerator){
                $entityManager->remove($foodRecipeNotInRefrigerator);
                $entityManager->flush();
            }
            $entityManager->remove($recipe);
            $entityManager->flush();
            $this->addFlash('success', 'Votre recette ' . $legacyName . ' a été supprimé !');
        }

        return $this->redirectToRoute("app_main");
    }

    #[Route('/recipe/add', name: 'app_recipe_add')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function addRecipe(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $recipe = new Recipe();
        $recipe->setOwner($user);
        $recipeForm = $this->createForm(RecipeFormType::class, $recipe);
        $recipeForm->handleRequest($request);
        $refrigerators = $entityManager->getRepository(Refrigerator::class)->findBy(['owner'=>$user]);
        $foods = empty($refrigerators)?array():$refrigerators[0]->getFoods();
        if ($recipeForm->isSubmitted() && $recipeForm->isValid()) {
            $recipe->setCreateDate(new \DateTime("now"));
            $recipe->setOwner($user);

            $recipefoodsArr = explode("\r\n",$request->request->get('recipefoods'));
            array_push($recipefoodsArr,"");
//            dd($recipefoodsArr);

            $checkerFood = null;
            foreach ($recipefoodsArr as $recipefoods){
                $defaultQuantity = 1;
                $defaultUnit = "pincée(s)";
                if($recipefoods == ""){
                    $existFood = false;
                    foreach($user->getRefrigerators() as $refrigerator){
                        foreach ($refrigerator->getFoods() as $food){
                            if($food->getName() == $checkerFood->getName()){
                                $foodRecipeInRefrigerator = new FoodRecipeInRefrigerator();
                                $foodRecipeInRefrigerator->setRefrigerator($refrigerator);
                                $foodRecipeInRefrigerator->setFood($food);
                                $foodRecipeInRefrigerator->setQuantity($checkerFood->getQuantity());
                                $foodRecipeInRefrigerator->setUnit($checkerFood->getUnit());
                                $recipe->addFoodRecipeInRefrigerator($foodRecipeInRefrigerator);
                                $entityManager->persist($foodRecipeInRefrigerator);
                                $entityManager->flush();
                                $existFood = true;
                                break;
                            }
                        }
                        if($existFood) break;
                    }
                    if(!$existFood){
                        $foodsCanBe = $entityManager->getConnection()->prepare("SELECT * FROM food INNER JOIN refrigerator ON food.refrigerator_id = refrigerator.id WHERE food.name LIKE :foodName AND refrigerator.owner_id = :ownerId");
                        $foodsCanBe = $foodsCanBe->executeQuery(['foodName' => $checkerFood->getName(), 'ownerId' => $user->getId()])->fetchAllAssociative();
                        //TODO test avec choco
                        $checkerFood->setCanBeRegroup(!empty($foodsCanBe));
                        $recipe->addFoodRecipeNotInRefrigerator($checkerFood);
                        $entityManager->persist($checkerFood);
                        $entityManager->flush();
                    }
                    $checkerFood = null;
                    continue;
                }
                if($checkerFood == null){
                    $checkerFood = new FoodRecipeNotInRefrigerator();
                    if(array_key_exists(0, explode(" ",$recipefoods))){
                        $defaultQuantity = floatval(explode(" ",$recipefoods)[0]);
                    }
                    if($defaultQuantity == 0) $defaultQuantity = 1;
                    $checkerFood->setQuantity($defaultQuantity);
                    if(array_key_exists(1, explode(" ",$recipefoods))){
                        $defaultUnit = explode(" ",$recipefoods)[1];
                    }
                    $checkerFood->setUnit($defaultUnit);
                    $checkerFood->setName($recipefoods);
                }else {
                    if (array_key_exists(0, explode(" ",$recipefoods)) && explode(" ", $recipefoods)[0] == "de") {
                        $name = substr($recipefoods, 3);
                    } else {
                        $name = $recipefoods;
                    }
                    $checkerFood->setName($name);
                }
            }
//            dd($recipe);
            foreach ($user->getRecipes() as $legacyRecipe) {
                if ($legacyRecipe->getName() == $recipe->getName() && $legacyRecipe->getId() != $recipe->getId()) {
                    $this->addFlash('error', "Vous avez déjà une recette portant ce nom :)");
                    return $this->redirectToRoute("app_main");
                }
            }

            $entityManager->persist($recipe);
            $entityManager->flush();
            $this->addFlash("success", "Vous avez ajouté une nouvelle recette ! ");
            return $this->redirectToRoute("app_recipe", ["number" => count($entityManager->getRepository(Recipe::class)->findBy(['owner'=>$user])) + 1]);
        }
        return $this->render('recipe/add.html.twig', [
            'form' => $recipeForm,
            'user' => $user,
            'refrigerators'=>$refrigerators,
            'foods'=>$foods
        ]);
    }
}