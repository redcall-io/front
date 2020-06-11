<?php

namespace Bundles\ApiBundle\Entity;

use Bundles\ApiBundle\Repository\TokenRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TokenRepository::class)
 */
class Token
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $secret;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $hitCount;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastHitAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity=TokenLog::class, mappedBy="token", orphanRemoval=true)
     */
    private $tokenLogs;

    public function __construct()
    {
        $this->tokenLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): Token
    {
        $this->username = $username;

        return $this;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    public function getHitCount(): ?int
    {
        return $this->hitCount;
    }

    public function setHitCount(?int $hitCount): self
    {
        $this->hitCount = $hitCount;

        return $this;
    }

    public function getLastHitAt(): ?\DateTimeInterface
    {
        return $this->lastHitAt;
    }

    public function setLastHitAt(?\DateTimeInterface $lastHitAt): self
    {
        $this->lastHitAt = $lastHitAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection|TokenLog[]
     */
    public function getTokenLogs(): Collection
    {
        return $this->tokenLogs;
    }

    public function addTokenLog(TokenLog $tokenLog): self
    {
        if (!$this->tokenLogs->contains($tokenLog)) {
            $this->tokenLogs[] = $tokenLog;
            $tokenLog->setToken($this);
        }

        return $this;
    }

    public function removeTokenLog(TokenLog $tokenLog): self
    {
        if ($this->tokenLogs->contains($tokenLog)) {
            $this->tokenLogs->removeElement($tokenLog);
            // set the owning side to null (unless already changed)
            if ($tokenLog->getToken() === $this) {
                $tokenLog->setToken(null);
            }
        }

        return $this;
    }
}
