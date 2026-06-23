<?php

namespace App\Entity;

use App\Repository\InterventionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterventionRepository::class)]
class Intervention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'interventions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $interventionDate = null;

    #[ORM\Column(length: 120)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $problem = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $diagnostic = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $actionsDone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $duration = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $internalNotes = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $priority = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $device = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $serialNumber = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $operatingSystem = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $replacedParts = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $quoteReference = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $invoiceReference = null;

    /**
     * @var Collection<int, InterventionFile>
     */
    #[ORM\OneToMany(targetEntity: InterventionFile::class, mappedBy: 'intervention')]
    private Collection $files;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $paymentStatus = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $quoteNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $quoteAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $invoiceAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $quoteStatus = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $quoteSentAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $invoiceSentAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastReminderAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $recommendations = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;


    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        //$this->interventionDate = new \DateTimeImmutable();
        $this->status = 'planned';
        $this->priority = 'normal';
        $this->files = new ArrayCollection();
        $this->paymentStatus = 'pending';
        $this->quoteStatus = 'draft';
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s',
            $this->getClient(),
            $this->getInterventionDate()?->format('d/m/Y H:i') ?? 'Sans date'
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getInterventionDate(): ?\DateTimeImmutable
    {
        return $this->interventionDate;
    }

    public function setInterventionDate(?\DateTimeImmutable $interventionDate): static
    {
        $this->interventionDate = $interventionDate;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getProblem(): ?string
    {
        return $this->problem;
    }

    public function setProblem(string $problem): static
    {
        $this->problem = $problem;

        return $this;
    }

    public function getDiagnostic(): ?string
    {
        return $this->diagnostic;
    }

    public function setDiagnostic(?string $diagnostic): static
    {
        $this->diagnostic = $diagnostic;

        return $this;
    }

    public function getActionsDone(): ?string
    {
        return $this->actionsDone;
    }

    public function setActionsDone(?string $actionsDone): static
    {
        $this->actionsDone = $actionsDone;

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getInternalNotes(): ?string
    {
        return $this->internalNotes;
    }

    public function setInternalNotes(?string $internalNotes): static
    {
        $this->internalNotes = $internalNotes;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(?string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getDevice(): ?string
    {
        return $this->device;
    }

    public function setDevice(?string $device): static
    {
        $this->device = $device;

        return $this;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(?string $serialNumber): static
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }

    public function getOperatingSystem(): ?string
    {
        return $this->operatingSystem;
    }

    public function setOperatingSystem(?string $operatingSystem): static
    {
        $this->operatingSystem = $operatingSystem;

        return $this;
    }

    public function getReplacedParts(): ?string
    {
        return $this->replacedParts;
    }

    public function setReplacedParts(?string $replacedParts): static
    {
        $this->replacedParts = $replacedParts;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getQuoteReference(): ?string
    {
        return $this->quoteReference;
    }

    public function setQuoteReference(?string $quoteReference): static
    {
        $this->quoteReference = $quoteReference;

        return $this;
    }

    public function getInvoiceReference(): ?string
    {
        return $this->invoiceReference;
    }

    public function setInvoiceReference(?string $invoiceReference): static
    {
        $this->invoiceReference = $invoiceReference;

        return $this;
    }

    /**
     * @return Collection<int, InterventionFile>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(InterventionFile $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setIntervention($this);
        }

        return $this;
    }

    public function removeFile(InterventionFile $file): static
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            if ($file->getIntervention() === $this) {
                $file->setIntervention(null);
            }
        }

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(?string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getQuoteNumber(): ?string
    {
        return $this->quoteNumber;
    }

    public function setQuoteNumber(?string $quoteNumber): static
    {
        $this->quoteNumber = $quoteNumber;

        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    public function getQuoteAt(): ?\DateTimeImmutable
    {
        return $this->quoteAt;
    }

    public function setQuoteAt(?\DateTimeImmutable $quoteAt): static
    {
        $this->quoteAt = $quoteAt;

        return $this;
    }

    public function getInvoiceAt(): ?\DateTimeImmutable
    {
        return $this->invoiceAt;
    }

    public function setInvoiceAt(?\DateTimeImmutable $invoiceAt): static
    {
        $this->invoiceAt = $invoiceAt;

        return $this;
    }

    public function getQuoteStatus(): ?string
    {
        return $this->quoteStatus;
    }

    public function setQuoteStatus(?string $quoteStatus): static
    {
        $this->quoteStatus = $quoteStatus;

        return $this;
    }

    public function getQuoteSentAt(): ?\DateTimeImmutable
    {
        return $this->quoteSentAt;
    }

    public function setQuoteSentAt(?\DateTimeImmutable $quoteSentAt): static
    {
        $this->quoteSentAt = $quoteSentAt;

        return $this;
    }

    public function getInvoiceSentAt(): ?\DateTimeImmutable
    {
        return $this->invoiceSentAt;
    }

    public function setInvoiceSentAt(?\DateTimeImmutable $invoiceSentAt): static
    {
        $this->invoiceSentAt = $invoiceSentAt;

        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getLastReminderAt(): ?\DateTimeImmutable
    {
        return $this->lastReminderAt;
    }

    public function setLastReminderAt(?\DateTimeImmutable $lastReminderAt): static
    {
        $this->lastReminderAt = $lastReminderAt;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getRecommendations(): ?string
    {
        return $this->recommendations;
    }

    public function setRecommendations(?string $recommendations): static
    {
        $this->recommendations = $recommendations;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }
}
