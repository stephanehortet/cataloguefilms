<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Classe\Search;
use App\Entity\Auteur;
use App\Entity\Equipe;
use App\Entity\Film;
use App\Entity\FilmAuteur;
use App\Entity\Format;
use App\Entity\Television;
use App\Entity\UserPanier;
use App\Form\SearchType;
use App\Repository\FilmRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Classe\StreamlikeUrl;
use Symfony\Component\Validator\Constraints\DateTime;

class HomeController extends AbstractController
{
    private $entityManager;

    public function __construct (EntityManagerInterface $entityManager, StreamlikeUrl $streamlikeUrl)
    {
        $this->entityManager = $entityManager;
        $this->streamlikeUrl = $streamlikeUrl;
    }

    /**
     * @Route("/", name="home")
     */
    public function index(FilmRepository $repository, Request $request): Response
    {
        $search = new Search();
        $search->page = $request->get('page',1);
        $search->statut = $request->get('vue','mosaique');


        $form = $this->createForm(SearchType::class, $search);

        $vue = $request->query->get('vue', 'mosaique');

        $form->handleRequest($request);
        [$min_annee, $max_annee] = $repository->findMinMaxAnnee($search);
        [$min_duree, $max_duree] = $repository->findMinMaxDuree($search);

        $session = $request->getSession();
        $recherche = $session->get('recherche', null);

        if($form->get('rechercher')->isClicked()){
            $search->page = "1";
            $session->set('recherche', $search);
            $recherche = $session->get('recherche');

        }

        if($recherche){
            $search = $recherche;
            $search->page = $request->get('page',1);
        }

        $films = $repository->findWithSearch($search);
        $films_nb =  $films->getTotalItemCount();

        return $this->render('home/index.html.twig',[
            'films' => $films,
            'form' => $form->createView(),
            'films_nb' => $films_nb,
            'min_annee' => $min_annee,
            'max_annee' => $max_annee,
            'min_duree' => $min_duree,
            'max_duree' => $max_duree,
            'titre_search' => $search->titres,
            'realisateur_search' => $search->realisateurs,
            'production_search' => $search->productions,
            'format_search' => $search->formats,
            'categorie_search' => $search->categories,
            'genre_search' => $search->genres,
            'min_duree_search' => $search->min_duree,
            'max_duree_search' => $search->max_duree,
            'min_annee_search' => $search->min_annee,
            'max_annee_search' => $search->max_annee,
            'tags_search' => $search->tags,
            'departement_search' => $search->departement,
            'ville_search' => $search->ville,
            'accessibilite_search' => $search->accessibilite,
            'specificite_search' => $search->specificite,
            'criteres_sup' => $request->get('criteres_sup'),
            'vue' => $vue,
            'page' => $search->page,
        ]);
    }

