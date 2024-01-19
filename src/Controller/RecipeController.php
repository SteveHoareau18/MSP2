<?php

namespace App\Controller;

use App\Entity\Food;
use App\Entity\FoodRecipeInRefrigerator;
use App\Entity\FoodRecipeNotInRefrigerator;
use App\Entity\FreshUser;
use App\Entity\Recipe;
use App\Entity\Refrigerator;
use App\Form\FoodFormType;
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
            if(!array_key_exists($number-1,$recipes)) return $this->redirectToRoute("app_main");
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
            return $this->redirectToRoute("app_main");
        }
    }
    #[Route('/recipe/{number}/food/remove/{id}', name: 'app_recipe_food_remove')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function removeFoodInRecipe(Request $request, EntityManagerInterface $entityManager, $number, $id): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $recipes = $entityManager->getRepository(Recipe::class)->findBy(['owner' => $user->getId()]);
        if(!key_exists($number-1,$recipes)) return $this->redirectToRoute("app_recipe",['number'=>$number-2]);
        $recipe = $recipes[$number - 1];
        if ($recipe == null) {
            return $this->redirectToRoute("app_recipe", ["number" => 1]);
        }
        if($recipe->getOwner()->getId() != $user->getId()){
            $this->addFlash("error",'Une erreur est survenue...');
            return $this->redirectToRoute("app_main");
        }
        $foodRecipe = $entityManager->getRepository(FoodRecipeInRefrigerator::class)->find($id);
        if ($foodRecipe == null) {
            $foodRecipe = $entityManager->getRepository(FoodRecipeNotInRefrigerator::class)->find($id);
            if($foodRecipe == null) return $this->redirectToRoute("app_recipe", ["number" => 1]);
        }

        if($foodRecipe->getRecipe()->getId() != $recipe->getId()){
            $this->addFlash("error",'Une erreur est survenue...');
            return $this->redirectToRoute("app_main");
        }

        if ($request->request->has('_remove_' . $id . '_token') && $this->isCsrfTokenValid('_remove_food_recipe_token_value', $request->request->get('_remove_' . $id . '_token'))) {
            $name = $foodRecipe->getName();
            $entityManager->remove($foodRecipe);
            $entityManager->flush();
            $this->addFlash('success', "L'aliment " . $name . " a été enlevé dans la recette ".$recipe->getName()." !");
        } else {
            $this->addFlash('error', "Une erreur est survenue, merci de re-essayer...");
        }
        return $this->redirectToRoute("app_recipe", ['number' => $number]);
    }

    #[Route('/recipe/{number}/food/modify/{id}', name: 'app_recipe_food_modify')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function modifyFoodInRefrigirator(Request $request, EntityManagerInterface $entityManager, $number, $id): Response
    {
        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $recipes = $entityManager->getRepository(Recipe::class)->findBy(['owner' => $user->getId()]);
        if(!key_exists($number-1,$recipes)) return $this->redirectToRoute("app_recipe",['number'=>$number-2]);
        $recipe = $recipes[$number - 1];
        if ($recipe == null) {
            return $this->redirectToRoute("app_recipe", ["number" => 1]);
        }
        if($recipe->getOwner()->getId() != $user->getId()){
            $this->addFlash("error",'Une erreur est survenue...');
            return $this->redirectToRoute("app_main");
        }
        $foodRecipe = $entityManager->getRepository(FoodRecipeInRefrigerator::class)->find($id);
        if ($foodRecipe == null) {
            $foodRecipe = $entityManager->getRepository(FoodRecipeNotInRefrigerator::class)->find($id);
            if($foodRecipe == null) return $this->redirectToRoute("app_recipe", ["number" => 1]);
        }

        if($foodRecipe->getRecipe()->getId() != $recipe->getId()){
            $this->addFlash("error",'Une erreur est survenue...');
            return $this->redirectToRoute("app_main");
        }


        if ($request->request->has('_modify_' . $id . '_token') && $this->isCsrfTokenValid('_modify_food_recipe_token_value', $request->request->get('_modify_' . $id . '_token'))) {
            $name = $foodRecipe->getName();
            $entityManager->remove($foodRecipe);
            $entityManager->flush();
            $this->addFlash('success', "L'aliment " . $name . " a été enlevé dans la recette ".$recipe->getName()." !");
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
        if(!array_key_exists($number-1,$recipes)) return $this->redirectToRoute("app_recipe",$number-2);
        $recipe = $recipes[$number - 1];
        if ($recipe == null) {
            return $this->redirectToRoute("app_recipe", ["number" => 1]);
        }
        $legacyName = $recipe->getName();
        if ($request->query->has('token') && $this->isCsrfTokenValid('manual-delete', $request->query->get('token'))) {
            $entityManager->remove($recipe);
            $entityManager->flush();
            $this->addFlash('success', 'Votre recette ' . $legacyName . ' a été supprimé !');
        }

        return $this->redirectToRoute("app_main");
    }

    #[Route('/recipe/{number}/food/add', name: 'app_recipe_add_food')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function addFoodInRecipe(Request $request, EntityManagerInterface $entityManager,$number): Response
    {
        if($entityManager->getRepository(FreshUser::class)->findOneBy(['email'=>$this->getUser()->getUserIdentifier()]) == null){

        }

        $user = $entityManager->getRepository(FreshUser::class)->findOneBy(['email'=>$this->getUser()->getUserIdentifier()]);
        if($number > 0 && $number < 11){
            if($entityManager->getRepository(Recipe::class)->findAll()[$number-1] != null){
                $recipe = $entityManager->getRepository(Recipe::class)->findAll()[$number-1];
                if($recipe->getOwner()->getId() == $user->getId()) {
                    $refrigerators = $user->getRefrigerators();
                    $allFoodsInRecipe = array();
                    foreach ($recipe->getFoodRecipeInRefrigerators() as $foodRecipeInRefrigerator) {
                        array_push($allFoodsInRecipe, $foodRecipeInRefrigerator);
                    }
                    foreach ($recipe->getFoodRecipeNotInRefrigerators() as $foodRecipeNotInRefrigerator) {
                        array_push($allFoodsInRecipe, $foodRecipeNotInRefrigerator);
                    }
                    if ($request->request->has('name') && $request->request->has('quantity') && $request->request->has('unit')) {
                        $foodRecipeName = $request->request->get('name');
                        $foodRecipeQuantity = $request->request->get('quantity');
                        $foodRecipeUnit = $request->request->get('unit');
                        foreach ($recipe->getFoodRecipeInRefrigerators() as $foodRecipeInRefrigerator) {
                            if ($foodRecipeInRefrigerator->getFood()->getName() == $foodRecipeName) {
                                $this->addFlash('error', 'Cet aliment est déjà dans la recette...');
                                return $this->redirectToRoute("app_recipe_add_food", ['number'=>$number]);
                            }
                        }
                        foreach ($recipe->getFoodRecipeNotInRefrigerators() as $foodRecipeNotInRefrigerator) {
                            if ($foodRecipeNotInRefrigerator->getName() == $foodRecipeName) {
                                $this->addFlash('error', 'Cet aliment est déjà dans la recette...');
                                return $this->redirectToRoute("app_recipe_add_food", ['number'=>$number]);
                            }
                        }
                        $existFood = false;
                        foreach ($user->getRefrigerators() as $refrigerator) {
                            foreach ($refrigerator->getFoods() as $food) {
                                if ($food->getName() == $foodRecipeName) {
                                    $foodRecipeInRefrigerator = new FoodRecipeInRefrigerator();
                                    $foodRecipeInRefrigerator->setRefrigerator($refrigerator);
                                    $foodRecipeInRefrigerator->setFood($food);
                                    $foodRecipeInRefrigerator->setQuantity($foodRecipeQuantity);
                                    $foodRecipeInRefrigerator->setUnit($foodRecipeUnit);
                                    $recipe->addFoodRecipeInRefrigerator($foodRecipeInRefrigerator);
                                    $entityManager->persist($foodRecipeInRefrigerator);
                                    $entityManager->flush();
                                    $existFood = true;
                                    break;
                                }
                            }
                        }
                        if (!$existFood) {
                            $foodRecipeNotInRefrigerator = new FoodRecipeNotInRefrigerator();
                            $foodsCanBe = $entityManager->getConnection()->prepare(
                                "SELECT * FROM food INNER JOIN refrigerator ON food.refrigerator_id = refrigerator.id 
                                    WHERE food.name LIKE :foodName AND refrigerator.owner_id = :ownerId");
                            $foodRecipeNameLike = "%" . $foodRecipeName . "%";
                            $foodsCanBe = $foodsCanBe->executeQuery(['foodName' => $foodRecipeNameLike, 'ownerId' => $user->getId()])->fetchAllAssociative();
                            $foodRecipeNotInRefrigerator->setCanBeRegroup(!empty($foodsCanBe));
                            $foodRecipeNotInRefrigerator->setName($foodRecipeName);
                            $foodRecipeNotInRefrigerator->setQuantity($foodRecipeQuantity);
                            $foodRecipeNotInRefrigerator->setUnit($foodRecipeUnit);
                            $recipe->addFoodRecipeNotInRefrigerator($foodRecipeNotInRefrigerator);
                            $entityManager->persist($foodRecipeNotInRefrigerator);
                            $entityManager->flush();
                        }
                        $entityManager->persist($recipe);
                        $entityManager->flush();
                        $this->addFlash('success', "Vous avez ajouté l'aliment " . $foodRecipeName . " dans la recette " . $recipe->getName() . " !");
                        if (!$request->request->has('add-food')) {
                            return $this->redirectToRoute("app_recipe", ['number'=>$number]);
                        }else{
                            return $this->redirectToRoute("app_recipe_add_food",['number'=>$number]);
                        }
                    }
                    return $this->render('recipe/add.html.twig', [
                        'user' => $user,
                        'refrigerators' => $refrigerators,
                        'recipe' => $recipe,
                        'allFoodsInRecipe' => $allFoodsInRecipe,
                        'number' => $number
                    ]);
                }
            }
        }else{
            return $this->redirectToRoute("app_recipe",['number'=>count($user->getRecipes())]);
        }
        $this->addFlash("error","Une erreur est survenue...");
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
        $number = count($user->getRecipes())+1;
        if ($recipeForm->isSubmitted() && $recipeForm->isValid()) {
            if($entityManager->getRepository(Recipe::class)->findOneBy(['name'=>$recipe->getName()])){
                $this->addFlash('error', "Vous avez déjà une recette portant ce nom :)");
                return $this->redirectToRoute("app_recipe_add");
            }
            $recipe->setCreateDate(new \DateTime("now"));
            $recipe->setOwner($user);
            if($request->request->has('name') && $request->request->has('quantity') && $request->request->has('unit')){
                $foodRecipeName = $request->request->get('name');
                $foodRecipeQuantity = $request->request->get('quantity');
                $foodRecipeUnit = $request->request->get('unit');
                $existFood = false;
                foreach($user->getRefrigerators() as $refrigerator) {
                    foreach ($refrigerator->getFoods() as $food) {
                        if($food->getName() == $foodRecipeName){
                            $foodRecipeInRefrigerator = new FoodRecipeInRefrigerator();
                            $foodRecipeInRefrigerator->setRefrigerator($refrigerator);
                            $foodRecipeInRefrigerator->setFood($food);
                            $foodRecipeInRefrigerator->setQuantity($foodRecipeQuantity);
                            $foodRecipeInRefrigerator->setUnit($foodRecipeUnit);
                            $recipe->addFoodRecipeInRefrigerator($foodRecipeInRefrigerator);
                            $entityManager->persist($foodRecipeInRefrigerator);
                            $entityManager->flush();
                            $existFood = true;
                            break;
                        }
                    }
                }

                if(!$existFood){
                    $foodRecipeNotInRefrigerator = new FoodRecipeNotInRefrigerator();
                    $foodsCanBe = $entityManager->getConnection()->prepare(
                        "SELECT * FROM food INNER JOIN refrigerator ON food.refrigerator_id = refrigerator.id 
                                    WHERE food.name LIKE :foodName AND refrigerator.owner_id = :ownerId");
                    $foodRecipeNameLike = "%".$foodRecipeName."%";
                    $foodsCanBe = $foodsCanBe->executeQuery(['foodName' => $foodRecipeNameLike, 'ownerId' => $user->getId()])->fetchAllAssociative();
                    $foodRecipeNotInRefrigerator->setCanBeRegroup(!empty($foodsCanBe));
                    $foodRecipeNotInRefrigerator->setName($foodRecipeName);
                    $foodRecipeNotInRefrigerator->setQuantity($foodRecipeQuantity);
                    $foodRecipeNotInRefrigerator->setUnit($foodRecipeUnit);
                    $recipe->addFoodRecipeNotInRefrigerator($foodRecipeNotInRefrigerator);
                    $entityManager->persist($foodRecipeNotInRefrigerator);
                    $entityManager->flush();
                }
            }

            $entityManager->persist($recipe);
            $entityManager->flush();
            $this->addFlash("success", "Vous avez ajouté une nouvelle recette ! ");
            return $this->redirectToRoute("app_recipe_add_food", ["number" => $number]);
        }
        return $this->render('recipe/add.html.twig', [
            'form' => $recipeForm,
            'user' => $user,
            'refrigerators'=>$refrigerators,
            'foods'=>$foods,
            'number'=>$number
        ]);
    }
}