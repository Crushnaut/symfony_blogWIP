<?php
// src/General/SymProjectBundle/Controller/PageController.php

namespace General\SymProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PageController extends Controller
{
    public function indexAction(Request $request, $filter = null)
    {

        $em = $this->getDoctrine()->getManager();

        $blogs = $em->getRepository('GeneralSymProjectBundle:Blog')
            ->getLatestBlogs();
//        exit(\Doctrine\Common\Util\Debug::dump($blogs));


        // Search code
        $results = null;
        $query = $request->query->get('q');

        if (!empty($query)) {
            $em = $this->getDoctrine()->getManager();

            $results = $em->createQueryBuilder()
                ->from('GeneralSymProjectBundle:Blog', 'b')
                ->select('b')
                ->where('b.title LIKE :search')
                ->setParameter(':search', "%${query}%")
                ->getQuery()
                ->getResult();
        }


        // Pagination code
        $page = $request->get('page');

        $count_per_page = 5;
        $total_count = $this->getTotalBlogs();
        $total_pages = ceil($total_count/$count_per_page);

        if (!is_numeric($page)) {
            $page = 1;
        } else {
            $page = floor($page);
        }

        if ($total_count <= $count_per_page) {
            $page = 1;
        }

        if (($page * $count_per_page) > $total_count) {
            $page = $total_pages;
        }

        $offset = 0;

        if ($page > 1) {
            $offset = $count_per_page * ($page - 1);
        }

        $em = $this->getDoctrine()->getManager();

        $blogQuery = $em->createQueryBuilder()
            ->select('b')
            ->from('GeneralSymProjectBundle:Blog', 'b')
            ->setFirstResult($offset)
            ->setMaxResults($count_per_page);

        $blogFinalQuery = $blogQuery->getQuery();

        $blogPage = $blogFinalQuery->getArrayResult();

        foreach ($blogPage as $blog) {
            $blog_id = $blog['id'];
            $commentRepository = $this->getDoctrine()
                ->getRepository('GeneralSymProjectBundle:Comment');

            $comments[] = $commentRepository->findByBlog($blog_id);
        }

//        exit(\Doctrine\Common\Util\Debug::dump($comments));

        return $this->render('GeneralSymProjectBundle:Default:index.html.twig', array(
            'blogs'        => $blogs,
            'blogPage'     => $blogPage,
            'total_pages'  => $total_pages,
            'current_page' => $page,
            'comments'     => $comments,
            'query'        => $query,
            'results'      => $results,

        ));
    }

    public function aboutAction(Request $request)
    {
        // Search code
        $results = null;
        $query = $request->query->get('q');

        if (!empty($query)) {
            $em = $this->getDoctrine()->getManager();

            $results = $em->createQueryBuilder()
                ->from('GeneralSymProjectBundle:Blog', 'b')
                ->select('b')
                ->where('b.title LIKE :search')
                ->setParameter(':search', "%${query}%")
                ->getQuery()
                ->getResult();
        }

        return $this->render('GeneralSymProjectBundle:Default:about.html.twig', array(
            'query'        => $query,
            'results'      => $results,

        ));
    }

    public function sidebarAction()
    {
        $em = $this->getDoctrine()->getManager();

        $tags = $em->getRepository('GeneralSymProjectBundle:Blog')
            ->getTags();

        $tagWeights = $em->getRepository('GeneralSymProjectBundle:Blog')
            ->getTagWeights($tags);

        return $this->render('GeneralSymProjectBundle:Page:sidebar.html.twig', array(
            'tags' => $tagWeights,
        ));
    }

    public function getTotalBlogs()
    {
        $em = $this->getDoctrine()->getManager();

        $countQuery = $em->createQueryBuilder()
            ->select('Count(b)')
            ->from('GeneralSymProjectBundle:Blog', 'b');

        $finalQuery = $countQuery->getQuery();

        $total = $finalQuery->getSingleScalarResult();

        return $total;
    }

    public function searchAction(Request $request)
    {
        // Search code
        $results = null;
        $query = $request->query->get('q');

        if (!empty($query)) {
            $em = $this->getDoctrine()->getManager();

            $results = $em->createQueryBuilder()
                ->from('GeneralSymProjectBundle:Blog', 'b')
                ->select('b')
                ->where('b.title LIKE :search')
                ->setParameter(':search', "%${query}%")
                ->getQuery()
                ->getResult();
        }

//        exit(\Doctrine\Common\Util\Debug::dump($results));
        return $this->render('GeneralSymProjectBundle:Default:search.html.twig', array(
            'query'        => $query,
            'results'      => $results,

        ));
    }
}