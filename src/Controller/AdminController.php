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

class AdminController extends AbstractController
{
    /**
     * @Route("/admin/tableau-de-bord", name="show_dashboard", methods={"GET"})
     */
    public function showDashboard(EntityManagerInterface $entityManager):Response
    {

        $articles=$entityManager->getRepository(Article::class)->findAll();
        return $this->render('admin/show_dashboard.html.twig', [
            'articles'=>$articles,

        ]);

    }
    /**
     * @Route("/admin/creer-un-article",name="create_article", methods={"GET|POST"})
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


    return $this->render('admin/form/create_article.html.twig', ['form'=>$form->createView()]);
}


}