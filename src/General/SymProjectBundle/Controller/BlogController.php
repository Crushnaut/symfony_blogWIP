<?php
// src/GeneralSymProjectBundle/Controller/BlogController.php

namespace General\SymProjectBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class BlogController extends Controller
{
    public function showAction(Request $request, $id, $slug)
    {
        $em = $this->getDoctrine()->getManager();

        $blog = $em->getRepository('GeneralSymProjectBundle:Blog')->find($id);

        if (!$blog) {
            throw $this->createNotFoundException('Unable to find blog post');
        }

        $comments = $em->getRepository('GeneralSymProjectBundle:Comment')
            ->getComments($blog->getId());

//        exit(\Doctrine\Common\Util\Debug::dump($blog));

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

        foreach ($blogPage as $blogs) {
            $blog_id = $blogs['id'];
            $commentRepository = $this->getDoctrine()
                ->getRepository('GeneralSymProjectBundle:Comment');

            $comment[] = $commentRepository->findByBlog($blog_id);
        }

//        exit(\Doctrine\Common\Util\Debug::dump($comments));

        return $this->render('GeneralSymProjectBundle:Default:show.html.twig', array(
            'blog'         => $blog,
            'blogPage'     => $blogPage,
            'total_pages'  => $total_pages,
            'current_page' => $page,
            'comment'      => $comment,
            'comments'     => $comments,
            'query'        => $query,
            'results'      => $results,

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

    public function archiveAction()
    {
        $em = $this->getDoctrine()->getManager();

        $blogs = $em->getRepository('GeneralSymProjectBundle:Blog')
            ->getLatestBlogs();

        if (!$blogs) {
            throw $this->createNotFoundException('Unable to find blog posts');
        }

        foreach ($blogs as $post) {
            $year = $post->getCreated()->format('Y');
            $month = $post->getCreated()->format('F');
            $blogPosts[$year][$month][] = $post;
        }

//        exit(\Doctrine\Common\Util\Debug::dump($blogPosts[2013]['October']));

        return $this->render('GeneralSymProjectBundle:Default:archive.html.twig', array(
            'blogPosts' => $blogPosts,
        ));
    }
}