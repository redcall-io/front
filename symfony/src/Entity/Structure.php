<?php

namespace App\Entity;

use Bundles\PegassCrawlerBundle\Entity\Pegass;
use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * @ORM\Entity(repositoryClass="App\Repository\StructureRepository")
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="pf_extid_idx", columns={"platform", "identifier"})
 *     },
 *     indexes={
 *         @ORM\Index(name="name_idx", columns={"name"})
 *     }
 * )
 */
class Structure
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=5)
     */
    private $platform;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $identifier;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $president;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Volunteer", mappedBy="structures")
     */
    private $volunteers;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Structure", inversedBy="childrenStructures")
     */
    private $parentStructure;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Structure", mappedBy="parentStructure")
     */
    private $childrenStructures;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastPegassUpdate;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="structures")
     */
    private $users;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\PrefilledAnswers", mappedBy="structure")
     */
    private $prefilledAnswers;

    public function __construct()
    {
        $this->volunteers         = new ArrayCollection();
        $this->childrenStructures = new ArrayCollection();
        $this->users              = new ArrayCollection();
        $this->prefilledAnswers   = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId() : ?int
    {
        return $this->id;
    }

    public function getPlatform()
    {
        return $this->platform;
    }

    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getIdentifier() : ?int
    {
        return $this->identifier;
    }

    /**
     * @param int $identifier
     *
     * @return Structure
     */
    public function setIdentifier(int $identifier) : self
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Structure
     */
    public function setName(string $name) : self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPresident() : ?string
    {
        return $this->president;
    }

    /**
     * @param string|null $president
     *
     * @return Structure
     */
    public function setPresident(?string $president) : self
    {
        $this->president = $president;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isEnabled() : bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return Structure
     */
    public function setEnabled(bool $enabled) : self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return Collection|Volunteer[]
     */
    public function getVolunteers() : Collection
    {
        return $this->volunteers->filter(function (Volunteer $volunteer) {
            return $volunteer->isEnabled();
        });
    }

    /**
     * @param Volunteer $volunteer
     *
     * @return Structure
     */
    public function addVolunteer(Volunteer $volunteer) : self
    {
        if (!$this->volunteers->contains($volunteer)) {
            $this->volunteers[] = $volunteer;
            $volunteer->addStructure($this);
        }

        return $this;
    }

    /**
     * @param Volunteer $volunteer
     *
     * @return Structure
     */
    public function removeVolunteer(Volunteer $volunteer) : self
    {
        if ($this->volunteers->contains($volunteer)) {
            $this->volunteers->removeElement($volunteer);
            $volunteer->removeStructure($this);
        }

        return $this;
    }

    /**
     * @return Structure|null
     */
    public function getParentStructure() : ?self
    {
        return $this->parentStructure;
    }

    /**
     * @param self|null $parentStructure
     *
     * @return Structure
     */
    public function setParentStructure(?self $parentStructure) : self
    {
        $this->parentStructure = $parentStructure;

        return $this;
    }

    /**
     * @return Structure[]
     */
    public function getAncestors() : array
    {
        if (!$this->parentStructure) {
            return [];
        }

        return array_merge([$this->parentStructure], $this->parentStructure->getAncestors());
    }

    /**
     * @return Collection|self[]
     */
    public function getChildrenStructures() : Collection
    {
        return $this->childrenStructures;
    }

    /**
     * @param self $childrenStructure
     *
     * @return Structure
     */
    public function addChildrenStructure(self $childrenStructure) : self
    {
        if (!$this->childrenStructures->contains($childrenStructure)) {
            $this->childrenStructures[] = $childrenStructure;
            $childrenStructure->setParentStructure($this);
        }

        return $this;
    }

    /**
     * @param self $childrenStructure
     *
     * @return Structure
     */
    public function removeChildrenStructure(self $childrenStructure) : self
    {
        if ($this->childrenStructures->contains($childrenStructure)) {
            $this->childrenStructures->removeElement($childrenStructure);
            // set the owning side to null (unless already changed)
            if ($childrenStructure->getParentStructure() === $this) {
                $childrenStructure->setParentStructure(null);
            }
        }

        return $this;
    }

    public function hasChildren() : bool
    {
        return (bool) $this->childrenStructures->count();
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getLastPegassUpdate() : ?DateTimeInterface
    {
        return $this->lastPegassUpdate;
    }

    /**
     * @param DateTimeInterface|null $lastPegassUpdate
     *
     * @return Structure
     */
    public function setLastPegassUpdate(?DateTimeInterface $lastPegassUpdate) : self
    {
        $this->lastPegassUpdate = $lastPegassUpdate;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers() : Collection
    {
        return $this->users;
    }

    public function addUser(User $user) : self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addStructure($this);
        }

        return $this;
    }

    public function removeUser(User $user) : self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            $user->removeStructure($this);
        }

        return $this;
    }

    /**
     * @return Collection|PrefilledAnswers[]
     */
    public function getPrefilledAnswers() : Collection
    {
        return $this->prefilledAnswers;
    }

    public function addPrefilledAnswer(PrefilledAnswers $prefilledAnswer) : self
    {
        if (!$this->prefilledAnswers->contains($prefilledAnswer)) {
            $this->prefilledAnswers->add($prefilledAnswer);
            $prefilledAnswer->setStructure($this);
        }

        return $this;
    }

    public function removePrefilledAnswer(PrefilledAnswers $prefilledAnswers) : self
    {
        if ($this->prefilledAnswers->contains($prefilledAnswers)) {
            $this->prefilledAnswers->remove($prefilledAnswers);
            $prefilledAnswers->setStructure(null);
        }

        return $this;
    }


    /**
     * @return Volunteer|null
     */
    public function getPresidentVolunteer() : ?Volunteer
    {
        foreach ($this->getVolunteers() as $volunteer) {
            if ($volunteer->getNivol() === $this->getPresident()) {
                return $volunteer;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getActiveVolunteers()
    {
        return array_filter($this->volunteers->toArray(), function (Volunteer $volunteer) {
            return $volunteer->isEnabled();
        });
    }

    /**
     * @return DateTime|null
     *
     * @throws Exception
     */
    public function getNextPegassUpdate() : ?DateTime
    {
        if (!$this->lastPegassUpdate) {
            return null;
        }

        // Doctrine loaded an UTC-saved date using the default timezone (Europe/Paris)
        $utc      = (new DateTime($this->lastPegassUpdate->format('Y-m-d H:i:s'), new DateTimeZone('UTC')));
        $interval = new DateInterval(sprintf('PT%dS', Pegass::TTL[Pegass::TYPE_STRUCTURE] * 24 * 60 * 60));

        $nextPegassUpdate = clone $utc;
        $nextPegassUpdate->add($interval);

        return $nextPegassUpdate;
    }

    /**
     * @return bool
     */
    public function canForcePegassUpdate() : bool
    {
        if (!$this->lastPegassUpdate) {
            return true;
        }

        // Doctrine loaded an UTC-saved date using the default timezone (Europe/Paris)
        $utc = (new DateTime($this->lastPegassUpdate->format('Y-m-d H:i:s'), new DateTimeZone('UTC')));

        // Can happen when update dates are spread on a larger timeframe
        // See: PegassManager:spreadUpdateDatesInTTL()
        if ($utc->getTimestamp() > time()) {
            return true;
        }

        // Prevent several updates in less than 1h
        return time() - $utc->getTimestamp() > 3600;
    }

    public function getDisplayName() : string
    {
        return mb_strtoupper($this->name);
    }

    /**
     * @return array
     */
    public function toSearchResults() : array
    {
        return [
            'id'   => (string) $this->getId(),
            'name' => $this->getDisplayName(),
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getDisplayName();
    }
}
