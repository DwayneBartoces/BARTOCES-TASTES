<?php

namespace App\Repository;

require_once __DIR__ . '/../Entity/User.php';

use App\Entity\User;
use App\Service\DatabaseService;
use PDO;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository implements PasswordUpgraderInterface
{
    private DatabaseService $db;

    public function __construct(?DatabaseService $db = null)
    {
        $this->db = $db ?? new DatabaseService();
    }

    private function pdo(): ?PDO
    {
        return $this->db->getConnection();
    }

    
    public function find(int $id): ?User
    {
        $pdo = $this->pdo();
        if (!$pdo) return null;

        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? (new User())->fromArray($row) : null;
    }

    
    public function findByUsername(string $username): ?User
    {
        $pdo = $this->pdo();
        if (!$pdo) return null;

        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = ? LIMIT 1");
        $stmt->execute([trim($username)]);
        $row = $stmt->fetch();
        return $row ? (new User())->fromArray($row) : null;
    }

    
    public function findByEmail(string $email): ?User
    {
        $pdo = $this->pdo();
        if (!$pdo) return null;

        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ? LIMIT 1");
        $stmt->execute([strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ? (new User())->fromArray($row) : null;
    }

    
    public function findByUsernameOrEmail(string $identifier): ?User
    {
        $pdo = $this->pdo();
        if (!$pdo) return null;

        $clean = trim($identifier);
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1");
        $stmt->execute([$clean, strtolower($clean)]);
        $row = $stmt->fetch();
        return $row ? (new User())->fromArray($row) : null;
    }

    
    public function findAll(): array
    {
        $rows = $this->db->getUsers();
        return array_map(fn($row) => (new User())->fromArray($row), $rows);
    }

    
    public function countByRole(string $role): int
    {
        $pdo = $this->pdo();
        if (!$pdo) return 0;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `role` = ?");
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }

    
    public function countTotal(): int
    {
        $pdo = $this->pdo();
        if (!$pdo) return 0;

        return (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
    }

    
    public function save(User $user): bool
    {
        $pdo = $this->pdo();
        if (!$pdo) return false;

        if ($user->getId() === null) {
            
            $result = $this->db->createUser([
                'name'     => $user->getName() ?? $user->getUsername(),
                'username' => $user->getUsername(),
                'email'    => $user->getEmail(),
                'phone'    => $user->getPhone() ?? '',
                'password' => $user->getPassword(),
                'role'     => $user->getRole(),
                'status'   => $user->getStatus(),
            ]);

            if ($result['success'] ?? false) {
                $user->setId((int)$result['id']);
                return true;
            }
            return false;
        } else {
            
            $stmt = $pdo->prepare(
                "UPDATE `users` SET `username` = ?, `name` = ?, `email` = ?, `phone` = ?, `role` = ?, `status` = ?, `password` = ? WHERE `id` = ?"
            );
            return $stmt->execute([
                $user->getUsername(),
                $user->getName(),
                $user->getEmail(),
                $user->getPhone(),
                $user->getRole(),
                $user->getStatus(),
                $user->getPassword(),
                $user->getId(),
            ]);
        }
    }

    
    public function updateStatus(int $id, string $status): bool
    {
        $pdo = $this->pdo();
        if (!$pdo) return false;

        $clean = in_array($status, ['active', 'disabled', 'archived'], true) ? $status : 'active';
        $stmt = $pdo->prepare("UPDATE `users` SET `status` = ? WHERE `id` = ?");
        return $stmt->execute([$clean, $id]);
    }

    
    public function resetPassword(int $id, string $newPlainPassword): bool
    {
        $pdo = $this->pdo();
        if (!$pdo) return false;

        $hash = password_hash($newPlainPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE `users` SET `password` = ? WHERE `id` = ?");
        return $stmt->execute([$hash, $id]);
    }

    
    public function delete(int $id): bool
    {
        return $this->db->deleteUser($id);
    }

    
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->save($user);
    }
}
