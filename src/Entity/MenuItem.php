<?php

namespace App\Entity;

class MenuItem
{
    private ?int $id = null;
    private string $name = '';
    private ?string $description = null;
    private float $price = 0.0;
    private string $category = 'classics';
    private ?string $image = null;
    private ?string $badge = null;
    private int $stock = 0;
    private int $lowStockThreshold = 5;
    private bool $isActive = true;
    private ?int $createdBy = null;
    private ?string $creatorName = null;
    private ?string $creatorRole = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id                = isset($data['id']) ? (int)$data['id'] : null;
            $this->name              = $data['name'] ?? '';
            $this->description       = $data['description'] ?? null;
            $this->price             = (float)($data['price'] ?? 0.0);
            $this->category          = $data['category'] ?? 'classics';
            $this->image             = $data['image'] ?? null;
            $this->badge             = $data['badge'] ?? null;
            $this->stock             = (int)($data['stock'] ?? 0);
            $this->lowStockThreshold = (int)($data['low_stock_threshold'] ?? 5);
            $this->isActive          = (bool)($data['is_active'] ?? true);
            $this->createdBy         = isset($data['created_by']) && $data['created_by'] !== '' ? (int)$data['created_by'] : null;
            $this->creatorName       = $data['creator_name'] ?? null;
            $this->creatorRole       = $data['creator_role'] ?? null;
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getPrice(): float { return $this->price; }
    public function getCategory(): string { return $this->category; }
    public function getImage(): ?string { return $this->image; }
    public function getBadge(): ?string { return $this->badge; }
    public function getStock(): int { return $this->stock; }
    public function getLowStockThreshold(): int { return $this->lowStockThreshold; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function getCreatorName(): ?string { return $this->creatorName; }
    public function getCreatorRole(): ?string { return $this->creatorRole; }

    public function isOutOfStock(): bool { return $this->stock <= 0; }
    public function isLowStock(): bool { return $this->stock > 0 && $this->stock <= $this->lowStockThreshold; }

    
    public function canEditBy(array|User $user): bool
    {
        $role = $user instanceof User ? $user->getRole() : ($user['role'] ?? 'user');
        $userId = $user instanceof User ? $user->getId() : (int)($user['id'] ?? 0);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'staff') {
            return $this->createdBy !== null && $this->createdBy === $userId;
        }

        return false;
    }
}
