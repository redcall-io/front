<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Component\HttpFoundation\NoContentResponse;
use App\Entity\Badge;
use App\Form\Type\BadgeWidgetType;
use App\Form\Type\CategoryWigetType;
use App\Manager\BadgeManager;
use App\Model\Csrf;
use Bundles\PaginationBundle\Manager\PaginationManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/badges", name="admin_badge_")
 */
class BadgeController extends BaseController
{
    /**
     * @var PaginationManager
     */
    private $paginationManager;

    /**
     * @var BadgeManager
     */
    private $badgeManager;

    public function __construct(PaginationManager $paginationManager, BadgeManager $badgeManager)
    {
        $this->paginationManager = $paginationManager;
        $this->badgeManager      = $badgeManager;
    }

    /**
     * @Route(name="index")
     * @Template("admin/badge/badges.html.twig")
     */
    public function index(Request $request) : array
    {
        $searchForm = $this->createSearchForm($request, 'admin.badge.search');

        $badges = $this->paginationManager->getPager(
            $this->badgeManager->getSearchInPublicBadgesQueryBuilder(
                $searchForm->get('criteria')->getData()
            )
        );

        return [
            'badges' => $badges,
            'counts' => $this->badgeManager->getVolunteerCountInSearch($badges),
            'search' => $searchForm->createView(),
        ];
    }

    /**
     * @Route(path="/remove-{id}/{token}", name="remove")
     */
    public function remove(Badge $badge, Csrf $token)
    {
        if ($badge->canBeRemoved()) {
            $this->badgeManager->remove($badge);
        }

        return new NoContentResponse();
    }

    /**
     * @Route(path="/manage-{id}", name="manage")
     * @Template("admin/badge/manage.html.twig")
     */
    public function manage(Request $request, ?Badge $badge = null)
    {
        $collection = clone $badge->getSynonyms();
        $form       = $this->createManageForm($request, $badge);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update required on the owning side
            foreach ($collection as $synonym) {
                $synonym->setSynonym(null);
                $this->badgeManager->save($synonym);
            }
            foreach ($badge->getSynonyms() as $synonym) {
                $synonym->setSynonym($badge);
                $this->badgeManager->save($synonym);
            }

            $this->badgeManager->save($badge);

            return $this->redirectToRoute('admin_badge_manage', array_merge($request->query->all(), [
                'id' => $badge->getId(),
            ]));
        }

        return [
            'badge' => $badge,
            'form'  => $form->createView(),
        ];
    }

    /**
     * @Route(path="/toggle-visibility-{id}/{token}", name="toggle_visibility")
     */
    public function toggleVisibility(Badge $badge, Csrf $token)
    {
        if ($badge->isUsable()) {
            $badge->setVisibility(1 - $badge->getVisibility());

            $this->badgeManager->save($badge);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    private function createSearchForm(Request $request, string $label) : FormInterface
    {
        return $this->createFormBuilder(null, ['csrf_protection' => false])
                    ->setMethod('GET')
                    ->add('criteria', TextType::class, [
                        'label'    => $label,
                        'required' => false,
                    ])
                    ->add('submit', SubmitType::class, [
                        'label' => 'base.button.search',
                    ])
                    ->getForm()
                    ->handleRequest($request);
    }

    private function createManageForm(Request $request, ?Badge $badge) : FormInterface
    {
        $builder = $this
            ->createFormBuilder($badge, [
                'data_class' => Badge::class,
            ])
            ->add('name', TextType::class, [
                'label' => 'admin.badge.form.name',
            ])
            ->add('description', TextType::class, [
                'label' => 'admin.badge.form.description',
            ])
            ->add('category', CategoryWigetType::class, [
                'label'    => 'admin.badge.form.category',
                'required' => false,
            ])
            ->add('renderingPriority', NumberType::class, [
                'label'    => 'admin.badge.form.rendering_priority',
                'required' => false,
            ])
            ->add('triggeringPriority', NumberType::class, [
                'label'    => 'admin.badge.form.triggering_priority',
                'required' => false,
            ])
            ->add('parent', BadgeWidgetType::class, [
                'label'    => 'admin.badge.form.parent',
                'required' => false,
            ])
            ->add('synonyms', BadgeWidgetType::class, [
                'label'    => 'admin.badge.form.synonyms',
                'required' => false,
                'multiple' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.save',
            ]);

        return $builder
            ->getForm()
            ->handleRequest($request);
    }

}