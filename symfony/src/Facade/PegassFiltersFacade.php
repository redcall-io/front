<?php

namespace App\Facade;

use Bundles\ApiBundle\Contracts\FacadeInterface;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Symfony\Component\Validator\Constraints as Assert;

class PegassFiltersFacade implements FacadeInterface
{
    /**
     * @Assert\Range(min = 1)
     *
     * @var int
     */
    private $page = 1;

    /**
     * @Assert\NotBlank
     * @Assert\Choice(choices = {
     *     Pegass::TYPE_AREA,
     *     Pegass::TYPE_DEPARTMENT,
     *     Pegass::TYPE_STRUCTURE,
     *     Pegass::TYPE_VOLUNTEER
     * })
     *
     * @var string|null
     */
    private $type;

    static public function getExample(FacadeInterface $child = null) : FacadeInterface
    {
        $facade = new self;

        $facade->page = 1;
        $facade->type = Pegass::TYPE_VOLUNTEER;
    }

    public function getPage() : int
    {
        return $this->page;
    }

    public function setPage(int $page) : PegassFiltersFacade
    {
        $this->page = $page;

        return $this;
    }

    public function getType() : ?string
    {
        return $this->type;
    }

    public function setType(?string $type) : PegassFiltersFacade
    {
        $this->type = $type;

        return $this;
    }
}