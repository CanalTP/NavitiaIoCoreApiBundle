<?php

namespace CanalTP\NavitiaIoCoreApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CanalTP\NavitiaIoCoreApiBundle\Entity\Token;

class UserRestController extends Controller
{
    /**
     * @param string $username
     * @param string $_format
     *
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function getUserAction($username, $_format)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserByUsername($username);

        if (!is_object($user)) {
            throw $this->createNotFoundException();
        }

        $tokens = $this->get('canal_tp_tyr.api')->getUserKeys($user->getId());
        if (is_array($tokens)) {
            $user->setTokens(Token::createFromObjects($tokens));
        }

        $data = $this->container->get('serializer')->serialize(
            array('users' => $user),
            $_format
        );

        return new Response($data);
    }

    /**
     * @param string $_format
     *
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function getUsersAction(Request $request, $_format)
    {
        $userManager = $this->get('fos_user.user_manager');
        $sortField = $request->query->getAlpha('sort_by', 'id');
        $sortOrder = $request->query->getAlpha('sort_order', 'asc');
        $count = $request->query->getInt('count', 10);

        if ($request->query->has('start_date') && $request->query->has('end_date')) {
            $users = $userManager->findUsersBetweenDates(
                new \DateTime($request->query->get('start_date')),
                new \DateTime($request->query->get('end_date')),
                $sortField,
                $sortOrder
            );
        } else {
            $users = $userManager->findSortedUsers($sortField, $sortOrder);
        }

        if (!is_array($users)) {
            throw $this->createNotFoundException();
        }

        $paginator = $this->container->get('knp_paginator');
        $pagination = $paginator->paginate(
            $users,
            $request->query->getInt('page', 1),
            ($count > 0 ? $count : 1)
        );
        $pagination->setCustomParameters(
            array(
                'total_result'      => $pagination->getTotalItemCount(),
                'start_page'        => $pagination->getCurrentPageNumber(),
                'items_per_page'    => $pagination->getItemNumberPerPage()
            )
        );

        foreach ($pagination->getItems() as $user) {
            $tokens = $this->get('canal_tp_tyr.api')->getUserKeys($user->getId());
            if (is_array($tokens)) {
                $user->setTokens(Token::createFromObjects($tokens));
            }
        }

        $data = $this->container->get('serializer')->serialize(
            $pagination,
            $_format
        );

        return new Response($data);
    }
}
