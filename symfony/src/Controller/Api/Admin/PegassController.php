<?php

namespace App\Controller\Api\Admin;

use App\Facade\Admin\Pegass\PegassFacade;
use App\Facade\Admin\Pegass\PegassFiltersFacade;
use App\Transformer\Admin\PegassTransformer;
use Bundles\ApiBundle\Annotation\Endpoint;
use Bundles\ApiBundle\Annotation\Facade;
use Bundles\ApiBundle\Model\Facade\QueryBuilderFacade;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

/**
 * RedCall aims to trigger people from the Red Cross, but people arrive, evolve, leave.
 *
 * In order to stay sync, RedCall regularly update every entity using the Pegass database, a tool widely used in the
 * French Red Cross.
 *
 * @Route("/api/admin/pegass", name="api_admin_pegass_")
 * @IsGranted("ROLE_ADMIN")
 */
class PegassController
{
    /**
     * @var PegassManager
     */
    private $pegassManager;

    /**
     * @var PegassTransformer
     */
    private $pegassTransformer;

    public function __construct(PegassManager $pegassManager, PegassTransformer $pegassTransformer)
    {
        $this->pegassManager     = $pegassManager;
        $this->pegassTransformer = $pegassTransformer;
    }

    /**
     * Get Pegass records from RedCall cache.
     *
     * @Endpoint(
     *   priority = 999,
     *   request  = @Facade(class     = PegassFiltersFacade::class),
     *   response = @Facade(class     = QueryBuilderFacade::class,
     *                      decorates = @Facade(class = PegassFacade::class))
     * )
     * @Route(name="records", methods={"GET"})
     */
    public function records(PegassFiltersFacade $filters)
    {
        $qb = $this->pegassManager->getEnabledEntitiesQueryBuilder(
            $filters->getType()
        );

        return new QueryBuilderFacade($qb, $filters->getPage(), function (Pegass $pegass) {
            return $this->pegassTransformer->expose($pegass);
        });
    }
}