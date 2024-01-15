<?php

namespace App\Controller;

use App\Entity\Food;
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
            if($number < 1 || $number > 2){
                return $this->redirectToRoute("app_main");
            }
            $recipe = $recipes[$number - 1];
            if ($recipe == null) {
                return $this->redirectToRoute("app_recipe", ["number" => 1]);
            }


            return $this->render('recipe/index.html.twig', [
                'recipe' => $recipe,
                'number' => $number,
                'user' => $user,
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
            foreach ($recipe->getAlerts() as $alert) {
                $entityManager->remove($alert);
                $entityManager->flush();
            }
            foreach ($recipe->getFoods() as $food) {
                $entityManager->remove($food);
                $entityManager->flush();
            }
            $entityManager->remove($recipe);
            $entityManager->flush();
            $this->addFlash('success', 'Votre frigo ' . $legacyName . ' a été supprimé !');
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
            dd($recipeForm);
            foreach ($user->getRecipes() as $legacyRecipe) {
                if ($legacyRecipe->getName() == $recipe->getName()) {
                    $this->addFlash('error', "Vous avez déjà une recette portant ce nom :)");
                    return $this->redirectToRoute("app_main");
                }
            }
            $entityManager->persist($recipe);
            $entityManager->flush();
            $this->addFlash("success", "Vous avez ajouté un nouveau frigo !");
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