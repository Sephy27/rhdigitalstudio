<?php

namespace App\Entity;

use App\Repository\GalerieRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Mime\MimeTypes;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: GalerieRepository::class)]
#[Vich\Uploadable]
#[ORM\HasLifecycleCallbacks]
class Galerie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $featuredImage = null;

    #[Vich\UploadableField(mapping: 'uploads', fileNameProperty: 'featuredImage')]
    #[Assert\File(maxSize: '50M', mimeTypes: [
        // Images
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        // Vidéos
        'video/mp4',
        'video/quicktime',
        'video/x-msvideo', // avi
    ],
    mimeTypesMessage: 'Merci d’envoyer une image (JPG, PNG, WEBP, GIF) ou une vidéo (MP4, MOV, AVI).'
    )]
    private ?File $featuredImageFile = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private ?int $position = 0;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 20, options: ['default' => 'image'])]
    private string $mediaType = 'image'; // "image" ou "video"

    public function __construct()
    {
        // 🔥 IMPORTANT : ça évite l'erreur "This value should not be null"
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
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

    public function getFeaturedImage(): ?string
    {
        return $this->featuredImage;
    }

    public function setFeaturedImage(?string $featuredImage): self
    {
        $this->featuredImage = $featuredImage;

        return $this;
    }

    public function getFeaturedImageFile(): ?File
    {
        return $this->featuredImageFile;
    }

    public function setFeaturedImageFile(?File $featuredImageFile): static
    {
        $this->featuredImageFile = $featuredImageFile;

        if ($featuredImageFile !== null) {
            // Essaie de deviner le mime-type
            $mimeType = $featuredImageFile->getMimeType() ?? MimeTypes::getDefault()->guessMimeType($featuredImageFile->getPathname());

            if (\is_string($mimeType) && str_starts_with($mimeType, 'video/')) {
                $this->mediaType = 'video';
            } else {
                $this->mediaType = 'image';
            }
        }

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

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

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType): static
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    public function isVideo(): bool
    {
        return $this->mediaType === 'video';
    }

    public function isImage(): bool
    {
        return $this->mediaType === 'image';
    }
}
