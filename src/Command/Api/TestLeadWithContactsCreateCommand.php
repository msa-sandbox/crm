<?php

declare(strict_types=1);

namespace App\Command\Api;

use App\Api\V1\Dto\Request\Lead\CreateLeadWithContactCollectionDto;
use App\Api\V1\Handler\Lead\CreateLeadHandler;
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
    name: 'api:test:leads:create-with-contacts',
    description: 'Test command to create leads with embedded contacts (POST /leads/complex)',
)]
class TestLeadWithContactsCreateCommand extends Command
{
    use CliAuthTrait;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly CreateLeadHandler $handler,
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
        $dto = $this->serializer->deserialize($json, CreateLeadWithContactCollectionDto::class, 'json');

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

        // Call handler (same logic as controller POST /leads/complex)
        $result = $this->handler->createBulkWithContacts($dto);

        // Show response data
        dump($result);

        $io->success('Leads with contacts created successfully');
        return Command::SUCCESS;
    }

    /**
     * Example JSON payload for lead creation with embedded contacts.
     * Matches format expected by CreateLeadWithContactCollectionDto.
     */
    private function sampleJson(): string
    {
        return <<<JSON
[
  {
    "title": "Website redesign project",
    "status": "active",
    "pipelineStage": "proposal",
    "budget": 5000,
    "description": "Lead interested in redesigning corporate website.",
    "notes": "Has existing contract with competitor.",
    "_embedded": {
      "contacts": [
        {
          "firstName": "Bob",
          "lastName": "Smith",
          "email": "bob.smith@example.com",
          "phone": "+442071838750",
          "company": "Techify Ltd",
          "city": "London",
          "country": "UK"
        },
        {
          "firstName": "Carol",
          "lastName": "Davis",
          "email": "carol.davis@example.org",
          "phone": "+33142345678",
          "company": "Techify Ltd",
          "city": "Paris",
          "country": "France"
        }
      ]
    }
  },
  {
    "title": "CRM integration consulting",
    "status": "active",
    "pipelineStage": "negotiation",
    "budget": 2500,
    "description": "Potential CRM consulting engagement.",
    "notes": "Requested follow-up next week.",
    "_embedded": {
      "contacts": [
        {
          "firstName": "Alice",
          "lastName": "Johnson",
          "email": "alice@example.com",
          "phone": "+12025550123",
          "company": "Acme Corp",
          "city": "New York",
          "country": "USA",
          "notes": "Primary contact for project"
        }
      ]
    }
  }
]
JSON;
    }
}
