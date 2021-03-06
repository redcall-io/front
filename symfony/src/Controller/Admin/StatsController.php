<?php

namespace App\Controller\Admin;

use App\Base\BaseController;
use App\Manager\ReportManager;
use App\Manager\StatisticsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @Route("/admin/stats", name="admin_stats_")
 */
class StatsController extends BaseController
{
    /**
     * @var ReportManager
     */
    private $reportManager;

    public function __construct(ReportManager $reportManager)
    {
        $this->reportManager = $reportManager;
    }

    /**
     * @Route("/", name="home")
     * @Template
     */
    public function index()
    {
        return [];
    }

    /**
     * @Route("/general", name="general")
     * @Template()
     */
    public function general(StatisticsManager $statisticsManager, Request $request) : array
    {
        // TODO make a real form 🤮

        $from = $request->query->get('from') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('from')) : new \DateTime('-7days');
        $to   = $request->query->get('to') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('to')) : new \DateTime();

        $from->setTime(0, 0);
        $to->setTime(23, 59, 59);

        return [
            'stats' => $statisticsManager->getDashboardStatistics($from, $to),
            'from'  => $from,
            'to'    => $to,
        ];
    }

    /**
     * @Route("/structure", name="structure")
     * @Template
     */
    public function structure(Request $request)
    {
        $form = $this->createStructureForm($request);

        $report = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $report = $this->reportManager->createStructureReport(
                $form->get('from')->getData(),
                $form->get('to')->getData(),
                $form->get('min')->getData()
            );
        }

        return [
            'form'   => $form->createView(),
            'report' => $report,
        ];
    }

    private function createStructureForm(Request $request) : FormInterface
    {
        return $this
            ->createFormBuilder([
                'from' => new \DateTime(sprintf('%d-01-01', (new \DateTime())->format('Y') - 1)),
                'to'   => new \DateTime(sprintf('%d-12-31', (new \DateTime())->format('Y') - 1)),
                'min'  => 5,
            ])
            ->add('from', DateType::class, [
                'label'       => 'admin.statistics.structure.form.from',
                'widget'      => 'single_text',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('to', DateType::class, [
                'label'       => 'admin.statistics.structure.form.to',
                'widget'      => 'single_text',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('min', NumberType::class, [
                'label'       => 'admin.statistics.structure.form.min',
                'constraints' => [
                    new NotBlank(),
                    new Range(['min' => 1]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'base.button.submit',
                'attr'  => [
                    'class' => 'btn btn-primary trigger-launch',
                ],
            ])
            ->getForm()
            ->handleRequest($request);
    }
}