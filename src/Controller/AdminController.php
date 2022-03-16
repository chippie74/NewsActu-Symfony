<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleFormType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


/**
 * @Route("/admin")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/tableau-de-bord", name="show_dashboard", methods={"GET"})
     */
    public function showDashboard(EntityManagerInterface $entityManager):Response
    {

        $articles=$entityManager->getRepository(Article::class)->findAll();
        return $this->render('admin/show_dashboard.html.twig', [
            'articles'=>$articles,

        ]);

    }
    /**
     * @Route("/creer-un-article",name="create_article", methods={"GET|POST"})
     */
  public function createArticle(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger)
  : Response
{
    $article=new Article();
    $form=$this->createForm(ArticleFormType::class,$article)
    ->handleRequest($request);
//traitement du formulaire
    if($form->isSubmitted() && $form->isValid()){
// Pour accéder à une valeur d'un input de $form, on fait :
                // $form->get('title')->getData()
//setting des proprietes non mappees dans le formulaire
        $article->setAlias($slugger->slug($article->getTitle()));
        $article->setCreatedAt(new DateTime());
        $article->setUpdatedAt(new DateTime());
        //variabilisation du fichier 'photo' uploadé.
        $file= $form->get('photo')->getData();

        if($file){
        
            // Maintenant il s'agit de reconstruire le nom du fichier pour le sécuriser.
            //1ere etape on deconstuit le nom du fichier et on variabilise
            $extension='.' . $file->guessExtension();
            $originalFilename=pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            //Assainissement du nom de fichier(du filename)
        //    $safeFilename= $slugger->slug($originalFilename);
            $safeFilename=$article->getAlias();
            //2eme etape on reconstruit le nom du fichier maintenant qu il est safe
            //uniqid() est une fonction native de php elle permet de rajouter une valeur numerique(id) unique et auto-generée
            $newFilename=  $safeFilename . '_' . uniqid("",true) . $extension;
            try{
            //on a configuré un parametre 'uploads_dir' dans le fichier services.yaml
            //ce param contient le chemin de notre dossier d'upload de photo
                $file->move($this->getParameter('uploads_dir'),$newFilename);
            //on set le nom de la photo pas le chemin
                $article->setPhoto($newFilename);

            }catch(FileException $exception){

            }
        }
        $entityManager->persist($article);
        $entityManager->flush();

        $this->addFlash('success','Bravo votre article est bien en ligne');

        return $this->redirectToRoute('show_dashboard');
     }


    return $this->render('admin/form/form_article.html.twig', ['form'=>$form->createView()]);

    }
    //l'action est exécutée 2 fois et accessible par les 2 méthodes (GET|POST)
     /**
      * @Route("/modifier-un-article/{id}", name="update_article", methods={"GET|POST"})
     */
    public function updateArticle(Article $article, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger) : Response
    {

    $originalphoto=$article->getPhoto() ?? '';
    //1er TOUR en méthode GET 
    $form=$this->createForm(ArticleFormType::class, $article, [
        'photo'=>$originalphoto
    ])->handleRequest($request);

    //2eme tour de l action en methode POST
    if($form->isSubmitted() && $form->isValid()){

        $article->setAlias($slugger->slug($article->getTitle()));
        $article->setUpdatedAt(new DateTime());

        $file= $form->get('photo')->getData();

        if($file){
        
            $extension='.' . $file->guessExtension();
            $originalFilename=pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

           
            $safeFilename=$article->getAlias();
            
            $newFilename=  $safeFilename . '_' . uniqid("",true) . $extension;
            try{
            
                $file->move($this->getParameter('uploads_dir'),$newFilename);
            
                $article->setPhoto($newFilename);

            }catch(FileException $exception){
                #code a executer si une erreur est attrapee
            }
        } else {

            $article->setPhoto($originalphoto);
        }
        
        $entityManager->persist($article);
        $entityManager->flush();

        $this->addFlash('success',"L'article" . $article->getTitle() . " a bien été modifié");
        return $this->redirectToRoute("show_dashboard");
    }

    //on reTOURNE la vue pour la méthode GET
    return $this->render('admin/form/form_article.html.twig', [
        'form'=>$form->createView(),
        'article'=>$article
    ]);

    }
    /**
     * @Route("/archiver-un-article/{id}", name="soft_delete_article",methods={"GET"})
     */
    public function softDeleteArticle(Article $article, EntityManagerInterface $entityManager) : Response
    {
        # On set la propriété deletedAt pour archiver l'article.
            # De l'autre coté on affichera les articles où deletedAt === null

        $article->setDeletedAt(new DateTime());
        $entityManager->persist($article);
        $entityManager->flush();

        $this->addFlash('success',"L'article " . $article->getTitle() . " a bien été archivé");

        return $this->redirectToRoute('show_dashboard');

    }

    /**
     * @Route("/archiver-un-article/{id}", name="hard_delete_article",methods={"GET"})
     */
    public function hardDeleteArticle(Article $article, EntityManagerInterface $entityManager) : Response
    {
        $entityManager->remove($article);
        $entityManager->flush();

        $this->addFlash('success',"L'article " . $article->getTitle() . " a bien été supprimé de la base de données");

        return $this->redirectToRoute("show_dashboard");

    }

    /**
     * @Route("/restaurer-un-article/{id}", name="restore_article", methods={"GET"})
     */
    public function restoreArticle(Article $article, EntityManagerInterface $entityManager) : Response
    {
        $article->setDeletedAt();

        $entityManager->persist($article);
        $entityManager->flush();

        $this->addFlash('success',"L'article " . $article->getTitle() . " a bien été restauré");
        return $this->redirectToRoute("show_dashboard");
    }
}



