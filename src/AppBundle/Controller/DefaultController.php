<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $post = new Post();
        $form = $this->createForm('AppBundle\Form\PostType', $post);
        $searchform = $this->createForm('AppBundle\Form\PostSearchType');
        $searchform->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        // Search?
        if ($request->get('post_search')) {
            $search = $request->get('post_search');

            $posts = $em->getRepository('AppBundle:Post')
                ->createQueryBuilder('p')
                ->where('p.title LIKE :search')
                ->setParameter('search', '%'.$search['search'].'%')
                ->getQuery()
                ->getResult();
        } else {
            $posts = $em->getRepository('AppBundle:Post')->findAll();
        }

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $posts, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            10/*limit per page*/
        );

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'post' => $post,
            'searchform' => $searchform->createView(),
            'form' => $form->createView(),
            'pagination' => $pagination,
        ]);
    }

}
