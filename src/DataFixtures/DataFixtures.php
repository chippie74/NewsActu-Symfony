<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class DataFixtures extends Fixture
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger) 
    {
        $this->slugger=$slugger;
    }   

#cette fonction load() sera executée en ligne de commande, avec : php bin/console doctrine:fixture:load --append
# => le drapeau --append permet de ne pas purger la BDD.

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        //set
        // $manager->persist($product);
        //déclaration d'une variable de type array
        $categories = [
            'Politique',
            'Société',
            'People',
            'Économie',
            'Santé',
            'Sport',
            'Espace',
            'Sciences',
            'Mode',
            'Informatique',
            'Écologie',
            'Cinéma',
            'Hi Tech',
        ];
        //la boucle foreach est optimisée pour les arrays
        //
        foreach($categories as $cat){

            $categorie = new Categorie();
            //appel des setters de notre objet $categorie
            $categorie->setName($cat);
            $categorie->setAlias($this->slugger->slug($cat));
            $categorie->setCreatedAt(new DateTime());
            $categorie->setUpdatedAt(new DateTime());
            //entitymanager on appel sa methode persist()pour inserer en BDD l'objet $categorie
            $manager->persist($categorie);
        }
//on vide l'entitymanager pour la suite
        $manager->flush();
    }
}
