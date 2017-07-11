<?php

use Application\AddMoney;
use Application\CreateAccount;
use Application\WithdrawMoney;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\SchemaException;
use Domain\Account;
use Domain\Event\AccountBlocked;
use Domain\Event\AccountCreated;
use Domain\Event\MoneyAdded;
use Domain\Event\MoneyWithdrawn;
use Infrastructure\EventSourcedAccountRepository;
use Money\Currency;
use Money\Money;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator;
use Prooph\EventStore\Adapter\Doctrine\DoctrineEventStoreAdapter;
use Prooph\EventStore\Adapter\Doctrine\Schema\EventStoreSchema;
use Prooph\EventStore\Adapter\PayloadSerializer\JsonPayloadSerializer;
use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;
use Prooph\EventStoreBusBridge\EventPublisher;
use Prooph\EventStoreBusBridge\TransactionManager;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Exception\CommandDispatchException;
use Prooph\ServiceBus\Plugin\Router\CommandRouter;
use Prooph\ServiceBus\Plugin\Router\EventRouter;
use Rhumsaa\Uuid\Uuid;

require_once __DIR__ . '/vendor/autoload.php';

// Connection and schema setup
$config = new Configuration();
$connectionParams = array(
    'dbname' => \getenv('DB_NAME'),
    'user' => \getenv('DB_USER'),
    'password' => \getenv('DB_PASSWORD'),
    'host' => \getenv('DB_HOST'),
    'port' => \getenv('DB_PORT'),
    'driver' => 'pdo_mysql',
);

$connection = DriverManager::getConnection($connectionParams, $config);
$schema = $connection->getSchemaManager()->createSchema();
try {
    EventStoreSchema::createSingleStream($schema, 'event_stream', true);
    foreach ($schema->toSql($connection->getDatabasePlatform()) as $sql) {
        $connection->exec($sql);
    }
} catch (SchemaException $e) {}

// Event bus and event store setup
$eventBus = new EventBus();
$eventStore = new EventStore(
    new DoctrineEventStoreAdapter(
        $connection,
        new FQCNMessageFactory(),
        new NoOpMessageConverter(),
        new JsonPayloadSerializer()
    ),
    new ProophActionEventEmitter()
);

$eventRouter = new EventRouter();

$eventRouter->attach($eventBus->getActionEventEmitter());

(new EventPublisher($eventBus))->setUp($eventStore);

// Repo setup
$accountRepository = new EventSourcedAccountRepository(
    new AggregateRepository(
        $eventStore,
        AggregateType::fromAggregateRootClass(Account::class),
        new AggregateTranslator()
    )
);

// Command bus setup
$commandBus = new CommandBus();

$transactionManager = new TransactionManager();

$transactionManager->setUp($eventStore);
$commandBus->utilize($transactionManager);

$commandRouter = new CommandRouter();
$commandRouter->attach($commandBus->getActionEventEmitter());

// Routing
$commandRouter
    ->route(CreateAccount::class)
    ->to(function (CreateAccount $command) use ($accountRepository) {
        $account = Account::new($command->id(), $command->currency());

        $accountRepository->save($account);
    });

$commandRouter
    ->route(AddMoney::class)
    ->to(function (AddMoney $command) use ($accountRepository) {
        /** @var Account $account */
        $account = $accountRepository->get($command->id());
        $account->add(new Money($command->amount(), new Currency($command->currency())));
        $accountRepository->save($account);
    });

$commandRouter
    ->route(WithdrawMoney::class)
    ->to(function (WithdrawMoney $command) use ($accountRepository) {
        /** @var Account $account */
        $account = $accountRepository->get($command->id());
        $account->withdraw(new Money($command->amount(), new Currency($command->currency())));
        $accountRepository->save($account);
    });

$eventRouter
    ->route(AccountCreated::class)
    ->to(function (AccountCreated $event) {
        dump('CREATED: ' . $event->currency());
    });

$eventRouter
    ->route(AccountBlocked::class)
    ->to(function (AccountBlocked $event) {
        dump('BLOCKED: ' . $event->cause());
    });

$eventRouter
    ->route(MoneyAdded::class)
    ->to(function (MoneyAdded $event) {
        dump('LOADED: ' . $event->amount());
    });

$eventRouter
    ->route(MoneyWithdrawn::class)
    ->to(function (MoneyWithdrawn $event) {
        dump('WITHDRAWN: ' . $event->amount());
    });


// Demo
//$id = Uuid::uuid4();
//$command = new CreateAccount($id, 'PLN');
//
//$commandBus->dispatch($command);
//
//for ($i = 0, $iMax = random_int(3, 20); $i < $iMax; $i++) {
//    $commandBus->dispatch(new AddMoney($id, random_int(1, 1000), 'PLN'));
//}

try {
    $id = Uuid::uuid4();
    $commandBus->dispatch(new CreateAccount($id, 'PLN'));
    $commandBus->dispatch(new AddMoney($id, random_int(1, 1000), 'PLN'));
    $commandBus->dispatch(new WithdrawMoney($id, random_int(1, 2500), 'PLN'));

    dump($accountRepository->get($id));
} catch (CommandDispatchException $exception) {
    if ($exception->getPrevious() instanceof \Domain\Exception\NegativeBalanceException) {
        dump($exception->getPrevious()->getMessage());
        dump($accountRepository->get($id));
    }
}

//$events = $eventStore->loadEventsByMetadataFrom(new StreamName('event_stream'), []);
//
//foreach ($events as $event) {
//    //var_dump(get_class($event));
//    $eventBus->dispatch($event);
//}
