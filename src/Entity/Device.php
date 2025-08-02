<?php

namespace App\Entity;

use App\Repository\DeviceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: DeviceRepository::class)]
class Device
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 80)]
    private ?string $ip = null;

    /**
     * @var Collection<int, DeviceLog>
     */
    #[ORM\OneToMany(targetEntity: DeviceLog::class, mappedBy: 'device', orphanRemoval: true)]
    private Collection $logs;

    public function __construct()
    {
        $this->logs = new ArrayCollection();
    }

    public static function create(): self
    {
        return new self();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return Collection<int, DeviceLog>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(DeviceLog $log): static
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setDevice($this);
        }

        return $this;
    }

    public function removeLog(DeviceLog $log): static
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getDevice() === $this) {
                $log->setDevice(null);
            }
        }

        return $this;
    }
}
