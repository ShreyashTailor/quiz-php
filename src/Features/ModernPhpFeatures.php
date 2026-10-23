<?php
/**
 * Modern PHP 8.3+ Features Used in QuizMaster
 * 
 * This file documents the PHP 8.3 features utilized in the codebase.
 * Requires PHP 8.3 or higher.
 */

declare(strict_types=1);

namespace QuizMaster\Features;

#[\Attribute]
readonly class RouteAttribute
{
    public function __construct(
        public string $path,
        public array $methods = ['GET'],
        public string $name = ''
    ) {}
}

#[\Attribute]
readonly class MiddlewareAttribute
{
    public function __construct(
        public string $class,
        public array $params = []
    ) {}
}

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
    
    public function label(): string
    {
        return match($this) {
            self::User => 'Regular User',
            self::Admin => 'Administrator',
        };
    }
}

enum QuizStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    
    public function badgeClass(): string
    {
        return match($this) {
            self::Pending => 'badge-secondary',
            self::Approved => 'badge-default',
            self::Rejected => 'badge-destructive',
        };
    }
}

interface RepositoryInterface
{
    public function findById(int $id): ?object;
    public function findAll(array $criteria = []): array;
    public function save(object $entity): int;
    public function delete(int $id): bool;
}

final class UserRepository implements RepositoryInterface
{
    public function __construct(
        private readonly \QuizMaster\Database $db
    ) {}
    
    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE id = ?',
            [$id],
            'i'
        );
    }
    
    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE username = ?',
            [$username],
            's'
        );
    }
    
    public function findAll(array $criteria = []): array
    {
        $sql = 'SELECT * FROM users';
        $params = [];
        $types = '';
        
        if (!empty($criteria)) {
            $where = [];
            foreach ($criteria as $key => $value) {
                $where[] = "$key = ?";
                $params[] = $value;
                $types .= is_int($value) ? 'i' : 's';
            }
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        return $this->db->fetchAll($sql, $params, $types);
    }
    
    public function save(object $entity): int
    {
        // Implementation would use reflection or specific entity handling
        return 0;
    }
    
    public function delete(int $id): bool
    {
        $result = $this->db->executeNonQuery(
            'DELETE FROM users WHERE id = ?',
            [$id],
            'i'
        );
        return $result > 0;
    }
}

readonly class QuizDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public int $createdBy,
        public string $creatorName,
        public QuizStatus $status,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $approvedAt = null,
        public ?int $approvedBy = null,
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            description: $data['description'] ?? '',
            createdBy: $data['created_by'],
            creatorName: $data['creator_name'],
            status: QuizStatus::tryFrom($data['status']) ?? QuizStatus::Pending,
            createdAt: new \DateTimeImmutable($data['created_at']),
            approvedAt: $data['approved_at'] ? new \DateTimeImmutable($data['approved_at']) : null,
            approvedBy: $data['approved_by'] ?? null,
        );
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'created_by' => $this->createdBy,
            'creator_name' => $this->creatorName,
            'status' => $this->status->value,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'approved_at' => $this->approvedAt?->format('Y-m-d H:i:s'),
            'approved_by' => $this->approvedBy,
        ];
    }
}

function processQuizData(array $data): array
{
    return match (true) {
        !isset($data['title']) => ['error' => 'Title is required'],
        strlen($data['title']) < 3 => ['error' => 'Title must be at least 3 characters'],
        strlen($data['title']) > 255 => ['error' => 'Title must not exceed 255 characters'],
        !isset($data['description']) => ['error' => 'Description is required'],
        default => [
            'success' => true,
            'quiz' => QuizDTO::fromArray($data),
        ],
    };
}

function sanitizeInput(string $input, int $maxLength = 255): string
{
    return match (true) {
        $input === '' => '',
        strlen($input) > $maxLength => mb_substr($input, 0, $maxLength, 'UTF-8'),
        default => htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8'),
    };
}

function validateEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword(string $password): array
{
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    if (strlen($password) > 72) {
        $errors[] = 'Password must not exceed 72 characters (bcrypt limit)';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    return $errors;
}

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function generateCsrfToken(): string
{
    return bin2hex(random_bytes(32));
}

function validateCsrfToken(string $token, string $sessionToken): bool
{
    return hash_equals($sessionToken, $token);
}

// Usage of new random extension functions (PHP 8.2+)
function generateSecureToken(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

function generateApiKey(): string
{
    return 'qm_' . bin2hex(random_bytes(16)) . '_' . bin2hex(random_bytes(16));
}

// Pattern matching with match expression (PHP 8.0+)
function getStatusMessage(QuizStatus $status): string
{
    return match ($status) {
        QuizStatus::Pending => 'Quiz is awaiting admin review',
        QuizStatus::Approved => 'Quiz is live and available to users',
        QuizStatus::Rejected => 'Quiz was rejected by admin',
    };
}

// Nullsafe operator (PHP 8.0+)
function getApprovedByName(?array $quiz): ?string
{
    return $quiz['approved_by']?->username ?? null;
}

// Constructor property promotion (PHP 8.0+)
final class ApiResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $data = [],
        public readonly int $statusCode = 200,
    ) {}
    
    public static function success(string $message, array $data = []): self
    {
        return new self(true, $message, $data, 200);
    }
    
    public static function error(string $message, int $statusCode = 400, array $data = []): self
    {
        return new self(false, $message, $data, $statusCode);
    }
    
    public function toJson(): string
    {
        return json_encode([
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
        ], JSON_THROW_ON_ERROR);
    }
}