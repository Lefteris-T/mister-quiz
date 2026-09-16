
# 🐘 PHP Best Practices - Οδηγός

***Πηγή*:** [Modern PHP Development: Best Practices for Today](https://dev.to/sepehr/modern-php-development-best-practices-for-today-322f) (Sepehr Mohseni, Ιανουάριος 2026)

---

## 📋 Περιεχόμενα

1. [Έκδοση PHP](#έκδοση-php)
2. [Σύγχρονα Features της PHP 8.x](#σύγχρονα-features-της-php-8x)
3. [Design Patterns](#design-patterns)
4. [Testing](#testing)
5. [Performance (Production)](#performance-production)
6. [Σύνοψη](#σύνοψη)

---

## ⚠️ Έκδοση PHP

Κάθε feature χρειάζεται συγκεκριμένη έκδοση PHP. Έλεγξε τη δική σου με `php -v` και μη χρησιμοποιείς features νεότερα από αυτήν.

- **PHP 8.0+:** Constructor Property Promotion, Named Arguments, `match`
- **PHP 8.1+:** Enums, `readonly` properties
- **PHP 8.2+:** `readonly class`
- **PHP 8.4+:** Property Hooks, Asymmetric Visibility
- **PHP 8.5+:** Pipe Operator `|>`

---

## 🚀 Σύγχρονα Features της PHP 8.x

### Property Hooks (PHP 8.4+)

- Γράψε τη λογική get/set απευθείας πάνω στο property, χωρίς ξεχωριστές μεθόδους `getName()` / `setName()`.
- Χρήσιμο για validation ή μορφοποίηση τιμών τη στιγμή που διαβάζονται ή γράφονται.
- Μέσα στο `set`, η νέα τιμή είναι διαθέσιμη ως `$value`.
  - *Το άρθρο γράφει `value` χωρίς `$`, που δεν είναι έγκυρο. Εδώ έχει διορθωθεί.*

```php
class User
{
    public string $name {
        get => trim($this->name ?? '');   // ο getter αφαιρεί τα κενά
        set {                             // ο setter κάνει validation
            if (strlen($value) < 3) {
                throw new InvalidArgumentException('Name too short');
            }
            $this->name = $value;
        }
    }
}

$user = new User();
$user->name = '  John  ';
echo $user->name; // 'John'
```

### Asymmetric Visibility (PHP 8.4+)

- Δώσε διαφορετική ορατότητα για διάβασμα και για γράψιμο: π.χ. όλοι διαβάζουν, μόνο η κλάση γράφει.
- Σύνταξη: `public private(set)`.
- *Το άρθρο δείχνει εδώ `public readonly`. Μοιάζει, αλλά δεν είναι το ίδιο: το `readonly` (PHP 8.1+) γράφεται **μόνο μία φορά**, ενώ το `private(set)` επιτρέπει αλλαγές μέσα στην κλάση όσες φορές χρειαστεί.*

```php
class Product
{
    public private(set) string $id;

    public function __construct(string $id)
    {
        $this->id = $id; // ✅ επιτρέπεται μέσα στην κλάση
    }
}

$product = new Product('abc123');
echo $product->id;        // ✅ 'abc123'
// $product->id = 'xyz';  // ❌ Error: εγγραφή εκτός κλάσης
```

### Pipe Operator `|>` (PHP 8.5+)

- Περνάει το αποτέλεσμα κάθε βήματος στο επόμενο, από πάνω προς τα κάτω.
- Αντικαθιστά δυσανάγνωστες εμφωλευμένες κλήσεις όπως `f(g(h($x)))`.
- Κάθε βήμα πρέπει να είναι callable με **μία** παράμετρο: `trim(...)` ή arrow function μέσα σε παρενθέσεις.
  - *Το άρθρο γράφει `trim(?)`. Αυτή η σύνταξη δεν υπάρχει στην PHP 8.5, οπότε εδώ έχει διορθωθεί.*

```php
$result = $input
    |> trim(...)
    |> strtolower(...)
    |> ucwords(...)
    |> (fn($s) => str_replace(' ', '-', $s));

// Ισοδύναμο με:
// $result = str_replace(' ', '-', ucwords(strtolower(trim($input))));
```

### Constructor Property Promotion (PHP 8.0+)

- Δήλωσε και αρχικοποίησε τα properties απευθείας στις παραμέτρους του constructor.
- Μειώνει σημαντικά τον επαναλαμβανόμενο κώδικα (boilerplate).
- Συνδύασέ το με `readonly` για αμετάβλητα (immutable) value objects.

```php
// ❌ Πριν την PHP 8.0
class Product
{
    private string $name;
    private float $price;

    public function __construct(string $name, float $price)
    {
        $this->name = $name;
        $this->price = $price;
    }
}

// ✅ PHP 8.0+
class Product
{
    public function __construct(
        private string $name,
        private float $price,
        private int $stock = 0,
    ) {}
}
```

### Named Arguments (PHP 8.0+)

- Δώσε τα ορίσματα με το όνομα της παραμέτρου: ο κώδικας εξηγεί μόνος του τι σημαίνει κάθε τιμή.
- Μπορείς να παραλείψεις προαιρετικές παραμέτρους στη μέση (π.χ. `phone`) και να κρατήσεις τα defaults τους.

```php
function createUser(
    string $name,
    string $email,
    ?string $phone = null,
    bool $newsletter = false,
    string $role = 'user',
): User {
    // ...
}

$user = createUser(
    name: 'John Doe',
    email: 'john@example.com',
    newsletter: true, // παραλείπουμε το phone, κρατάμε το default role
);
```

### Match Expression (PHP 8.0+)

- Προτίμησε `match` αντί για `switch` όταν θέλεις να επιστρέψεις μια τιμή.
- Κάνει **αυστηρή** σύγκριση (`===`), άρα δεν γίνονται κρυφές μετατροπές τύπων.
- Δεν χρειάζεται `break`, και επειδή είναι expression επιστρέφει κατευθείαν τιμή.
- Πολλές τιμές στο ίδιο σκέλος χωρίζονται με κόμμα.
- Χωρίς `default`, αν δεν ταιριάξει καμία τιμή, πετάει `UnhandledMatchError`.

```php
function getDiscount(string $customerType): float
{
    return match ($customerType) {
        'vip', 'premium' => 0.20,
        'regular'        => 0.10,
        'new'            => 0.05,
        default          => 0.0,
    };
}
```

### Enums (PHP 8.1+)

- Χρησιμοποίησε enums αντί για σκόρπια strings ή σταθερές (π.χ. `'pending'`), ώστε η PHP να ελέγχει τις τιμές.
- Τα enums μπορούν να έχουν μεθόδους (π.χ. `label()`, `color()`), ώστε η σχετική λογική να μένει σε ένα σημείο.
- Χρησιμοποίησε **backed enums** (`: string` ή `: int`) όταν αποθηκεύεις την τιμή σε βάση δεδομένων ή τη μετατρέπεις σε JSON.
- Βάλε το enum ως τύπο παραμέτρου για type-safe συναρτήσεις.

```php
enum Status: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }
}

$status = Status::Pending;
echo $status->value;   // 'pending'
echo $status->label(); // 'Awaiting Review'

function updateOrderStatus(Order $order, Status $status): void
{
    $order->status = $status;
    $order->save();
}
```

---

## 🏗️ Design Patterns

### Repository Pattern

- Κρύψε την πρόσβαση στα δεδομένα πίσω από ένα **interface** (π.χ. `UserRepositoryInterface`).
- Η υλοποίηση (π.χ. `EloquentUserRepository`) περιέχει τα queries. Ο υπόλοιπος κώδικας εξαρτάται μόνο από το interface.
- Έτσι μπορείς να αντικαταστήσεις το repository με mock στα unit tests.

```php
interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function all(): Collection;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function delete(User $user): bool;
}

class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly User $model,
    ) {}

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    // ... υπόλοιπες μέθοδοι του interface
}
```

### Service Layer Pattern

- Βάλε την business logic σε **service classes** (π.χ. `OrderService`), όχι στους controllers.
- Δώσε τις εξαρτήσεις (repositories, άλλα services) μέσω του constructor (dependency injection).
- Τύλιξε πολλαπλές εγγραφές σε `DB::transaction()`: ή αποθηκεύονται όλες ή καμία.
- Έλεγξε τους κανόνες (π.χ. διαθέσιμο stock) **πριν** γράψεις στη βάση και πέτα συγκεκριμένο exception (π.χ. `InsufficientStockException`).
- Μετά την επιτυχία, στείλε ένα event (π.χ. `OrderCreated`), ώστε άλλα κομμάτια της εφαρμογής να αντιδράσουν.

```php
class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function createOrder(CreateOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto) {
            // 1. Έλεγχος διαθέσιμου stock
            foreach ($dto->items as $item) {
                $product = $this->productRepository->find($item->productId);

                if ($product->stock < $item->quantity) {
                    throw new InsufficientStockException($product);
                }
            }

            // 2. Δημιουργία παραγγελίας
            $order = $this->orderRepository->create([
                'user_id' => $dto->userId,
                'status' => OrderStatus::Pending,
                'total' => $this->calculateTotal($dto->items),
            ]);

            // 3. Προσθήκη items & μείωση stock ...

            event(new OrderCreated($order));

            return $order;
        });
    }
}
```

### Data Transfer Objects (DTOs)

- Μετέφερε δεδομένα ανάμεσα στα layers (π.χ. controller → service) με typed αντικείμενα αντί για απλά arrays.
- Κάν' τα αμετάβλητα με `readonly class` (PHP 8.2+).
- Πρόσθεσε static factory methods όπως `fromRequest()` και `fromArray()` για εύκολη δημιουργία.
- Στο `fromRequest()` πάρε μόνο **validated** δεδομένα (`$request->validated(...)`).

```php
readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone = null,
        public bool $newsletter = false,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            phone: $request->validated('phone'),
            newsletter: $request->boolean('newsletter'),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            phone: $data['phone'] ?? null,
            newsletter: $data['newsletter'] ?? false,
        );
    }
}
```

---

## 🧪 Testing

- Γράψε **και** unit tests **και** feature tests.
- Τρέξε τα tests με: `php artisan test`

### Unit Tests (PHPUnit + Mockery)

- Τέσταρε κάθε class **απομονωμένα**: αντικατέστησε τις εξαρτήσεις της με mocks (`Mockery::mock(...)`).
- Στήσε τα κοινά αντικείμενα στο `setUp()` και καθάρισε με `Mockery::close()` στο `tearDown()`.
- Οργάνωσε κάθε test σε **Arrange → Act → Assert**.
- Δώσε περιγραφικά ονόματα στα tests (π.χ. `test_creates_order_successfully`).
- Τέσταρε και τα σενάρια αποτυχίας, όχι μόνο την επιτυχία.
- Το `expectException()` μπαίνει **πριν** από την κλήση που περιμένεις να αποτύχει.

```php
class OrderServiceTest extends TestCase
{
    private OrderService $service;
    private $orderRepository;
    private $productRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->service = new OrderService(
            $this->orderRepository,
            $this->productRepository,
        );
    }

    public function test_throws_exception_when_insufficient_stock(): void
    {
        // Arrange
        $product = new Product(['id' => 1, 'stock' => 1]);
        $this->productRepository
            ->shouldReceive('find')
            ->andReturn($product);

        $dto = new CreateOrderDTO(
            userId: 1,
            items: [new OrderItemDTO(productId: 1, quantity: 5, price: 99.99)],
        );

        // Assert & Act
        $this->expectException(InsufficientStockException::class);
        $this->service->createOrder($dto);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Feature Tests (Laravel)

- Τέσταρε ολόκληρη τη ροή μέσω HTTP (route → controller → service → βάση).
- Χρησιμοποίησε το `RefreshDatabase`, ώστε κάθε test να ξεκινά με καθαρή βάση.
  - ⚠️ Το `RefreshDatabase` **σβήνει** τα δεδομένα της βάσης των tests. Βεβαιώσου ότι το `phpunit.xml` δείχνει σε ξεχωριστή βάση (π.χ. SQLite `:memory:`) και **όχι** στη `mister_quiz`.
- Φτιάξε δεδομένα για τα tests με factories (`User::factory()->create()`).
- Κάνε login με `actingAs($user)` και στείλε αιτήματα με `postJson()`.
- Έλεγξε **και** το response (`assertCreated()`, `assertJsonStructure()`) **και** την κατάσταση της βάσης (`assertDatabaseHas()`).
- Γράψε test και για χρήστες χωρίς login (`assertUnauthorized()`).

```php
class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_order(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 49.99]);

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ]);

        // Assert
        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'status', 'total', 'items'],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 8, // 10 - 2
        ]);
    }

    public function test_unauthenticated_user_cannot_create_order(): void
    {
        $response = $this->postJson('/api/orders', ['items' => []]);

        $response->assertUnauthorized();
    }
}
```

---

## ⚡ Performance (Production)

> ⚠️ Οι παρακάτω ρυθμίσεις είναι **μόνο για production server**. Μην τις βάλεις στο τοπικό περιβάλλον ανάπτυξης (XAMPP / `php artisan serve`).

### OPcache

- Ενεργοποίησε το OPcache στο `php.ini`: κρατά τον μεταγλωττισμένο κώδικα στη μνήμη, ώστε η PHP να μην ξαναδιαβάζει τα αρχεία σε κάθε request.
- Ρυθμίσεις που προτείνει το άρθρο:
  - `opcache.enable=1`: ενεργοποιεί το OPcache
  - `opcache.memory_consumption=256`: μνήμη (MB) για τον cached κώδικα
  - `opcache.interned_strings_buffer=16`: μνήμη (MB) για επαναλαμβανόμενα strings
  - `opcache.max_accelerated_files=20000`: μέγιστος αριθμός αρχείων στο cache
  - `opcache.validate_timestamps=0`: δεν ελέγχει αν άλλαξαν τα αρχεία
  - `opcache.revalidate_freq=0`: κάθε πόσα δευτερόλεπτα γίνεται ο έλεγχος (δεν μετράει όταν `validate_timestamps=0`)
  - `opcache.save_comments=1`: κρατά τα docblock comments (τα χρειάζονται κάποιες βιβλιοθήκες)
  - `opcache.jit_buffer_size=256M`: μνήμη για τον JIT compiler
  - `opcache.jit=1255`: ενεργοποιεί και ρυθμίζει τον JIT
- ⚠️ Με `validate_timestamps=0`, οι αλλαγές στον κώδικα **δεν** φαίνονται μέχρι να γίνει restart ο PHP server.

```ini
; php.ini (production)
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.save_comments=1
opcache.jit_buffer_size=256M
opcache.jit=1255
```

### Preloading

- Φόρτωσε τις κλάσεις που χρησιμοποιούνται συχνά (models, services, repositories) **μία φορά** όταν ξεκινά ο server, αντί σε κάθε request.
- Φτιάξε ένα `preload.php` και δήλωσέ το στο `php.ini` με `opcache.preload` (και `opcache.preload_user`).
- ⚠️ Αλλαγές στις preloaded κλάσεις χρειάζονται restart του server.
- ⚠️ Το preloading **δεν** υποστηρίζεται στα Windows.

```php
// preload.php
require __DIR__ . '/vendor/autoload.php';

$classesToPreload = [
    \App\Models\User::class,
    \App\Models\Product::class,
    \App\Models\Order::class,
    \App\Services\OrderService::class,
    \App\Repositories\EloquentUserRepository::class,
];

foreach ($classesToPreload as $class) {
    class_exists($class);
}
```

```ini
; php.ini
opcache.preload=/var/www/app/preload.php
opcache.preload_user=www-data
```

---

## ✅ Σύνοψη

- Χρησιμοποίησε τα σύγχρονα features της PHP 8.x: property hooks, asymmetric visibility, pipe operator, enums, `match`.
- Οργάνωσε τον κώδικα με design patterns (Repository, Service Layer) για καθαρή αρχιτεκτονική.
- Γράψε ολοκληρωμένα unit **και** feature tests.
- Ρύθμισε OPcache και JIT για καλύτερη απόδοση στο production.
- Χρησιμοποίησε DTOs για type-safe μεταφορά δεδομένων.
