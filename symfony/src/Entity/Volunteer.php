<?php

namespace App\Entity;

use Bundles\PegassCrawlerBundle\Entity\Pegass;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Table(indexes={
 *     @ORM\Index(name="nivolx", columns={"nivol"}),
 *     @ORM\Index(name="emailx", columns={"email"}),
 *     @ORM\Index(name="enabledx", columns={"enabled"}),
 *     @ORM\Index(name="phone_number_optinx", columns={"phone_number_optin"}),
 *     @ORM\Index(name="email_optinx", columns={"email_optin"}),
 *     @ORM\Index(name="optout_untilx", columns={"optout_until"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\VolunteerRepository")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @UniqueEntity("nivol")
 * @UniqueEntity("email")
 */
class Volunteer
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    private $identifier;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, unique=true)
     * @Assert\NotNull
     * @Assert\NotBlank
     * @Assert\Length(max=80)
     */
    private $nivol;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, nullable=true)
     * @Assert\NotNull
     * @Assert\NotBlank
     * @Assert\Length(max=80)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, nullable=true)
     * @Assert\NotNull
     * @Assert\NotBlank
     * @Assert\Length(max=80)
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, nullable=true)
     * @Assert\Length(max=80)
     * @Assert\Email
     */
    private $email;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 1})
     */
    private $enabled = true;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $locked = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $minor = false;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastPegassUpdate;

    /**
     * @var array
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $report;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Structure", inversedBy="volunteers")
     */
    private $structures;

    /**
     * @var User
     *
     * @ORM\OneToOne(targetEntity="App\Entity\User", mappedBy="volunteer")
     */
    private $user;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $phoneNumberLocked = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $emailLocked = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 1})
     */
    private $phoneNumberOptin = true;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 1})
     */
    private $emailOptin = true;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message", mappedBy="volunteer", cascade={"persist"})
     * @ORM\OrderBy({"communication" = "DESC"})
     */
    private $messages;

    /**
     * @ORM\OneToMany(targetEntity=Phone::class, mappedBy="volunteer", orphanRemoval=true, cascade={"all"})
     * @ORM\OrderBy({"preferred" = "DESC"})
     *
     * @Assert\Valid
     */
    private $phones;

    /**
     * @ORM\ManyToMany(targetEntity=Badge::class, inversedBy="volunteers")
     */
    private $badges;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $optoutUntil;

    public function __construct()
    {
        $this->structures = new ArrayCollection();
        $this->messages   = new ArrayCollection();
        $this->phones     = new ArrayCollection();
        $this->badges     = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    public function getIdentifier() : ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier) : self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getNivol() : ?string
    {
        return $this->nivol;
    }

    public function setNivol(string $nivol) : self
    {
        $this->nivol = $nivol;

        return $this;
    }

    public function getFirstName() : ?string
    {
        return $this->firstName;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName() : ?string
    {
        return $this->lastName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhoneNumber() : ?string
    {
        $phone = $this->getPhone();

        return $phone ? $phone->getE164() : null;
    }

    public function hasPhoneNumber(string $phoneNumber) : bool
    {
        foreach ($this->phones as $phone) {
            /** @var Phone $phone */
            if ($phoneNumber === $phone->getE164()) {
                return true;
            }
        }

        return false;
    }

    public function getEmail() : ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email) : Volunteer
    {
        $this->email = $email;

        return $this;
    }

    public function isEnabled() : ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled) : Volunteer
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isLocked() : ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked) : Volunteer
    {
        $this->locked = $locked;

        return $this;
    }

    public function isMinor() : ?bool
    {
        return $this->minor;
    }

    public function setMinor(bool $minor) : Volunteer
    {
        $this->minor = $minor;

        return $this;
    }

    public function getFormattedPhoneNumber() : ?string
    {
        return $this->getPhone() ? $this->getPhone()->getNational() : null;
    }

    public function getLastPegassUpdate() : ?DateTime
    {
        return $this->lastPegassUpdate;
    }

    public function setLastPegassUpdate(DateTime $lastPegassUpdate) : Volunteer
    {
        $this->lastPegassUpdate = $lastPegassUpdate;

        return $this;
    }

    public function getReport() : ?array
    {
        return $this->report ? json_decode($this->report, true) : null;
    }

    public function setReport(array $report) : Volunteer
    {
        $this->report = json_encode($report, JSON_PRETTY_PRINT);

        return $this;
    }

    public function isCallable() : bool
    {
        $hasPhone = $this->getPhone() && $this->phoneNumberOptin;
        $hasEmail = $this->email && $this->emailOptin;
        $isOptin  = !$this->optoutUntil || $this->optoutUntil->getTimestamp() < time();

        return $this->enabled && $isOptin && ($hasPhone || $hasEmail);
    }

    public function addReport(string $message)
    {
        $report   = $this->getReport() ?? [];
        $report[] = $message;
        $this->setReport($report);
    }

    public function getStructures(bool $onlyEnabled = true) : Collection
    {
        if ($onlyEnabled) {
            return $this->getEnabledStructures();
        }

        return $this->structures;
    }

    public function getEnabledStructures() : Collection
    {
        return $this->structures->filter(function (Structure $structure) {
            return $structure->isEnabled();
        });
    }

    public function getStructureIds() : array
    {
        return array_map(function (Structure $structure) {
            return $structure->getId();
        }, $this->getStructures()->toArray());
    }

    public function addStructure(Structure $structure) : self
    {
        if (!$this->structures->contains($structure)) {
            $this->structures[] = $structure;
        }

        return $this;
    }

    public function removeStructure(Structure $structure) : self
    {
        if ($this->structures->contains($structure)) {
            $this->structures->removeElement($structure);
        }

        return $this;
    }

    public function getMainStructure(bool $onlyEnabled = true) : ?Structure
    {
        /** @var Structure|null $mainStructure */
        $mainStructure = null;
        $structures    = $onlyEnabled ? $this->getStructures() : $this->structures;
        foreach ($structures as $structure) {
            /** @var Structure $structure */
            if (!$mainStructure || $structure->getIdentifier() < $mainStructure->getIdentifier()) {
                $mainStructure = $structure;
            }
        }

        return $mainStructure;
    }

    public function toSearchResults()
    {
        $badges = implode(', ', array_map(function (Badge $badge) {
            return $badge->getName();
        }, $this->getVisibleBadges()));

        return [
            'id'    => strval($this->getId()),
            'nivol' => strval($this->getNivol()),
            'human' => sprintf('%s %s%s', $this->getFirstName(), $this->getLastName(), $badges ? sprintf(' (%s)', $badges) : null),
        ];
    }

    public function getNextPegassUpdate() : ?DateTime
    {
        if (!$this->lastPegassUpdate) {
            return null;
        }

        // Doctrine loaded an UTC-saved date using the default timezone (Europe/Paris)
        $utc      = (new DateTime($this->lastPegassUpdate->format('Y-m-d H:i:s'), new DateTimeZone('UTC')));
        $interval = new DateInterval(sprintf('PT%dS', Pegass::TTL[Pegass::TYPE_VOLUNTEER] * 24 * 60 * 60));

        $nextPegassUpdate = clone $utc;
        $nextPegassUpdate->add($interval);

        return $nextPegassUpdate;
    }

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

    public function getDisplayName()
    {
        if ($this->firstName && $this->lastName) {
            return sprintf('%s %s', $this->toName($this->firstName), $this->toName($this->lastName));
        }

        return sprintf('#%s', mb_strtoupper($this->nivol));
    }

    public function getTruncatedName() : string
    {
        if ($this->firstName && $this->lastName) {
            return sprintf('%s %s', $this->toName($this->firstName), strtoupper(substr($this->lastName, 0, 1)));
        }

        return sprintf('#%s', mb_strtoupper($this->nivol));
    }

    public function __toString()
    {
        return $this->getDisplayName();
    }

    public function getUser() : ?User
    {
        if ($this->user && $this->user->isTrusted()) {
            return $this->user;
        }

        return null;
    }

    public function setUser(?User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function isUserEnabled() : bool
    {
        return $this->user && $this->user->isVerified() && $this->user->isTrusted();
    }

    public function isPhoneNumberLocked() : ?bool
    {
        return $this->phoneNumberLocked;
    }

    public function setPhoneNumberLocked(bool $phoneNumberLocked) : self
    {
        $this->phoneNumberLocked = $phoneNumberLocked;

        return $this;
    }

    public function isEmailLocked() : ?bool
    {
        return $this->emailLocked;
    }

    public function setEmailLocked(bool $emailLocked) : self
    {
        $this->emailLocked = $emailLocked;

        return $this;
    }

    public function shouldBeLocked(Volunteer $volunteer) : bool
    {
        $old = get_object_vars($volunteer);
        $new = get_object_vars($this);

        foreach ($old as $key => $value) {
            if ('phone' === $key || 'email' === $key || 'structures' === $key || 'user' === $key) {
                continue;
            }

            if ($old[$key] !== $new[$key]) {
                return true;
            }
        }

        return false;
    }

    public function getHiddenPhone() : ?string
    {
        if (null === ($phone = $this->getPhone())) {
            return null;
        }

        return $phone->getHidden();
    }

    public function getHiddenEmail() : ?string
    {
        if (null === $this->email) {
            return null;
        }

        $username = substr($this->email, 0, strrpos($this->email, '@'));
        $domain   = substr($this->email, strrpos($this->email, '@') + 1);

        return substr($username, 0, 1).str_repeat('*', max(strlen($username) - 2, 0)).substr($username, -1).'@'.$domain;
    }

    public function isPhoneNumberOptin() : ?bool
    {
        return $this->phoneNumberOptin;
    }

    public function setPhoneNumberOptin(bool $phoneNumberOptin) : self
    {
        $this->phoneNumberOptin = $phoneNumberOptin;

        return $this;
    }

    public function isEmailOptin() : ?bool
    {
        return $this->emailOptin;
    }

    public function setEmailOptin(bool $emailOptin) : self
    {
        $this->emailOptin = $emailOptin;

        return $this;
    }

    public function getMessages() : Collection
    {
        return $this->messages;
    }

    /**
     * @Assert\Callback()
     */
    public function doNotDisableRedCallUsers(ExecutionContextInterface $context, $payload)
    {
        if ($this->user && !$this->enabled) {
            $context->buildViolation('form.volunteer.errors.redcall_user')
                    ->atPath('enabled')
                    ->addViolation();
        }
    }

    public function getPhones() : Collection
    {
        return $this->phones;
    }

    public function addPhone(Phone $phone) : self
    {
        if (!$this->phones->contains($phone)) {
            $this->phones[] = $phone;
            $phone->setVolunteer($this);
        }

        return $this;
    }

    public function removePhone(Phone $phone) : self
    {
        if ($this->phones->removeElement($phone)) {
            // set the owning side to null (unless already changed)
            if ($phone->getVolunteer() === $this) {
                $phone->setVolunteer(null);
            }
        }

        return $this;
    }

    public function getPhone() : ?Phone
    {
        foreach ($this->getPhones() as $phone) {
            if ($phone->isPreferred()) {
                return $phone;
            }
        }

        return null;
    }

    /**
     * @return Collection|Badge[]
     */
    public function getBadges() : Collection
    {
        return $this->badges;
    }

    public function setBadges(array $badges)
    {
        $this->badges->clear();
        foreach ($badges as $badge) {
            $this->badges->add($badge);
        }
    }

    public function addBadge(Badge $badge) : self
    {
        if (!$this->badges->contains($badge)) {
            $this->badges[] = $badge;
            $badge->addVolunteer($this);
        }

        return $this;
    }

    public function removeBadge(Badge $badge) : self
    {
        if ($this->badges->contains($badge)) {
            $this->badges->removeElement($badge);
            $badge->removeVolunteer($this);
        }

        return $this;
    }

    /**
     * @param Badge[] $badges
     */
    public function setExternalBadges(array $badges)
    {
        foreach ($this->badges as $badge) {
            /** @var Badge $badge */
            if ($badge->isExternal()) {
                $this->removeBadge($badge);
            }
        }

        foreach ($badges as $badge) {
            $this->addBadge($badge);
        }
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (!$this->getPhones()->count()) {
            return;
        }

        $main = 0;
        foreach ($this->getPhones() as $phone) {
            /** @var Phone $phone */
            if ($phone->isPreferred()) {
                $main++;
            }
        }

        if (0 === $main) {
            if (1 === $this->getPhones()->count()) {
                foreach ($this->getPhones() as $phone) {
                    $phone->setPreferred(true);
                }
            } else {
                $context->buildViolation('form.phone_card.error_no_preferred')
                        ->atPath('phones')
                        ->addViolation();
            }
        }

        if ($main > 1) {
            $context->buildViolation('form.phone_card.error_multi_preferred')
                    ->atPath('phones')
                    ->addViolation();
        }

        $phones = [];
        foreach ($this->getPhones() as $phone) {
            $phones[$phone->getE164()] = ($phones[$phone->getE164()] ?? 0) + 1;
        }
        foreach ($phones as $count) {
            if ($count > 1) {
                $context->buildViolation('form.phone_card.error_duplicate')
                        ->atPath('phones')
                        ->addViolation();
            }
        }
    }

    public function getVisibleBadges() : array
    {
        $badges = $this->badges->toArray();

        // Only use synonyms
        foreach ($badges as $key => $badge) {
            /** @var Badge $badge */
            if ($badge->getSynonym() && !in_array($badge->getSynonym(), $badges)) {
                $badges[] = $badge->getSynonym();
                unset($badges[$key]);
            }
        }

        // Only use visible badges
        $badges = array_filter($badges, function (Badge $badge) {
            return $badge->isVisible();
        });

        // Only rendering the higher badge in the hierarchy
        $badges = array_filter($badges, function (Badge $badge) use ($badges) {
            foreach ($badge->getChildren() as $child) {
                if (in_array($child, $badges)) {
                    return false;
                }
            }

            return true;
        });

        // Sorting badges by category's priority and then by priority
        usort($badges, function (Badge $a, Badge $b) {
            if ($a->getCategory() && $b->getCategory()
                && $a->getCategory()->getPriority() !== $b->getCategory()->getPriority()) {
                return $a->getCategory()->getPriority() <=> $b->getCategory()->getPriority();
            }

            if ($a->getCategory() && !$b->getCategory()) {
                return -1;
            }

            if (!$a->getCategory() && $b->getCategory()) {
                return 1;
            }

            return $a->getRenderingPriority() <=> $b->getRenderingPriority();
        });

        return $badges;
    }

    public function getBadgePriority() : int
    {
        $lowest = 0xFFFFFFFF;

        foreach ($this->getVisibleBadges() as $badge) {
            /** @var Badge $badge */
            if ($badge->getRenderingPriority() < $lowest) {
                $lowest = $badge->getRenderingPriority();
            }
        }

        return $lowest;
    }

    public function getOptoutUntil() : ?\DateTimeInterface
    {
        return $this->optoutUntil;
    }

    public function setOptoutUntil(?\DateTimeInterface $optoutUntil) : self
    {
        $this->optoutUntil = $optoutUntil;

        return $this;
    }

    private function toName(string $name) : string
    {
        return preg_replace_callback('/[^\\s\-]+/ui', function (array $match) {
            return sprintf("%s%s", mb_strtoupper(mb_substr($match[0], 0, 1)), mb_strtolower(mb_substr($match[0], 1)));
        }, $name);
    }
}
