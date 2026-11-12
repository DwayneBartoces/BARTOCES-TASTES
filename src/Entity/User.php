<?php

namespace Symfony\Component\Security\Core\User {
    if (!interface_exists(UserInterface::class, false)) {
        interface UserInterface
        {
            public function getRoles(): array;
            public function eraseCredentials(): void;
            public function getUserIdentifier(): string;
        }
    }

    if (!interface_exists(PasswordAuthenticatedUserInterface::class, false)) {
        interface PasswordAuthenticatedUserInterface
        {
            public function getPassword(): ?string;
        }
    }

    if (!interface_exists(PasswordUpgraderInterface::class, false)) {
        interface PasswordUpgraderInterface
        {
            public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void;
        }
    }
}

namespace App\Entity {

    use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
    use Symfony\Component\Security\Core\User\UserInterface;

    
    class User implements UserInterface, PasswordAuthenticatedUserInterface
    {
        private ?int $id = null;

        private ?string $username = null;

        
        private array $roles = [];

        
        private ?string $password = null;

        private ?string $name = null;

        private ?string $email = null;

        private ?string $phone = null;

        private string $role = 'user';

        private string $status = 'active';

        private ?\DateTimeInterface $createdAt = null;

        public function __construct(array $data = [])
        {
            if (!empty($data)) {
                $this->fromArray($data);
            }
        }

        public function getId(): ?int
        {
            return $this->id;
        }

        public function setId(?int $id): static
        {
            $this->id = $id;
            return $this;
        }

        public function getUsername(): ?string
        {
            return $this->username;
        }

        public function setUsername(string $username): static
        {
            $this->username = trim($username);
            return $this;
        }

        
        public function getUserIdentifier(): string
        {
            return (string) ($this->username ?? $this->email ?? '');
        }

        
        public function getRoles(): array
        {
            $roles = $this->roles;
            $roles[] = 'ROLE_USER';

            if ($this->role === 'admin') {
                $roles[] = 'ROLE_ADMIN';
                $roles[] = 'ROLE_STAFF';
            } elseif ($this->role === 'staff') {
                $roles[] = 'ROLE_STAFF';
            }

            return array_values(array_unique($roles));
        }

        public function setRoles(array $roles): static
        {
            $this->roles = $roles;
            return $this;
        }

        public function getPassword(): ?string
        {
            return $this->password;
        }

        public function setPassword(string $password): static
        {
            $this->password = $password;
            return $this;
        }

        
        public function setPlainPassword(string $plainPassword): static
        {
            $this->password = password_hash($plainPassword, PASSWORD_DEFAULT);
            return $this;
        }

        public function verifyPassword(string $plainPassword): bool
        {
            if (empty($this->password)) {
                return false;
            }
            return password_verify($plainPassword, $this->password);
        }

        
        public function eraseCredentials(): void
        {
            
        }

        public function getName(): ?string
        {
            return $this->name;
        }

        public function setName(?string $name): static
        {
            $this->name = $name ? trim($name) : null;
            return $this;
        }

        public function getEmail(): ?string
        {
            return $this->email;
        }

        public function setEmail(?string $email): static
        {
            $this->email = $email ? strtolower(trim($email)) : null;
            return $this;
        }

        public function getPhone(): ?string
        {
            return $this->phone;
        }

        public function setPhone(?string $phone): static
        {
            $this->phone = $phone ? trim($phone) : null;
            return $this;
        }

        public function getRole(): string
        {
            return $this->role;
        }

        public function setRole(string $role): static
        {
            $validRoles = ['admin', 'staff', 'user'];
            $clean = strtolower(trim($role));
            $this->role = in_array($clean, $validRoles, true) ? $clean : 'user';
            return $this;
        }

        public function isAdmin(): bool
        {
            return $this->role === 'admin' || in_array('ROLE_ADMIN', $this->getRoles(), true);
        }

        public function isStaff(): bool
        {
            return $this->role === 'staff' || $this->role === 'admin' || in_array('ROLE_STAFF', $this->getRoles(), true);
        }

        public function isStaffOnly(): bool
        {
            return $this->role === 'staff';
        }

        public function getStatus(): string
        {
            return $this->status ?: 'active';
        }

        public function setStatus(string $status): static
        {
            $validStatuses = ['active', 'disabled', 'archived'];
            $clean = strtolower(trim($status));
            $this->status = in_array($clean, $validStatuses, true) ? $clean : 'active';
            return $this;
        }

        public function isActive(): bool
        {
            return $this->getStatus() === 'active';
        }

        public function isDisabled(): bool
        {
            return $this->getStatus() === 'disabled';
        }

        public function isArchived(): bool
        {
            return $this->getStatus() === 'archived';
        }

        public function getCreatedAt(): ?\DateTimeInterface
        {
            return $this->createdAt;
        }

        public function setCreatedAt(?\DateTimeInterface $createdAt): static
        {
            $this->createdAt = $createdAt;
            return $this;
        }

        
        public function fromArray(array $data): static
        {
            if (isset($data['id'])) {
                $this->id = (int) $data['id'];
            }
            if (isset($data['username'])) {
                $this->username = (string) $data['username'];
            }
            if (isset($data['password'])) {
                $this->password = (string) $data['password'];
            }
            if (isset($data['name'])) {
                $this->name = (string) $data['name'];
            }
            if (isset($data['email'])) {
                $this->email = (string) $data['email'];
            }
            if (isset($data['phone'])) {
                $this->phone = (string) $data['phone'];
            }
            if (isset($data['role'])) {
                $this->setRole((string) $data['role']);
            }
            if (isset($data['status'])) {
                $this->setStatus((string) $data['status']);
            }
            if (isset($data['roles']) && is_array($data['roles'])) {
                $this->roles = $data['roles'];
            }
            if (!empty($data['created_at'])) {
                try {
                    $this->createdAt = new \DateTime($data['created_at']);
                } catch (\Exception $e) {
                    $this->createdAt = null;
                }
            }
            return $this;
        }

        
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'username' => $this->username,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => $this->password,
                'role' => $this->role,
                'status' => $this->status,
                'roles' => $this->getRoles(),
                'created_at' => $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null,
            ];
        }

        
        public function toSessionArray(): array
        {
            return [
                'id' => $this->id,
                'name' => $this->name ?? $this->username,
                'username' => $this->username,
                'email' => $this->email,
                'phone' => $this->phone ?? '',
                'role' => $this->role,
                'status' => $this->status,
                'roles' => $this->getRoles(),
            ];
        }
    }
}
