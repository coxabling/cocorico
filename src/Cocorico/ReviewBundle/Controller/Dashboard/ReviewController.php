<?php

/*
 * This file is part of the Cocorico package.
 *
 * (c) Cocolabs SAS <contact@cocolabs.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cocorico\ReviewBundle\Controller\Dashboard;

use Cocorico\CoreBundle\Entity\Booking;
use Cocorico\ReviewBundle\Entity\Review;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Review controller.
 *
 * @Route("/review")
 */
class ReviewController extends Controller
{
    /**
     * Creates a new rating for a booking.
     *
     * @Route("/new/{booking_id}", name="cocorico_dashboard_review_new")
     *
     * @Method({"GET", "POST"})
     * @ParamConverter("booking", class="Cocorico\CoreBundle\Entity\Booking",
     *          options={"id" = "booking_id"})
     * @Security("is_granted('add', booking)")
     *
     * @param  Request $request
     * @param  Booking $booking
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request, Booking $booking)
    {
        $user = $this->getUser();
        $formHandler = $this->get('cocorico.form.handler.review');
        $breadcrumbManager = $this->get('cocorico.breadcrumbs_manager');
        $translator = $this->get('translator');

        //Reviews form handling
        $review = $formHandler->create($booking, $user);
        if (!$review) {
            throw new AccessDeniedException('Review already added for this booking by user');
        }
        $form = $this->createCreateForm($review);
        $submitted = $formHandler->process($form);
        if ($submitted !== false) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $translator->trans('review.new.success', array(), 'cocorico_review')
            );

            return $this->redirect($this->generateUrl('cocorico_dashboard_reviews_added'));
        }

        //Breadcrumbs
        $breadcrumbManager->addPreItems($request);
        $breadcrumbManager->addItem(
            $translator->trans('Comments', array(), 'cocorico_breadcrumbs'),
            $this->generateUrl('cocorico_dashboard_reviews_received')
        );
        $breadcrumbManager->addItem(
            $booking->getListing()->getTitle(),
            $this->generateUrl('cocorico_dashboard_review_new', array('booking_id' => $booking->getId()))
        );

        return $this->render(
            'CocoricoReviewBundle:Dashboard/Review:new.html.twig',
            array(
                'form' => $form->createView(),
                'booking' => $booking,
                'reviewTo' => $review->getReviewTo()
            )
        );
    }

    /**
     * Creates a form to create a review entity.
     *
     * @param review $review The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Review $review)
    {
        $form = $this->get('form.factory')->createNamed(
            '',
            'review_new',
            $review
        );

        return $form;
    }


    /**
     * List of reviews made by the user
     *
     * @Route("/reviews-made", name="cocorico_dashboard_reviews_added")
     *
     * @Method({"GET"})
     *
     * @param  Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function madeReviewsAction(Request $request)
    {
        $user = $this->getUser();
        $userType = $request->getSession()->get('profile', 'asker');

        $reviewManager = $this->get('cocorico.review.manager');
        $reviews = $reviewManager->getUserReviews($userType, $user);
        $bookings = $reviewManager->getUnreviewedBooking($userType, $user);

        return $this->render(
            'CocoricoReviewBundle:Dashboard/Review:made_reviews.html.twig',
            array(
                'reviews' => $reviews,
                'bookings' => $bookings
            )
        );
    }

    /**
     * List of reviews received to the user user
     *
     * @Route("/reviews-received", name="cocorico_dashboard_reviews_received")
     *
     * @Method({"GET"})
     *
     * @param  Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function receivedReviewsAction(Request $request)
    {
        $user = $this->getUser();
        $userType = $request->getSession()->get('profile', 'asker');

        $reviewManager = $this->get('cocorico.review.manager');

        $reviews = $reviewManager->getUserReviews($userType, $user, false);
        $bookings = $reviewManager->getUnreviewedBooking($userType, $user);

        return $this->render(
            'CocoricoReviewBundle:Dashboard/Review:received_reviews.html.twig',
            array(
                'reviews' => $reviews,
                'bookings' => $bookings
            )
        );
    }
}
