<?php

declare(strict_types=1);

namespace App\Command\Api;

use App\Api\V1\Dto\Request\Contact\CreateContactCollection;
use App\Api\V1\Handler\Contact\CreateContactHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'api:test:contacts:create',
    description: 'Test command to create contacts (POST /contacts)',
)]
class TestContactCreateCommand extends Command
{
    use CliAuthTrait;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly CreateContactHandler $handler,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('sample', null, InputOption::VALUE_NONE, 'Use built-in example JSON payload');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Authorize CLI user
        $this->authenticateCli($this->tokenStorage);

        // Prepare JSON payload (always use built-in example)
        $json = $this->sampleJson();

        // Deserialize JSON -> DTO collection
        $dto = $this->serializer->deserialize($json, CreateContactCollection::class, 'json');

        // Validate DTO
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $io->error('DTO validation failed');
            foreach ($violations as $violation) {
                $io->writeln(sprintf(
                    '- %s: %s',
                    $violation->getPropertyPath(),
                    $violation->getMessage()
                ));
            }

            return Command::FAILURE;
        }

        // Call handler (same logic as controller POST /contacts)
        $result = $this->handler->createBulk($dto);

        // Show response data
        dump($result);

        $io->success('Contacts created successfully');
        return Command::SUCCESS;
    }

    /**
     * Example JSON payload for contact creation.
     * Matches format expected by CreateContactCollection DTO.
     */
    private function sampleJson(): string
    {
        return <<<JSON
[
  {
    "firstName": "John",
    "lastName": "Doe",
    "email": "john.doe@example.com",
    "phone": "+123456789",
    "company": "Example Corp",
    "city": "London",
    "country": "UK",
    "notes": "Test contact via CLI"
  },
  {
    "firstName": "Jane",
    "lastName": "Smith",
    "email": "jane.smith@example.com",
    "phone": "+987654321",
    "company": "Acme Ltd",
    "city": "Berlin",
    "country": "Germany",
    "notes": "Created using sample payload"
  }
]
JSON;
    }
}