    /**
     * @Route("/reset", name="home_reset")
     */
    public function reinitialiser(Request $request): Response
    {
        $session = $request->getSession();
        $session->set('recherche', null);
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/film/{id}", name="film_page")
     */
    public function show($id, Request $request): Response
    {
        //Récupération du user afin de vérifier si l'utilisateur est bien un professionnel connecté
        $user = $this->getUser();

        if($user  && $user->getStatut() == "validé") {
            $panierUser = $this->entityManager->getRepository(UserPanier::class)->findOneByUserFilm($user->getId(), $id);
        }else{
            $panierUser = null;
        }

        $film = $this->entityManager->getRepository(Film::class)->findOneById($id);

        //Récupération des auteurs afin de collecter la liste des films d'un même auteur
        $auteurs_collection = new ArrayCollection();

        foreach ($film->getAuteur() as $auteur){
            $liste_film_auteur = $this->entityManager->getRepository(Auteur::class)->findByIdFm($auteur->getIdFm());

            foreach ($liste_film_auteur as $film_auteur){

                $film_autre = $this->entityManager->getRepository(FilmAuteur::class)->findOneByAuteurId($film_auteur->getId());
                dump($film_autre);
                if($film_auteur->getId() != $id){
                    $auteurs_collection->add($film_auteur);
                }
            }

        }
        dd("stop");
        $lieux_tournages_14_all = array();
        $lieux_tournages_27_all = array();
        $lieux_tournages_50_all = array();
        $lieux_tournages_61_all = array();
        $lieux_tournages_76_all = array();
        $lieux_tournages_region_autre_all = array();
        $lieux_tournages_all = array();

        foreach ($film->getTournage() as $tournage){
            foreach ($tournage->getLieuTournage() as $lieu_tournage){
                if($lieu_tournage->getDepartement() == "14"){
                    $lieu_tournage_14 = $lieu_tournage->getVille()." (".$lieu_tournage->getDepartement().")";
                    array_push($lieux_tournages_14_all, $lieu_tournage_14);
                    array_push($lieux_tournages_all, $lieu_tournage_14);
                }
                if($lieu_tournage->getDepartement() == "27"){
                    $lieu_tournage_27 = $lieu_tournage->getVille()." (".$lieu_tournage->getDepartement().")";
                    array_push($lieux_tournages_27_all, $lieu_tournage_27);
                    array_push($lieux_tournages_all, $lieu_tournage_27);
                }
                if($lieu_tournage->getDepartement() == "50"){
                    $lieu_tournage_50 = $lieu_tournage->getVille()." (".$lieu_tournage->getDepartement().")";
                    array_push($lieux_tournages_50_all, $lieu_tournage_50);
                    array_push($lieux_tournages_all, $lieu_tournage_50);
                }
                if($lieu_tournage->getDepartement() == "61"){
                    $lieu_tournage_61 = $lieu_tournage->getVille()." (".$lieu_tournage->getDepartement().")";
                    array_push($lieux_tournages_61_all, $lieu_tournage_61);
                    array_push($lieux_tournages_all, $lieu_tournage_61);
                }
                if($lieu_tournage->getDepartement() == "76"){
                    $lieu_tournage_76 = $lieu_tournage->getVille()." (".$lieu_tournage->getDepartement().")";
                    array_push($lieux_tournages_76_all, $lieu_tournage_76);
                    array_push($lieux_tournages_all, $lieu_tournage_76);
                }
                if($lieu_tournage->getRegion() || $lieu_tournage->getPays()){
                    $lieu_tournage_region_autre = $lieu_tournage->getRegion().$lieu_tournage->getPays();
                    array_push($lieux_tournages_region_autre_all, $lieu_tournage_region_autre);
                    array_push($lieux_tournages_all, $lieu_tournage_region_autre);
                }
            }
        }

        $lieux_tournages_14_all = array_unique($lieux_tournages_14_all);
        $lieux_tournages_27_all = array_unique($lieux_tournages_27_all);
        $lieux_tournages_50_all = array_unique($lieux_tournages_50_all);
        $lieux_tournages_61_all = array_unique($lieux_tournages_61_all);
        $lieux_tournages_76_all = array_unique($lieux_tournages_76_all);
        $lieux_tournages_region_autre_all = array_unique($lieux_tournages_region_autre_all);
        $lieux_tournages_all = array_unique($lieux_tournages_all);

        //Récupération des données de session pour voir si un token est déjà actif
        $session = $request->getSession();
        $creditNb = null;
        $url_media = "";
        $media = "";
        $media_security = "token";
        $need_credit = false;

        //Si c'est un professionnel validé, affichage du média
        if($user  && $user->getStatut() == "validé"){

            if($request->query->get('credit') == "demande"){
                $content_admin = "Catalogue des films - Nouvelle demande de crédits :      
                        <br>          
                        Structure : ".$user->getStructure()." <br>
                        Prénom : ".$user->getPrenom()." <br>
                        Nom : ".$user->getNom()." <br>
                        Adresse mail : ".$user->getEmail()." <br>   
                        Téléphone : ".$user->getTelephone();

                $mail_admin = new Mail();
                $mail_admin->send('stephanehortet@normandieimages.fr', 'mailautomatique ', 'Demande de crédits pour catalogue films', $content_admin);

                $this->addFlash('notification', "Votre demande de crédits supplémentaires a été transmise à nos services." );
            }

            //Récupération du nombre de crédit disponible
            $creditNb = $user->getCreditNb();

            //Vérification si le film est dans streamlike
            if(!$film->getIdStreamlike()){
                $media = "no_media_streamlike";
            }else{
                //Récupération du média dans streamlike
                $media = $this->streamlikeUrl->getMedia($film->getIdStreamlike());


                if($media["visibility"]["state"] != "online"){
                    $media = "no_media_streamlike";
                    return $this->render('home/solo.html.twig', [
                        'film' => $film,
                        'creditNb' => $creditNb,
                        'lieu_tournage_array' => $lieux_tournages_all,
                        'auteurs_collection' => $auteurs_collection,
                        'url_media' => $url_media,
                        'media_security' => $media_security,
                        'media_exist' => $media,
                        'need_credit' => $need_credit,
                        'panierUser' => $panierUser,
                    ]);
                }

                //Si le média est accessible dans streamlike
                if($media["visibility"]["state"] == "online"){
                    //Vérification si il est en accès libre sur le site de NI
                    if(array_key_exists("referrers", $media["security"])){
                        foreach ($media["security"]["referrers"] as $referrer){
                            if($referrer['label'] == "catalogue films"){
                                $media_security = "referrer";
                            }
                            if($referrer['label'] == "detail film NI"){
                                $media = "no_media_streamlike";
                            }
                        }
                    }

                    //Si le média n'est pas en accès libre
                    if($media_security == "token"){

                        $actifToken = false;
                        //Vérification si le token est actif
                        $media_list = $this->streamlikeUrl->getUrl($film->getIdStreamlike());
                        $now = new \DateTime();

                        if($media_list['total_count'] > 0){
                            foreach($media_list['data'] as $media_data){
                                if($media_data['url'] == $session->get('save_url_'.$film->getIdStreamlike())){
                                    $expired_at = new \DateTime($media_data['expired_at']);
                                    if ($now < $expired_at) {
                                        $actifToken = true;
                                    }
                                }
                            }
                        }

                        //Si $actifToken = true, on affiche le média
                        if($actifToken == true){
                            $url_media = $session->get('save_url_'.$film->getIdStreamlike());
                        }else{
                            //Si un crédit a été utilisé pour visionner le film, on l'affiche
                            if($request->query->get('credit') == "show-".$film->getIdStreamlike()){
                                //S'il reste suffisamment de crédit, génération de l'url tokenisé du film
                                if($creditNb > 0){
                                    $user->setCreditNb($user->getCreditNb() - 1);
                                    $this->entityManager->flush();
                                    $url_media = $this->streamlikeUrl->postUrl($film->getIdStreamlike());
                                    $session->set('save_url_'.$film->getIdStreamlike(), $url_media);
                                    return $this->redirectToRoute('film_page', ['id' => $id, '_fragment' => 'ancre-film-complet']);
                                }else{
                                    $need_credit = true;
                                }
                            }else{
                                $need_credit = true;
                            }
                        }
                    }
                }
            }
        }

        if (!$film) {
            return $this->redirectToRoute('home');
        }

        return $this->render('home/solo.html.twig', [
            'film' => $film,
            'creditNb' => $creditNb,
            'lieu_tournage_all' => $lieux_tournages_all,
            'auteurs_collection' => $auteurs_collection,
            'url_media' => $url_media,
            'media_security' => $media_security,
            'media_exist' => $media,
            'need_credit' => $need_credit,
            'panierUser' => $panierUser,
        ]);
    }

    /**
     * @Route("/film/suppression/{idFm}/{token}", name="delete-film")
     */
    public function deleteFilm($idFm, $token)
    {
        $films = $this->entityManager->getRepository(Film::class)->findByIdFm($idFm);
        foreach ($films as $film){
            if($token == $film->getTokenDel()) {
                foreach ($film->getAuteur() as $auteur){
                    $this->entityManager->remove($auteur);
                }
                foreach ($film->getProduction() as $production){
                    $this->entityManager->remove($production);
                }
                foreach ($film->getUserPaniers() as $panier){
                    $this->entityManager->remove($panier);
                }
                $this->entityManager->remove($film);
            }
        }
        $this->entityManager->flush();

        return $this->render('home/suppression.html.twig');
    }
}
